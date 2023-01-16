<?php

/**
 * @name Trait
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\ScanLogin;

use Vipkwd\Utils\Ip as VkIP;
use Vipkwd\Utils\Image\Thumb as VkThumb;
use Vipkwd\Utils\Crypt;
use Vipkwd\Utils\SocketPush\WebPusher;
trait Traits
{
    private static $_instance = [];
    /**
     * 单例入口
     *
     * @param array $options
     * @return self
     */
    static function instance($options): self
    {
        $k = md5(json_encode($options));
        if (!isset(self::$_instance[$k])) {
            self::$_instance[$k] = new self($options);
        }
        return self::$_instance[$k];
    }

    /**
     * 加密：二维码原始数据
     *
     * @param string $event 事件标识
     * @param array $data 二维码原始数据
     * @param int $expireDays <7> 二维码数据源有效期(天数)
     * @param int $expireSeconds <0> 二维码数据源有效期(秒数)，此参数大于0时，参数$expireDays无效
     * @return array[int: days, int: timestamp, string: text]
     */
    private static function createQrcodeData(string $event, array $data, int $expireDays = 7, int $expireSeconds = 0)
    {
        if ($expireSeconds <= 0) {
            if ($expireDays <= 0) {
                $expireDays = 7;
            }
            $expireSeconds =  $expireDays * 24 * 3600;
        } else {
            $expireDays = -1;
        }
        $expireTimestamps = time() + $expireSeconds;

        //标记APP 扫码完成且事件鉴定通过后需要上报扫码状态（典型使用场景：扫码后台登录二维码后，需要更新 登录页面的码状态为 “已扫码，请在手机上确认”）
        $data['notify'] = (isset($data['notify']) && $data['notify']) ? true : false;

        $data = json_encode(array_merge($data, [
            'event' => $event,
            'expires' => $expireTimestamps
        ]));

        return [
            'days' => $expireDays,
            'timestamp' => $expireTimestamps,
            'text' => 'ev.' . $event . '|' . Crypt::authcode($data, 'ENCODE', '', $expireSeconds),
        ];
    }

    /**
     * 解码： 二维码扫描结果
     *
     * @param string $text 二维码扫描结果
     * @param string $event 二维码事件验证
     * @return array|string 返回字符串表示验证错误时的行为描述, 解码有效时返回解码数组
     */
    private static function qrcodeScan($text, $event)
    {
        $text && $text = Crypt::authcode($text);
        if ($text) {
            $data = json_decode($text, true);
            if (is_array($data) && !empty($data)) {
                if ( $data['expires'] === 0 || $data['expires'] > time()) {
                    if (isset($data['event']) && $data['event'] == $event) {
                        return $data;
                    }
                    return '无效事件';
                }
                return '二维码已过期';
            }
        }
        return '二维码失效';
    }


    /**
     * 必要参数检测器
     * 
     * @param array $params
     * @param string|array $fields
     * 
     * @return boolean
     */
    static function paramValidator(array $params = [], $fields = [])
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $errField = '';
        foreach ($fields as $field) {
            switch (gettype($field)) {
                case "string":
                case "number":
                    if (!isset($params[$field])) {
                        $errField = $field;
                        break 2;
                    }
                    break;
                default:
                    break;
            }
        }
        if ($errField) {
            // header('Content-type: image/jpeg');
            VkThumb::instance()->createPlaceholder("200x200", 1, 1, 12, "{$errField}参数缺失");
            exit;
        }
    }

    /**
     * 生成网址二维码签名
     */
    private static function urlQrcodeSign(array &$parmas = [])
    {
        $params['clientIp'] = VkIP::getClientIp();
        ksort($params);
        return md5(md5(json_encode($params)) . 'urlQrcode' . VkIP::getClientIp());
    }

    
    
    /**
     * 推送自定义网页socket通知
     * 
     * @param string $clientId 二维码展示网页监听的 socketId(身份标识)
     * @param string $qrcodeId 展示在网页的那张二维码的ID(身份标识)，源网页接收推送消息的时候应该鉴别qrcodeId
     * @param string $websocketUrl websocket网关地址
     * @param string $type <normal>推送事件标识 [ complete/fail/confirm/cancel/其他自定义事件标识 ]
     * @param array $data 推送自定义数据
     * 
     * @return boolean
     */
    private function pushWebMsg(string $clientId, string $qrcodeId, string $type = 'normal', array $data = [])
    {
        $data['clientId'] = $clientId;
        $data['qrcodeId'] = $qrcodeId;
        $event = ['complete', 'fail', 'confirm', 'cancel'];
        $data['scan_state'] = in_array($type, $event) ? $type : ($type ? $type : 'normal');
        $res = WebPusher::instance($this->_options['web_pusher_url'])->to($clientId)->data($data, '', $this->_options['salt_key'])->get();
        return $res == 'ok';
    }
}

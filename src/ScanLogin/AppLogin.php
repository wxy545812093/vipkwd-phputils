<?php

/**
 * @name APP二维码扫码服务支持
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\ScanLogin;

use Vipkwd\Utils\Ip as VkIP;
use Vipkwd\Utils\Http;
use Vipkwd\Utils\Crypt;
use Vipkwd\Utils\Image\Qrcode as VkQrcode;

class AppLogin
{

    use Traits;

    private $_options = [];
    private $_request;
    private $_query;
    private $_queryArray = [];

    private function __construct($options)
    {
        $this->_request = Http::request();
        $this->_query = $this->_request->query;
        $this->_queryArray = array_values((array)$this->_request->query);
        $this->_queryArray = array_shift($this->_queryArray);
        $this->_options = array_merge([
            "web_pusher_url" => '',
            'salt_key' => '',
        ], $options);
    }

    /**
     * 步骤1、生成后台登录二维码原始数据
     * 
     * @param string $clientId  为socket标识
     * @param string $qrcodeId 手机最后扫码的那一个码ID
     * @param string $event 自协商的场景事件类型（如二维码用于后台登录，则可为 "admin-login"）
     * @param array $params 自定义数据
     * @param string $dataType 响应数据类型 array|header header时自动发送图片类型的二进制流
     * 
     * @return \header
     */
    public function createQrcode(string $clientId, string $qrcodeId, string $event, array $params = [], string $type = 'array')
    {
        $params = array_merge(['clientId' => $clientId, 'qrcodeId' => $qrcodeId], $params);
        $params['clientIp'] = VkIP::getClientIp();
        $params['notify'] = true; //标记APP 扫码完成且事件鉴定通过后需要上报扫码状态（典型使用场景：扫码后台登录二维码后，需要更新 登录页面的码状态为 “已扫码，请在手机上确认”）

        $qrcodeData = self::createQrcodeData($event, $params, 0, 600); //10分钟有效
        unset($params);
        // text	    String	'hello'	二维码内容
        // size	    Number	340	单位是px
        // quality	    String	'L'	二维码解析度L/M/Q/H
        // colorDark	String	'#000000'	黑色二维码
        // colorLight	String	'#ffffff'	白色背景

        $qrcodeData = array_merge($qrcodeData, [
            'expireDate' => date('n月j日', $qrcodeData['timestamp']),
            'text' => $qrcodeData['text'],
            'size' => 200,
            'quality' => 'M',
            'colorDark' => '#000000',
            'colorLight' => '#ffffff',
        ]);
        if ($type === 'array') {
            return $qrcodeData;
        }
        //text 加密后的扫码核心数据
        VkQrcode::make($qrcodeData['text'], false, '30%');
    }

    /**
     * 步骤2、解密（扫码）前端提交含有预定义事件 的二维码扫描结果
     * 
     * @param string $text 扫描二维码得到的原始加密文本
     * @param string|integer $scanUserId 扫码人的身份标识（通常是user_id）
     * @param string $event 约定的事件类型匹配
     * @param \Closure|null $eventCallback 事件匹配时的 执行任务（任务返回布尔结果, false时拒绝本地事件Invoke）
     * 
     * @return array
     */
    public function scanEventInvoke(string $text, $scanUserId = 0, string $event, \Closure $eventCallback = null): array
    {
        $isEvent = substr($text, 0, 3) == 'ev.';
        $arr = explode('|', $text);
        if ($isEvent) {
            !$event && $event = substr($arr[0], 3);
            $text = $arr[1];
            $data = self::qrcodeScan($text, $event);
            if (is_array($data)) {
                if ($scanUserId) {
                    $data['scan_user_id'] = $scanUserId;
                    $state = true;
                    if ($event == $data['event'] && $eventCallback && is_callable($eventCallback)) {
                        $state = (bool)$eventCallback($data);
                    }
                    if ($state === true) {
                        $seconds = 3600;
                        // 上面对原始数据已验证通过，二次延长覆盖原始数据的过期时间，防止后续验签时，数据失效;
                        $data['expires'] = time() + $seconds;

                        //下发事件原始数据，以被后续验证事件来源(即是验签的作用)
                        $data['text'] = Crypt::authcode(json_encode($data), 'ENCODE', '', $seconds);
                        $data['scan_state'] = 0;
                        $data['scan_msg'] = 'ok';
                        return $data;
                    }
                    $data['scan_state'] = 10;
                    $data['scan_msg'] = '事件拉起失败';
                    return $data;
                } else {
                    $data['scan_state'] = 11;
                    $data['scan_msg'] = '未能识别扫码用户身份';
                    return $data;
                }
            }
            return [
                'scan_state' => 13,
                'scan_msg' => $data,
            ];
        }
        return [
            'scan_state' => 14,
            'scan_msg' => '解码失败'
        ];
    }

    /**
     * 步骤3、监听需要下发“扫码完成”的事件
     * 
     * @param string $clientId  为socket标识
     * @param string $qrcodeId 手机最后扫码的那一个码ID
     * @param string $event 约定的事件类型匹配
     * @param array $params  [text,event]
     * @param string|integer $scanUserId 扫码人的身份标识（前序流程已约定值）
     * 
     * @return array|null
     */
    public function scanEventComplete(string $clientId, string $qrcodeId, string $event, array &$params, $scanUserId = 0): ?array
    {
        $params = array_merge(['event' => '', 'scan_user_id' => 0, 'text' => '', 'clientId' => $clientId, 'qrcodeId' => $qrcodeId], $params);
        $data = self::qrcodeScan($params['text'], $params['event']);
        if (is_array($data)) {
            //TODO 应该进行 data 与  params 比对;
            if ($params['event'] && $params['event'] == $event && $params['scan_user_id'] == $scanUserId) {
                //后台发起的扫码，仅通知对应的 码视图 更新为 “已扫码，请在手机上确认”状态

                if (isset($params['clientId']) && $data['clientId'] == $params['clientId'] && isset($params['qrcodeId']) && $data['qrcodeId'] == $params['qrcodeId']) {
                    //通知页面扫码结果
                    if ($this->pushWebMsg($params['clientId'], $params['qrcodeId'], 'complete')) {
                        return [
                            'scan_state' => 0,
                            'scan_msg' => '远程通知成功'
                        ];
                    } else {
                        return [
                            'scan_state' => 20,
                            'scan_msg' => '远程通知失败'
                        ];
                    }
                }
            }
        }
        return null;
        // return [
        //     'scan_state' => 21,
        //     'scan_msg' => '未能识别扫码内容'
        // ];
    }

    /**
     * 步骤4、解密（扫码）结果中包含的Hook事件确认
     * 如：手机端确认扫码
     * 
     * @param string $clientId  为socket标识
     * @param string $qrcodeId 手机最后扫码的那一个码ID
     * @param string $event 约定的事件类型匹配
     * @param array $params
     * @param string|integer $scanUserId 扫码人的身份标识（前序流程已约定值）
     * 
     * @return array|null
     */
    public function scanEventConfirm(string $clientId, string $qrcodeId, string $event, array $params, $scanUserId = 0): ?array
    {
        $params = array_merge(['event' => $event, 'scan_user_id' => 0, 'text' => '', 'clientId' => $clientId, 'qrcodeId' => $qrcodeId], $params);
        if ($event == $params['event']) {
            $parmas['scan_user_id'] = $scanUserId;
            if ($this->pushWebMsg($params['clientId'], $params['qrcodeId'], 'confirm', $params)) {
                return [
                    'scan_state' => 0,
                    'scan_msg' => '远程通知成功'
                ];
            }
            return [
                'scan_state' => 30,
                'scan_msg' => '远程通知失败'
            ];
        }
        return null;
    }
    
    /**
     * 步骤5、解码“确认授权”时的自定义参数（携带站内用户身份标识的）
     * 
     * @param string $clientId  为socket标识
     * @param string $qrcodeId 手机最后扫码的那一个码ID
     * @param string $event 约定的事件类型匹配
     * @param array $params
     * 
     * @return array
     */
    public function decryptFormData(string $clientId, string $qrcodeId, string $text):array
    {
        if ($clientId && $qrcodeId && $text) {
            $form = json_decode(base64_decode($text), true);
            if (is_array($form)) {
                $sign = $form['sign'];
                unset($form['sign']);
                ksort($form);
                $vSign = md5(http_build_query($form) . $this->_options['salt_key']);
                //3秒登录超时
                if ($sign == $vSign && $form['clientId'] == $clientId && $form['qrcodeId'] == $qrcodeId) {

                    if ($form['time'] > 0) {
                        if ($form['time'] > (time() - 3)) {
                            return $form;
                        }
                    } else {
                        return $form;
                    }
                }
            }
        }
        return [];
    }
}

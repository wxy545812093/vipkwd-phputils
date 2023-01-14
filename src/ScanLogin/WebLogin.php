<?php

/**
 * @name 网页二维码扫码服务支持
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\ScanLogin;

use \Exception;
use Vipkwd\Utils\Http;
use Vipkwd\Utils\Image\Qrcode as VkQrcode;

use Vipkwd\Utils\SocketPush\WebPusher;

class WebLogin{

    use Traits;

    private $_options = [];
    private $_request;
    private $_query;

    private function __construct($options)
    {
        $this->_request = Http::request();
        $this->_query = $this->_request->query;
        $this->_options = array_merge([
            "web_pusher_url" => '',
            'scan_event' => '',
            'salt_key' => '',
        ], $options);
        $this->_options['scan_event'] = '';
    }

    /**
     * 生成 urlQrcode
     *  - 内部将尝试自动捕获get参数 clientId 和 qrcodeId
     * 
     * @param string $url 扫描二维码后，跳转的域名(可含路劲，但不含参数)
     * @param array $params 网址预携带的参数（GET 模式追加参数到$url），最终链接为: $url?a=xxx&b=yyy
     * @param int $expireSeconds <0> 二维码有效期（秒数），默认0不过期
     * 
     * @exception \Exception
     * @return \header
     */
    public function createUrlQrcode(string $url, array $params = [], $expireSeconds = 0)
    {
        $this->_options['scan_event'] = false;
        if ($this->_query->clientId) {
            $params['clientId'] = trim($this->_query->clientId);
        }
        if ($this->_query->qrcodeId) {
            $params['qrcodeId'] = trim($this->_query->qrcodeId);
        }

        self::paramValidator($params, [
            'clientId',
            'qrcodeId',
        ]);
        if ($expireSeconds > 0) {
            $params['qr_expires'] = time() + $expireSeconds;
        }
        // $params['notify'] = true; //标记APP 扫码完成且事件鉴定通过后需要上报扫码状态（典型使用场景：扫码后台登录二维码后，需要更新 登录页面的码状态为 “已扫码，请在手机上确认”）
        $params = array_merge(['qr_notice' => false, 'qr_event' => $params['event'] ?? '', 'qr_type' => 'url', 'qr_expires' => 0], $params);
        $params['sign'] = self::urlQrcodeSign($params);
        $parseUrl = parse_url($url);
        $url = '';
        if (isset($parseUrl['scheme']) && isset($parseUrl['host'])) {
            $url = $parseUrl['scheme'] . '://' . $parseUrl['host'];
            if (isset($parseUrl['path'])) {
                $url .= $parseUrl['path'];
            }
            $url .= '?';
            VkQrcode::make($url . http_build_query($params), false, 'M');
        }
        throw new Exception('$url is invalid!');
    }

    /**
     * 扫描urlQrcode后，验证扫描结果
     * - 成功 则原样返回生成urlQrcode时的自定义数据($params)
     * - 失败 返回error_state 大于0
     * 
     * @return array
     */
    public function decryptUrlQrcode()
    {
        if($this->_options['scan_event'] == 'confirm' && $this->_query->qr_confirm === 10010){
            return $this->decryptConfirmUrlQrcode();
        }
        if($this->_options['scan_event'] == 'cancel' && $this->_query->qr_cancel === 10011){
            return $this->decryptCancelUrlQrcode();
        }
        if (!$this->_query->clientId || !$this->_query->qrcodeId || $this->_query->qr_type != 'url') {
            return [
                'error_state' => 300,
                'error_msg' => '无效码图'
            ];
        }
        $eventTag = $this->_options['scan_event'] == 'confirm' ? '确认事件' : ($this->_options['scan_event'] == 'cancel' ? '取消事件' : '码图');
        $query = array_values((array)$this->_query);
        $query = array_shift($query);
        $sign = $query['sign'] ?? '';
        if (!$sign) {
            return [
                'error_state' => 301,
                'error_msg' => '未授信的'.$eventTag
            ];
        }
        unset($query['sign']);
        if ($sign != self::urlQrcodeSign($query)) {
            return [
                'error_state' => 302,
                'error_msg' => $eventTag.'验签失败'
            ];
        }
        if ($query['qr_expires'] > 0 && $query['qr_expires'] < time()) {
            return [
                'clientId' => $query['clientId'],
                'qrcodeId' => $query['qrcodeId'],
                'error_state' => 303,
                'error_msg' => $eventTag.'已失效'
            ];
        }
        if(!in_array($this->_options['scan_event'], ['confirm','cancel'])){
            $confirmParam = $query;
            $confirmParam['qr_confirm'] = 10010;
            $confirmParam['sign'] = self::urlQrcodeSign($confirmParam);
            $query['confirm_url'] = $this->_request->url_path . '?'. http_build_query($confirmParam);

            unset($confirmParam['qr_confirm']);
            $confirmParam['qr_cancel'] = 10011;
            $confirmParam['sign'] = self::urlQrcodeSign($confirmParam);
            $query['cancel_url'] = $this->_request->url_path . '?'. http_build_query($confirmParam);
        }
        $query['error_state'] = 0;
        $query['error_msg'] = '';
        return $query;
    }

    /**
     * 验证[确认URL扫码]授权的参数
     */
    public function decryptConfirmUrlQrcode()
    {
        if($this->_query->qr_confirm === 10010){
            $this->_options['scan_event'] = 'confirm';
            return $this->decryptUrlQrcode();
        }
        return false;
    }

    /**
     * 验证[取消URL扫码]授权的参数
     */
    public function decryptCancelUrlQrcode()
    {
        if(Http::request()->query->qr_cancel === 10011){
            $this->_options['scan_event'] = 'cancel';
            return $this->decryptUrlQrcode();
        }
        return false;
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
    public function pushWebMsg(string $clientId, string $qrcodeId, string $type = 'normal', array $data = [])
    {
        $data['clientId'] = $clientId;
        $data['qrcodeId'] = $qrcodeId;
        $event = ['complete', 'fail', 'confirm', 'cancel'];
        $data['scan_state'] = in_array($type, $event) ? $type : ($type ? $type : 'normal');
        $res = WebPusher::instance($this->_options['web_pusher_url'])->to($clientId)->data($data,'', $this->_options['salt_key'])->get();
        return $res == 'ok';
    }



}

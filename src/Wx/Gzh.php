<?php
/**
 * @name 公众号
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Wx;
use \Exception;
use \Vipkwd\Utils\Wx\Gzhcommon;
use Vipkwd\Utils\Http as vipkwdHttp;
class Ghz{

    private $mp_appid;
    private $mp_app_secret;
    private $request;

    private function __construct(string $appid, string $app_secret)
    {
        $this->mp_appid = $appid;
        $this->mp_app_secret = $app_secret;
        // $this->request = vipkwdHttp::request();
    }

    /**
     * 秘钥实例化
     * 
     * @param string $appid 公众号APPID
     * @param string $app_secret 公众号APP秘钥
     */
    static function instance(string $appid, string $app_secret)
    {
        return (new self($appid, $app_secret));
    }

    /**
     * 公众号消息通知
     * @param string $user_gzh_openid 用户绑定的公众号OPEN_ID
     * @param string $message_template_id 小程序中已申请成功的消息模板ID
     * @param array $data 具体匹配模板的数据 参考示例结构
     * @param array $miniprogram <[]> 跳转小程序配置 ['appid' => '小程序APPID', 'pagepath' => '指定小程序跳转页面']
     */
    public function send(string $user_gzh_openid, string $message_template_id, array $data, array $miniprogram = []){
        $sdata = [
            'touser' => $user_gzh_openid,
            'template_id' => $message_template_id,
            // 'miniprogram' => [
            //     'appid' => 'xcx_appid', //小程序APPID
            //     'pagepath' => '/pages/index/index', //指定小程序跳转页面
            // ],
            'miniprogram' => $miniprogram,
            // 'data' => [
            //     'first' => ['value' => $noticeConfig['h1_title']],
            //     'keyword1' => ['value' => date('Y-m-d H:i', $orderInfo['ddfwtime'])],
            //     'keyword2' => ['value' => $orderInfo['dizhi']],
            //     'keyword3' => ['value' => $orderInfo['zongjia'] . '元'],
            //     'keyword4' => ['value' => $orderInfo['fwname']],
            // ]
            'data' => $data
        ];
        $sdata=json_encode($sdata);
        $access_token=Gzhcommon::instance($this->mp_appid, $this->mp_app_secret)->getAccesstoken();
        //拼接subscribeMessage.send的URL
        $api="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $res=Gzhcommon::Curl($api,'post',$sdata);
        return $res;
    }


    /**
     * 关注公众号
     * @param string $gzh_redirect_url 公众号回调地址 数组接收公众号openid
     */
    public function follow(string $gzh_redirect_url){
        Gzhcommon::instance($this->mp_appid, $this->mp_app_secret)->baseAuth($gzh_redirect_url);
    } 

}

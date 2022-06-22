<?php

/**
 * @name 微信小程序
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx;

use \Exception;
use Vipkwd\Utils\Http as vipkwdHttp;
use Vipkwd\Utils\Wx\Gzhcommon;

class Miniprogram
{


    private $xcx_appid;
    private $xcx_app_secret;
    private $request;

    private function __construct(string $xcx_appid, string $xcx_app_secret)
    {
        $this->xcx_appid = $xcx_appid;
        $this->xcx_app_secret = $xcx_app_secret;
        $this->request = vipkwdHttp::request();
    }

    /**
     * 秘钥实例化
     * 
     * @param string $xcx_appid 小程序APPID
     * @param string $xcx_app_secret 小程序APP秘钥
     */
    static function instance(string $xcx_appid, string $xcx_app_secret)
    {
        return (new self($xcx_appid, $xcx_app_secret));
    }

    public function login()
    {
        $code = $this->request->query->code;
        $quscene = $this->request->query->quscene;
        //api接口
        $api = "https://api.weixin.qq.com/sns/jscode2session?appid={$this->xcx_appid}&secret={$this->xcx_app_secret}&js_code={$code}&grant_type=authorization_code";

        $res = Gzhcommon::Curl($api, 'get', '', true);
        return isset($res['openid']) ? $res['openid'] : false;
    }
}

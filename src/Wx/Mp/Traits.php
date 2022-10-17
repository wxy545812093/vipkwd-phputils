<?php

/**
 * @name Trait
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Mp;

use Vipkwd\Utils\Wx\Mp\Base;
use Vipkwd\Utils\Http as vkHttp;

trait Traits
{

    private $mp_appid;
    private $mp_app_secret;
    private $request;
    private $access_token = '';

    private function __construct(string $appid, string $app_secret)
    {
        $this->mp_appid = $appid;
        $this->mp_app_secret = $app_secret;
        $this->request = vkHttp::request();
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
     * 设置accessToken
     * 
     * @param string $token
     * 
     * @return $this
     */
    public function setAccessToken(string $token)
    {
        $this->access_token = $token;
        return $this;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    public static function xmlToArray($xml)
    {
        if (!$xml) {
            throw new \Exception("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 捕获结构响应信息
     */
    private static function catchApi($data)
    {
        if (is_array($data)) {
            if (isset($res['errcode']) && $res['errcode']) {
                return ['code' => false, 'msg' => $res['errmsg']];
            }
            return ['code' => true, 'data' => $data];
        }
        return ['code' => false, 'msg' => '接口响应结构异常'];
    }

    /**
     * 组件标准响应
     * 
     * @param string|array $msg
     * @param string|int $code
     * @param array $inspect 响应内容实体
     * 
     * @return array
     */
    private static function response($msg, $code = 0, $inspect = [])
    {
        if (is_array($msg)) {
            $res = self::catchApi($msg);
            if ($res['code'] !== false) {
                return $res['data'];
            }
            $msg = $res['msg'];
        }
        $data['data'] = $inspect;
        $data['code'] = $code;
        $data['msg'] = $msg;
        return $data;
    }

    private function openApi(string $cgiPath, array $query = [])
    {
        $cgiPath = 'https://api.weixin.qq.com/' . trim($cgiPath, '/');
        $cgiPath .= "?access_token=" . $this->getAccessToken();
        if (!empty($query)) {
            $cgiPath .= '&' . http_build_query($query);
        }
        return $cgiPath;
    }

    /**
     * 获取accessToken
     * 
     * @param boolean <false> 是否强制重新获取token
     * @return string
     */
    private function getAccessToken(bool $flush = false)
    {
        if (!$this->access_token || $flush) {
            $this->access_token = Base::instance($this->mp_appid, $this->mp_app_secret)->getAccessToken();
        }
        return $this->access_token;
    }

    /**
     * 快速复用 公众ID、秘钥
     * @param class $classMap  类映射 ...cloneWith(Image::class)
     */
    public function cloneWith($classMap){
        return $classMap::instance($this->mp_appid, $this->mp_app_secret);
    }
}

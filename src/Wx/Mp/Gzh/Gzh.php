<?php

/**
 * @name 公众号
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Mp\Gzh;

use Vipkwd\Utils\Wx\Mp\Base;
use Vipkwd\Utils\Wx\Mp\Traits;

class Gzh
{
    use Traits;

    /**
     * 获知微信服务器的IP地址列表
     * 
     * https://developers.weixin.qq.com/doc/offiaccount/Basic_Information/Get_the_WeChat_server_IP_address.html
     * @return array
     */
    public function getWxServerId()
    {
        $res = Base::curl($this->openApi('/cgi-bin/get_api_domain_ip'));
        return static::response($res, 1);
    }

    /**
     * 网络检测
     * 
     * https://developers.weixin.qq.com/doc/offiaccount/Basic_Information/Network_Detection.html
     * 
     * @param string $action <all> 执行的检测动作，允许的值：dns（做域名解析）、ping（做 ping 检测）、all（dns和 ping 都做）
     * @param string $operator <DEFAULT> 检测入口运营商, 允许的值：CHINANET（电信出口）、UNICOM（联通出口）、CAP（腾讯自建出口）、DEFAULT（根据 ip 来选择运营商）
     * @return array
     */
    public function checkNetwork(string $action = 'all', string $operator = 'DEFAULT')
    {
        $res = Base::curl($this->openApi('/cgi-bin/callback/check'), 'post',  json_encode([
            "action" => $action,
            "check_operator" => strtoupper($operator)
        ]));
        return static::response($res, 1);
    }

    /**
     * 清空公众号api调用配额
     * 每个帐号每月共10次清零操作机会
     * https://developers.weixin.qq.com/doc/offiaccount/openApi/clear_quota.html
     * 
     * @return array
     */
    public function clearApiQuota()
    {
        $res = Base::curl($this->openApi('/cgi-bin/clear_quota'), 'post',  json_encode([
            "appid" => $this->mp_appid
        ]));
        return static::response($res, 1);
    }

    /**
     * 查询公众号api调用配额
     * https://developers.weixin.qq.com/doc/offiaccount/openApi/get_api_quota.html
     * 
     * @param string $cgiPath api的请求地址，例如"/cgi-bin/message/custom/send";不要前缀“https://api.weixin.qq.com”
     * 
     * @throw \Exception
     * @return array
     */
    public function getApiQuota(string $cgiPath)
    {
        $cgiPath = str_ireplace('https://api.weixin.qq.com', '', $cgiPath);
        $cgiPath = '/' . trim($cgiPath, '/');
        $res = Base::curl($this->openApi('/cgi-bin/openapi/quota/get'), 'post',  json_encode([
            "cgi_path" => $cgiPath
        ]));
        return static::response($res, 1);
    }

    /**
     * 查询公众号rid信息
     * https://developers.weixin.qq.com/doc/offiaccount/openApi/get_rid_info.html
     * 
     * @param string $rid rid的有效期只有7天，即只可查询最近7天的rid，查询超过7天的 rid 会出现报错，错误码为76001
     * @throw \Exception
     * @return array
     */
    public function getRidInfo(string $rid)
    {
        $res = Base::curl($this->openApi('/cgi-bin/openapi/rid/get'), 'post',  json_encode([
            "rid" => $rid
        ]));
        return static::response($res, 1);
    }
}

<?php

/**
 * @name 基础能力 - 用户管理
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @link https://developers.weixin.qq.com/doc/offiaccount/Message_Management/One-time_subscription_info.html
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Mp\User;

use Vipkwd\Utils\Wx\Mp\Base;
use Vipkwd\Utils\Wx\Mp\Traits;

class User
{
    use Traits;

    /**
     * 获取关注者列表
     * 
     * @link https://developers.weixin.qq.com/doc/offiaccount/User_Management/Getting_a_User_List.html
     * @param string $nextOpenid 第一个拉取的OPENID，不填默认从头开始拉取
     * 
     * @return array
     */
    public function getUserList(string $nextOpenid = '')
    {
        $res = Base::curl($this->openApi('/cgi-bin/user/get', ['next_openid' => $nextOpenid]));
        return static::response($res, 1);
    }

    /**
     * 关注公众号
     * @param string $gzh_redirect_url 公众号回调地址 数组接收公众号openid
     * 
     * @return array
     */
    public function follow(string $gzh_redirect_url)
    {
        $res = Base::instance($this->mp_appid, $this->mp_app_secret)->baseAuth($gzh_redirect_url);
        return static::response($res, 1);
    }

    /**
     * 根据openid获取用户的基本信息
     * @param string $openid
     * 
     * @return array
     */
    public function getUserInfoByOpenid($openid)
    {
        $res = Base::curl($this->openApi('/cgi-bin/user/info', ['openid' => $openid]));
        return static::response($res, 1);
    }

    /**
     * 获取新用户基本信息(未关注公众号的)
     * @param string $openid
     * step1: json = User::class->follow($gzh_redirect_url)
     * step2: json = User::class->getUserInfo($redirect_url)
     * 
     * @return array
     */
    public function getUserInfo($openid)
    {
        $res = Base::curl($this->openApi('/sns/userinfo', ['openid' => $openid, 'lang' => 'zh_CN']));
        return static::response($res, 1);
    }
}

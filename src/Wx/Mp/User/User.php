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
     * @param string $nextOpenid 第一个拉取的OPENID，不填默认从头开始拉取（分页概念）
     * 
     * @return array
     */
    public function getUserList(string $nextOpenid = '')
    {
        $res = Base::curl($this->openApi('/cgi-bin/user/get', ['next_openid' => $nextOpenid]));
        return static::response($res, 1);
    }


    /**
     * 获取公众号用户的 OpenId
     * @param string $gzh_redirect_url 公众号回调地址 数组接收公众号openid
     * 
     * @return array
     */
    public function getOpenId(string $gzh_redirect_url)
    {
        $res = Base::instance($this->mp_appid, $this->mp_app_secret)->baseAuth($gzh_redirect_url);
        return static::response($res, 1);
    }

    /**
     * 是否关注公众号
     * @param string $openId
     * 
     * @return boolean
     */
    public function isFollow(string $openId): bool
    {
        $res = $this->getUserInfoByOpenid($openId);
        if(is_array($res) && isset($res['subscribe'])){
            return $res['subscribe'] == '1';
        }
        return false;
    }

    /**
     * 关注公众号 - 成功默认视图
     * 
     * @return void
     */
    static function followSuccessView($message = '订阅/绑定成功'){
        echo '<div style="display:flex; align-items:center;justify-content: center;height: 600px;flex-direction: column;">';
        echo '    <div><svg t="1664185253350" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="2561" width="100" height="100"><path d="M512 512m-448 0a448 448 0 1 0 896 0 448 448 0 1 0-896 0Z" fill="#4CAF50" p-id="2562"></path><path d="M738.133333 311.466667L448 601.6l-119.466667-119.466667-59.733333 59.733334 179.2 179.2 349.866667-349.866667z" fill="#CCFF90" p-id="2563"></path></svg></div>';
        echo '    <div style="font-size:3rem"><p>' . $message . '</p></div>';
        echo '</div>';
    }
    /**
     * 关注公众号 - 失败默认视图
     * 
     * @return void
     */
    static function followFailView($message = '订阅/绑定失败，请退回重新尝试')    {
        echo '<div style="display:flex; align-items:center;justify-content: center;height: 600px;flex-direction: column;">';
        echo '    <div><svg t="1664185381981" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="3670" width="100" height="100"><path d="M549.044706 512l166.189176-166.249412a26.383059 26.383059 0 0 0 0-36.98447 26.383059 26.383059 0 0 0-37.044706 0L512 475.015529l-166.249412-166.249411a26.383059 26.383059 0 0 0-36.98447 0 26.383059 26.383059 0 0 0 0 37.044706L475.015529 512l-166.249411 166.249412a26.383059 26.383059 0 0 0 0 36.98447 26.383059 26.383059 0 0 0 37.044706 0L512 548.984471l166.249412 166.249411a26.383059 26.383059 0 0 0 36.98447 0 26.383059 26.383059 0 0 0 0-37.044706L548.984471 512zM512 1024a512 512 0 1 1 0-1024 512 512 0 0 1 0 1024z" fill="#E84335" p-id="3671"></path></svg></div>';
        echo '    <div style="font-size:3rem"><p>' . $message . '</p></div>';
        echo '</div>';
    }

    /**
     * 根据openid获取用户的基本信息
     * @param string $openId
     * 
     * @return array
     */
    public function getUserInfoByOpenid(string $openId)
    {
        $res = Base::curl($this->openApi('/cgi-bin/user/info', ['openid' => $openId, 'lang'=> 'zh_CN'])); //zh_CN 简体，zh_TW 繁体，en 英语
        return static::response($res, 1);
    }

    /**
     * 获取新用户基本信息(未关注公众号的)
     * @param string $openId
     * step1: json = User::class->follow($gzh_redirect_url)
     * step2: json = User::class->getUserInfo($redirect_url)
     * 
     * @return array
     */
    public function getUserInfo(string $openId)
    {
        $res = Base::curl($this->openApi('/sns/userinfo', ['openid' => $openId, 'lang' => 'zh_CN']));
        return static::response($res, 1);
    }
}

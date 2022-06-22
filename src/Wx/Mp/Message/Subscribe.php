<?php

/**
 * @name 基础能力 - 消息 - 公众号一次性订阅消息
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @link https://developers.weixin.qq.com/doc/offiaccount/Message_Management/One-time_subscription_info.html
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Mp\Message;

use Vipkwd\Utils\Wx\Mp\Base;
use Vipkwd\Utils\Wx\Mp\Traits;
use \Exception;
use Vipkwd\Utils\Color;

class Subscribe
{
    use Traits;

    /**
     * 推送订阅模板消息给到授权微信用户
     * 
     * @link https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Template_Message_Interface.html#3
     * @param string $user_gzh_openid 用户绑定的公众号OPEN_ID
     * @param string $template_id 订阅消息模板ID
     * @param array $data 具体匹配模板的数据 参考示例结构
     * @param array $miniprogram <[]> 跳转小程序配置 参考示例结构
     * @param string $url <''> 必须有ICP备案
     * 
     * @return array
     */
    public function send(string $user_gzh_openid, string $template_id, array $data, string $url = '', array $miniprogram = [])
    {
        $data = array_merge([
            'scene' => 10000, //开发者可以填0-10000的整型值，用来标识订阅场景值
            'title' => '', //消息标题，15字以内
            'content' => '',
            'color' => '#000000'
        ], $data);
        
        if(empty($data['content']) || empty($user_gzh_openid) || empty($template_id) || empty($data['title'])){
            return ['code' => 1, '参数无效'];
        }

        $sData = [
            'touser' => $user_gzh_openid, //接收者openid
            'url' => $url, //url和 miniprogram 都是非必填字段，若都不传则模板无跳转；若都传，会优先跳转至小程序
            'template_id' => $template_id,
            'scene' => $data['scene'],
            'title' => $data['title'],
            'data' => [
                'content' => [
                    'value' => trim($data['content']),
                    'color' => Color::colorHexFix($data['color']),
                ]
            ],
            'miniprogram' => $miniprogram,
            // 'miniprogram' => [
            //     'appid' => 'xcx_appid', //小程序APPID
            //     'pagepath' => '/pages/index/index', //指定小程序跳转页面(可携带参数)
            // ],
        ];
        $res = Base::curl($this->openApi('/cgi-bin/message/template/subscribe'), 'post', json_encode($sData));
        return static::response($res, 1);
    }

}

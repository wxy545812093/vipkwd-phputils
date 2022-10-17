<?php

/**
 * @name 基础能力 - 模板消息
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @link https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Receiving_standard_messages.html
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Mp\Message;

use Vipkwd\Utils\Wx\Mp\Base;
use Vipkwd\Utils\Wx\Mp\Traits;

class Template
{
    use Traits;
    /**
     * 添加模块并获取模板ID
     *
     * @param string $template_id_short 模板库中模板的编号，有“TM**”和“OPENTMTM**”等形式
     * @return string $template_id 返回可供接口调用的 模块ID
     * @throw \Exception
     * -e.g: echo "Template::instance(mp_appid, mp_app_secret)->templateAdd('TMxxxxxxx')"
     */
    public function templateAdd(string $template_id_short)
    {
        $res = Base::curl($this->openApi('/cgi-bin/template/api_add_template'), 'post', json_encode([
            "template_id_short" => $template_id_short
        ]));
        return static::response($res, 1);
    }
    /**
     * 删除模板
     *
     * @param string $template_id 公众帐号下模板消息ID
     * @return boolean
     * @throw \Exception
     */
    public function templateDelete(string $template_id)
    {
        $res = Base::curl($this->openApi('/cgi-bin/template/del_private_template'), 'post', json_encode([
            "template_id" => $template_id
        ]));
        return static::response($res, 1);
    }
    /**
     * 获取模板列表
     *
     * @throw \Exception
     *
     * @return array
     */
    public function templateList()
    {
        $res = Base::curl($this->openApi('/cgi-bin/template/get_all_private_template'));
        return static::response($res, 1);
    }


    /**
     * 发送模板消息
     *
     * @link https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Template_Message_Interface.html#3
     * @param string $user_gzh_openid 用户绑定的公众号OPEN_ID
     * @param string $template_id 已申请成功的消息模板ID
     * @param array $data 具体匹配模板的数据 参考示例结构
     * @param array|string $link <[]> webview URL地址 或 跳转小程序配置 ['appid' => '小程序APPID', 'pagepath' => '指定小程序跳转页面']
     *
     * @return array
     */
    public function sendMessage(string $user_gzh_openid, string $template_id, array $data,  $link = ''):? array
    {
        $sData = [
            'touser' => $user_gzh_openid, //接收者openid
            'url' => (is_string($link) && $link) ? $link : '', //url和 miniprogram 都是非必填字段，若都不传则模板无跳转；若都传，会优先跳转至小程序
            'template_id' => $template_id,

            // 'miniprogram' => [
            //     'appid' => 'xcx_appid', //小程序APPID
            //     'pagepath' => '/pages/index/index', //指定小程序跳转页面(可携带参数)
            // ],
            'miniprogram' => (is_array($link) && $link) ? $link : '',

            // 'data' => [
            //     'first' => ['value' => $order['title'],"color":"#173177"], //color: 模板内容字体颜色，不填默认为黑色
            //     'keyword1' => ['value' => $order['dizhi'],"color":"#173177"],//value: 可用\n换行
            //     'keyword2' => ['value' => $order['zongjia'] . '元'],
            //     'keyword3' => ['value' => $order['fwname']],
            // ]
            'data' => $data
        ];
        $res = Base::curl($this->openApi('/cgi-bin/message/template/send'), 'post', json_encode($sData));
        return static::response($res, 1);
    }

    /**
     * 发送模板消息 -- 接收异步通知
     *
     * @return array
     */
    public function sendNotifyCallback()
    {
        $res = ["code" => 3, 'msg' => '远程响应错误'];
        $data = static::xmlToArray(file_get_contents("php://input"));

        if (isset($data['Status'])) {

            $res['data'] = ["msg_id" => $data['MsgID'], "ctime" => $data['CreateTime']];

            if ($data['Status'] == 'success') {

                $res['code'] = 0;
                $res['msg'] = 'ok';

            } else if ($data['Status'] == 'failed:user block') {

                $res['code'] = 1;
                $res['msg'] = '用户拒收公众号消息';

            }else{

                $res['code'] = 2;
                $res['msg'] = '远程其他错误';

            }
            return $data;
        }
    }
}

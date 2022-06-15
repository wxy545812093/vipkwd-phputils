<?php
/**
 * @name 微信支付通知
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Wx;
use \Exception;

class Paynotify{

    /**
     * 异步的支付通知
     * @param string $wx_key Api密钥
     * @param callable $callback 解密成功 执行本地业务
     */
    public function async(string $wx_key, callable $callback){
        
        // 微信公众平台推送过来的post数据
        $xml =  file_get_contents('php://input');
        // 获取数据
        $data = self::toArray($xml);
        // 保存微信服务器返回的签名sign
        $data_sign = $data['sign'];
        // sign不参与签名算法
        unset($data['sign']);

        $sign = self::makeSign($data, $wx_key);

        $result = false;
        // 判断签名是否正确  判断支付状态
        if (($sign === $data_sign) && ($data['return_code'] == 'SUCCESS') && ($data['result_code'] == 'SUCCESS')) {
            // $user_openid = $data['openid'];

            //开始业务逻辑
            $response = call_user_func($callback,
                $data['out_trade_no'], //订单单号
                $data['total_fee'] / 100,
                $data['transaction_id'] //微信支付流水号
            );
            if (isset($response['errcode']) && $response['errcode'] == "0") {
                $result = true;
            }
        }
        // 返回状态给微信服务器
        if ($result) {
            $data = [
                "return_code" => 'SUCCESS',
                "return_msg" => 'OK',
            ];
        } else {
            $data = [
                "return_code" => 'FAIL',
                "return_msg" => '签名失败',
            ];
        }
        exit(self::toXml($data));
    }

    private static function toXml($array)
    {
        if (!is_array($array) || count($array) <= 0) {
            return;
        }
        $xml = '<xml version="1.0">';
        foreach ($array as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
    private static function toArray($xml)
    {
        if (!$xml) {
            return false;
        }
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }
    private static function makeSign($data, $wx_key)
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = self::toUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $wx_key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);

        return $result;
    }

    private static function ToUrlParams($array)
    {
        $buff = "";
        foreach ($array as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
}

<?php

/**
 * @name 小程序微信支付
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Libs\Payment;

class MiniWeixin
{

    protected $appid;
    protected $mch_id;
    protected $key;
    protected $openid;
    protected $out_trade_no;
    protected $body;
    protected $total_fee;
    protected $notify_url;

    /**
     * @param string $appid 小程序appid
     * @param string $openid 微信用户openid
     * @param string $mch_id 微信支付商户支付号
     * @param string $key Api密钥
     * @param string $out_trade_no 业务侧订单号
     * @param string $body
     * @param string $total_fee 订单金额(单位分)
     * @param string $notify_url 通知地址
     */
    public function __construct(string $appid, string $openid, string $mch_id, string $key, string $out_trade_no, string $body, string $total_fee, string $notify_url)
    {
        $this->appid = $appid;
        $this->openid = $openid;
        $this->mch_id = $mch_id;
        $this->key = $key;
        $this->out_trade_no = $out_trade_no;
        $this->body = $body;
        $this->total_fee = $total_fee;
        $this->notify_url = $notify_url;

        // 微信小程序调起支付组件
        //统一下单接口
        return $this->weixinapp();
    }


    //统一下单接口
    private function unifiedOrder()
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $parameters = array(
            'appid' => $this->appid, //小程序ID
            'mch_id' => $this->mch_id, //商户号
            'nonce_str' => self::createNoncestr(), //随机字符串
            'body' => $this->body,
            'out_trade_no' => $this->out_trade_no,
            'total_fee' => $this->total_fee,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'], //终端IP
            'notify_url' => $this->notify_url . '/api/v1/weinotify', //通知地址  确保外网能正常访问
            'openid' => $this->openid, //用户id
            'trade_type' => 'JSAPI' //交易类型
        );
        //统一下单签名
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = self::arrayToXml($parameters);
        $return = self::xmlToArray(self::postXmlCurl($xmlData, $url, 60));

        return $return;
    }


    private static function postXmlCurl(string $xml, string $url, int $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        set_time_limit(0);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            //throw new WxPayException("curl出错，错误码:$error");
        }
    }


    //数组转换成xml
    private static function arrayToXml(array $arr): string
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . self::arrayToXml($val) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }


    //xml转换成数组
    private static function xmlToArray(string $xml): array
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }


    //微信小程序接口
    private function weixinapp(): array
    {
        //统一下单接口
        $unifiedorder = $this->unifiedOrder();
        // dump($unifiedorder);
        $parameters = [
            'appId' => $this->appid, //小程序ID
            'timeStamp' => strval(time()), //时间戳
            'nonceStr' => self::createNoncestr(), //随机串
            'package' => 'prepay_id=' . $unifiedorder['prepay_id'], //数据包
            'signType' => 'MD5' //签名方式
        ];
        //签名
        $parameters['paySign'] = $this->getSign($parameters);
        return $parameters;
    }


    //作用：产生随机字符串，不长于32位
    private static function createNoncestr(int $length = 32): string
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    //作用：生成签名
    private function getSign(array $obj): string
    {
        foreach ($obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = self::formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }


    ///作用：格式化参数，签名过程需要使用
    private static function formatBizQueryParaMap(array $paraMap, bool $urlencode): string
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}

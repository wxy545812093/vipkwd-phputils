<?php

/**
 * @name 提交JSAPI输入对象
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay\Maps;

class WxPayJsApiPay extends WxPayDataBase
{
    /**
     * 设置微信分配的公众账号ID
     * @param string $value
     **/
    public function setAttrAppId($value)
    {
        $this->values['appId'] = $value;
    }
    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     **/
    public function getAttrAppId()
    {
        return $this->values['appId'];
    }
    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     **/
    public function hasAttrAppId()
    {
        return array_key_exists('appId', $this->values);
    }


    /**
     * 设置支付时间戳
     * @param string $value
     **/
    public function setAttrTimeStamp($value)
    {
        $this->values['timeStamp'] = $value;
    }
    /**
     * 获取支付时间戳的值
     * @return 值
     **/
    public function getAttrTimeStamp()
    {
        return $this->values['timeStamp'];
    }
    /**
     * 判断支付时间戳是否存在
     * @return true 或 false
     **/
    public function hasAttrTimeStamp()
    {
        return array_key_exists('timeStamp', $this->values);
    }

    /**
     * 随机字符串
     * @param string $value
     **/
    public function setAttrNonceStr($value)
    {
        $this->values['nonceStr'] = $value;
    }
    /**
     * 获取notify随机字符串值
     * @return 值
     **/
    public function getAttrReturnCode()
    {
        return $this->values['nonceStr'];
    }
    /**
     * 判断随机字符串是否存在
     * @return true 或 false
     **/
    public function hasAttrReturnCode()
    {
        return array_key_exists('nonceStr', $this->values);
    }


    /**
     * 设置订单详情扩展字符串
     * @param string $value
     **/
    public function setAttrPackage($value)
    {
        $this->values['package'] = $value;
    }
    /**
     * 获取订单详情扩展字符串的值
     * @return 值
     **/
    public function getAttrPackage()
    {
        return $this->values['package'];
    }
    /**
     * 判断订单详情扩展字符串是否存在
     * @return true 或 false
     **/
    public function hasAttrPackage()
    {
        return array_key_exists('package', $this->values);
    }

    /**
     * 设置签名方式
     * @param string $value
     **/
    public function setAttrSignType($value)
    {
        $this->values['signType'] = $value;
    }
    /**
     * 获取签名方式
     * @return 值
     **/
    public function getAttrSignType()
    {
        return $this->values['signType'];
    }
    /**
     * 判断签名方式是否存在
     * @return true 或 false
     **/
    public function hasAttrSignType()
    {
        return array_key_exists('signType', $this->values);
    }

    /**
     * 设置签名方式
     * @param string $value
     **/
    public function setAttrPaySign($value)
    {
        $this->values['paySign'] = $value;
    }
    /**
     * 获取签名方式
     * @return 值
     **/
    public function getAttrPaySign()
    {
        return $this->values['paySign'];
    }
    /**
     * 判断签名方式是否存在
     * @return true 或 false
     **/
    public function hasAttrPaySign()
    {
        return array_key_exists('paySign', $this->values);
    }
}

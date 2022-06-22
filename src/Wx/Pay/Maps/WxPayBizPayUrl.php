<?php

/**
 * @name 扫码支付模式一生成二维码参数
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay\Maps;

class WxPayBizPayUrl extends WxPayDataBase
{
    /**
     * 设置微信分配的公众账号ID
     * @param string $value
     **/
    public function setAttrAppId($value)
    {
        $this->values['appid'] = $value;
    }
    /**
     * 获取微信分配的公众账号ID的值
     * @return 值
     **/
    public function getAttrAppId()
    {
        return $this->values['appid'];
    }
    /**
     * 判断微信分配的公众账号ID是否存在
     * @return true 或 false
     **/
    public function hasAttrAppId()
    {
        return array_key_exists('appid', $this->values);
    }


    /**
     * 设置微信支付分配的商户号
     * @param string $value
     **/
    public function setAttrMchId($value)
    {
        $this->values['mch_id'] = $value;
    }
    /**
     * 获取微信支付分配的商户号的值
     * @return 值
     **/
    public function getAttrMchId()
    {
        return $this->values['mch_id'];
    }
    /**
     * 判断微信支付分配的商户号是否存在
     * @return true 或 false
     **/
    public function hasAttrMchId()
    {
        return array_key_exists('mch_id', $this->values);
    }

    /**
     * 设置支付时间戳
     * @param string $value
     **/
    public function setAttrTimeStamp($value)
    {
        $this->values['time_stamp'] = $value;
    }
    /**
     * 获取支付时间戳的值
     * @return 值
     **/
    public function getAttrTimeStamp()
    {
        return $this->values['time_stamp'];
    }
    /**
     * 判断支付时间戳是否存在
     * @return true 或 false
     **/
    public function hasAttrTimeStamp()
    {
        return array_key_exists('time_stamp', $this->values);
    }

    /**
     * 设置随机字符串
     * @param string $value
     **/
    public function setAttrNonceStr($value)
    {
        $this->values['nonce_str'] = $value;
    }
    /**
     * 获取随机字符串的值
     * @return 值
     **/
    public function getAttrNonceStr()
    {
        return $this->values['nonce_str'];
    }
    /**
     * 判断随机字符串是否存在
     * @return true 或 false
     **/
    public function hasAttrNonceStr()
    {
        return array_key_exists('nonce_str', $this->values);
    }

    /**
     * 设置商品ID
     * @param string $value
     **/
    public function setAttrProductId($value)
    {
        $this->values['product_id'] = $value;
    }
    /**
     * 获取商品ID的值
     * @return 值
     **/
    public function getAttrProductId()
    {
        return $this->values['product_id'];
    }
    /**
     * 判断商品ID是否存在
     * @return true 或 false
     **/
    public function hasAttrProductId()
    {
        return array_key_exists('product_id', $this->values);
    }
}

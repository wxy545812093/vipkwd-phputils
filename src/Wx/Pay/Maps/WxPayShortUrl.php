<?php

/**
 * @name 短链转换输入对象
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay\Maps;

class WxPayShortUrl extends WxPayDataBase
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
     * 设置需要转换的URL，签名用原串，传输需URL encode
     * @param string $value
     **/
    public function setAttrLongUrl($value)
    {
        $this->values['long_url'] = $value;
    }
    /**
     * 获取需要转换的URL，签名用原串，传输需URL encode的值
     * @return 值
     **/
    public function getAttrLongUrl()
    {
        return $this->values['long_url'];
    }
    /**
     * 判断需要转换的URL，签名用原串，传输需URL encode是否存在
     * @return true 或 false
     **/
    public function hasAttrLongUrl()
    {
        return array_key_exists('long_url', $this->values);
    }


    /**
     * 设置随机字符串，不长于32位。推荐随机数生成算法
     * @param string $value
     **/
    public function setAttrNonceStr($value)
    {
        $this->values['nonce_str'] = $value;
    }
    /**
     * 获取随机字符串，不长于32位。推荐随机数生成算法的值
     * @return 值
     **/
    public function getAttrNonceStr()
    {
        return $this->values['nonce_str'];
    }
    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     **/
    public function hasAttrNonceStr()
    {
        return array_key_exists('nonce_str', $this->values);
    }
}

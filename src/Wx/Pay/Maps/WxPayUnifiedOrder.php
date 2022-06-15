<?php

/**
 * @name 统一下单输入对象
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay\Maps;

class WxPayUnifiedOrder extends WxPayDataBase
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
	 * 设置微信支付分配的终端设备号，商户自定义
	 * @param string $value
	 **/
	public function setAttrDeviceInfo($value)
	{
		$this->values['device_info'] = $value;
	}
	/**
	 * 获取微信支付分配的终端设备号，商户自定义的值
	 * @return 值
	 **/
	public function getAttrDeviceInfo()
	{
		return $this->values['device_info'];
	}
	/**
	 * 判断微信支付分配的终端设备号，商户自定义是否存在
	 * @return true 或 false
	 **/
	public function hasAttrDeviceInfo()
	{
		return array_key_exists('device_info', $this->values);
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

	/**
	 * 设置商品或支付单简要描述
	 * @param string $value
	 **/
	public function setAttrBody($value)
	{
		$this->values['body'] = $value;
	}
	/**
	 * 获取商品或支付单简要描述的值
	 * @return 值
	 **/
	public function getAttrBody()
	{
		return $this->values['body'];
	}
	/**
	 * 判断商品或支付单简要描述是否存在
	 * @return true 或 false
	 **/
	public function hasAttrBody()
	{
		return array_key_exists('body', $this->values);
	}


	/**
	 * 设置商品名称明细列表
	 * @param string $value
	 **/
	public function setAttrDetail($value)
	{
		$this->values['detail'] = $value;
	}
	/**
	 * 获取商品名称明细列表的值
	 * @return 值
	 **/
	public function getAttrDetail()
	{
		return $this->values['detail'];
	}
	/**
	 * 判断商品名称明细列表是否存在
	 * @return true 或 false
	 **/
	public function hasAttrDetail()
	{
		return array_key_exists('detail', $this->values);
	}


	/**
	 * 设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
	 * @param string $value
	 **/
	public function setAttrAttach($value)
	{
		$this->values['attach'] = $value;
	}
	/**
	 * 获取附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据的值
	 * @return 值
	 **/
	public function getAttrAttach()
	{
		return $this->values['attach'];
	}
	/**
	 * 判断附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据是否存在
	 * @return true 或 false
	 **/
	public function hasAttrAttach()
	{
		return array_key_exists('attach', $this->values);
	}


	/**
	 * 设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
	 * @param string $value
	 **/
	public function setAttrOutTradeNo($value)
	{
		$this->values['out_trade_no'] = $value;
	}
	/**
	 * 获取商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号的值
	 * @return 值
	 **/
	public function getAttrOutTradeNo()
	{
		return $this->values['out_trade_no'];
	}
	/**
	 * 判断商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号是否存在
	 * @return true 或 false
	 **/
	public function hasAttrOutTradeNo()
	{
		return array_key_exists('out_trade_no', $this->values);
	}


	/**
	 * 设置符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型
	 * @param string $value
	 **/
	public function setAttrFeeType($value)
	{
		$this->values['fee_type'] = $value;
	}
	/**
	 * 获取符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型的值
	 * @return 值
	 **/
	public function getAttrFeeType()
	{
		return $this->values['fee_type'];
	}
	/**
	 * 判断符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型是否存在
	 * @return true 或 false
	 **/
	public function haFeeType()
	{
		return array_key_exists('fee_type', $this->values);
	}


	/**
	 * 设置订单总金额，只能为整数，详见支付金额
	 * @param string $value
	 **/
	public function setAttrTotalFee($value)
	{
		$this->values['total_fee'] = $value;
	}
	/**
	 * 获取订单总金额，只能为整数，详见支付金额的值
	 * @return 值
	 **/
	public function getAttrTotalFee()
	{
		return $this->values['total_fee'];
	}
	/**
	 * 判断订单总金额，只能为整数，详见支付金额是否存在
	 * @return true 或 false
	 **/
	public function hasAttrTotalFee()
	{
		return array_key_exists('total_fee', $this->values);
	}


	/**
	 * 设置APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
	 * @param string $value
	 **/
	public function setAttrSpbillCreateIp($value)
	{
		$this->values['spbill_create_ip'] = $value;
	}
	/**
	 * 获取APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。的值
	 * @return 值
	 **/
	public function getAttrSpbillCreateIp()
	{
		return $this->values['spbill_create_ip'];
	}
	/**
	 * 判断APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。是否存在
	 * @return true 或 false
	 **/
	public function hasAttrSpbillCreateIp()
	{
		return array_key_exists('spbill_create_ip', $this->values);
	}


	/**
	 * 设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则
	 * @param string $value
	 **/
	public function setAttrTimeStart($value)
	{
		$this->values['time_start'] = $value;
	}
	/**
	 * 获取订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则的值
	 * @return 值
	 **/
	public function getAttrTimeStart()
	{
		return $this->values['time_start'];
	}
	/**
	 * 判断订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则是否存在
	 * @return true 或 false
	 **/
	public function hasAttrTimeStart()
	{
		return array_key_exists('time_start', $this->values);
	}


	/**
	 * 设置订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则
	 * @param string $value
	 **/
	public function setAttrTimeExpire($value)
	{
		$this->values['time_expire'] = $value;
	}
	/**
	 * 获取订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则的值
	 * @return 值
	 **/
	public function getAttrTimeExpire()
	{
		return $this->values['time_expire'];
	}
	/**
	 * 判断订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则是否存在
	 * @return true 或 false
	 **/
	public function hasAttrTimeExpire()
	{
		return array_key_exists('time_expire', $this->values);
	}


	/**
	 * 设置商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
	 * @param string $value
	 **/
	public function setAttrGoodsTag($value)
	{
		$this->values['goods_tag'] = $value;
	}
	/**
	 * 获取商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠的值
	 * @return 值
	 **/
	public function getAttrGoodsTag()
	{
		return $this->values['goods_tag'];
	}
	/**
	 * 判断商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠是否存在
	 * @return true 或 false
	 **/
	public function hasAttrGoodsTag()
	{
		return array_key_exists('goods_tag', $this->values);
	}


	/**
	 * 设置接收微信支付异步通知回调地址
	 * @param string $value
	 **/
	public function setAttrNotifyUrl($value)
	{
		$this->values['notify_url'] = $value;
	}
	/**
	 * 获取接收微信支付异步通知回调地址的值
	 * @return 值
	 **/
	public function getAttrNotifyUrl()
	{
		return $this->values['notify_url'];
	}
	/**
	 * 判断接收微信支付异步通知回调地址是否存在
	 * @return true 或 false
	 **/
	public function hasAttrNotifyUrl()
	{
		return array_key_exists('notify_url', $this->values);
	}


	/**
	 * 设置取值如下：JSAPI，NATIVE，APP，详细说明见参数规定
	 * @param string $value
	 **/
	public function setAttrTradeType($value)
	{
		$this->values['trade_type'] = $value;
	}
	/**
	 * 获取取值如下：JSAPI，NATIVE，APP，详细说明见参数规定的值
	 * @return 值
	 **/
	public function getAttrTradeType()
	{
		return $this->values['trade_type'];
	}
	/**
	 * 判断取值如下：JSAPI，NATIVE，APP，详细说明见参数规定是否存在
	 * @return true 或 false
	 **/
	public function hasAttrTradeType()
	{
		return array_key_exists('trade_type', $this->values);
	}


	/**
	 * 设置trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
	 * @param string $value
	 **/
	public function setAttrProductId($value)
	{
		$this->values['product_id'] = $value;
	}
	/**
	 * 获取trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。的值
	 * @return 值
	 **/
	public function getAttrProductId()
	{
		return $this->values['product_id'];
	}
	/**
	 * 判断trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。是否存在
	 * @return true 或 false
	 **/
	public function hasAttrProductId()
	{
		return array_key_exists('product_id', $this->values);
	}


	/**
	 * 设置trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。下单前需要调用【网页授权获取用户信息】接口获取到用户的Openid。
	 * @param string $value
	 **/
	public function setAttrOpenId($value)
	{
		$this->values['openid'] = $value;
	}
	/**
	 * 获取trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。下单前需要调用【网页授权获取用户信息】接口获取到用户的Openid。 的值
	 * @return 值
	 **/
	public function getAttrOpenId()
	{
		return $this->values['openid'];
	}
	/**
	 * 判断trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。下单前需要调用【网页授权获取用户信息】接口获取到用户的Openid。 是否存在
	 * @return true 或 false
	 **/
	public function hasAttrOpenId()
	{
		return array_key_exists('openid', $this->values);
	}
}

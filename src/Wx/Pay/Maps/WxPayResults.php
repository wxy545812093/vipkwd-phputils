<?php

/**
 * @name 接口调用结果类
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay\Maps;

class WxPayResults extends WxPayDataBase
{
	/**
	 *
	 * 检测签名
	 * 
	 * @throw WxPayException
	 */
	public function CheckSign()
	{
		//fix异常
		if (!$this->hasAttrSign()) {
			throw new WxPayException("签名错误！");
		}

		$sign = $this->makeSignStr();
		if ($this->getAttrSign() == $sign) {
			return true;
		}
		throw new WxPayException("签名错误！");
	}

	/**
	 *
	 * 使用数组初始化
	 * @param array $array
	 */
	public function FromArray($array)
	{
		$this->values = $array;
	}

	/**
	 *
	 * 使用数组初始化对象
	 * @param array $array
	 * @param boolean 是否检测签名 $noCheckSign
	 * 
	 * @return self
	 */
	public static function InitFromArray($array, $noCheckSign = false)
	{
		$obj = new self();
		$obj->FromArray($array);
		if ($noCheckSign == false) {
			$obj->CheckSign();
		}
		return $obj;
	}

	/**
	 *
	 * 设置参数
	 * 
	 * @param string $key
	 * @param string $value
	 */
	public function setData($key, $value)
	{
		$this->values[$key] = $value;
	}

	/**
	 * 将xml转为array
	 * @param string $xml
	 * @throws WxPayException
	 */
	public static function Init($xml)
	{
		$obj = new self();
		$obj->xmlToArray($xml);
		if ($obj->values['return_code'] != 'SUCCESS') {
			return $obj->getAttrValues();
		}
		$obj->CheckSign();
		return $obj->getAttrValues();
	}
}

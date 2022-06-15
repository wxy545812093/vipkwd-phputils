<?php

/**
 * @name 回调基础类
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay\Maps;

class WxPayNotifyReply extends  WxPayDataBase
{
	/**
	 *
	 * 设置错误码 FAIL 或者 SUCCESS
	 * @param string
	 */
	public function setAttrReturnCode($return_code)
	{
		$this->values['return_code'] = $return_code;
	}

	/**
	 *
	 * 获取错误码 FAIL 或者 SUCCESS
	 * @return string $return_code
	 */
	public function getAttrReturnCode()
	{
		return $this->values['return_code'];
	}

	/**
	 *
	 * 设置错误信息
	 * @param string $return_code
	 */
	public function setAttrReturnMsg($return_msg)
	{
		$this->values['return_msg'] = $return_msg;
	}

	/**
	 *
	 * 获取错误信息
	 * @return string
	 */
	public function getAttrReturnMsg()
	{
		return $this->values['return_msg'];
	}

	/**
	 *
	 * 设置返回参数
	 * @param string $key
	 * @param string $value
	 */
	public function setData($key, $value)
	{
		$this->values[$key] = $value;
	}
}

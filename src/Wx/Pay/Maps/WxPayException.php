<?php

/**
 * @name 微信支付API异常类
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay\Maps;

use \Exception;

class WxPayException extends Exception
{
	public function errorMessage()
	{
		return $this->getMessage();
	}
}

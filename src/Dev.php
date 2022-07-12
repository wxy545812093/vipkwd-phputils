<?php

/**
 * @name 开发调试函数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;
use Vipkwd\Utils\Type\Str as VipkwdStr;
class Dev{
    const SEPA = \DIRECTORY_SEPARATOR;
    use \Vipkwd\Utils\Libs\Develop;

    /**
	 *   Returns the last occurred PHP error or an empty string if no error occurred. Unlike error_get_last(),
	 * it is nit affected by the PHP directive html_errors and always returns text, not HTML.
	 */
    /**
     * 自定义前次错误捕获器(异于error_get_last)
     *
     * @return string
     */
	static function getLastError(): string{
		$message = error_get_last()['message'] ?? '';
		$message = ini_get('html_errors') ? VipkwdStr::htmlToText($message) : $message;
		$message = preg_replace('#^\w+\(.*?\): #', '', $message);
		return $message;
	}


}
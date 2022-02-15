<?php
/**
 * @name 3DES-CBC 加密解密算法
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs\Crypt;

use Vipkwd\Utils\Libs\Crypt\Traits;
use \Exception;

class Des{
    use Traits;
    private static $_ivLength = 8;
    private static $_modeType = "des-ede3-cbc";

    // https://blog.csdn.net/mangojo/article/details/90268132
}
<?php
/**
 * @name AES-256-CBC 加密解密算法
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs\Crypt;

use Vipkwd\Utils\Libs\Crypt\Traits;
use \Exception;

class Aes{
    use Traits;
    private static $_ivLength = 16;
    private static $_modeType = "AES-256-CBC";
    
    // https://www.jianshu.com/p/54a027ed96f8
}
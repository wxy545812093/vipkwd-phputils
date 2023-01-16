<?php
/**
 * @name JsonDB
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Db;

use \JsonDb\JsonDb\JsonDb;

class Json extends JsonDb
{
    // ¾²Ì¬ÊµÀý»¯
    static function instance(array $options = []){
        return new static($options);
    }
}
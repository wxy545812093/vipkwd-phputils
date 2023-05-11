<?php

/**
 * @name Debugger调试器
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @link https://github.com/nette/tracy
 * @license https://github.com/nette/tracy/blob/master/license.md
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils;

use \Tracy\NativeSession;
use \JanDrabek\Tracy\GitVersionPanel;
class Debugger extends \Tracy\Debugger
{

    /**
     * 执行预定义方案
     * 
     * @return void
     */
    static function default(?string $logSavePath = '/var/log'): void
    {
        self::$strictMode = true;
        // self::$strictMode = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED; // all errors except deprecated notices

        //self::$showBar = false;
        self::$scream = true;
        //self::$errorTemplate = '500.html';
        self::$dumpTheme = 'dark';
        self::$maxDepth = 3; // default: 3
        self::$maxLength = 150; // default: 150
        self::setSessionStorage(new NativeSession);
        // self::$showLocation = Tracy\Dumper::LOCATION_SOURCE; // Shows path to where the dump() was called
        // self::$showLocation = Tracy\Dumper::LOCATION_CLASS | Tracy\Dumper::LOCATION_LINK; // Shows both paths to the classes and link to where the dump() was called
        // self::$showLocation = false; // Hides additional location information
        // self::$showLocation = true; // Shows all additional location information
        self::getBlueScreen()->scrubber = function (string $key, $value, ?string $class): bool {
            return preg_match('#password#i', $key) && $value !== null;
        };
        self::getBar()->addPanel(new GitVersionPanel());
        self::enable(null, $logSavePath);
    }
}

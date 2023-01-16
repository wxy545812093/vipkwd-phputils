<?php

/**
 * @name 二维码
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Image;

use Vipkwd\Utils\Libs\Qrcode\Qrcode as PHPQRcode;

class Qrcode
{

    /**
     * 生成二维码
     * 
     * 1、outfile === false, header输出png;
     * 2、outfile !== false && saveAndPrint === true, 保存到 outfile指向地址 并header输出;
     * 3、outfile !== file && saveAndPrint !== true, 仅保存到 outfile指向地址;
     * 
     * @param string $text 二维码内容
     * @param boolean|string $outFile 文件
     * @param string $level <M> 纠错级别 L:7% M:15% Q:25% H:30%
     * @param integer $size 二维码大小
     * @param integer $margin 边距常量
     * @param boolean $saveAndPrint <false>
     *
     * @return void
     */
    static function make(string $text, $outFile = false, string $level = "15%", int $size = 6, int $margin = 2, bool $saveAndPrint = false)
    {
        switch ($level) {
            case "L":
            case "7%":
                $level = '7%';
                break;
            case "Q":
            case "25%":
                $level = '25%';
                break;

            case "H":
            case "30%":
                $level = '30%';
                break;
            default:
                $level = '15%';
                break;
        }
        PHPQRcode::png($text, $outFile, $level, $size, $margin, $saveAndPrint);
        exit;
    }
}

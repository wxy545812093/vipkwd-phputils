<?php

/**
 * @name 图像条码类
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @link https://mywebmymail.com/easyphpthumbnail/
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Image;

/**
 * Image_Barcode2 class
 *
 * Package to render barcodes
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Image
 * @package   Image_Barcode2
 * @author    Marcelo Subtil Marcal <msmarcal@php.net>
 * @copyright 2005 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://pear.php.net/package/Image_Barcode2
 */

//require_once 'Image/Barcode2/Writer.php';
//require_once 'Image/Barcode2/Driver.php';
//require_once 'Image/Barcode2/Exception.php';

use Vipkwd\Utils\Image\Barcode2\Writer as Image_Barcode2_Writer;
use Vipkwd\Utils\Image\Barcode2\Driver as Image_Barcode2_Driver;
use Vipkwd\Utils\Image\Barcode2\BException as Image_Barcode2_Exception;
use Vipkwd\Utils\Image\Barcode2\Common as Image_Barcode2_Common;
use Vipkwd\Utils\Image\Barcode2\DualWidth as Image_Barcode2_DualWidth;
use Vipkwd\Utils\Image\Barcode2\DualHeight as Image_Barcode2_DualHeight;
/**
 * Image_Barcode2 class
 *
 * Package which provides a method to create barcode using GD library.
 *
 * @category  Image
 * @package   Image_Barcode2
 * @author    Marcelo Subtil Marcal <msmarcal@php.net>
 * @copyright 2005 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Image_Barcode2
 */
class Barcode
{
    /**
     * Image type
     */
    const IMAGE_PNG     = 'png';
    const IMAGE_GIF     = 'gif';
    const IMAGE_JPEG    = 'jpg';

    /**
     * Barcode type
     */
    const BARCODE_CODE39    = 'code39';
    const BARCODE_INT25     = 'int25';
    const BARCODE_EAN13     = 'ean13';
    const BARCODE_UPCA      = 'upca';
    const BARCODE_UPCE      = 'upce';
    const BARCODE_CODE128   = 'code128';
    const BARCODE_EAN8      = 'ean8';
    const BARCODE_POSTNET   = 'postnet';

    /**
     * Rotation type
     */
    const ROTATE_NONE     = 0;
    const ROTATE_RIGHT    = 90;
    const ROTATE_UTURN    = 180;
    const ROTATE_LEFT     = 270;


    /**
     * 绘制图形条码
     *
     * @param string  $text          条码文本
     * @param string  $type          条码类型，支持以下类型:
     *                               code39 - Code 3 of 9
     *                               int25  - 2 Interleaved 5
     *                               ean13  - EAN 13
     *                               upca   - UPC-A
     *                               upce   - UPC-E
     *                               code128
     *                               ean8
     *                               postnet
     * @param string  $imgType       输出图片类型(gif,jpg,png)
     * @param boolean $sendToBrowser <true>是，是否直接header响应绘制结果.
     * @param integer $height        条码线高度 默认60
     * @param integer $width         条码线宽度 默认1
     * @param boolean $showText      底部是否绘制条码文本
     * @param integer $rotation      旋转角度(旋转步值: 90) 默认不旋转 
     *
     * @return resource The corresponding gd image resource
     *               
     * @throws Image_Barcode2_Exception
     * 
     * @since  Image_Barcode2 0.3
     */
    static function draw($text, 
        $type = self::BARCODE_INT25,
        $imgType = self::IMAGE_PNG,
        $sendToBrowser = true,
        $height = 60,
        $width = 1,
        $showText = true,
        $rotation = self::ROTATE_NONE
    ) {
        //Make sure no bad files are included
        if (!preg_match('/^[a-z0-9]+$/', $type)) {
            throw new Image_Barcode2_Exception('Invalid barcode type ' . $type);
        }

        if (!include_once __DIR__.'/Barcode2/Driver/' . ucfirst($type) . '.php') {
            throw new Image_Barcode2_Exception($type . ' barcode is not supported');
        }

        $classname = '\\Vipkwd\\Utils\\Image\\Barcode2\\Driver\\' . ucfirst($type);

        $obj = new $classname(new Image_Barcode2_Writer());

        if (!$obj instanceof Image_Barcode2_Driver) {
            throw new Image_Barcode2_Exception(
                "'$classname' does not implement Image_Barcode2_Driver"
            );
        }

        if (!$obj instanceof Image_Barcode2_DualWidth) {
            $obj->setBarcodeWidth($width);
        }

        if (!$obj instanceof Image_Barcode2_DualHeight) {
            $obj->setBarcodeHeight($height);
        }

        $obj->setBarcode($text);
        $obj->setShowText($showText);

        $obj->validate();
        $img = $obj->draw();

        // Rotate image on demand
        if ($rotation !== self::ROTATE_NONE) {
            $img = imagerotate($img, $rotation, 0);
        }

        if ($sendToBrowser) {
            // Send image to browser
            switch ($imgType) {
            case self::IMAGE_GIF:
                header('Content-type: image/gif');
                imagegif($img);
                imagedestroy($img);
                break;

            case self::IMAGE_JPEG:
                header('Content-type: image/jpg');
                imagejpeg($img);
                imagedestroy($img);
                break;

            default:
                header('Content-type: image/png');
                imagepng($img);
                imagedestroy($img);
                break;
            }
        }

        return $img;
    }
}

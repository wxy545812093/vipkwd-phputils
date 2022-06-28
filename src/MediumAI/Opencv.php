<?php

/**
 * @name 视觉/机器学习库(开发版)
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\MediumAI;

use Vipkwd\Utils\Color;
use Vipkwd\Utils\Image\Thumb;
use Vipkwd\Utils\System\File;

use \Exception;
use \Imagick, \ImagickPixel, ImagickDraw;

class Opencv
{

    private $options = [];
    private static $_instance = [];
    private $_result;
    private $_im;

    /**
     * 单例入口
     *
     * @param array $options
     * @return self
     */
    static function instance($options = []): self
    {
        $k = md5(json_encode($options));
        if (!isset(self::$_instance[$k])) {
            self::$_instance[$k] = new self($options);
        }
        return self::$_instance[$k];
    }

    /**
     * 获取图片神经元
     *
     * @param string $imgPath 源图
     * @param string $xmlPath 神经元检测模型xml
     * @return self
     */
    public function faceDetect(string $imgPath, string $xmlPath): self
    {
        $imgPath = File::normalizePath($imgPath);
        $xmlPath = self::getXml($xmlPath);
        $this->_im = new Imagick($imgPath);
        $this->_result = face_detect($imgPath, $xmlPath);
        return $this;
    }

    /**
     * 标记轮廓
     *
     * @param integer $width <3> 线框宽度
     * @param string $color <"#E733F4"> 线框颜色
     * @param integer $deg <0> 轮廓旋转角度
     * @param string|null $imgPath 默认空，源图覆写 
     * @return self
     */
    public function drawBorder(?int $width = 3, ?string $color = '#E733F4', ?int $deg = 0, ?string $imgPath = null): self
    {
        $color = Color::colorHexFix($color ?? '#E733F4');
        if ($imgPath) {
            $imgPath = File::normalizePath($imgPath);
            if ($imgPath && File::exists($imgPath) && is_file($imgPath)) {
                $this->_im = new Imagick($imgPath);
            }
        }
        $draw = new ImagickDraw();
        @$draw->setFillAlpha(0);
        $draw->setStrokeColor(new ImagickPixel($color));
        $draw->setStrokeWidth($width > 0 ? $width : 3);
        $im = $this->_im;
        if (is_array($this->_result) && !empty($this->_result)) {
            foreach ($this->_result as $v) {
                $im_cl = clone $im;
                $im_cl->cropImage($v['w'], $v['h'], $v['x'], $v['y']);

                // 使像素围绕图像中心旋转。程度表示移动每个像素的弧度
                $im_cl->swirlImage($deg > 0 ? intval($deg) : 0);

                // header("Content-Type: image/jpg");
                // // Display the image
                // echo $im_cl->getImageBlob();
                // exit($im_cl);
                // exit;
                $im->compositeImage($im_cl, \Imagick::COMPOSITE_OVER, $v['x'], $v['y']);

                $draw->rectangle($v['x'], $v['y'], $v['x'] + $v['w'], $v['y'] + $v['h']);
                $im->drawimage($draw);
                unset($im_cl, $v);
            }
            $im->setImageFormat('png');
            $this->_result = null;
        }
        $this->_result = $im;
        unset($im);
        unset($color, $draw, $width, $deg);
        return $this;
    }

    /**
     * 响应链式调用结果
     *
     * @return mixed
     */
    public function data()
    {
        return $this->_result;
    }

    /**
     * 自动响应header并输出图片
     *
     * @return void
     */
    public function sendPNG()
    {
        if ($this->_result instanceof Imagick) {
            header("Content-Type: image/png");
            echo $this->_result;
        }
        exit;
    }



    private static function getXml($path)
    {
        $path = File::normalizePath($path);
        $path = str_replace('.xml', '', rtrim($path, '/'));
        return $path . '.xml';
    }
}

<?php

/**
 * @name 图片压缩
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Image;
use \Exception;
/**
 * 图片压缩类：通过缩放来压缩。
 * 如果要保持源图比例，把参数$percent保持为1即可。
 * 即使原比例压缩，也可大幅度缩小。数码相机4M图片。也可以缩为700KB左右。如果缩小比例，则体积会更小。
 *
 * 结果：可保存、可直接显示。
 */
class Compress
{

	private $src;
	private $image;
	private $imageInfo;
	private $percent = 0.9;

	/**
	 * 实例化
	 * @param float $percent  压缩比例
	 * @return object Compress
	 */
	static function instance($percent = 1)
	{
		return new static($percent);
	}

	/**
	 * 设定图片源
	 *
	 * @param string $src 源图片地址
	 * @param float $percent <1> 压缩比例
	 * @throw \Exception
	 * @return object Compress
	 */
	public function setOrigin(string $src, $percent = 0)
	{
		if (is_file($src)) {
			if ($percent > 0) {
				$this->percent = floatval($percent);
			}
			$this->src = $src;
			return $this;
		}
		throw new \Exception("图片源无效");
	}

	/**
	 * 高清压缩图片
	 * @param string $saveName  提供图片名（如无扩展名，则复用源图扩展）用于保存。或不提供文件名直接显示
	 */
	public function compress($saveName = '')
	{

		$this->_open();
		if (!empty($saveName)) $this->_save($saveName);  //保存
		else $this->_show();
	}

	/**
	 * 内部：打开图片
	 */
	private function _open()
	{
		if(!$this->src){
			throw new Exception('Missing source file');
		}
		list($width, $height, $type, $attr) = getimagesize($this->src);
		$this->imageInfo = array(
			'width' => $width,
			'height' => $height,
			'type' => image_type_to_extension($type, false),
			'attr' => $attr
		);

		$fun = "imagecreatefrom" . $this->imageInfo['type'];
		$this->image = $fun($this->src);
		$this->_thump();
	}


	/**
	 * 保存图片到硬盘：
	 * @param  string $dstImgName  1、可指定字符串不带后缀的名称，使用源图扩展名 。2、直接指定目标图片名带扩展名。
	 */

	private function _save($dstImgName)
	{

		if (empty($dstImgName)) return false;
		$allowImgs = ['.jpg', '.jpeg', '.png', '.bmp', '.wbmp', '.gif'];   //如果目标图片名有后缀就用目标图片扩展名 后缀，如果没有，则用源图的扩展名
		$dstExt =  strrchr($dstImgName, ".");
		$sourceExt = strrchr($this->src, ".");
		if (!empty($dstExt)) $dstExt = strtolower($dstExt);
		if (!empty($sourceExt)) $sourceExt = strtolower($sourceExt);
		//有指定目标名扩展名
		if (!empty($dstExt) && in_array($dstExt, $allowImgs)) {
			$dstName = $dstImgName;
		} elseif (!empty($sourceExt) && in_array($sourceExt, $allowImgs)) {

			$dstName = $dstImgName . $sourceExt;
		} else {

			$dstName = $dstImgName . $this->imageInfo['type'];
		}

		$funcs = "image" . $this->imageInfo['type'];
		$funcs($this->image, $dstName);
	}


	/**
	 * 输出图片:保存图片则用saveImage()
	 */
	private function _show()
	{

		header('Content-Type: image/' . $this->imageInfo['type']);
		$funcs = "image" . $this->imageInfo['type'];
		$funcs($this->image);
	}


	/**
	 * 内部：操作图片
	 */
	private function _thump()
	{
		$nWidth = intval($this->imageInfo['width'] * $this->percent);
		$nHeight = intval($this->imageInfo['height'] * $this->percent);
		$tmp = imagecreatetruecolor($nWidth, $nHeight);
		//将原图复制到载体上，并且按照一定比例压缩,极大的保持了清晰度(imagecopyresampled)
		imagecopyresampled($tmp, $this->image, 0, 0, 0, 0, $nWidth, $nHeight, $this->imageInfo['width'], $this->imageInfo['height']);
		$this->image && imagedestroy($this->image);
		$this->image = $tmp;
		unset($tmp);
	}

	private function __construct($percent = 1)
	{
		$percent = floatval($percent);
		if ($percent > 0)
			$this->percent = $percent;
	}

	/**
	 * 销毁图片
	 */
	public function __destruct()
	{
		$this->image && imagedestroy($this->image);
	}
}

<?php

/**
 * @name 图像处理类
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @link https://mywebmymail.com/easyphpthumbnail/
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\{Tools,Str as VipkwdStr, Dev};

class Thumb{

	use ThumbCoreVar, ThumbCore, Thumbmaker;

	/**
	 * 添加描边框
	 *
	 * @param integer $frameWidthPiex <10> 
	 * @param string $frameColor
	 * @return self
	 */
	public function eventAddFrame(int $frameWidthPiex=10, string $frameColor="#0ff"):self{
		$this->numberRangeLimit($frameWidthPiex, 0, self::$maxNumber);
		$this ->Framewidth = $frameWidthPiex;
		$this ->Framecolor = $this->colorHexFix($frameColor);
		return $this;
	}

	/**
	 * 自动调整尺寸
	 *
	 * @param integer $size （ size > 0 ）
	 * @return self
	 */
	public function eventAutoResize(int $size):self{
		$this->Thumbsize = $size;
		return $this;
	}

	/**
	 * 设置尺寸
	 *
	 * @param integer|null $widthPiex
	 * @param integer|null $heightPiex
	 * @return self
	 */
	public function eventResize(?int $widthPiex=null, ?int $heightPiex = null):self{
		$heightPiex >0 && $this->Thumbheight = $heightPiex;
		$widthPiex >0 && $this->Thumbwidth = $widthPiex;
		return $this;
	}

	/**
	 * 百分比缩放
	 *
	 * @param integer $percentage （0~100）
	 * @return self
	 */
	public function eventPercentZoom(int $percentage):self{
		$this->numberRangeLimit($percentage);
		$this -> Thumbsize = $percentage;
		$this -> Percentage = true;
		return $this;
	}

	/**
	 * 图像放大（填充)
	 *
	 * @param integer $size ( size > 0)
	 * @return self
	 */
	public function eventEnlarged(int $size):self{
		$this->numberRangeLimit($size, 0, self::$maxNumber);
		$this -> Thumbsize = $size;
		$this -> Inflate = true;
		return $this;
	}

	/**
	 * 设置图片质量比
	 *
	 * @param integer $num （0~100）
	 * @return self
	 */
	public function changeQuality(int $num):self{
		$this->numberRangeLimit($num);
		$this -> Quality = $num;
		return $this;
	}

	/**
	 * 开启边框阴影
	 *
	 * @return self
	 */
	public function filterShadow(bool $state = true):self{
		$this ->Shadow = $state;
		return $this;
	}

	/**
	 * 遮罩阴影
	 *
	 * @param string $colorHex 遮罩颜色Hex
	 * @param integer $coverWidth 遮罩覆盖宽度px
	 * @param integer $depth <20> 透明度 （0~100）
	 * @param integer $direction <2> 1从右向左 2从左向右
	 * @return self
	 */
	public function filterShading(string $colorHex,int $coverWidth, int $depth = 20, int $direction = 2):self{
		$colorHex = $this->colorHexFix($colorHex);
		$this->numberRangeLimit($direction, 1, 2);
		$this->numberRangeLimit($coverWidth, 0, self::$maxNumber);
		$this->numberRangeLimit($depth);
		$this -> Shading = array(1,$depth,$coverWidth,$direction);
		$this -> Shadingcolor = $colorHex;
		return $this;
	}

	/**
	 * 镜像
	 *
	 * @param string $colorHex 镜面色值Hex
	 * @param integer $mirrorHeight <100> 镜像高度（0~100）
	 * @param integer $startDepth <10> 镜面透明度梯度起始强度（0~100）
	 * @param integer $endDepth <60> 镜面透明度梯度结束强度（0~100）
	 * @param integer $gapPiex <2> 镜面与原图 触点间距px
	 * @return self
	 */
	public function filterMirror(string $colorHex, int $mirrorHeight=100, int $startDepth=10, int $endDepth=60, int $gapPiex = 2):self{
		$this->numberRangeLimit($startDepth);
		$this->numberRangeLimit($endDepth);
		$this->numberRangeLimit($mirrorHeight);
		$this->numberRangeLimit($gapPiex,0, self::$maxNumber);
		$this -> Mirror = array(1,$startDepth,$endDepth,$mirrorHeight,$gapPiex);
		$this -> Mirrorcolor = $this->colorHexFix($colorHex);
		return $this;
	}

	/**
	 * 底片效果
	 *
	 * @param string $state <true> 状态开关 默认开
	 * 
	 * @return self
	 */
	public function filterNegative(bool $state = true):self{
		$this->Negative = $state;
		return $this;
	}

	/**
	 * 颜色替换(场景：证件照换背景)
	 *
	 * @param string $findHex
	 * @param string $replaceHex
	 * @param integer $rgbGap
	 * @return self
	 */
	public function filterColorReplace(string $findHex, string $replaceHex, int $rgbGap = 10):self{
		$this -> Colorreplace = array(
			1,
			$this->colorHexFix($findHex),
			$this->colorHexFix($replaceHex),
			$rgbGap);
		return $this;
	}
	
	/**
	 * 彩图灰化（去彩色）
	 *
	 * @param boolean $state <true> 状态开关 默认开
	 * @return self
	 */
	public function filterGreyscale(bool $state = true):self{
		$this->Greyscale = $state;
		return $this;
	}

	/**
	 * 二值化（黑白照）
	 *
	 * @param boolean $state <true> 状态开关 默认开
	 * @return self
	 */
	public function filterBinaryzation(bool $state = true):self{
		$this->Binaryzation = $state;
		return $this;
	}

	/**
	 * 色彩反转
	 *
	 * @param boolean $state <true> 状态开关 默认开
	 * @return self
	 */
	public function filterColorFlip(bool $state = true):self{
		$this->ColorFlip = $state;
		return $this;
	}

	/**
	 * 色彩融合/混合
	 *
	 * @param integer $r （0~255）
	 * @param integer $g （0~255）
	 * @param integer $b （0~255）
	 * @param integer $opacity （0~127）
	 * @return void
	 */
	public function filterMergeColor(int $r, int $g, int $b, int $opacity = 0){
		$this -> numberRangeLimit($r,0, 255);
		$this -> numberRangeLimit($g,0, 255);
		$this -> numberRangeLimit($b,0, 255);
		$this -> numberRangeLimit($opacity,0, 127);
		$this -> Colorize = array(1, $r, $g, $b, $opacity);
		return $this;
	}

	/**
	 * 调整对比度
	 *
	 * @param integer $depth 明暗度（-100 ~ 100）(暗 < 0 < 明)
	 * @return self
	 */
	public function filterContrast(int $depth):self{
		$this -> numberRangeLimit($depth, -100, 100);
		$this -> Contrast = array(1,$depth);
		return $this;
	}

	/**
	 * 调色板
	 *
	 * @param integer $colorAmounts 调色板的颜色数量
	 * @return self
	 */
	public function filterPalette(int $colorAmounts):self{
		$this -> numberRangeLimit($colorAmounts, 1, self::$maxNumber);
		$this -> Palette = array(1,$colorAmounts);
		return $this;
	}

	/**
	 * 移除噪点
	 *
	 * @return self
	 */
	public function filterRemoveNoise(bool $state = true):self{
		$this -> Medianfilter = $state;
		return $this;
	}
	

	/**
	 * 扭曲:旋转
	 *
	 * @param integer $strength 效果强度(0~100)
	 * @param integer $direction <0> 旋转方向 0=顺时针1=逆时针
	 * @return self
	 */
	public function eventRotateWarp(int $strength, int $direction = 0):self{
		$this -> numberRangeLimit($strength, 0, 100);
		$this -> numberRangeLimit($direction, 0, 1);
		$this -> Twirlfx = array(1, $strength, $direction);
		return $this;
	}

	/**
	 * 扭曲:波纹
	 *
	 * @param integer $hAmount 水平波数量
	 * @param integer $hAmplitudePiex 水平波振幅
	 * @param integer $vAmount 垂直波数量
	 * @param integer $vAmplitudePiex 垂直波振幅
	 * @return self
	 */
	public function eventWavesWarp(int $hAmount, int $hAmplitudePiex, int $vAmount=0, int $vAmplitudePiex=0):self{
		$this -> Ripplefx = array(1,$hAmount, $hAmplitudePiex, $vAmount, $vAmplitudePiex);
		return $this;
	}

	/**
	 * 扭曲:湖面形
	 *
	 * @param integer $density 波的密度
	 * @param integer $areaSize 从底部测量的湖泊面积(0 - 100)
	 * @return self
	 */
	public function eventLakeWarp(int $density, int $areaSize):self{
		$this->numberRangeLimit($areaSize, 0, 100);
		$this -> Lakefx = array(1,$density, $areaSize);
		return $this;
	}

	/**
	 * 扭曲:圆形水滴效果
	 *
	 * @param integer $Amplitude 振幅px
	 * @param integer $radius 半径px
	 * @param integer $waveLength 波长
	 * @return self
	 */
	public function eventWaterdropWarp(float $amplitude, int $radius, int $waveLength):self{
		$this -> numberRangeLimit($radius, 0.1, self::$maxNumber);
		$this -> numberRangeLimit($waveLength, 1, self::$maxNumber);
		$this -> Waterdropfx = array(1,$amplitude,$radius,$waveLength);
		return $this;
	}

	/**
	 * 调整伽马(灰度)系数
	 * 
	 * gamma值即伽马值，是对曲线的优化调整，是亮度和对比度的辅助功能。Gamma也叫灰度系数
	 *
	 * @param float $coefficient 校正系数 ( 系数 > 0 )
	 * @return self
	 */
	public function eventChangeGamma(float $coefficient):self{
		$this->numberRangeLimit($coefficient, 0.00001, self::$maxNumber);
		$this -> Gamma = array(1,$coefficient);
		return $this;
	}
	/**
	 * 马赛克
	 *
	 * @param integer $depth 深度（0~150）
	 * @return self
	 */
	public function filterMosaic(int $depth):self{
		$this->numberRangeLimit($depth,0, 150);
		$this->Pixelate = array(1, $depth);
		return $this;
	}

	/**
	 * 提亮
	 *
	 * @param integer $depth 深度（-100 ~ 100）
	 * @return self
	 */
	public function eventBrightness(int $depth):self{
		$this->numberRangeLimit($depth, -100, 100);
		$this->Brightness = array(1, $depth);
		return $this;
	}
	
	/**
	 * 设置背景色
	 *
	 * @param string $colorHex HEX色值（f00 或 ff0000）
	 * 
	 * @return self
	 */
	public function setBackgroundColor(string $colorHex):self{
		$this->Backgroundcolor = $this->colorHexFix($colorHex);
		return $this;
	}

	/**
	 * 图像虚化（仅重新定位像素）
	 *
	 * @param integer $pixelRange <2> 像素范围
	 * @param integer $repeats <1> 定位(虚化)次数 （1~10）
	 * @return self
	 */
	public function filterVacuity(int $pixelRange = 2, int $repeats = 1):self{
		$this	-> numberRangeLimit($repeats, 1, 10);
		$this -> Pixelscramble = array(1,$pixelRange, $repeats);
		return $this;
	}
	/**
	 * 边角切割
	 *
	 * @param integer $percentage 切角百分比
	 * @param integer $type <1> 1直角切割 2圆角切割
	 * @param boolean $lt <true> 切割左上角
	 * @param boolean $lb <true> 切割左下角
	 * @param boolean $rt <true> 切割右上角
	 * @param boolean $rb <true> 切割右下角
	 * @return self
	 */
	public function eventClipCorner(int $percentage, int $type=1, bool $lt=true, bool $lb=true, bool $rt=true, bool $rb=true):self{
		$this->numberRangeLimit($percentage);
		$this->numberRangeLimit($type, 1, 2);
		$this -> Clipcorner = array($type, $percentage, 0, (int)$lt, (int)$lb, (int)$rt, (int)$rb);
		return $this;
	}

	/**
	 * 透明图像
	 *
	 * @param string $color 覆盖色值HEX 
	 * @param integer $type <0> 0=PNG 1=GIF 2=原始文件格式
	 * @param integer $rgbTolerance <0> RGB容错值（0~100）
	 * @return self
	 */
	public function eventMakeTransparent(string $color, int $type = 0, int $rgbTolerance = 0):self{
		$this->numberRangeLimit($type, 0, 2);
		$this->numberRangeLimit($rgbTolerance);
		$this -> Maketransparent = array(1, $type, $this->colorHexFix($color), $rgbTolerance);
		return $this;
	}

	/**
	 * 怀旧滤镜
	 *
	 * @param integer $noise <10> 噪点度(0~100)
	 * @param integer $depth <80> 深褐度(0~100)
	 * @return self
	 */ 
	public function filterNostalgic(int $noise =10, int $depth = 80):self{
		$this->numberRangeLimit($noise);
		$this->numberRangeLimit($depth);
		$this -> Ageimage = array(1,$noise,$depth);
		return $this;
	}
	
	/**
	 * 书签活页夹
	 *
	 * @param integer $frameWidth <10> 页夹宽度px
	 * @param string $frameColor <"#00ffff"> 页夹背景
	 * @param integer $binderSpacingPiex <5> 活页间距px
	 * @return self
	 */
	public function filterBinder(int $frameWidth=10, string $frameColor="#0ff", int $binderSpacingPiex=5):self{
		$this -> eventAddFrame($frameWidth, $frameColor);
		$this -> Binder = !0;
		$this -> Binderspacing = $binderSpacingPiex;
		return $this;
	}

	/**
	 * 翻转图像
	 *
	 * @param string $direction <h> h 水平翻转；v 垂直翻转
	 * @return self
	 */
	public function eventFlip(string $direction = "h"):self{
		//水平
		strtoupper($direction) == "H" && $this -> Fliphorizontal = true;
		//垂直
		strtoupper($direction) == "V" && $this -> Flipvertical = true;
		return $this;
	}

	/**
	 * 旋转
	 *
	 * @param integer $degSize 度数 （-180 ~ 180）负数向左旋转
	 * @param boolean $keepSize
	 * @return self
	 */
	public function eventRotate(int $degSize, bool $keepSize = false):self{
		$this->numberRangeLimit($degSize, -180, 180, 0);
		$this -> Rotate = $degSize;
		//保持原图尺寸(默认角度倾斜会撑宽原图))
		$keepSize && $this -> Croprotate = true;
		return $this;
	}

	/**
	 * 按长边放大至正方向(有留白)
	 *
	 * @return self
	 */
	public function eventSquareSpacing(bool $state = true):self{
		$this -> Square = $state;
		return $this;
	}

	/**
	 * 图像相对剪裁(无留白)
	 * 
	 * @param integer $type <1> 0=square-crop 1=center-crop 
	 * @param integer $unit <1> 0=percentage 1=pixels
	 * @param integer $leftGap <0> 剪除边距
	 * @param integer $rightGap <0> 剪除边距
	 * @param integer $topGap <0> 剪除边距
	 * @param integer $bottomGap <0> 剪除边距
	 * 
	 * @return self
	 */
	public function eventCrop(int $type = 1, int $unit = 1, int $leftGap = 0, int $rightGap = 0, int $topGap = 0, int $bottomGap = 0):self{
		$this->numberRangeLimit($type, 0, 1, 1);
		$this->numberRangeLimit($unit, 0, 1, 1);
		$this->numberRangeLimit($leftGap, 0, self::$maxNumber);
		$this->numberRangeLimit($rightGap, 0, self::$maxNumber);
		$this->numberRangeLimit($topGap, 0, self::$maxNumber);
		$this->numberRangeLimit($bottomGap, 0, self::$maxNumber);
		$type += 1;
		$this -> Cropimage = array($type, $unit, $leftGap, $rightGap, $topGap, $bottomGap);
		return $this;
	}

    /**
     * 图像定点剪裁
     *
     * @param  integer $w      裁剪区域宽度
     * @param  integer $h      裁剪区域高度
     * @param  integer $x      裁剪区域x坐标
     * @param  integer $y      裁剪区域y坐标
     * @param  integer|null $width  图像保存宽度
     * @param  integer|null $height 图像保存高度
     *
     * @return self
     */
	public function eventAbsoluteCrop(int $w, int $h, int $x = 0, int $y = 0, ?int $width = null, ?int $height = null):self{
		$this->numberRangeLimit($w, 0, self::$maxNumber);
		$this->numberRangeLimit($h, 0, self::$maxNumber);
		$this->numberRangeLimit($x, 0, self::$maxNumber);
		$this->numberRangeLimit($y, 0, self::$maxNumber);
		$this->Cropimage = array($w, $h, $x, $y, $width, $height, 'absolute');
		return $this;
	}
	/**
	 * PNG图片水印
	 *
	 * @param string $markFilePng 水印图地址
	 * @param integer $transparency <10> 透明度 0-100 100不透明
	 * @return self
	 */
	public function watermarkPng(string $markFilePng, int $transparency = 10):self{
		$this->numberRangeLimit($transparency);
		$this -> Watermarkpng = File::realpath($markFilePng);
		if(!empty(static::$__position)){
			$this -> Watermarkposition = static::$__position;
		}
		$this -> Watermarktransparency = $transparency; //透明度
		return $this;
	}


	/**
	 * 文字水印
	 *
	 * @param string $markText
	 * @param integer $fontSize <16>
	 * @param string $textColor <"#000000">
	 * @param string $ttfPath <"">
	 * @return self
	 */
	public function watermarkText(string $markText, int $fontSize=16, string $textColor="#000", string $ttfPath=""):self{
		$this->numberRangeLimit($fontSize, 8, 100);
		if($ttfPath && File::exists($ttfPath)){
			$this -> Copyrightfonttype = $ttfPath;
		}else{
			if(File::exists(self::$_defaultTTF)){
				$this -> Copyrightfonttype = self::$_defaultTTF;
			}
		}
		$this -> Copyrighttext = $markText;
		if(!empty(static::$__position)){
			$this -> Copyrightposition = static::$__position;
		}
		$this -> Copyrightfontsize = $fontSize;
		$this -> Copyrighttextcolor = $this->colorHexFix($textColor);
		return $this;
	}

	/**
	 * 宝丽来效果
	 *
	 * @param integer $thumbSize 图像大小
	 * @param string $text 文字内容
	 * @param string $ttfPath 字体文件
	 * @param integer $fontSize 字体大小
	 * @param string $textColor 字体颜色
	 * @return self
	 */
	public function polaroidEffect(int $thumbSize, string $text="", string $ttfPath="", int $fontSize=50, string $textColor="#000"):self{
		$this->numberRangeLimit($thumbSize, 10, self::$maxNumber);
		$this->numberRangeLimit($fontSize, 10, self::$maxNumber);
		$this -> Thumbsize = 200;
		// $this -> Shadow = true;
		$this -> Polaroid = true;
		$text && $this -> Polaroidtext = trim($text);
		File::exists($ttfPath) && $this -> Polaroidfonttype = $ttfPath;
		$this -> Polaroidfontsize = $fontSize;
		$this -> Polaroidtextcolor = $this->colorHexFix($textColor);
		return $this;
	}
	/**
	 * 预定义滤镜
	 *
	 * @param boolean $edge <true>
	 * @param boolean $emboss <true>
	 * @return self
	 */
	public function filterPreDefined(bool $edge=true, bool $emboss=true):self{
		$this -> Edge = $edge;
		$this -> Emboss = $emboss;
		$this -> Sharpen = true;
		$this -> Blur = true;
		$this -> Mean = true;
		return $this;
	}

	/**
	 * 透视原图并填补背景
	 * @param string $direction <"right">  透视方向 left/right/top/bottom
	 * @param integer $perspective  <10> 透视强度（0~100）
	 * @return self
	 */
	public function perspectiveOrigin(string $direction="right", int $perspective=10):self{
		$this->numberRangeLimit($perspective);
		$directions = [ "left"=> 0, "right"=> 1, "top"=> 2, "bottom"=> 3 ];
		!array_key_exists($direction, $directions) && $direction = "right";
		$this -> Perspective = array(1,$directions[$direction], $perspective);
		return $this;
	}

	/**
	 * 透视缩略图并填补背景
	 *
	 * @param string $direction <"right">  透视方向 left/right/top/bottom
	 * @param integer $perspective  <10> 透视强度（0~100）
	 * @return self
	 */
	public function perspectiveThumb(string $direction="right", int $perspective=10):self{
		$this->numberRangeLimit($perspective);
		$directions = [ "left"=> 0, "right"=> 1, "top"=> 2, "bottom"=> 3 ];
		!array_key_exists($direction, $directions) && $direction = "right";
		$this -> Perspectivethumb = array(1,$directions[$direction], $perspective);
		return $this;
	}
	 
	/**
	 * 16进制色值转RGB数值
	 * 
	 * -- #dfdfdf转换成(239,239,239)
	 *
	 * @param string $color
	 * @return void
	 */
	public function hex2rgb(string $color ) {
		$color = str_replace("#", "", $color);
		if ( strlen( $color ) == 6 ) {
			list( $r, $g, $b ) = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) == 3 ) {
			list( $r, $g, $b ) = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return false;
		}
		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );
		return array( 'r' => $r, 'g' => $g, 'b' => $b );
	}

	/**
	 * 为canvas填补文字
	 *
	 * @param string $text
	 * @param integer $textSize
	 * @param string $textColor
	 * @param string $ttfPath
	 * @return self
	 */
	public function textForCanvas(string $text, int $textSize = 20, string $textColor="#000",string $ttfPath=""):self{
		$this->numberRangeLimit($textSize, 1, self::$maxNumber);
		$this -> Addtext = array(
			1,
			$text,
			!empty(static::$__position) ? static::$__position : $this->Addtext[2],
			File::exists($ttfPath) ? $ttfPath : '',
			$textSize,
			$this->colorHexFix($textColor)
		);
		return $this;
	}

	/**
	 * 设置文件保存目录
	 *
	 * @param string $savePath
	 * @return self
	 */
	public function saveName(string $saveFileName = null):self{
		$saveFileName = rtrim(File::normalizePath($saveFileName),'/');

		$savePath = dirname($saveFileName);
		$saveName = basename($saveFileName);
		// $this->Thumbsaveas = 'jpg';
		if(empty($saveName)){
			$saveName = VipkwdStr::md5_16(VipkwdStr::uuid());
		}
		$saves = explode('.', $saveName);
		$ext = array_pop($saves);
		if( !in_array($ext, ['jpg','jpeg','png','gif'])){
			$saves[]= 'jpg';
			$ext = 'jpg';
		}
		$saves = implode('.', $saves);
		
		if(!is_dir($savePath)){
			@mkdir($savePath, 0755, true);
		}
		$this->Thumblocation = $savePath.'/';
		$this->Thumbprefix = '';
		// $this->Thumbfilename = empty($saveName) ? '' : $saveName;
		$this->Thumbsaveas = $ext;
		return $this;
	}

	/**
	 * 获取图片指定像素点的亮度值
	 *
	 * @param integer $x
	 * @param integer $y
	 * @return void
	 */
	public function getBrightnessOfPixel(int $x, int $y){
		$rgb = imagecolorat($this->image, $x, $y);
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;

		//红绿蓝能量不同，亮度不同，对应系数也不同（参考https://www.w3.org/TR/AERT/#color-contrast）
		$brightness = intval($r * 0.299 + $g * 0.587 + $b * 0.114);

		return $brightness;
	}

	/**
	 * 获取图片信息
	 *
	 * @param string $fileName
	 * @return array
	 */
	public function getImageInfo(string $fileName):array{
		//获取图像信息
		$info = @getimagesize($fileName);

		//检测图像合法性
		if (false === $info || (\IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
				return [];
		}
		//设置图像信息
		return [
			'width'  => $info[0],
			'height' => $info[1],
			'type'   => \image_type_to_extension($info[2], false),
			'mime'   => $info['mime'],
			'bits'	 => $info['bits'],
			"atime"	 => fileatime($fileName),
			"mtime"	 => filemtime($fileName),
			"ctime"	 => filectime($fileName),
			"md5"	 => hash_file("md5",$fileName),
			"file"	 => $fileName,
			"path"	 => dirname($fileName),
			"name"	 => basename($fileName),
		];
	}

	/**
	 * 设置定位(水印位置等)
	 * 
	 * --支持 9宫格( $x=1 ~ 9)
	 * --支持 百分比 ($x=20%,$y=20%)
	 * --支持 数值定位 ($x=10, $y=100)
	 * --不支持 三种方式的混合定位
	 *
	 * @param integer|string $x
	 * @param integer|string $y
	 * @return self
	 */
	public function setPosition($x, $y = null):self{
		switch($x){
			case "1": $x = $y = "0%";break;
			case "2": $x = "50%"; 	$y = "0%";break;
			case "3": $x = "100%"; 	$y = "0%";break;
			case "4": $x = "0%"; 		$y = "50%";break;
			case "5": $x = "50%";		$y = "50%";break;
			case "6": $x = "100%";	$y = "50%";break;
			case "7": $x = "0%";		$y = "100%";break;
			case "8": $x = "50%";		$y = "100%";break;
			case "9": $x = "100%";	$y = "100%";break;
			default: 
				//数值
				if(!strpos("$x","%") && !strpos("$y","%")){
					$x = intval($x);
					$y = intval($y);
				}else{
					$x = str_replace("%","", $x)."%";
					$y = str_replace("%","", $y ?? "0")."%";
				}
				break;
		}
		static::$__position = "{$x} {$y}";
		return $this;
	}

	/**
	 * 实例化入口
	 *
	 * @param array $option <[]> 预留参数
	 * @param string $instanceID <"1"> 单例ID
	 * @return self
	 */
	static function instance(array $option = [], string $instanceID = "1"):self{
			$_k = md5(strval($instanceID));
			if(!isset(static::$_instance[$_k])){
					static::$_instance[$_k] = new self($option);
			}
			return static::$_instance[$_k];
	}

	/**
	 * 从画布创建图像
	 *
	 * @param int $width
	 * @param int $height
	 * @param string $bgcolor <#666>background color
	 * @param int $filetype <IMAGETYPE_PNG> PHP常量 IMAGETYPE_XXX
	 * @param boolean $transparent <false>
	 * 
	 * @return self
	 */	
	private function createCanvas(int $width, int $height, string $bgcolor='#666', int $filetype=IMAGETYPE_PNG, bool $transparent=false):self{
		$this->im=imagecreatetruecolor($width,$height);
		$this->size=array($width,$height,$filetype);
		$bgcolor = $this->colorHexFix($bgcolor);
		$color=imagecolorallocate(
			$this->im,
			hexdec(substr($bgcolor,1,2)),
			hexdec(substr($bgcolor,3,2)),
			hexdec(substr($bgcolor,5,2))
		);
		imagefilledrectangle($this->im,0,0,$width,$height,$color);
		if ($transparent) {
			$this->Keeptransparency=true;
			imagecolortransparent($this->im,$color);
		}
		return $this;
	}

	/**
	 * 创建和输出缩略图
	 *
	 * @param bool $storage <false> true 保存到本地
	 * @param bool $showInfo <false> true返回图片信息
	 * 
	 * @return array|null
	 */	
	public function createThumb(bool $storage=false, bool $showInfo=false):array{

		$list = [];
		if (is_array($this->_originFilePath) && $storage) {
			foreach ($this->_originFilePath as $name) {
				if(file_exists($name)){
					$this->image=$name;
					$this->thumbmaker();
					$thumb = $this->savethumb();
					$info = [];
					$showInfo && $info = self::getImageInfo($thumb);
					$info["origin"] = $name;
					$info["thumb"] = $thumb;
					$list[] = $info;
				}
			}
			return $list;
		} else {
			if(file_exists($this->_originFilePath)){
				$this->image=$this->_originFilePath;
				$this->thumbmaker();
				if ($storage) {
					$thumb = $this->savethumb();
					$info = [];
					$showInfo && $info = self::getImageInfo($thumb);
					$info["origin"] = $this->_originFilePath;
					$info["thumb"] = $thumb;
					$list[] = $info;
					return $list;
				} else {
					$this->displaythumb();
				}
			}
		}
		return [];
	}
    	
	/**
	 * 输出图片的base64数据
	 *
	 * @return string|array
	 */	
	public function createBase64(){

		$list = [];
		$n = 1;
		if(!is_array($this->_originFilePath)){
			$n = 0;
			$this->_originFilePath = [$this->_originFilePath];
		}
		foreach ($this->_originFilePath as $file) {
			if(file_exists($file)){
				$this->image=$file;
				$this->thumbmaker();
				ob_start();
				switch($this->size[2]) {
					case 1:
						$encoding='data:image/gif;base64,';
						imagegif($this->thumb);
						break;
					case 2:
						$encoding='data:image/jpeg;base64,';
						imagejpeg($this->thumb,NULL,$this->Quality);
						break;
					case 3:
						$encoding='data:image/png;base64,';
						imagepng($this->thumb);
						break;
				}
				$imagecode=ob_get_contents();
				ob_end_clean();
				$list[] = $encoding . chunk_split(base64_encode($imagecode)) . '"';
			}
		}
		return $n === 0 ? $list[0] : $list;
	}
	
  	/**
	 * 创建一个动画的PNG图像
	 *
	 * @param string $outputFilename
	 * @param string $delay
	 * 
	 * @throws \Exception
	 * @return array
	 */	
	
	public function createApng(string $outputFilename, string $delay="10"):array{
		$imageData = array();
		$IHDR = array();
		$sequenceNumber = 0;
		if(!is_array($this->_originFilePath)){
			$n = 0;
			$this->_originFilePath = [$this->_originFilePath];
		}
		$outputFilename = File::normalizePath($outputFilename);
		if(is_dir($outputFilename)){
			throw new \Exception( $outputFilename. "[output file] cannot be a directory!");
		}
		File::createDir(dirname($outputFilename));

		foreach ($this->_originFilePath as $frame) {
			if (file_exists($frame)) {
				$fh = fopen($frame,'rb');
				$chunkData = fread($fh, 8);                                                 
				$header = unpack("C1highbit/"."A3signature/". "C2lineendings/"."C1eof/"."C1eol", $chunkData);
				if (is_array($header) && $header['highbit'] == 0x89 && $header['signature'] == "PNG") {
					$IDAT='';
					while (!feof($fh)) {
						$chunkData = fread($fh, 8);
						$chunkDataHeader = unpack ("N1length/A4type", $chunkData);                    
						switch ($chunkDataHeader['type']) {
							case 'IHDR':                                                  
								if (count($IHDR) == 0) {
									$chunkData = fread($fh, $chunkDataHeader['length']);     
									$IHDR = unpack("N1width/"."N1height/". "C1bits/"."C1color/"."C1compression/"."C1prefilter/"."C1interlacing", $chunkData);
									fseek($fh, 4, SEEK_CUR);                                
								} else {
									fseek($fh, $chunkDataHeader['length'] + 4, SEEK_CUR);    
								}
								break;            
							case 'IDAT':                                                     
								$IDAT .= fread($fh, $chunkDataHeader['length']);     
								fseek($fh, 4, SEEK_CUR);                                     
								break;                      
							case 'IEND';                                                     
								break 2;                                                   
							default:
								fseek($fh, $chunkDataHeader['length'] + 4, SEEK_CUR);
								break;
						}                    
					}
					fclose($fh);
					$imageData[] = $IDAT;
				} else {
					fclose($fh);
				}
			}
		}

		$pngHeader = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
		$IHDR_chunk = $this->create_chunk('IHDR', pack('NNCCCCC', $IHDR['width'], $IHDR['height'], $IHDR['bits'], $IHDR['color'], $IHDR['compression'], $IHDR['prefilter'], $IHDR['interlacing']));
		$acTL_chunk = $this->create_chunk('acTL', pack("NN", count($imageData), 0));
		$data = $this->create_fcTL($sequenceNumber, $IHDR['width'], $IHDR['height'], $delay);  
		$fcTL_chunk = $this->create_chunk('fcTL', $data);
		$sequenceNumber += 1;
		if (count($imageData) == 1) {$acTL_chunk = $fcTL_chunk = '';}
		$fh = fopen($outputFilename, 'w');
		foreach ($imageData as $key => $image) {
			if ($key == 0) {
				$firstFrame = $this->create_chunk('IDAT', $image);
				fwrite($fh, $pngHeader . $IHDR_chunk . $acTL_chunk . $fcTL_chunk . $firstFrame);
			} else {
				$data = $this->create_fcTL($sequenceNumber, $IHDR['width'], $IHDR['height'], $delay);  
				$fcTL_chunk = $this->create_chunk('fcTL', $data);
				$sequenceNumber += 1;
				$data = pack("N", $sequenceNumber);
				$data .= $image; 
				$fdAT_chunk = $this->create_chunk('fdAT', $data);
				$sequenceNumber += 1;            
				fwrite($fh, $fcTL_chunk . $fdAT_chunk);
			}
		}
		fwrite($fh, $this->create_chunk('IEND'));
		fclose($fh);
		return self::getImageInfo($outputFilename);
	}


	/**
	 * 生成占位图
	 * 
	 * -e.g: phpunit("Thumb::createPlaceholder",["100x100",3, 3]);
	 * -e.g: phpunit("Thumb::createPlaceholder",["100x100",3, 2]);
	 * -e.g: phpunit("Thumb::createPlaceholder",["100x100",3, 1]);
	 * -e.g: phpunit("Thumb::createPlaceholder",["100x100",3]);
	 *
	 * @param string $WxHsize 400x300 || 4:3x400 || 16:9x300
	 * @param integer $getType <1> 1:header 2:binData 3: savePath;
	 * @param integer $format <1> 1:jpg 2:gif 3:png
	 * @param integer $fontSize 文字大小 <宽或高的1%> 默认：min($width * 0.1, $height * 0.1)
	 * @param string $text	<宽 x 高> 最大12个字
	 * @param string $bgColor <#666666> #ffffff || #fff || #f
	 * @param string $textColor <#ffffff> #ffffff || #fff || #f
	 * @param integer $angle <0> 角度(度数合法值)
	 * @return header
	 */
	public function createPlaceholder(string $WxHsize, int $getType=1, int $format = 1, int $fontSize=0, string $text = "W x H", string $bgColor="#666", string $textColor="#fff", int $angle =0){
		$WxHsize = strtolower(str_replace([" ",'X'],["",'x'], trim($WxHsize)));
		list($width, $height) = explode("x", $WxHsize);
		if(strpos($width, ":")){
			list($w1,$h1) = explode(":", $width);
			$width = bcmul(bcdiv($height, $h1, 4), $w1, 0);
		}else if(strpos($height, ":")){
			list($w1,$h1) = explode(":", $height);
			$height = bcmul(bcdiv($width, $w1, 4), $height, 0);
		}
		$width *= 1;
		$height *= 1;
		$this->numberRangeLimit($width, 50, 2048);
		$this->numberRangeLimit($height, 50, 2048);
		$this->numberRangeLimit($angle, -180, 180, 0);
		$this->numberRangeLimit($format, 1,3);
		$this->numberRangeLimit($getType, 1,3);
		!$textColor && $textColor = "#fff";
		!$bgColor && $bgColor = "#666";

		// 文本大小
		$size = $fontSize >= 1 ? $fontSize : intval(($width > $height ? $height : $width) * 0.1);
		// 设置文本内容
		$text = trim(preg_replace('/\ +/',' ',$text));
		$_text = strtoupper(str_replace(' ','',$text));
		$content = ($_text && $_text != "WXH") ? mb_substr($text,0,12) : $width . ' x ' . $height;

		$bgColor = $this->hex2rgb($this->colorHexFix($bgColor));
		$textColor = $this->hex2rgb($this->colorHexFix($textColor));
		$etag = md5(json_encode(compact("angle", "height", "width", "content","size", "textColor", "bgColor", "format" )));
		if($getType === 1 && array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag){
			Http::sendCode(304);
			exit();
		}
		// 创建画布
		$this->thumb = imagecreatetruecolor($width, $height);
		// 设置文本颜色
		$textColor = imagecolorallocate($this->thumb, $textColor['r'],$textColor['g'],$textColor['b']);
		// 设置画布颜色
		$backgroundColor = imagecolorallocate($this->thumb, $bgColor['r'],$bgColor['g'],$bgColor['b']);
		// 创建画布并且填充颜色
		imagefilledrectangle($this->thumb, 0, 0, $width, $height, $backgroundColor);
		// 设置字体文字路径
		$fontPath = File::realpath(VIPKWD_UTILS_LIB_ROOT.'/support/ttfs/msyh.ttf');
		//计算文本范围
		$position = imagettfbbox($size, $angle, $fontPath, $content);
		$x        = intval(($width - $position[2] - $position[0]) / 2);
		$y        = intval(($height - $position[3] - $position[5]) / 2);
		// 写入文本
		@imagefttext($this->thumb, $size, $angle, $x, $y, $textColor, $fontPath, $content);

		// 开启缓存
		ob_start();
		// 输出图像
		switch($format) {
			case 1:
			default:
				imagejpeg($this->thumb,NULL,$this->Quality ? intval($this->Quality): 90);
				$ext = "jpeg";
				break;
			case 2:
				imagegif($this->thumb);
				$ext = "gif";
				break;
			case 3:
				imagepng($this->thumb);
				$ext = "png";
				break;
		}
		$content = ob_get_clean();
		$this->im && imagedestroy($this->im);
		$this->thumb && imagedestroy($this->thumb);
		if($getType === 2){
			return $content;
		}else if($getType === 3){
			$temp = File::pathToUnix(sys_get_temp_dir()) .'/'.md5($etag).'.'. $ext;
			File::write($temp, $content);
			return $temp;
		}
		header('Cache-Control: public');
		header('max-age: 31536000');
		header('Last-Modified: ' . gmdate("D, d M Y H:i:s", strtotime(date('Y-m-01 00:00:00'))) . ' GMT');
		header("Etag:" . $etag);
		header('Content-type: image/'.$mime);
		header('Content-Length:'.strlen($content));
		exit($content);
	}

	/**
	 * 设置操作源图片(组)
	 *
	 * @param string|array $filePaths 一维数组图片PATH 或 单图PATH
	 * 
	 * @return self
	 */
	public function setImage($filePaths):self{
		$n = 1;
		if(!is_array($filePaths)){
			$n = 0;
			$filePaths = [$filePaths];
		}
		foreach($filePaths as $k=> &$file){
			$file = realpath(File::normalizePath($file));
			if(!file_exists($file)){
				unset($filePaths[$k]);
			}
			unset($file);
		}
		if(empty($filePaths)){
			throw new \Exception('图片资源无效');
		}
		$this->_originFilePath = $n === 0 ? $filePaths[0] : $filePaths;
		return $this;
	}
}


trait Thumbmaker {

    private static $_instance = [];
	private static $__position = "";
	private static $maxNumber = 99999999;
	private static $_defaultTTF = VIPKWD_UTILS_LIB_ROOT.'/support/ttfs/msyh.ttf';
	//操作源图片
	private $_originFilePath = [];
	private static $ThumbSaveName = null;
	/**
	 * 数值区间验证
	 *
	 * @param integer|float $num
	 * @param integer|float $min
	 * @param integer|float $max
	 * @param integer|float|null $default
	 * @return void
	 */
	private function numberRangeLimit(&$num, $min = 0, $max = 100, $default = null){
		if($num < $min){
			if($default) return $default;
			$num = $min;
		}
		if($num > $max){
			if($default) return $default;
			$num = $max;
		}
		$num *= 1;
		return $num;
	}
	
	/**
	 * 16进制色值检测/修补
	 *
	 * @param string $color  "#f" "#ff" "#fff" "#ffffff"
	 * @return string 标准色值hex "#ffffff"
	 */
	private function colorHexFix(string $color):string{
		$color = str_replace("#","", trim($color));
		$len = strlen($color);
		switch($len){
			case "3":
				$color = str_split($color);
				$color = implode("", [ $color[0], $color[0], $color[1], $color[1], $color[2], $color[2], ]);
				break;
			case "2":
				$color = implode("", [ $color, $color, $color]);
				break;
			case "1":
				$color = str_pad("", 6, $color);
				break;
			default:break;
		}
		$color = strtoupper(substr(str_pad($color, 6, "0"), 0, 6));
		
		// Dev::vdump([$color, preg_match("/^[0-9A-F]{6}$/i", $color),1],1);
		if(!preg_match("/^([0-9A-F]+){6}$/i", $color)){
			$color = "000000";
		}
		return "#".$color;
	}

	/**
	 * 资源坐标定位管理
	 *
	 * @param string $position "80% 70%" 或 "80 70"
	 * @param callback $callback
	 * @return array
	 */
	private function formatPosition(string $position, callable $callback):array{
		$_pos = str_replace("%","", $position);
		$cpos = explode(" ", $_pos);
		if($_pos == $position){
			//直接设置X,Y
			return $callback($cpos[0]*1, $cpos[1] ? $cpos[1]*1 : 0, false);
		}
		$this->numberRangeLimit($cpos[0], 0, 100);
		$this->numberRangeLimit($cpos[1], 0, 100);
		//百分比配置X,Y
		return $callback($cpos[0]*1, $cpos[1]*1, true);
	}

	/**
	 * 擦出EXIF信息并转存图像
	 *
	 * @param string $srcImage
	 * @param string $outputFilename
	 */	      
	protected function wipeExif(string $srcImage, string $outputFilename):void{
		if (file_exists($srcImage)) {
			$src_file = fopen($srcImage, 'rb');
			$wipe = false;
			if ($src_file) {
				$header = unpack("C1byte1/" . "C1byte2/", fread($src_file, 2));
				if ($header['byte1'] == 0xff & $header['byte2'] == 0xd8) {
					$header = unpack("C1byte1/" . "C1byte2/", fread($src_file, 2));
					while ($header['byte1'] == 0xff && ($header['byte2'] >= 0xe0 && $header['byte2'] <= 0xef || $header['byte2'] == 0xfe)) {
						$length = unpack("n1length", fread($src_file, 2));	
						fseek($src_file, $length['length'] - 2, SEEK_CUR);
						$header = unpack("C1byte1/" . "C1byte2/", fread($src_file, 2));
						$wipe = true;
					}
					if ($wipe) {
						fseek($src_file, -2, SEEK_CUR);
						$image_data = "\xff\xd8" . fread($src_file, filesize($srcImage));
						fclose($src_file);
						$des_file = fopen($outputFilename, 'w');
						if ($des_file) {
							fwrite($des_file, $image_data);
							fclose($des_file);
							chmod($outputFilename, octdec($this->Chmodlevel));
						}
					} else {
						fclose($src_file);
					}
				} else {
					fclose($src_file);
				}
			}
		}
	}

	/**
	 * 读取图像EXIF信息
	 *
	 * @param string source image
	 */	      
	protected function readExif(string $srcImage){
		$exif = "";
		if (file_exists($srcImage)) {
			if(function_exists('exif_read_data')){
				//return exif_read_data($srcImage, "EXIF", true);
			}
			$src_file = fopen($srcImage, 'rb');
			if ($src_file) {
				$header = unpack("C1byte1/" . "C1byte2/", fread($src_file, 2));
				if ($header['byte1'] == 0xff & $header['byte2'] == 0xd8) {
					$exifdata = fread($src_file, 2);
					$header = unpack("C1byte1/" . "C1byte2/", $exifdata);
					while ($header['byte1'] == 0xff && ($header['byte2'] >= 0xe0 && $header['byte2'] <= 0xef || $header['byte2'] == 0xfe)) {
						$exif .= $exifdata;
						$exifdata = fread($src_file, 2);
						$exif .= $exifdata;
						$length = unpack("n1length", $exifdata);
						$exif .= fread($src_file, $length['length'] - 2);
						$exifdata = fread($src_file, 2);
						$header = unpack("C1byte1/" . "C1byte2/", $exifdata);
					}
					fclose($src_file);
				} else {
					fclose($src_file);
				}
			}
		}
		return $exif;
	}

	/**
	 * 写入 EXIF 信息到图像
	 *
	 * @param string 待
	 * @param string 二进制格式的Exif数据
	 */	      
	protected function insertExif($srcImage, $exifData):void{
        if (file_exists($srcImage)) {
            $src_file = fopen($srcImage, 'rb');
            if ($src_file) {
                $header = unpack("C1byte1/" . "C1byte2/", fread($src_file, 2));
                if ($header['byte1'] == 0xff & $header['byte2'] == 0xd8) {
                    $header = unpack("C1byte1/" . "C1byte2/", fread($src_file, 2));
                    while ($header['byte1'] == 0xff && ($header['byte2'] >= 0xe0 && $header['byte2'] <= 0xef || $header['byte2'] == 0xfe)) {
						$length = unpack("n1length", fread($src_file, 2));	
						fseek($src_file, $length['length'] - 2, SEEK_CUR);
						$header = unpack("C1byte1/" . "C1byte2/", fread($src_file, 2));
                    }
				    fseek($src_file, -2, SEEK_CUR);
				    $image_data = "\xff\xd8" . $exifData . fread($src_file, filesize($srcImage));
	                fclose($src_file);
				    $des_file = fopen($srcImage, 'w');
				    if ($des_file) {
						fwrite($des_file, $image_data);
						fclose($des_file);
						chmod($srcImage, octdec($this->Chmodlevel));
				    }
                } else {
                    fclose($src_file);
                }
            }
        }
  	}
}

trait ThumbCoreVar{
    /**
	 * 缩略图的大小(px)
	 * 自动缩放横向或纵向
	 *
	 * @var int
	 */	
	protected $Thumbsize;	
	/**
	 * 缩略图的高度(px)
	 * 强制所有缩略图都具有相同的高度
	 *
	 * @var int
	 */	
	protected $Thumbheight;		
	/**
	 * 缩略图的宽度(px)
	 * 强制所有缩略图都具有相同的宽度
	 *
	 * @var int
	 */	
	protected $Thumbwidth;
	/**
	 * 尺寸百分数（而不是px）
	 * 
	 * @var boolean
	 */		
	protected $Percentage;	
	/**
	 * 放大图像
	 *
	 * @var boolean
	 */		
	protected $Inflate;
	/**
	 * JPEG图像的质量（0 ~ 100）
	 *
	 * @var int
	 */		
	protected $Quality;	
	/**
	 * 图像周围的帧宽度(px)
	 *
	 * @var int
	 */	
	protected $Framewidth;
	/**
	 * 框架帧颜色:“#00FF00”
	 *
	 * @var string
	 */		
	protected $Framecolor;	
	/**
	 * 背景颜色:“#00FF00”
	 *
	 * @var string
	 */		
	protected $Backgroundcolor;	
	/**
	 * 添加阴影
	 * 
	 * @var boolean
	 */		
	protected $Shadow;
	/**
	 * 显示活页夹环
	 *
	 * @var boolean
	 */		
	protected $Binder;
	/**
	 * 活页夹环间距(px)
	 *
	 * @var int
	 */			
	protected $Binderspacing;	
	/**
	 * PNG水印图像路径
	 *
	 * @var string
	 */		
	protected $Watermarkpng;
	/**
	 * Position of watermark image, bottom right corner: '100% 100%'
     * 水印图像的位置，右下角:“100% 100%”
	 *
	 * @var string
	 */		
	protected $Watermarkposition;
	/**
	 * Transparency level of watermark image 0 - 100
     * 水印图像的透明度等级为0 - 100
	 *
	 * @var int
	 */		
	protected $Watermarktransparency;
	/**
	 * CHMOD level of saved thumbnails: '0755'
     * 保存缩略图的chmod权限:'0755'
	 *
	 * @var string
	 */		
	protected $Chmodlevel;
	/**
	 * 缩略图本地PATH
	 *
	 * @var string
	 */		
	protected $Thumblocation;
	/**
	 * Filetype conversion for saving thumbnail
     * 保存缩略图的文件类型转换
	 *
	 * @var string
	 */		
	protected $Thumbsaveas;	
	/**
	 * Prefix for saving thumbnails
     * 用于保存缩略图的前缀
	 *
	 * @var string
	 */		
	protected $Thumbprefix;

	/**
	 * Clip corners; array with 7 values
	 * [0]: 0=disable 1=straight 2=rounded
	 * [1]: Percentage of clipping
	 * [2]: Clip randomly 0=disable 1=enable
	 * [3]: Clip top left 0=disable 1=enable
	 * [4]: Clip bottom left 0=disable 1=enable
	 * [5]: Clip top right 0=disable 1=enable
	 * [6]: Clip bottom right 0=disable 1=enable
	 *
	 * @var array
	 */		
	protected $Clipcorner;
	/**
	 * 怀旧老化/灰色效果
     * array with 3 values
	 * [0]: Boolean 0=disable 1=enable
	 * [1]: Add noise 0-100, 0=disable
	 * [2]: Sephia depth 0-100, 0=disable (greyscale)
	 *
	 * @var array
	 */		
	protected $Ageimage;
	/**
	 * 裁剪图像
     * array with 6 values
	 * [0]: 0=disable 1=enable free crop 2=enable center crop 3=enable square crop
	 * [1]: 0=percentage 1=pixels
	 * [2]: Crop left
	 * [3]: Crop right
	 * [4]: Crop top
	 * [5]: Crop bottom
	 *
	 * @var array
	 */		
	protected $Cropimage;	
	/**
	 * PNG边框图像PATH
	 *
	 * @var string
	 */			
	protected $Borderpng;
	/**
	 * Copyright text
     * 版权文本
	 *
	 * @var string
	 */		
	protected $Copyrighttext;
	/**
	 * Position for Copyrighttext text, bottom right corner: '100% 100%'
     * 版权文本 位置
	 *
	 * @var string
	 */			
	protected $Copyrightposition;
	/**
	 * Path to TTF Fonttype
     * 字体文件位置(不指定则 默认使用系统字体)
	 *
	 * @var string
	 */			
	protected $Copyrightfonttype;		
	/**
	 * Fontsize for Copyrighttext text
     * 字号
	 *
	 * @var string
	 */			
	protected $Copyrightfontsize;	

	/**
     * 文字颜色: '#000000'(不指定则 默认黑白)
	 *
	 * @var string
	 */			
	protected $Copyrighttextcolor;

	/**
	 * Add text to the image
	 * [0]: 0=disable 1=enable
	 * [1]: Text string
	 * [2]: Position for text, bottom right corner: '100% 100%'
	 * [3]: Path to TTF Fonttype, if no TTF font is specified, system font will be used
	 * [4]: Fontsize for text
	 * [5]: Text color in web format: '#000000'
	 *
	 * @var array
	 */		
	protected $Addtext;	
	/**
	 * Rotate image in degrees
	 *
	 * @var int
	 */				
	protected $Rotate;	
	/**
	 * Flip the image horizontally
	 *
	 * @var boolean
	 */				
	protected $Fliphorizontal;		
	/**
	 * Flip the image vertically
	 *
	 * @var boolean
	 */				
	protected $Flipvertical;	
	/**
	 * Create square canvas thumbs
	 *
	 * @var boolean
	 */			
	protected $Square;
	/**
	 * Apply a filter to the image
	 *
	 * @var boolean
	 */			
	protected $Applyfilter;
	/**
	 * Apply a 3x3 filter matrix to the image; array with 9 values
	 * [0]: a1,1
	 * [1]: a1,2
	 * [2]: a1,3
	 * [3]: a2,1
	 * [4]: a2,2
	 * [5]: a2,3
	 * [6]: a3,1
	 * [7]: a3,2
	 * [8]: a3,3
	 *
	 * @var array
	 */		
	protected $Filter;
	/**
	 * Divisor for filter
	 *
	 * @var int
	 */				
	protected $Divisor;
	/**
	 * Offset for filter
	 *
	 * @var int
	 */				
	protected $Offset;
	/**
	 * Blur filter
	 *
	 * @var boolean
	 */				
	protected $Blur;	
	/**
	 * Sharpen filter
	 *
	 * @var boolean
	 */				
	protected $Sharpen;		
	/**
	 * Edge filter
	 *
	 * @var boolean
	 */				
	protected $Edge;
	/**
	 * Emboss filter
	 *
	 * @var boolean
	 */				
	protected $Emboss;
	/**
	 * Mean filter
	 *
	 * @var boolean
	 */				
	protected $Mean;	
	/**
	 * Rotate and crop the image
	 *
	 * @var boolean
	 */				
	protected $Croprotate;	
	/**
	 * Apply perspective to the image; array with 3 values
	 * [0]: 0=disable 1=enable
	 * [1]: Direction 0=left 1=right 2=top 3=bottom
	 * [2]: Perspective strength 0 - 100
	 *
	 * @var array
	 */		
	protected $Perspective;
	/**
	 * Apply perspective to the thumbnail; array with 3 values
	 * [0]: 0=disable 1=enable
	 * [1]: Direction 0=left 1=right 2=top 3=bottom
	 * [2]: Perspective strength 0 - 100
	 *
	 * @var array
	 */		
	protected $Perspectivethumb;
	/**
	 * Apply shading gradient to the image; array with 4 values
	 * [0]: 0=disable 1=enable
	 * [1]: Shading strength 0 - 100
	 * [2]: Shading area 0 - 100
	 * [3]: Shading direction 0=right 1=left 2=top 3=bottom
	 *
	 * @var array
	 */		
	protected $Shading;
	/**
	 * Shading gradient color in web format: '#00FF00'
	 *
	 * @var string
	 */		
	protected $Shadingcolor;		
	/**
	 * Apply a mirror effect to the thumbnail; array with 4 values
	 * [0]: 0=disable 1=enable
	 * [1]: Mirror transparency gradient starting strength 0 - 100
	 * [2]: Mirror transparency gradient ending strength 0 - 100
	 * [3]: Mirror area 0 - 100
	 * [4]: Mirror 'gap' between original image and reflection in px
	 *
	 * @var array
	 */	
	protected $Mirror;
	/**
	 * Mirror gradient color in web format: '#00FF00'
	 *
	 * @var string
	 */		
	protected $Mirrorcolor;		
	/**
	 * Create image negative
	 *
	 * @var boolean
	 */			
	protected $Negative;
	/**
	 * Replace a color in the image; array with 4 values
	 * [0]: 0=disable 1=enable
	 * [1]: Color to replace in web format: '#00FF00'
	 * [2]: Replacement color in web format: '#FF0000'
	 * [3]: RGB tolerance 0 - 100
	 *
	 * @var array
	 */		
	protected $Colorreplace;
	/**
	 * Scramble pixels; array with 3 values
	 * [0]: 0=disable 1=enable
	 * [1]: Pixel range
	 * [2]: Repeats (use with care!)
	 *
	 * @var array
	 */		
	protected $Pixelscramble;
	/**
	 * Convert image to greyscale
	 *
	 * @var boolean
	 */				
	protected $Greyscale;
	/**
	 * Change brightness of the image; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Brightness -100 to 100
	 *
	 * @var array
	 */		
	protected $Brightness;	
	/**
	 * Change contrast of the image; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Contrast -100 to 100
	 *
	 * @var array
	 */		
	protected $Contrast;
	/**
	 * Change gamma of the image; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Gamma correction factor
	 *
	 * @var array
	 */		
	protected $Gamma;	
	/**
	 * Reduce palette of the image; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Amount of colors for palette
	 *
	 * @var array
	 */		
	protected $Palette;	
	/**
	 * Merge a color in the image; array with 5 values
	 * [0]: 0=disable 1=enable
	 * [1]: Red component 0 - 255
	 * [2]: Green component 0 - 255
	 * [3]: Blue component 0 - 255
	 * [4]: Opacity level 0 - 127
	 *
	 * @var array
	 */		
	protected $Colorize;	
	/**
	 * Pixelate the image; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Block size in px
	 *
	 * @var array
	 */		
	protected $Pixelate;		
	/**
	 * Apply a median filter to remove noise
	 *
	 * @var boolean
	 */				
	protected $Medianfilter;		
	/**
	 * Deform the image with twirl effect; array with 3 values
	 * [0]: 0=disable 1=enable
	 * [1]: Effect strength 0 to 100
	 * [2]: Direction of twirl 0=clockwise 1=anti-clockwise
	 *
	 * @var array
	 */		
	protected $Twirlfx;
	/**
	 * Deform the image with ripple effect; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Amount of horizontal waves
	 * [2]: Amplitude of horizontal waves in px
	 * [3]: Amount of vertical waves
	 * [4]: Amplitude of vertical waves in px	 
	 *
	 * @var array
	 */		
	protected $Ripplefx;		
	/**
	 * Deform the image with perspective ripple or 'lake' effect; array with 3 values
	 * [0]: 0=disable 1=enable
	 * [1]: Density of the waves	 
	 * [2]: Lake area measured from bottom 0 - 100	 
	 *
	 * @var array
	 */		
	protected $Lakefx;
	/**
	 * Deform the image with a circular waterdrop effect; array with 4 values
	 * [0]: 0=disable 1=enable
	 * [1]: Amplitude in px
	 * [2]: Radius in px
	 * [3]: Wavelength in px
	 *
	 * @var array
	 */		
	protected $Waterdropfx;
	/**
	 * Create transparent image; array with 4 values
	 * [0]: 0=disable 1=enable
	 * [1]: 0=PNG 1=GIF 2=Original File Format
	 * [2]: Replacement color in web format: '#FF0000'
	 * [3]: RGB tolerance 0 - 100
	 *
	 * @var array
	 */		
	protected $Maketransparent;
	/**
	 * Keep transparency of original image
	 *
	 * @var boolean
	 */				
	protected $Keeptransparency;	
	/**
	 * Filename for saving thumbnails
	 *
	 * @var string
	 */		
	protected $Thumbfilename;	
	/**
	 * Create Polaroid Look
	 *
	 * @var boolean
	 */			
	protected $Polaroid;
	/**
	 * Write text on Polaroid
	 *
	 * @var string
	 */			
	protected $Polaroidtext;	
	/**
	 * Path to TTF Fonttype
	 *
	 * @var string
	 */			
	protected $Polaroidfonttype;		
	/**
	 * Fontsize for polaroid text
	 *
	 * @var int
	 */			
	protected $Polaroidfontsize;	
	/**
	 * Polaroid text color in web format: '#000000'
	 *
	 * @var string
	 */			
	protected $Polaroidtextcolor;
	/**
	 * Polaroid frame color in web format: '#FFFFFF'
	 *
	 * @var string
	 */			
	protected $Polaroidframecolor;		
	/**
	 * Deform the image with a displacement map; array with 7 values
	 * [0]: 0=disable 1=enable
	 * [1]: Path to displacement image (grey #808080 is neutral)
	 * [2]: 0=resize the map to fit the image 1=keep original map size
	 * [3]: X coordinate for map position in px 
	 * [4]: Y coordinate for map position in px 
	 * [5]: X displacement scale in px
	 * [6]: Y displacement scale in px
	 *
	 * @var array
	 */		
	protected $Displacementmap;
	/**
	 * Deform the thumbnail with a displacement map; array with 7 values
	 * [0]: 0=disable 1=enable
	 * [1]: Path to displacement image (grey #808080 is neutral)
	 * [2]: 0=resize the map to fit the image 1=keep original map size
	 * [3]: X coordinate for map position in px 
	 * [4]: Y coordinate for map position in px 
	 * [5]: X displacement scale in px
	 * [6]: Y displacement scale in px
	 *
	 * @var array
	 */		
	protected $Displacementmapthumb;	
	/**
	 * The image filename or array with filenames
	 *
	 * @var string / array
	 */	
	private $image;	
	/**
	 * Original image
	 *
	 * @var image	 
	 */			
	private $im;
	/**
	 * Thumbnail image
	 *
	 * @var image	 
	 */			
	private $thumb;
	/**
	 * Temporary image
	 *
	 * @var image	 
	 */			
	private $newimage;	
	/**
	 * Dimensions of original image; array with 3 values
	 * [0]: Width
	 * [1]: Height
	 * [2]: Filetype
	 *
	 * @var array	 
	 */			
	private $size;
	/**
	 * Offset in px for binder
	 *
	 * @var int	 
	 */			
	private $bind_offset;
	/**
	 * Offset in px for shadow
	 *
	 * @var int	 
	 */			
	private $shadow_offset;
	/**
	 * Offset in px for frame
	 *
	 * @var int 
	 */			
	private $frame_offset;
	/**
	 * Thumb width in px
	 *
	 * @var int	 
	 */				
	private $thumbx;
	/**
	 * Thumb height in px
	 *
	 * @var int	 
	 */				
	private $thumby;


	
}

trait ThumbCore{

	/** 
	 * The following functions are required 'core' functions, you cannot delete them.
	 * Refer to the next section to create your own 'lightweight' class.
	 *
	 */

	/**
	 * Class constructor
	 *
	 */	
	private function __construct() {
        $this->init();
    }

	/**
	 * Class destructor
	 *
	 */	
	public function __destruct() {
		if(is_resource($this->im)) imagedestroy($this->im);
		if(is_resource($this->thumb)) imagedestroy($this->thumb);
		if(is_resource($this->newimage)) imagedestroy($this->newimage);
		if(!empty(static::$__position)) static::$__position = "";
	}

  private function init(){
		$this->Thumbsize              = 160;
		$this->Thumbheight            = 0;
		$this->Thumbwidth             = 0;
		$this->Percentage             = false;		
		$this->Framewidth             = 0;
		$this->Inflate                = false;
		$this->Shadow                 = false;
		$this->Binder                 = false;
		$this->Binderspacing          = 8;		
		$this->Backgroundcolor        = '#FFFFFF';
		$this->Framecolor             = '#FFFFFF';
		$this->Watermarkpng           = '';
		$this->Watermarkposition      = '100% 100%';
		$this->Watermarktransparency  = 70;	
		$this->Quality                = 90;
		$this->Chmodlevel             = '0755';
		$this->Thumblocation          = '';
		$this->Thumbsaveas            = '';
		$this->Thumbprefix            = '';
		$this->Clipcorner             = array(0,15,0,1,1,1,0);
		$this->Ageimage               = array(0,10,80);
		$this->Cropimage              = array(0,0,20,20,20,20);		
		$this->Borderpng              = '';
		$this->Copyrighttext          = '';
		$this->Copyrightposition      = '0% 95%';
		$this->Copyrightfonttype      = '';
		$this->Copyrightfontsize      = 2;
		$this->Copyrighttextcolor     = '';
		$this->Addtext                = array(0,'Text','50% 50%','',2,'#000000');
		$this->Rotate                 = 0;
		$this->Fliphorizontal         = false;
		$this->Flipvertical           = false;
		$this->Square                 = false;
		$this->Applyfilter            = false;		
		$this->Filter                 = array(0,0,0,0,1,0,0,0,0);
		$this->Divisor                = 1;
		$this->Offset                 = 0;
		$this->Blur                   = false;		
		$this->Sharpen                = false;	
		$this->Edge                   = false;	
		$this->Emboss                 = false;	
		$this->Mean                   = false;			
		$this->Croprotate             = false;	
		$this->Perspective            = array(0,0,30);
		$this->Perspectivethumb       = array(0,1,20);
		$this->Shading                = array(0,70,65,0);
		$this->Shadingcolor           = '#000000';		
		$this->Mirror                 = array(0,20,100,40,2);
		$this->Mirrorcolor            = '#FFFFFF';		
		$this->Negative               = false;
		$this->Colorreplace           = array(0,'#000000','#FFFFFF',30);
		$this->Pixelscramble          = array(0,3,1);
		$this->Greyscale              = false;
		$this->Binaryzation			  = false;
		$this->ColorFlip			  = false;	
		$this->Brightness             = array(0,30);
		$this->Contrast               = array(0,30);
		$this->Gamma                  = array(0,1.5);
		$this->Palette                = array(0,6);
		$this->Colorize               = array(0,100,0,0,0);
		$this->Pixelate               = array(0,3);
		$this->Medianfilter           = false;
		$this->Twirlfx                = array(0,20,0);
		$this->Ripplefx               = array(0,5,15,5,5);
		$this->Lakefx                 = array(0,15,80);
		$this->Waterdropfx            = array(0,1.2,400,40);
		$this->Maketransparent        = array(0,0,'#FFFFFF',30);
		$this->Keeptransparency       = false;
		$this->Thumbfilename          = '';
		$this->Polaroid               = false;
		$this->Polaroidtext           = '';
		$this->Polaroidfonttype       = '';
		$this->Polaroidfontsize       = 30;
		$this->Polaroidtextcolor      = '#000000';
		$this->Polaroidframecolor     = '#FFFFFF';		
		$this->Displacementmap        = array(0,'',0,0,0,50,50);
		$this->Displacementmapthumb   = array(0,'',0,0,0,25,25);
	}

	/**
	 * 对图像应用所有修改
	 *
	 */	
	private function thumbmaker() {

		if($this->loadimage()) {
			// Modifications to the original sized image			
			if ($this->Cropimage[0]>0) {$this->cropimage();}
			if ($this->Addtext[0]>0) {$this->addtext();}
			if ($this->Medianfilter) {$this->medianfilter();}
			if ($this->Greyscale) {$this->greyscale();}
			if ($this->Brightness[0]==1) {$this->brightness();}
			if ($this->Binaryzation) {$this->binaryzation();}
			if ($this->ColorFlip) {$this->colorFlip();}
			if ($this->Contrast[0]==1) {$this->contrast();}
			if ($this->Gamma[0]==1) {$this->gamma();}
			if ($this->Palette[0]==1) {$this->palette();}
			if ($this->Colorize[0]==1) {$this->colorize();}			
			if ($this->Colorreplace[0]==1) {$this->colorreplace();}
			if ($this->Pixelscramble[0]==1) {$this->pixelscramble();}
			if ($this->Pixelate[0]==1) {$this->pixelate();}
			if ($this->Ageimage[0]==1) {$this->ageimage();}
			if ($this->Fliphorizontal) {$this->rotateorflip(0,1);}
			if ($this->Flipvertical) {$this->rotateorflip(0,-1);}
			if ($this->Watermarkpng!='') {$this->addpngwatermark();}
			if ($this->Clipcorner[0]==1) {$this->clipcornersstraight();}
			if ($this->Clipcorner[0]==2) {$this->clipcornersround();}
			if (intval($this->Rotate)<>0 && !$this->Croprotate) {
				switch(intval($this->Rotate)) {
					case -90:
					case 270:
						$this->rotateorflip(1,0);
						break;
					case -270:
					case 90:
						$this->rotateorflip(1,0);
						break;
					case -180:
					case 180:
						$this->rotateorflip(1,0);
						$this->rotateorflip(1,0);
						break;
					default:
						$this->freerotate();
				}
			}
			if ($this->Croprotate) {$this->croprotate();}
			if ($this->Sharpen) {$this->sharpen();}			
			if ($this->Blur) {$this->blur();}
			if ($this->Edge) {$this->edge();}			
			if ($this->Emboss) {$this->emboss();}	
			if ($this->Mean) {$this->mean();}	
			if ($this->Applyfilter) {$this->filter();}
			if ($this->Twirlfx[0]==1) {$this->twirlfx();}
			if ($this->Ripplefx[0]==1) {$this->ripplefx();}
			if ($this->Lakefx[0]==1) {$this->lakefx();}
			if ($this->Waterdropfx[0]==1) {$this->waterdropfx();}
			if ($this->Displacementmap[0]==1) {$this->displace();}			
			if ($this->Negative) {$this->negative();}
			if ($this->Shading[0]==1) {$this->shading();}
			if ($this->Polaroid) {$this->polaroid();}			
			if ($this->Perspective[0]==1) {$this->perspective();}
			// Prepare the thumbnail (new canvas) and add modifications to the resized image (thumbnail)
			$this->createemptythumbnail();
			if ($this->Binder) {$this->addbinder();}
			if ($this->Shadow) {$this->addshadow();}
			@imagecopyresampled($this->thumb,$this->im,intval($this->Framewidth*($this->frame_offset-1)),intval($this->Framewidth),0,0,intval($this->thumbx-($this->frame_offset*$this->Framewidth)-$this->shadow_offset),intval($this->thumby-2*$this->Framewidth-$this->shadow_offset),imagesx($this->im),imagesy($this->im));
			if ($this->Borderpng!='') {$this->addpngborder();}
			if ($this->Copyrighttext!='') {$this->addcopyright();}		
			if ($this->Square) {$this->square();}
			if ($this->Mirror[0]==1) {$this->mirror();}
			if ($this->Displacementmapthumb[0]==1) {$this->displacethumb();}			
			if ($this->Perspectivethumb[0]==1) {$this->perspectivethumb();}
			if ($this->Maketransparent[0]==1) {$this->maketransparent();}
		}
	}

	/**
	 * 载入图像到内存
	 *
	 */	
	private function loadimage() {

		if (is_resource($this->im)) {
			return true;
		} else if (file_exists($this->image)) {
			$this->size=GetImageSize($this->image);
			switch($this->size[2]) {
				case 1:
					if (imagetypes() & IMG_GIF) {$this->im=imagecreatefromgif($this->image);return true;} else {$this->invalidImage('无效GIF图像');return false;}
					break;
				case 2:
					if (imagetypes() & IMG_JPG) {$this->im=imagecreatefromjpeg($this->image);$this->Keeptransparency=false;return true;} else {$this->invalidImage('无效JPG图像');return false;}
					break;
				case 3:
					if (imagetypes() & IMG_PNG) {$this->im=imagecreatefrompng($this->image);return true;} else {$this->invalidImage('无效PNG图像');return false;}
					break;
				default:
					$this->invalidImage('不支持的文件类型');
					return false;
			}
		} else {
			$this->invalidImage('原始图像文件无效');
			return false;
		}
				
	}

	/**
	 * 生成占位错误图像
	 *
	 * @param string $message
     * @return void
	 */	
	private function invalidImage(string $message):void{
		$this->thumb=imagecreate(80,75);
		$black=imagecolorallocate($this->thumb,0,0,0);
		$yellow=imagecolorallocate($this->thumb,255,255,0);
		imagefilledrectangle($this->thumb,0,0,80,75,imagecolorallocate($this->thumb,255,0,0));
		imagerectangle($this->thumb,0,0,79,74,$black);imageline($this->thumb,0,20,80,20,$black);
		imagefilledrectangle($this->thumb,1,1,78,19,$yellow);imagefilledrectangle($this->thumb,27,35,52,60,$yellow);
		imagerectangle($this->thumb,26,34,53,61,$black);
		imageline($this->thumb,27,35,52,60,$black);imageline($this->thumb,52,35,27,60,$black);
		imagestring($this->thumb,1,5,5,$message,$black);
	}		

	/**
	 * 生成空缩略图
	 *
	 */	
	private function createemptythumbnail():void{
	
		$thumbsize=$this->Thumbsize;
		$thumbwidth=$this->Thumbwidth;
		$thumbheight=$this->Thumbheight;
		if ($thumbsize==0) {$thumbsize=9999;$thumbwidth=0;$thumbheight=0;}
		if ($this->Percentage) {
			if ($thumbwidth>0) {$thumbwidth=intval(($thumbwidth/100)*$this->size[0]);}
			if ($thumbheight>0) {$thumbheight=intval(($thumbheight/100)*$this->size[1]);}
			if ($this->size[0]>$this->size[1])
				$thumbsize=intval(($thumbsize/100)*$this->size[0]);
			else
				$thumbsize=intval(($thumbsize/100)*$this->size[1]);
		}
		if (!$this->Inflate) {
			if ($thumbsize>$this->size[0] && $thumbsize>$this->size[1]) {$thumbsize=max($this->size[0],$this->size[1]);}
			if ($thumbheight>$this->size[1]) {$thumbheight=$this->size[1];}
			if ($thumbwidth>$this->size[0]) {$thumbwidth=$this->size[0];}
		}
		if ($this->Binder) {$this->frame_offset=3;$this->bind_offset=4;} else {$this->frame_offset=2;$this->bind_offset=0;}
		if ($this->Shadow) {$this->shadow_offset=3;} else {$this->shadow_offset=0;}
		if ($thumbheight>0 && $thumbwidth>0) {
			$this->thumb=imagecreatetruecolor(intval($this->Framewidth*$this->frame_offset+$thumbwidth+$this->shadow_offset),intval($this->Framewidth*2+$thumbheight+$this->shadow_offset));		
		} else if ($thumbheight>0) {
			$this->thumb=imagecreatetruecolor(intval($this->Framewidth*$this->frame_offset+ceil($this->size[0]/($this->size[1]/$thumbheight))+$this->shadow_offset),intval($this->Framewidth*2+$thumbheight+$this->shadow_offset));
		} else if ($thumbwidth>0) {
			$this->thumb=imagecreatetruecolor(intval($this->Framewidth*$this->frame_offset+$thumbwidth+$this->shadow_offset),intval($this->Framewidth*2+ceil($this->size[1]/($this->size[0]/$thumbwidth))+$this->shadow_offset));
		} else {
			$x1=intval($this->Framewidth*$this->frame_offset+$thumbsize+$this->shadow_offset);
			$x2=intval($this->Framewidth*$this->frame_offset+ceil($this->size[0]/($this->size[1]/$thumbsize))+$this->shadow_offset);
			$y1=intval($this->Framewidth*2+ceil($this->size[1]/($this->size[0]/$thumbsize))+$this->shadow_offset);
			$y2=intval($this->Framewidth*2+$thumbsize+$this->shadow_offset);
			if ($this->size[0]>$this->size[1]) {
				$this->thumb=imagecreatetruecolor($x1,$y1);
			} else {
				$this->thumb=imagecreatetruecolor($x2,$y2);
			}
		}
		$this->thumbx=imagesx($this->thumb);$this->thumby=imagesy($this->thumb);
		if ($this->Keeptransparency) {
			$alpha=imagecolortransparent($this->im);
			if ($alpha>=0) {
				$color=imagecolorsforindex($this->im,$alpha);
				$color_index=imagecolorallocate($this->thumb,$color['red'],$color['green'],$color['blue']);
				imagefill($this->thumb,0,0,$color_index);
				imagecolortransparent($this->thumb,$color_index);
			} else {
				imagealphablending($this->thumb,false);
				$color_alpha=imagecolorallocatealpha($this->im,0,0,0,127);
				imagefill($this->thumb,0,0,$color_alpha);
				imagesavealpha($this->thumb,true);
				imagealphablending($this->thumb,true);
			}
		} else {
			imagefilledrectangle($this->thumb,0,0,$this->thumbx,$this->thumby,imagecolorallocate($this->thumb,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));			
			if ($this->Polaroid)
				imagefilledrectangle($this->thumb,$this->bind_offset,0,$this->thumbx-$this->shadow_offset,$this->thumby-$this->shadow_offset,imagecolorallocate($this->thumb,hexdec(substr($this->Polaroidframecolor,1,2)),hexdec(substr($this->Polaroidframecolor,3,2)),hexdec(substr($this->Polaroidframecolor,5,2))));
			else			
				imagefilledrectangle($this->thumb,$this->bind_offset,0,$this->thumbx-$this->shadow_offset,$this->thumby-$this->shadow_offset,imagecolorallocate($this->thumb,hexdec(substr($this->Framecolor,1,2)),hexdec(substr($this->Framecolor,3,2)),hexdec(substr($this->Framecolor,5,2))));
		}
	}

	/**
	 * 保存缩略图到文件
	 *
	 */	
	private function savethumb():string{
	
		if ($this->Thumbsaveas!='') {
			switch (strtolower($this->Thumbsaveas)) {
				case "gif":
					$this->image=substr($this->image,0,strrpos($this->image,'.')).".".$this->Thumbsaveas;
					$this->size[2]=1;
					break;
				default:
				case "jpg":
					$this->image=substr($this->image,0,strrpos($this->image,'.')).".".$this->Thumbsaveas;
					$this->size[2]=2;
					break;
				case "jpeg":
					$this->image=substr($this->image,0,strrpos($this->image,'.')).".".$this->Thumbsaveas;
					$this->size[2]=2;
					break;			
				case "png":
					$this->image=substr($this->image,0,strrpos($this->image,'.')).".".$this->Thumbsaveas;
					$this->size[2]=3;
					break;
			}
		}
		if ($this->Thumbfilename!='') {
			$this->image=$this->Thumbfilename;
		}
		// (!$this->Thumblocation && !$this->Thumbprefix) && $this->savePath();

		$Thumbfilename = $this->Thumblocation.$this->Thumbprefix.basename($this->image);
		switch($this->size[2]) {
			case 1:
				imagegif($this->thumb,$Thumbfilename);
				break;
			case 3:
				imagepng($this->thumb,$Thumbfilename);
				break;
			default:
			case 2:
				imagejpeg($this->thumb,$Thumbfilename,$this->Quality);
				break;
		}		
		if ($this->Chmodlevel !='') {
			chmod($Thumbfilename,octdec($this->Chmodlevel));
		}
		imagedestroy($this->im);
		imagedestroy($this->thumb);
		return $Thumbfilename;
	}

	/**
	 * Display thumbnail on screen
	 *
	 */	
	private function displaythumb() {
		
		switch($this->size[2]) {
			case 1:
				header("Content-type: image/gif");imagegif($this->thumb);
				break;
			case 2:
				header("Content-type: image/jpeg");imagejpeg($this->thumb,NULL,$this->Quality);
				break;
			case 3:
				header("Content-type: image/png");imagepng($this->thumb);
				break;
		}
		$this->im && imagedestroy($this->im);
		$this->thumb && imagedestroy($this->thumb);
		exit;
	}
	
	/** 
	 * The following functions are optional functions, you can delete them to create your own lightweight class.
	 * When you delete a function remove also the reference in thumbmaker() and optionally in __construct and the variable declaration.
	 *
	 */

	/**
	 * Add watermark to image
	 *
	 */	
	private function addpngwatermark() {
	
		if (file_exists($this->Watermarkpng)) {
			$this->newimage=imagecreatefrompng($this->Watermarkpng);
			list($cposx, $cposy) = $this->formatPosition($this->Watermarkposition, function($x, $y, $percentage){
				if($percentage){
					$cposx = min(
						max(imagesx($this->im)*($x/100)-0.5*imagesx($this->newimage),0),
						imagesx($this->im)-imagesx($this->newimage)
					);
					$cposy = min(
						max(imagesy($this->im)*($y/100)-0.5*imagesy($this->newimage),0),
						imagesy($this->im)-imagesy($this->newimage)
					);
				}else{
					$cposx = min($x, imagesx($this->im)-imagesx($this->newimage));
					$cposy = min($y, imagesy($this->im)-imagesy($this->newimage));
				}
				return [intval($cposx), intval($cposy)];
			});
			// $wpos=explode(' ',str_replace('%','',$this->Watermarkposition));
			// $cposx = min(
			// 	max(imagesx($this->im)*($wpos[0]/100)-0.5*imagesx($this->newimage),0),
			// 	imagesx($this->im)-imagesx($this->newimage)
			// );
			// $cposy = min(
			// 	max(imagesy($this->im)*($wpos[1]/100)-0.5*imagesy($this->newimage),0),
			// 	imagesy($this->im)-imagesy($this->newimage)
			// );
			imagecopymerge(
				$this->im,
				$this->newimage,
				$cposx, $cposy,0,0,
				imagesx($this->newimage),
				imagesy($this->newimage),intval($this->Watermarktransparency)
			);
			imagedestroy($this->newimage);
		}
		
	}

	/**
	 * Drop shadow on thumbnail
	 *
	 */	
	private function addshadow() {
	
		$gray=imagecolorallocate($this->thumb,192,192,192);
		$middlegray=imagecolorallocate($this->thumb,158,158,158);
		$darkgray=imagecolorallocate($this->thumb,128,128,128);
		imagerectangle($this->thumb,$this->bind_offset,0,$this->thumbx-4,$this->thumby-4,$gray);
		imageline($this->thumb,$this->bind_offset,$this->thumby-3,$this->thumbx,$this->thumby-3,$darkgray);
		imageline($this->thumb,$this->thumbx-3,0,$this->thumbx-3,$this->thumby,$darkgray);
		imageline($this->thumb,$this->bind_offset+2,$this->thumby-2,$this->thumbx,$this->thumby-2,$middlegray);
		imageline($this->thumb,$this->thumbx-2,2,$this->thumbx-2,$this->thumby,$middlegray);
		imageline($this->thumb,$this->bind_offset+2,$this->thumby-1,$this->thumbx,$this->thumby-1,$gray);
		imageline($this->thumb,$this->thumbx-1,2,$this->thumbx-1,$this->thumby,$gray);
		
	}

	/**
	 * Clip corners original image
	 *
	 */	
	private function clipcornersstraight() {
	
		$clipsize=intval($this->Clipcorner[1]);
		if ($this->size[0]>$this->size[1])
			$clipsize=intval($this->size[0]*(intval($clipsize)/100));
		else
			$clipsize=intval($this->size[1]*(intval($clipsize)/100));
		if (intval($clipsize)>0) {
			$bgcolor=imagecolorallocate($this->im,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2)));
			if ($this->Clipcorner[2]) {$random1=rand(0,1);$random2=rand(0,1);$random3=rand(0,1);$random4=rand(0,1);} else {$random1=1;$random2=1;$random3=1;$random4=1;}
			for ($i=0;$i<$clipsize;$i++) {			
				if ($this->Clipcorner[3] && $random1) {imageline($this->im,0,$i,$clipsize-$i,$i,$bgcolor);}
				if ($this->Clipcorner[4] && $random2) {imageline($this->im,0,$this->size[1]-$i-1,$clipsize-$i,$this->size[1]-$i-1,$bgcolor);}				
				if ($this->Clipcorner[5] && $random3) {imageline($this->im,$this->size[0]-$clipsize+$i,$i,$this->size[0]+$clipsize-$i,$i,$bgcolor);}				
				if ($this->Clipcorner[6] && $random4) {imageline($this->im,$this->size[0]-$clipsize+$i,$this->size[1]-$i-1,$this->size[0]+$clipsize-$i,$this->size[1]-$i-1,$bgcolor);}
			}
		}
		
	}

	/**
	 * Clip round corners original image
	 *
	 */	
	private function clipcornersround() {
	
		$clipsize=intval($this->size[0]*($this->Clipcorner[1]/100));
		$clip_degrees=90/max($clipsize,1);
		$points_tl=array(0,0);
		$points_br=array($this->size[0],$this->size[1]);
		$points_tr=array($this->size[0],0);
		$points_bl=array(0,$this->size[1]);
		$bgcolor=imagecolorallocate($this->im,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2)));
		for ($i=0;$i<$clipsize;$i++) {
			$x=$clipsize*cos(deg2rad($i*$clip_degrees));
			$y=$clipsize*sin(deg2rad($i*$clip_degrees));
			array_push($points_tl,$clipsize-$x);
			array_push($points_tl,$clipsize-$y);
			array_push($points_tr,$this->size[0]-$clipsize+$x);
			array_push($points_tr,$clipsize-$y);
			array_push($points_br,$this->size[0]-$clipsize+$x);
			array_push($points_br,$this->size[1]-$clipsize+$y);
			array_push($points_bl,$clipsize-$x);
			array_push($points_bl,$this->size[1]-$clipsize+$y);
		}
		array_push($points_tl,$clipsize,0);
		array_push($points_br,$this->size[0]-$clipsize,$this->size[1]);
		array_push($points_tr,$this->size[0]-$clipsize,0);
		array_push($points_bl,$clipsize,$this->size[1]);
		if ($this->Clipcorner[2]) {$random1=rand(0,1);$random2=rand(0,1);$random3=rand(0,1);$random4=rand(0,1);} else {$random1=1;$random2=1;$random3=1;$random4=1;}
		if ($this->Clipcorner[3] && $random1) {imagefilledpolygon($this->im,$points_tl,count($points_tl)/2,$bgcolor);}
		if ($this->Clipcorner[4] && $random2) {imagefilledpolygon($this->im,$points_bl,count($points_bl)/2,$bgcolor);}		
		if ($this->Clipcorner[5] && $random3) {imagefilledpolygon($this->im,$points_tr,count($points_tr)/2,$bgcolor);}		
		if ($this->Clipcorner[6] && $random4) {imagefilledpolygon($this->im,$points_br,count($points_br)/2,$bgcolor);}

	}

	/**
	 * Convert original image to greyscale and/or apply noise and sephia effect
	 *
	 */	
	private function ageimage() {
	
		imagetruecolortopalette($this->im,!!1,256);
		for ($c=0;$c<256;$c++) {    
			$col=imagecolorsforindex($this->im,$c);
			$new_col=intval($col['red']*0.2125+$col['green']*0.7154+$col['blue']*0.0721);
			$noise=intval(rand(-$this->Ageimage[1],$this->Ageimage[1]));
			if ($this->Ageimage[2]>0) {
				$r=intval($new_col+$this->Ageimage[2]+$noise);
				$g=intval($new_col+$this->Ageimage[2]/1.86+$noise);
				$b=intval($new_col+$this->Ageimage[2]/-3.48+$noise);
			} else {
				$r=intval($new_col+$noise);
				$g=intval($new_col+$noise);
				$b=intval($new_col+$noise);
			}
			imagecolorset($this->im,$c,max(0,min(255,$r)),max(0,min(255,$g)),max(0,min(255,$b)));
		}
		
	}

	/**
	 * Add border to thumbnail
	 *
	 */	
	private function addpngborder() {
	
		if (file_exists($this->Borderpng)) {
			$borderim=imagecreatefrompng($this->Borderpng);
			imagecopyresampled($this->thumb,$borderim,$this->bind_offset,0,0,0,intval($this->thumbx-$this->shadow_offset-$this->bind_offset),intval($this->thumby-$this->shadow_offset),imagesx($borderim),imagesy($borderim));
			imagedestroy($borderim);
		}
	}

	/**
	 * Add binder effect to thumbnail
	 *
	 */	
	private function addbinder() {
	
		if (intval($this->Binderspacing)<4) {$this->Binderspacing=4;}
		$spacing=intval($this->thumby/$this->Binderspacing)-2;
		$offset=intval(($this->thumby-($spacing*$this->Binderspacing))/2);
		$gray=imagecolorallocate($this->thumb,192,192,192);
		$middlegray=imagecolorallocate($this->thumb,158,158,158);
		$darkgray=imagecolorallocate($this->thumb,128,128,128);		
		$black=imagecolorallocate($this->thumb,0,0,0);	
		$white=imagecolorallocate($this->thumb,255,255,255);		
		for ($i=$offset;$i<=$offset+$spacing*$this->Binderspacing;$i+=$this->Binderspacing) {
			imagefilledrectangle($this->thumb,8,$i-2,10,$i+2,$black);
			imageline($this->thumb,11,$i-1,11,$i+1,$darkgray);
			imageline($this->thumb,8,$i-2,10,$i-2,$darkgray);
			imageline($this->thumb,8,$i+2,10,$i+2,$darkgray);
			imagefilledrectangle($this->thumb,0,$i-1,8,$i+1,$gray);
			imageline($this->thumb,0,$i,8,$i,$white);
			imageline($this->thumb,0,$i-1,0,$i+1,$gray);
			imagesetpixel($this->thumb,0,$i,$darkgray);
		}
	}

	/**
	 * Add Copyright text to thumbnail
	 *
	 */	
	private function addcopyright() {

		if ($this->Copyrightfonttype=='') {
			$widthx=imagefontwidth($this->Copyrightfontsize)*strlen($this->Copyrighttext);
			$heighty=imagefontheight($this->Copyrightfontsize);
			$fontwidth=imagefontwidth($this->Copyrightfontsize);
		} else {		
			$dimensions=imagettfbbox($this->Copyrightfontsize,0,$this->Copyrightfonttype,$this->Copyrighttext);
			$widthx=$dimensions[2];
			$heighty=$dimensions[5];
			$dimensions=imagettfbbox($this->Copyrightfontsize,0,$this->Copyrightfonttype,'W');
			$fontwidth=$dimensions[2];
		}
		// $cpos=explode(' ',str_replace('%','',$this->Copyrightposition));
		// if (count($cpos)>1) {
		// 	$cposx=intval(min(max($this->thumbx*($cpos[0]/100)-0.5*$widthx,$fontwidth),$this->thumbx-$widthx-0.5*$fontwidth));
		// 	$cposy=intval(min(max($this->thumby*($cpos[1]/100)-0.5*$heighty,$heighty),$this->thumby-$heighty*1.5));
		// } else {
		// 	$cposx=intval($fontwidth);
		// 	$cposy=intval($this->thumby-10);
		// }
		list($cposx, $cposy) = $this->formatPosition($this->Copyrightposition, function($x, $y, $percentage)use($fontwidth, $widthx, $heighty){
			if($percentage){
				$cposx=min(max($this->thumbx*($x/100)-0.5*$widthx,$fontwidth),$this->thumbx-$widthx-0.5*$fontwidth);
				$cposy=min(max($this->thumby*($y/100)-0.5*$heighty,$heighty),$this->thumby-$heighty*1.5);
			}else{
				// $cposx=$fontwidth;
				// $cposy=$this->thumby-10;
				$cposx = min($x, $this->thumbx-$widthx-0.5*$fontwidth);
				$cposy = min($y, $this->thumby-$heighty*1.5);
			}
			return [intval($cposx), intval($cposy)];
		});

		if ($this->Copyrighttextcolor=='') {
			$colors=array();
			for ($i=$cposx;$i<($cposx+$widthx);$i++) {
				$indexis=ImageColorAt($this->thumb,$i,$cposy+0.5*$heighty);
				$rgbarray=ImageColorsForIndex($this->thumb,$indexis);
				array_push($colors,$rgbarray['red'],$rgbarray['green'],$rgbarray['blue']);
			}
			if (array_sum($colors)/count($colors)>180) {
				if ($this->Copyrightfonttype=='')
					imagestring($this->thumb,$this->Copyrightfontsize,$cposx,$cposy,$this->Copyrighttext,imagecolorallocate($this->thumb,0,0,0));
				else
					imagettftext($this->thumb,$this->Copyrightfontsize,0,$cposx,$cposy,imagecolorallocate($this->thumb,0,0,0),$this->Copyrightfonttype,$this->Copyrighttext);
			} else {
				if ($this->Copyrightfonttype=='')
					imagestring($this->thumb,$this->Copyrightfontsize,$cposx,$cposy,$this->Copyrighttext,imagecolorallocate($this->thumb,255,255,255));
				else
					imagettftext($this->thumb,$this->Copyrightfontsize,0,$cposx,$cposy,imagecolorallocate($this->thumb,255,255,255),$this->Copyrightfonttype,$this->Copyrighttext);				
			}
		} else {
			if ($this->Copyrightfonttype=='')
				imagestring(
					$this->thumb,
					$this->Copyrightfontsize,
					$cposx,
					$cposy,
					$this->Copyrighttext,
					imagecolorallocate(
						$this->thumb,
						hexdec(substr($this->Copyrighttextcolor,1,2)),
						hexdec(substr($this->Copyrighttextcolor,3,2)),
						hexdec(substr($this->Copyrighttextcolor,5,2))
					)
				);
			else
				imagettftext($this->thumb,$this->Copyrightfontsize,0,$cposx,$cposy,imagecolorallocate($this->thumb,hexdec(substr($this->Copyrighttextcolor,1,2)),hexdec(substr($this->Copyrighttextcolor,3,2)),hexdec(substr($this->Copyrighttextcolor,5,2))),$this->Copyrightfonttype,$this->Copyrighttext);				
		}
		
	}

	/**
	 * Add text to image
	 *
	 */	
	private function addtext() {

		if ($this->Addtext[3]=='') {
			$widthx=imagefontwidth($this->Addtext[4])*strlen($this->Addtext[1]);
			$heighty=imagefontheight($this->Addtext[4]);
			$fontwidth=imagefontwidth($this->Addtext[4]);
		} else {		
			$dimensions=imagettfbbox($this->Addtext[4],0,$this->Addtext[3],$this->Addtext[1]);
			$widthx=$dimensions[2];$heighty=$dimensions[5];
			$dimensions=imagettfbbox($this->Addtext[4],0,$this->Addtext[3],'W');
			$fontwidth=$dimensions[2];
		}
		// $cpos=explode(' ',str_replace('%','',$this->Addtext[2]));
		// if (count($cpos)>1) {
		// 	$cposx=intval(min(max($this->size[0]*($cpos[0]/100)-0.5*$widthx,$fontwidth),$this->size[0]-$widthx-0.5*$fontwidth));
		// 	$cposy=intval(min(max($this->size[1]*($cpos[1]/100)-0.5*$heighty,$heighty),$this->size[1]-$heighty*1.5));
		// } else {
		// 	$cposx=intval($fontwidth);
		// 	$cposy=intval($this->size[1]-10);
		// }
		list($cposx, $cposy) = $this->formatPosition($this->Addtext[2], function($x, $y, $percentage)use($fontwidth, $widthx, $heighty){
			if($percentage){
				$cposx=min(max($this->size[0]*($x/100)-0.5*$widthx,$fontwidth),$this->size[0]-$widthx-0.5*$fontwidth);
				$cposy=min(max($this->size[1]*($y/100)-0.5*$heighty,$heighty),$this->size[1]-$heighty*1.5);
			}else{
				// $cposx=intval($fontwidth);
				// $cposy=intval($this->size[1]-10);
				$cposx = min($x, $this->size[0]-$widthx-0.5*$fontwidth);
				$cposy = min($y, $this->size[1]-$heighty*1.5);
			}
			return [intval($cposx), intval($cposy)];
		});

		if ($this->Addtext[3]=='')
			imagestring($this->im,$this->Addtext[4],$cposx,$cposy,$this->Addtext[1],imagecolorallocate($this->im,hexdec(substr($this->Addtext[5],1,2)),hexdec(substr($this->Addtext[5],3,2)),hexdec(substr($this->Addtext[5],5,2))));
		else
			imagettftext($this->im,$this->Addtext[4],0,$cposx,$cposy,imagecolorallocate($this->im,hexdec(substr($this->Addtext[5],1,2)),hexdec(substr($this->Addtext[5],3,2)),hexdec(substr($this->Addtext[5],5,2))),$this->Addtext[3],$this->Addtext[1]);
		
	}

	/**
	 * Rotate the image at any angle
	 * Image is not scaled down
	 *
	 */	
	private function freerotate() {
	
		$angle=$this->Rotate;
		if ($angle<>0) {
			$centerx=intval($this->size[0]/2);
			$centery=intval($this->size[1]/2);
			$maxsizex=intval(ceil(abs(cos(deg2rad($angle))*$this->size[0])+abs(sin(deg2rad($angle))*$this->size[1])));
			$maxsizey=intval(ceil(abs(sin(deg2rad($angle))*$this->size[0])+abs(cos(deg2rad($angle))*$this->size[1])));
			if ($maxsizex & 1) {$maxsizex+=3;} else	{$maxsizex+=2;}
			if ($maxsizey & 1) {$maxsizey+=3;} else {$maxsizey+=2;}
			$this->newimage=imagecreatetruecolor($maxsizex,$maxsizey);
			imagefilledrectangle($this->newimage,0,0,$maxsizex,$maxsizey,imagecolorallocate($this->newimage,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));			
			$newcenterx=imagesx($this->newimage)/2;
			$newcentery=imagesy($this->newimage)/2;
			$angle+=180;
			for ($px=0;$px<imagesx($this->newimage);$px++) {
				for ($py=0;$py<imagesy($this->newimage);$py++) {
					$vectorx=intval(($newcenterx-$px)*cos(deg2rad($angle))+($newcentery-$py)*sin(deg2rad($angle)));
					$vectory=intval(($newcentery-$py)*cos(deg2rad($angle))-($newcenterx-$px)*sin(deg2rad($angle)));
					if (($centerx+$vectorx)>-1 && ($centerx+$vectorx)<($centerx*2) && ($centery+$vectory)>-1 && ($centery+$vectory)<($centery*2))
					    imagecopy($this->newimage,$this->im,$px,$py,$centerx+$vectorx,$centery+$vectory,1,1);
				}
			}
			imagedestroy($this->im);
			$this->im=imagecreatetruecolor(imagesx($this->newimage),imagesy($this->newimage));
			imagecopy($this->im,$this->newimage,0,0,0,0,imagesx($this->newimage),imagesy($this->newimage));
			imagedestroy($this->newimage);
			$this->size[0]=imagesx($this->im);
			$this->size[1]=imagesy($this->im);
		}
		
	}	

	/**
	 * Rotate the image at any angle
	 * Image is scaled down
	 *
	 */	
	private function croprotate() {
	
		$this->im=imagerotate($this->im,-$this->Rotate,imagecolorallocate($this->im,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));
		
	}
	
	/**
	 * Rotate the image +90, -90 or 180 degrees
	 * Flip the image over horizontal or vertical axis
	 *
	 * @param $rotate
	 * @param $flip
	 */		
	private function rotateorflip($rotate,$flip) {

		if ($rotate) {
			$this->newimage=imagecreatetruecolor($this->size[1],$this->size[0]);
		} else {
			$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		}
		if (intval($this->Rotate)>0 || $flip>0) {
			for ($px=0;$px<$this->size[0];$px++) {
				if ($rotate) {
					for ($py=0;$py<$this->size[1];$py++) {imagecopy($this->newimage,$this->im,$this->size[1]-$py-1,$px,$px,$py,1,1);}
				} else {
					for ($py=0;$py<$this->size[1];$py++) {imagecopy($this->newimage,$this->im,$this->size[0]-$px-1,$py,$px,$py,1,1);}
				}
			}
		} else {
			for ($px=0;$px<$this->size[0];$px++) {
				if ($rotate) {				
					for ($py=0;$py<$this->size[1];$py++) {imagecopy($this->newimage,$this->im,$py,$this->size[0]-$px-1,$px,$py,1,1);}
				} else {
					for ($py=0;$py<$this->size[1];$py++) {imagecopy($this->newimage,$this->im,$px,$this->size[1]-$py-1,$px,$py,1,1);}
				}					
			}
		}
		imagedestroy($this->im);
		$this->im=imagecreatetruecolor(imagesx($this->newimage),imagesy($this->newimage));
		imagecopy($this->im,$this->newimage,0,0,0,0,imagesx($this->newimage),imagesy($this->newimage));			
		imagedestroy($this->newimage);
		$this->size[0]=imagesx($this->im);
		$this->size[1]=imagesy($this->im);

	}
	
    private function absoluteCrop($w, $h, $x = 0, $y = 0, $width = null, $height = null):self{
        //设置保存尺寸
        empty($this->Cropimage[4]) && $this->Cropimage[4] = $this->size[0];
        empty($this->Cropimage[5]) && $this->Cropimage[5] = $this->size[1];

		//创建新图像
		$img = imagecreatetruecolor($this->Cropimage[4], $this->Cropimage[5]);
		// 调整默认颜色
		// $color = imagecolorallocate($img, 255, 255, 255);
		//vipkwd debug 保持PNG的透明效果
		$color = imagecolorallocatealpha($img, 0, 0, 0,127);
		imagefill($img, 0, 0, $color);
		//裁剪
		imagecopyresampled(
			$img,
			$this->im,
			0, 0,
			$this->Cropimage[2],
			$this->Cropimage[3],
			$this->Cropimage[4],
			$this->Cropimage[5],
			$this->Cropimage[0],
			$this->Cropimage[1]
		);
		imagedestroy($this->im); //销毁原图
		//设置新图像
		$this->im = $img;
		$this->size[0]=(int) $this->Cropimage[4];
		$this->size[1]=(int) $this->Cropimage[5];
        return $this;
    }

	/**
	 * Crop image in percentage, pixels or in a square
	 * Crop from sides or from center
	 * Negative value for bottom crop will enlarge the canvas
	 *
	 */		
	private function cropimage() {	
		if(isset($this->Cropimage[6]) && $this->Cropimage[6] === 'absolute'){
			return $this->absoluteCrop();
		}
		$this->Cropimage[1] = $this->Cropimage[1] ? 1 : 0;
		if ($this->Cropimage[1]==0) {
			$crop2=intval($this->size[0]*($this->Cropimage[2]/100));
			$crop3=intval($this->size[0]*($this->Cropimage[3]/100));
			$crop4=intval($this->size[1]*($this->Cropimage[4]/100));
			$crop5=intval($this->size[1]*($this->Cropimage[5]/100));
		} 
		if ($this->Cropimage[1]==1) {
			$crop2=intval($this->Cropimage[2]);
			$crop3=intval($this->Cropimage[3]);
			$crop4=intval($this->Cropimage[4]);
			$crop5=intval($this->Cropimage[5]);	
		}
		if ($this->Cropimage[0]==2) {
			$crop2=intval(($this->size[0]/2)-$crop2);
			$crop3=intval(($this->size[0]/2)-$crop3);
			$crop4=intval(($this->size[1]/2)-$crop4);
			$crop5=intval(($this->size[1]/2)-$crop5);
		}
		if ($this->Cropimage[0]==3) {
			if ($this->size[0] > $this->size[1]) {
				$crop2=$crop3=intval(($this->size[0]-$this->size[1])/2);
				$crop4=$crop5=0;
			} else {
				$crop4=$crop5=intval(($this->size[1]-$this->size[0])/2);
				$crop2=$crop3=0;			
			}
		}

		$this->newimage=imagecreatetruecolor(intval($this->size[0]-$crop2-$crop3),intval($this->size[1]-$crop4-$crop5));
		if ($crop5<0) {$crop5=0;imagefilledrectangle($this->newimage,0,0,imagesx($this->newimage),imagesy($this->newimage),imagecolorallocate($this->newimage,hexdec(substr($this->Polaroidframecolor,1,2)),hexdec(substr($this->Polaroidframecolor,3,2)),hexdec(substr($this->Polaroidframecolor,5,2))));}
		imagecopy($this->newimage,$this->im,0,0,$crop2,$crop4,$this->size[0]-$crop2-$crop3,$this->size[1]-$crop4-$crop5);
		imagedestroy($this->im);
		$this->im=imagecreatetruecolor(imagesx($this->newimage),imagesy($this->newimage));
		imagecopy($this->im,$this->newimage,0,0,0,0,imagesx($this->newimage),imagesy($this->newimage));
		imagedestroy($this->newimage);
		$this->size[0]=imagesx($this->im);
		$this->size[1]=imagesy($this->im);
	}

	/**
	 * Enlarge the canvas to be same width and height
	 *
	 */	
	private function square() {
	
		$squaresize=max($this->thumbx,$this->thumby);
		$this->newimage=imagecreatetruecolor($squaresize,$squaresize);
		imagefilledrectangle($this->newimage,0,0,$squaresize,$squaresize,imagecolorallocate($this->newimage,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));
		$centerx=intval(($squaresize-$this->thumbx)/2);
		$centery=intval(($squaresize-$this->thumby)/2);
		imagecopy($this->newimage,$this->thumb,$centerx,$centery,0,0,$this->thumbx,$this->thumby);
		imagedestroy($this->thumb);
		$this->thumb=imagecreatetruecolor($squaresize,$squaresize);
		imagecopy($this->thumb,$this->newimage,0,0,0,0,$squaresize,$squaresize);
		imagedestroy($this->newimage);
		
	}

	/**
	 * Apply a 3x3 filter matrix to the image
	 *
	 */	
	private function filter() {
		
		if (function_exists('imageconvolution')) {
			imageconvolution($this->im,array(array($this->Filter[0],$this->Filter[1],$this->Filter[2]), array($this->Filter[3],$this->Filter[4],$this->Filter[5]),array($this->Filter[6],$this->Filter[7],$this->Filter[8])),$this->Divisor,$this->Offset);	
		} else {
			$newpixel=array();
			$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$newpixel[0]=0;$newpixel[1]=0;$newpixel[2]=0;
					$a11=$this->rgbpixel($x-1,$y-1);$a12=$this->rgbpixel($x,$y-1);$a13=$this->rgbpixel($x+1,$y-1);
					$a21=$this->rgbpixel($x-1,$y);$a22=$this->rgbpixel($x,$y);$a23=$this->rgbpixel($x+1,$y);
					$a31=$this->rgbpixel($x-1,$y+1);$a32=$this->rgbpixel($x,$y+1);$a33=$this->rgbpixel($x+1,$y+1);
					$newpixel[0]+=$a11['red']*$this->Filter[0]+$a12['red']*$this->Filter[1]+$a13['red']*$this->Filter[2];
					$newpixel[1]+=$a11['green']*$this->Filter[0]+$a12['green']*$this->Filter[1]+$a13['green']*$this->Filter[2];
					$newpixel[2]+=$a11['blue']*$this->Filter[0]+$a12['blue']*$this->Filter[1]+$a13['blue']*$this->Filter[2];
					$newpixel[0]+=$a21['red']*$this->Filter[3]+$a22['red']*$this->Filter[4]+$a23['red']*$this->Filter[5];
					$newpixel[1]+=$a21['green']*$this->Filter[3]+$a22['green']*$this->Filter[4]+$a23['green']*$this->Filter[5];
					$newpixel[2]+=$a21['blue']*$this->Filter[3]+$a22['blue']*$this->Filter[4]+$a23['blue']*$this->Filter[5];
					$newpixel[0]+=$a31['red']*$this->Filter[6]+$a32['red']*$this->Filter[7]+$a33['red']*$this->Filter[8];
					$newpixel[1]+=$a31['green']*$this->Filter[6]+$a32['green']*$this->Filter[7]+$a33['green']*$this->Filter[8];
					$newpixel[2]+=$a31['blue']*$this->Filter[6]+$a32['blue']*$this->Filter[7]+$a33['blue']*$this->Filter[8];
					$newpixel[0]=max(0,min(255,intval($newpixel[0]/$this->Divisor)+$this->Offset));
					$newpixel[1]=max(0,min(255,intval($newpixel[1]/$this->Divisor)+$this->Offset));
					$newpixel[2]=max(0,min(255,intval($newpixel[2]/$this->Divisor)+$this->Offset));
					imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpixel[0],$newpixel[1],$newpixel[2],$a11['alpha']));
				}
			}
			imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
			imagedestroy($this->newimage);
		}
		
	}
	
	/**
	 * Apply a median filter matrix to the image to remove noise
	 *
	 */	
	private function medianfilter() {
		
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$newred=array();$newgreen=array();$newblue=array();
				$a11=$this->rgbpixel($x-1,$y-1);$a12=$this->rgbpixel($x,$y-1);$a13=$this->rgbpixel($x+1,$y-1);
				$a21=$this->rgbpixel($x-1,$y);$a22=$this->rgbpixel($x,$y);$a23=$this->rgbpixel($x+1,$y);
				$a31=$this->rgbpixel($x-1,$y+1);$a32=$this->rgbpixel($x,$y+1);$a33=$this->rgbpixel($x+1,$y+1);
				$newred[]=$a11['red'];$newgreen[]=$a11['green'];$newblue[]=$a11['blue'];
				$newred[]=$a12['red'];$newgreen[]=$a12['green'];$newblue[]=$a12['blue'];
				$newred[]=$a13['red'];$newgreen[]=$a13['green'];$newblue[]=$a13['blue'];
				$newred[]=$a21['red'];$newgreen[]=$a21['green'];$newblue[]=$a21['blue'];
				$newred[]=$a22['red'];$newgreen[]=$a22['green'];$newblue[]=$a22['blue'];
				$newred[]=$a23['red'];$newgreen[]=$a23['green'];$newblue[]=$a23['blue'];
				$newred[]=$a31['red'];$newgreen[]=$a31['green'];$newblue[]=$a31['blue'];
				$newred[]=$a32['red'];$newgreen[]=$a32['green'];$newblue[]=$a32['blue'];
				$newred[]=$a33['red'];$newgreen[]=$a33['green'];$newblue[]=$a33['blue'];
				sort($newred,SORT_NUMERIC);sort($newgreen,SORT_NUMERIC);sort($newblue,SORT_NUMERIC);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newred[4],$newgreen[4],$newblue[4],$a22['alpha']));		
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);
		
	}

	/**
	 * Return RGB values from pixel
	 *
	 */	
	private function rgbpixel($x,$y) {
			
		if ($x<0) {$x=0;}
		if ($x>=$this->size[0]) {$x=$this->size[0]-1;}
		if ($y<0) {$y=0;}
		if ($y>=$this->size[1]) {$y=$this->size[1]-1;}		
		$pixel=ImageColorAt($this->im,intval($x),intval($y));
		return array('red' => ($pixel >> 16 & 0xFF),'green' => ($pixel >> 8 & 0xFF),'blue' => ($pixel & 0xFF),'alpha' => ($pixel >>24 & 0xFF));
		
	}	

	/**
	 * Gaussian Blur Filter
	 *
	 */	
	private function blur() {

		$oldfilter=$this->Filter;$olddivisor=$this->Divisor;$oldoffset=$this->Offset;
		$this->Filter  = array(1,2,1,2,4,2,1,2,1);
		$this->Divisor = 16;
		$this->Offset  = 0;
		$this->filter();
		$this->Filter  = $oldfilter;
		$this->Divisor = $olddivisor;
		$this->Offset  = $oldoffset;		
		
	}

	/**
	 * Sharpen Filter
	 *
	 */	
	private function sharpen() {

		$oldfilter=$this->Filter;$olddivisor=$this->Divisor;$oldoffset=$this->Offset;
		$this->Filter  = array(-1,-1,-1,-1,16,-1,-1,-1,-1);
		$this->Divisor = 8;
		$this->Offset  = 0;
		$this->filter();
		$this->Filter  = $oldfilter;
		$this->Divisor = $olddivisor;
		$this->Offset  = $oldoffset;		
		
	}

	/**
	 * Edge Filter
	 *
	 */	
	private function edge() {

		$oldfilter=$this->Filter;$olddivisor=$this->Divisor;$oldoffset=$this->Offset;
		$this->Filter  = array(-1,-1,-1,-1,8,-1,-1,-1,-1);
		$this->Divisor = 1;
		$this->Offset  = 127;
		$this->filter();
		$this->Filter  = $oldfilter;
		$this->Divisor = $olddivisor;
		$this->Offset  = $oldoffset;		
		
	}

	/**
	 * Emboss Filter
	 *
	 */	
	private function emboss() {

		$oldfilter=$this->Filter;$olddivisor=$this->Divisor;$oldoffset=$this->Offset;
		$this->Filter  = array(2,0,0,0,-1,0,0,0,-1);
		$this->Divisor = 1;
		$this->Offset  = 127;
		$this->filter();
		$this->Filter  = $oldfilter;
		$this->Divisor = $olddivisor;
		$this->Offset  = $oldoffset;		
		
	}

	/**
	 * Mean Filter
	 *
	 */	
	private function mean() {

		$oldfilter=$this->Filter;$olddivisor=$this->Divisor;$oldoffset=$this->Offset;
		$this->Filter  = array(1,1,1,1,1,1,1,1,1);
		$this->Divisor = 9;
		$this->Offset  = 0;
		$this->filter();
		$this->Filter  = $oldfilter;
		$this->Divisor = $olddivisor;
		$this->Offset  = $oldoffset;		
		
	}
	
	/**
	 * Apply perspective to the image
	 *
	 */	
	private function perspective() {
		
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		imagefilledrectangle(
			$this->newimage,
			0,
			0,
			$this->size[0],
			$this->size[1],
			imagecolorallocate(
				$this->newimage,
				hexdec(substr($this->Backgroundcolor,1,2)),
				hexdec(substr($this->Backgroundcolor,3,2)),
				hexdec(substr($this->Backgroundcolor,5,2))
			)
		);
		if ($this->Perspective[1]==0 || $this->Perspective[1]==1) {
        $gradient=($this->size[1]-($this->size[1]*(max(100-$this->Perspective[2],1)/100)))/$this->size[0];
		    for ($c=0;$c<$this->size[0];$c++) {
			    if ($this->Perspective[1]==0) {
				    $length=intval($this->size[1]-(floor($gradient*$c)));
			    } else {
				    $length=intval($this->size[1]-(floor($gradient*($this->size[0]-$c))));
			    }
					imagecopyresampled(
						$this->newimage,
						$this->im,
						$c,
						intval(($this->size[1]-$length)/2),
						$c,0,1,$length,1,$this->size[1]);
		    }
		} else {
        $gradient=intval(($this->size[0]-($this->size[0]*(max(100-$this->Perspective[2],1)/100)))/$this->size[1]);
		    for ($c=0;$c<$this->size[1];$c++) {
			    if ($this->Perspective[1]==2) {
				    $length=intval($this->size[0]-(floor($gradient*$c)));
			    } else {
				    $length=intval($this->size[0]-(floor($gradient*($this->size[1]-$c))));
			    }
					imagecopyresampled($this->newimage,$this->im,intval(($this->size[0]-$length)/2),$c,0,$c,$length,1,$this->size[0],1);
		    }		
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);
	}		

	 /**
	 * Apply perspective to the thumbnail
	 *
	 */	
	private function perspectivethumb() {
		$this->newimage=imagecreatetruecolor($this->thumbx,$this->thumby);
		imagefilledrectangle(
			$this->newimage,
			0,
			0,
			$this->thumbx,
			$this->thumby,
			imagecolorallocate(
				$this->newimage,
				hexdec(substr($this->Backgroundcolor,1,2)),
				hexdec(substr($this->Backgroundcolor,3,2)),
				hexdec(substr($this->Backgroundcolor,5,2))
			)
		);
		if ($this->Perspectivethumb[1]==0 || $this->Perspectivethumb[1]==1) {
			$gradient=($this->thumby-($this->thumby*(max(100-$this->Perspectivethumb[2],1)/100)))/$this->thumbx;
			for ($c=0;$c<$this->thumbx;$c++) {
				if ($this->Perspectivethumb[1]==0) {
					$length=intval($this->thumby-(floor($gradient*$c)));
				} else {
					$length=intval($this->thumby-(floor($gradient*($this->thumbx-$c))));
				}
				imagecopyresampled($this->newimage,$this->thumb,$c,intval(($this->thumby-$length)/2),$c,0,1,$length,1,$this->thumby);
			}
		} else {
			$gradient=($this->thumbx-($this->thumbx*(max(100-$this->Perspectivethumb[2],1)/100)))/$this->thumby;
			for ($c=0;$c<$this->thumby;$c++) {
				if ($this->Perspectivethumb[1]==2) {
					$length=intval($this->thumbx-(floor($gradient*$c)));
				} else {
					$length=intval($this->thumbx-(floor($gradient*($this->thumby-$c))));
				}
				imagecopyresampled($this->newimage,$this->thumb,intval(($this->thumbx-$length)/2),$c,0,$c,$length,1,$this->thumbx,1);
			}
		}
		imagecopy($this->thumb,$this->newimage,0,0,0,0,$this->thumbx,$this->thumby);
		imagedestroy($this->newimage);
	}		

	/**
	 * Apply gradient shading to image
	 *
	 */	
	private function shading() {
		
		if ($this->Shading[3]==0 || $this->Shading[3]==1) {		
			$this->newimage=imagecreatetruecolor(1,$this->size[1]);
			imagefilledrectangle($this->newimage,0,0,1,$this->size[1],imagecolorallocate($this->newimage,hexdec(substr($this->Shadingcolor,1,2)),hexdec(substr($this->Shadingcolor,3,2)),hexdec(substr($this->Shadingcolor,5,2))));
		} else {
			$this->newimage=imagecreatetruecolor($this->size[0],1);
			imagefilledrectangle($this->newimage,0,0,$this->size[0],1,imagecolorallocate($this->newimage,hexdec(substr($this->Shadingcolor,1,2)),hexdec(substr($this->Shadingcolor,3,2)),hexdec(substr($this->Shadingcolor,5,2))));			
		}
		if ($this->Shading[3]==0) {
			$shadingstrength=$this->Shading[1]/($this->size[0]*($this->Shading[2]/100));
			for ($c=$this->size[0]-floor(($this->size[0]*($this->Shading[2]/100)));$c<$this->size[0];$c++) { 
				$opacity=intval($shadingstrength*($c-($this->size[0]-floor(($this->size[0]*($this->Shading[2]/100)))))); 
				imagecopymerge($this->im,$this->newimage,intval($c),0,0,0,1,$this->size[1],max(min($opacity,100),0));
			}	
		} else if ($this->Shading[3]==1) {
			$shadingstrength=$this->Shading[1]/($this->size[0]*($this->Shading[2]/100));
			for ($c=0;$c<floor($this->size[0]*($this->Shading[2]/100));$c++) { 
				$opacity=intval($this->Shading[1]-($c*$shadingstrength));			 
				imagecopymerge($this->im,$this->newimage,intval($c),0,0,0,1,$this->size[1],max(min($opacity,100),0));
			}			
		} else if ($this->Shading[3]==2) {
			$shadingstrength=$this->Shading[1]/($this->size[1]*($this->Shading[2]/100));
			for ($c=0;$c<floor($this->size[1]*($this->Shading[2]/100));$c++) { 
				$opacity=intval($this->Shading[1]-($c*$shadingstrength));			 
				imagecopymerge($this->im,$this->newimage,0,intval($c),0,0,$this->size[0],1,max(min($opacity,100),0));
			}			
		} else {
			$shadingstrength=$this->Shading[1]/($this->size[1]*($this->Shading[2]/100));
			for ($c=$this->size[1]-floor(($this->size[1]*($this->Shading[2]/100)));$c<$this->size[1];$c++) { 
				$opacity=intval($shadingstrength*($c-($this->size[1]-floor(($this->size[1]*($this->Shading[2]/100)))))); 
				imagecopymerge($this->im,$this->newimage,0,intval($c),0,0,$this->size[0],1,max(min($opacity,100),0));
			}			
		}
		imagedestroy($this->newimage);
	
	}		

	/**
	 * Apply mirror effect to the thumbnail with gradient 
	 *
	 */	
	private function mirror() {
		
		$bottom=intval(($this->Mirror[3]/100)*$this->thumby)+$this->Mirror[4];
		$this->newimage=imagecreatetruecolor($this->thumbx,$this->thumby+$bottom);
		imagefilledrectangle($this->newimage,0,0,$this->thumbx,$this->thumby+$bottom,imagecolorallocate($this->newimage,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));
		imagecopy($this->newimage,$this->thumb,0,0,0,0,$this->thumbx,$this->thumby);
		imagedestroy($this->thumb);$this->thumb=imagecreatetruecolor($this->thumbx,$this->thumby+$bottom);
		imagecopy($this->thumb,$this->newimage,0,0,0,0,$this->thumbx,$this->thumby+$bottom);
		imagedestroy($this->newimage);$this->thumbx=imagesx($this->thumb);$this->thumby=imagesy($this->thumb);
		for ($px=0;$px<$this->thumbx;$px++) {
			for ($py=$this->thumby-($bottom*2)+$this->Mirror[4];$py<($this->thumby-$bottom);$py++) {imagecopy($this->thumb,$this->thumb,$px,$this->thumby-($py-($this->thumby-($bottom*2)))-1+$this->Mirror[4],$px,$py,1,1);}
		}
		$this->newimage=imagecreatetruecolor($this->thumbx,1);
		imagefilledrectangle($this->newimage,0,0,$this->thumbx,1,imagecolorallocate($this->newimage,hexdec(substr($this->Mirrorcolor,1,2)),hexdec(substr($this->Mirrorcolor,3,2)),hexdec(substr($this->Mirrorcolor,5,2))));	
		$shadingstrength=intval(($this->Mirror[2]-$this->Mirror[1])/$bottom);
		for ($c=$this->thumby-$bottom;$c<$this->thumby;$c++) { 
			$opacity=intval($this->Mirror[1]+floor(($bottom-($this->thumby-$c))*$shadingstrength));
			imagecopymerge($this->thumb,$this->newimage,0,$c,0,0,$this->thumbx,1,max(min($opacity,100),0));
		}	
		imagedestroy($this->newimage);

	}

	/**
	 * Create a negative
	 *
	 */	
	private function negative() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_NEGATE);
		} else {
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$pixel=ImageColorAt($this->im,$x,$y);
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,255-($pixel >> 16 & 0xFF),255-($pixel >> 8 & 0xFF),255-($pixel & 0xFF),$pixel >> 24 & 0xFF));
				}
			}
		}

	}
	
	/**
	 * Replace a color
	 * Eucledian color vector distance
	 *
	 */	
	private function colorreplace() {
		
		$red=hexdec(substr($this->Colorreplace[1],1,2));$green=hexdec(substr($this->Colorreplace[1],3,2));$blue=hexdec(substr($this->Colorreplace[1],5,2));
		$rednew=hexdec(substr($this->Colorreplace[2],1,2));$greennew=hexdec(substr($this->Colorreplace[2],3,2));$bluenew=hexdec(substr($this->Colorreplace[2],5,2));
		$tolerance=sqrt(pow($this->Colorreplace[3],2)+pow($this->Colorreplace[3],2)+pow($this->Colorreplace[3],2));
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$pixel=ImageColorAt($this->im,$x,$y);
				$redpix=($pixel >> 16 & 0xFF);$greenpix=($pixel >> 8 & 0xFF);$bluepix=($pixel & 0xFF);
				if (sqrt(pow($redpix-$red,2)+pow($greenpix-$green,2)+pow($bluepix-$blue,2))<$tolerance)
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,$rednew,$greennew,$bluenew,$pixel >> 24 & 0xFF));	
			}
		}

	}	

	/**
	 * Randomly reposition pixels
	 *
	 */	
	private function pixelscramble() {
		
		for ($i=0;$i<$this->Pixelscramble[2];$i++) {
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$newx=$x+rand(-$this->Pixelscramble[1],$this->Pixelscramble[1]);
				$newy=$y+rand(-$this->Pixelscramble[1],$this->Pixelscramble[1]);
				if ($newx<0 && $newx>=$this->size[0]) {$newx=$x;}
				if ($newy<0 && $newy>=$this->size[1]) {$newy=$y;}
				imagecopy($this->newimage,$this->im,$newx,$newy,$x,$y,1,1);
				imagecopy($this->newimage,$this->im,$x,$y,$newx,$newy,1,1);
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);
		}
		
	}

	/**
	 * 二值化
	 *
	 * @return void
	 */
	private function binaryzation() {
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$pixel=ImageColorAt($this->im,$x,$y);
				$pixelRGBA = imagecolorsforindex($this->im, $pixel);
				imagesetpixel(
					$this->im,$x,$y,
					imagecolorallocatealpha(
						$this->im,
						$pixelRGBA['red'] <= 127 ? 0 : 255,
						$pixelRGBA['green'] <= 127 ? 0 : 255,
						$pixelRGBA['blue'] <= 127 ? 0 : 255,
						$pixel >> 24 & 0xFF
					)
				);
				// $this->grayRGB[$y][] = $grey;
			}
		}
	}

	/**
	 * Color flip.
	 *
	 * @return void
	 */
	private function colorFlip(){
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$pixel=ImageColorAt($this->im,$x,$y);
				$pixelRGBA = imagecolorsforindex($this->im, $pixel);
				imagesetpixel(
					$this->im,$x,$y,
					imagecolorallocatealpha(
						$this->im,
						255 - $pixelRGBA['red'],
						255 - $pixelRGBA['green'],
						255 - $pixelRGBA['blue'],
						$pixel >> 24 & 0xFF
					)
				);
				// $this->grayRGB[$y][] = $grey;
			}
		}
	}

	/**
	 * 图片灰化(去彩)
	 *
	 */	
	private function greyscale() {
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_GRAYSCALE);
		} else {
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$pixel=ImageColorAt($this->im,$x,$y);
					//公式: 灰度值为 r * 0.299 + g * 0.587 + b * 0.114
					$grey=intval(($pixel >> 16 & 0xFF)*0.299 + ($pixel >> 8 & 0xFF)*0.587 + ($pixel & 0xFF)*0.114);
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,$grey,$grey,$grey,$pixel >> 24 & 0xFF));
					// $this->grayRGB[$y][] = $grey;
				}
			}
		}
	}

	/**
	 * Change brightness
	 *
	 */	
	private function brightness() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_BRIGHTNESS,$this->Brightness[1]);
		} else {
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$pixel=ImageColorAt($this->im,$x,$y);
					$redpix=max(0,min(255,($pixel >> 16 & 0xFF)+($this->Brightness[1]/100)*255));
					$greenpix=max(0,min(255,($pixel >> 8 & 0xFF)+($this->Brightness[1]/100)*255));
					$bluepix=max(0,min(255,($pixel & 0xFF)+($this->Brightness[1]/100)*255));
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,$redpix,$greenpix,$bluepix,$pixel >> 24 & 0xFF));
				}
			}
		}		

	}

	/**
	 * Change contrast
	 *
	 */	
	private function contrast() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_CONTRAST,-$this->Contrast[1]);
		} else {
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$pixel=ImageColorAt($this->im,$x,$y);
					$redpix=max(0,min(255,(((($pixel >> 16 & 0xFF)/255)-0.5)*($this->Contrast[1]/100+1)+0.5)*255));
					$greenpix=max(0,min(255,(((($pixel >> 8 & 0xFF)/255)-0.5)*($this->Contrast[1]/100+1)+0.5)*255));
					$bluepix=max(0,min(255,(((($pixel & 0xFF)/255)-0.5)*($this->Contrast[1]/100+1)+0.5)*255));
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,$redpix,$greenpix,$bluepix,$pixel >> 24 & 0xFF));
				}
			}
		}		

	}

	/**
	 * Change gamma
	 *
	 */	
	private function gamma() {
		
		imagegammacorrect($this->im,1,$this->Gamma[1]);	

	}

	/**
	 * Reduce palette
	 *
	 */	
	private function palette() {
		
		imagetruecolortopalette($this->im,false,$this->Palette[1]);

	}

	/**
	 * Merge a color in the image
	 *
	 */	
	private function colorize() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_COLORIZE,$this->Colorize[1],$this->Colorize[2],$this->Colorize[3],$this->Colorize[4]);
		} else {
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$pixel=ImageColorAt($this->im,$x,$y);
					$redpix=max(0,min(255,($pixel >> 16 & 0xFF)+$this->Colorize[1]));
					$greenpix=max(0,min(255,($pixel >> 8 & 0xFF)+$this->Colorize[2]));
					$bluepix=max(0,min(255,($pixel & 0xFF)+$this->Colorize[3]));
					$alpha =max(0,min(127,($pixel >> 24 & 0xFF)+$this->Colorize[4]));
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,$redpix,$greenpix,$bluepix,$alpha));
				}
			}
		}		

	}

	/**
	 * Pixelate the image
	 *
	 */	
	private function pixelate() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_PIXELATE,$this->Pixelate[1],true);
		} else {
			for ($y=0;$y<$this->size[1];$y+=$this->Pixelate[1]) {
				for ($x=0;$x<$this->size[0];$x+=$this->Pixelate[1]) {
					$pixel=ImageColorAt($this->im,$x,$y);
					imagefilledrectangle($this->im,$x,$y,$x+$this->Pixelate[1]-1,$y+$this->Pixelate[1]-1,$pixel);	
				}
			}
		}

	}

	/**
	 * Bilinear interpolation 
	 *
	 */	
	private function bilinear($xnew,$ynew) {
		
		$xf=intval($xnew);$xc=$xf+1;$fracx=$xnew-$xf;$fracx1=1-$fracx;
		$yf=intval($ynew);$yc=$yf+1;$fracy=$ynew-$yf;$fracy1=1-$fracy;
		$ff=$this->rgbpixel($xf,$yf);$cf=$this->rgbpixel($xc,$yf);
		$fc=$this->rgbpixel($xf,$yc);$cc=$this->rgbpixel($xc,$yc);
		$red=intval($fracy1*($fracx1*$ff['red']+$fracx*$cf['red'])+$fracy*($fracx1*$fc['red']+$fracx*$cc['red']));
		$green=intval($fracy1*($fracx1*$ff['green']+$fracx*$cf['green'])+$fracy*($fracx1*$fc['green']+$fracx*$cc['green']));
		$blue=intval($fracy1*($fracx1*$ff['blue']+$fracx*$cf['blue'])+$fracy*($fracx1*$fc['blue']+$fracx*$cc['blue']));
		return array('red' => $red,'green' => $green,'blue' => $blue,'alpha' => $cc['alpha']);
		
	}

	/**
	 * Apply twirl FX to image
	 *
	 */	
	private function twirlfx() {
		
		$rotationamount=$this->Twirlfx[1]/1000;
		$centerx=intval($this->size[0]/2);$centery=intval($this->size[1]/2);
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$truex=$x-$centerx;$truey=$y-$centery;
				$theta=atan2(($truey),($truex));
				$radius=sqrt($truex*$truex+$truey*$truey);
				if ($this->Twirlfx[2]==0) {
					$newx=$centerx+($radius*cos($theta+$rotationamount*$radius));
					$newy=$centery+($radius*sin($theta+$rotationamount*$radius));
				} else {
					$newx=$centerx-($radius*cos($theta+$rotationamount*$radius));
					$newy=$centery-($radius*sin($theta+$rotationamount*$radius));					
				}
				$newpix=$this->bilinear($newx,$newy);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpix['red'],$newpix['green'],$newpix['blue'],$newpix['alpha']));
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);

	}

	/**
	 * Apply ripple FX to image
	 *
	 */	
	private function ripplefx() {
		
		$wavex=((2*pi())/$this->size[0])*$this->Ripplefx[1];
		$wavey=((2*pi())/$this->size[1])*$this->Ripplefx[3];
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$newx=$x+$this->Ripplefx[4]*sin($y*$wavey); 
				$newy=$y+$this->Ripplefx[2]*sin($x*$wavex);
				$newpix=$this->bilinear($newx,$newy);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpix['red'],$newpix['green'],$newpix['blue'],$newpix['alpha']));
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);

	}

	/**
	 * Apply lake FX to image
	 *
	 */	
	private function lakefx() {
		
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		$ystart=intval(max($this->size[1]-floor($this->size[1]*($this->Lakefx[2]/100)),0));
		if ($ystart>0) {
		    imagecopy($this->newimage,$this->im,0,0,0,0,$this->size[0],$this->size[1]);
		}
		for ($y=$ystart;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$newy=intval($y+3*pi()*(1/$this->size[1])*$y*sin(($this->size[1]*($this->Lakefx[1]/100)*($this->size[1]-$y))/$y)); 
				$newpix=$this->bilinear($x,$newy);
				imagesetpixel($this->newimage,intval($x),intval($y),imagecolorallocatealpha($this->newimage,$newpix['red'],$newpix['green'],$newpix['blue'],$newpix['alpha']));
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);

	}

	/**
	 * Apply waterdrop FX to image
	 *
	 */	
	private function waterdropfx() {
		
		$centerx=intval($this->size[0]/2);$centery=intval($this->size[1]/2);
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$truex=$x-$centerx;$truey=$y-$centery;
				$distance=sqrt($truex*$truex+$truey*$truey);	
				$amount=$this->Waterdropfx[1]*sin($distance/$this->Waterdropfx[3]*2*pi());
				$amount=$amount*($this->Waterdropfx[2]-$distance)/$this->Waterdropfx[2];
				if ($distance!=0) {$amount=$amount*$this->Waterdropfx[3]/$distance;}
				$newx=intval($x+$truex*$amount);
				$newy=intval($y+$truey*$amount);
				$newpix=$this->bilinear($newx,$newy);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpix['red'],$newpix['green'],$newpix['blue'],$newpix['alpha']));
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);
		
	}

	/**
	 * Create a transparent image
	 *
	 */	
	private function maketransparent() {
		
		$red=hexdec(substr($this->Maketransparent[2],1,2));$green=hexdec(substr($this->Maketransparent[2],3,2));$blue=hexdec(substr($this->Maketransparent[2],5,2));
		if ($this->Maketransparent[3]!=0) {
			$transparentcolor=imagecolorallocate($this->thumb,$red,$green,$blue);
			$tolerance=sqrt(pow($this->Maketransparent[3],2)+pow($this->Maketransparent[3],2)+pow($this->Maketransparent[3],2));
			for ($y=0;$y<$this->thumby;$y++) {
				for ($x=0;$x<$this->thumbx;$x++) {
					$pixel=ImageColorAt($this->thumb,$x,$y);
					$redpix=($pixel >> 16 & 0xFF);$greenpix=($pixel >> 8 & 0xFF);$bluepix=($pixel & 0xFF);
					if (sqrt(pow($redpix-$red,2)+pow($greenpix-$green,2)+pow($bluepix-$blue,2))<$tolerance)
						imagesetpixel($this->thumb,$x,$y,$transparentcolor);	
				}
			}
		}
		$transparentcolor=imagecolorallocate($this->thumb,$red,$green,$blue);
		imagecolortransparent($this->thumb,$transparentcolor);	
		if ($this->Maketransparent[1]!=2) {
			if ($this->Maketransparent[1]==0) {$this->size[2]=3;} else {$this->size[2]=1;}
		}
	}

	/**
	 * Create a PNG binary chunk
	 *
	 * @param string $type
	 * @param string $data
	 */
	private function create_chunk($type, $data = '') {
		$chunk = pack("N", strlen($data)) . $type . $data . pack("N", crc32($type . $data));        
		return $chunk;
	}

	/**
	 * Create a PNG fcTL binary chunk
	 *
	 * @param array $frameNumber
	 * @param string $width
	 * @param string $height
	 * @param string $delay
	 */
	private function create_fcTL($frameNumber, $width, $height, $delay) {

		$fcTL = array();
		$fcTL['sequence_number'] = $frameNumber;
		$fcTL['width'] = $width;
		$fcTL['height'] = $height;
		$fcTL['x_offset'] = 0;
		$fcTL['y_offset'] = 0;
		$fcTL['delay_num'] = $delay;
		$fcTL['delay_den'] = 1000;
		$fcTL['dispose_op'] = 0;
		$fcTL['blend_op'] = 0;
		$data = pack("NNNNN", $fcTL['sequence_number'], $fcTL['width'], $fcTL['height'], $fcTL['x_offset'], $fcTL['y_offset']);
		$data .= pack("nn", $fcTL['delay_num'], $fcTL['delay_den']);
		$data .= pack("cc", $fcTL['dispose_op'], $fcTL['blend_op']);
		return $data;
	}

	/**
	 * Apply polaroid look to original image
	 *
	 */	
	private function polaroid() {
	
		$originalarray=$this->Cropimage;
		if ($this->size[0]>$this->size[1]) {
			$cropwidth=intval(($this->size[0]-floor(($this->size[1]/1.05)))/2);
			$this->Cropimage=array(1,1,$cropwidth,$cropwidth,0,-1*intval(0.16*$this->size[1]));
			$this->cropimage();
			$this->Framewidth=intval(0.05*($this->size[1]-2*$cropwidth));
		} else {
			$cropheight=intval(($this->size[1]-floor(($this->size[0]/1.05)))/2);
			$bottom=-1*intval(0.16*$this->size[1]);
			$this->Cropimage=array(1,1,0,0,$cropheight,$cropheight);
			$this->cropimage();
			$this->Cropimage=array(1,1,0,0,0,$bottom);
			$this->cropimage();
			$this->Framewidth=intval(0.05*$this->size[0]);
		}
		$this->Cropimage=$originalarray;
		if ($this->Polaroidtext!='' && $this->Polaroidfonttype!='') {
		  $dimensions=imagettfbbox($this->Polaroidfontsize,0,$this->Polaroidfonttype,$this->Polaroidtext);
			$widthx=$dimensions[2];
			$heighty=$dimensions[5];
			$y=intval($this->size[1]-floor($this->size[1]*0.08)-$heighty);
			$x=intval(($this->size[0]-$widthx)/2);
			imagettftext($this->im,$this->Polaroidfontsize,0,$x,$y,imagecolorallocate($this->im,hexdec(substr($this->Polaroidtextcolor,1,2)),hexdec(substr($this->Polaroidtextcolor,3,2)),hexdec(substr($this->Polaroidtextcolor,5,2))),$this->Polaroidfonttype,$this->Polaroidtext);		
		}
		
	}

	/**
	 * Apply displacement map
	 *
	 */	
	private function displace() {
		
		if (file_exists($this->Displacementmap[1])) {
			$size=GetImageSize($this->Displacementmap[1]);
			switch($size[2]) {
				case 1:
					if (imagetypes() & IMG_GIF) {$map=imagecreatefromgif($this->Displacementmap[1]);} else {$map=imagecreatetruecolor(100,100);}
					break;
				case 2:
					if (imagetypes() & IMG_JPG) {$map=imagecreatefromjpeg($this->Displacementmap[1]);} else {$map=imagecreatetruecolor(100,100);}
					break;
				case 3:
					if (imagetypes() & IMG_PNG) {$map=imagecreatefrompng($this->Displacementmap[1]);} else {$map=imagecreatetruecolor(100,100);}
					break;
				default:
					$map=imagecreatetruecolor(100,100);
			}
		} else {
			$map=imagecreatetruecolor(100,100);
		}
		$mapxmax=imagesx($map);$mapymax=imagesy($map);
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		if ($this->Displacementmap[2]==0) {
			$maptmp=imagecreatetruecolor($this->size[0],$this->size[1]);
			imagecopyresampled($maptmp,$map,0,0,0,0,$this->size[0],$this->size[1],$mapxmax,$mapymax);
			imagedestroy($map);
			$map=imagecreatetruecolor($this->size[0],$this->size[1]);
			imagecopy($map,$maptmp,0,0,0,0,$this->size[0],$this->size[1]);
			imagedestroy($maptmp);
			$mapxmax=$this->size[0];
			$mapymax=$this->size[1];
			$mapx=$this->Displacementmap[3];
			$mapy=$this->Displacementmap[4];
		} else {
			$mapx=$this->Displacementmap[3];
			$mapy=$this->Displacementmap[4];		
		}
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$newx=$x;$newy=$y;
				if ($x>=$mapx && $y>=$mapy) {
					if ($x<$mapxmax && $y<$mapymax) {
						$pixelmap=ImageColorAt($map,$x-$mapx,$y-$mapy);
						$redmap=1+$pixelmap >> 16 & 0xFF;
						$greenmap=1+$pixelmap >> 8 & 0xFF;
						$newx=intval($x+(($redmap-128)*$this->Displacementmap[5])/256);
						$newy=intval($y+(($greenmap-128)*$this->Displacementmap[6])/256);
					}
				}
				$newpix=$this->bilinear($newx,$newy);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpix['red'],$newpix['green'],$newpix['blue'],$newpix['alpha']));	
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);
		imagedestroy($map);
		
	}

	/**
	 * Apply displacement map to thumbnail
	 *
	 */	
	private function displacethumb() {
		
		if (is_resource($this->thumb)) {
			$temparray=$this->Displacementmap;
			imagedestroy($this->im);
			$this->im=imagecreatetruecolor($this->thumbx,$this->thumby);
			imagecopy($this->im,$this->thumb,0,0,0,0,$this->thumbx,$this->thumby);
			$this->size[0]=$this->thumbx;
			$this->size[1]=$this->thumby;
			$this->Displacementmap=$this->Displacementmapthumb;
			$this->displace();
			$this->Displacementmap=$temparray;
			imagecopy($this->thumb,$this->im,0,0,0,0,$this->thumbx,$this->thumby);
		}		
		
	}
}
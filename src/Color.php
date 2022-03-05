<?php
/**
 * @name Rgb/Hex颜色值处理
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;
class Color{

	/**
	 * 16进制色值检测/修补
	 *
	 * @param string $color  "#f" "#ff" "#fff" "#ffffff"
	 * @return string 标准色值hex "#ffffff"
	 */
	static function colorHexFix(string $color):string{
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
	 * 16进制色值转RGB数值
	 * 
	 * -- #dfdfdf转换成(239,239,239)
	 *
	 * @param string $color
	 * @return void
	 */
	static function hex2rgb(string $color ) {
        $color = self::colorHexFix($color);
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
     * RGB数值转16进制色值
     *
     * @param integer $r
     * @param integer $g
     * @param integer $b
     * @return string
     */
    static function rgb2hex(int $r=255, int $g=255, int $b=255 ):string{
        return '#'
            . substr('0'.dechex($r), -2)
            . substr('0'.dechex($g), -2)
            . substr('0'.dechex($b), -2)
        ;
	}
}
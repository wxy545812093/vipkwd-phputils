<?php
/**
 * @name 经纬度操作类
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

/**
 * I know no such things as genius,it is nothing but labor and diligence.
 *
 * @copyright (c) 2015~2019 BD All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @author BD<657306123@qq.com>
 * 
 * 各地图Aself::PI坐标系统比较与转换;
 * WGS84坐标系：即地球坐标系，国际上通用的坐标系。设备一般包含GPS芯片或者北斗芯片获取的经纬度为WGS84地理坐标系,
 * 谷歌地图采用的是WGS84地理坐标系（中国范围除外）;
 * GCJ02坐标系：即火星坐标系，是由中国国家测绘局制订的地理信息系统的坐标系统。由WGS84坐标系经加密后的坐标系。
 * 谷歌中国地图和搜搜中国地图采用的是GCJ02地理坐标系; BD09坐标系：即百度坐标系，GCJ02坐标系经加密后的坐标系;
 * 搜狗坐标系、图吧坐标系等，估计也是在GCJ02基础上加密而成的。 chenhua
 */
class Position{
	const  BAIDU_LBS_TYPE = "bd09ll";
	const  PI = 3.1415926535897932384626;
	const  A = 6378245.0;
	const  EE = 0.00669342162296594323;
	/**
	 * WGS-84坐标系-to-火星系 (GCJ-02)
   * World Geodetic System ==> Mars Geodetic System
	 *
	 * @param float $lat
	 * @param float $lon
	 * @return array|null
	 */
	static function gps84ToGcj02($lat, $lon):?array{
		if(self::outOfChina($lat, $lon)){
			return null;
		}
		$dLat = self::transformLat($lon - 105.0, $lat - 35.0);
		$dLon = self::transformLon($lon - 105.0, $lat - 35.0);
		$radLat = $lat / 180.0 * self::PI;
		$magic = sin($radLat);
		$magic = 1 - self::EE * $magic * $magic;
		$sqrtMagic = sqrt($magic);
		$dLat = ($dLat * 180.0) / ((self::A * (1 - self::EE)) / ($magic * $sqrtMagic) * self::PI);
		$dLon = ($dLon * 180.0) / (self::A / $sqrtMagic * cos($radLat) * self::PI);
		$mgLat = $lat + $dLat;
		$mgLon = $lon + $dLon;
		return array($mgLat, $mgLon);
	}

	/**
	 * 是否在中国
	 *
	 * @param double $lat
	 * @param double $lon
	 * @return bool
	 */
	static function outOfChina($lat, $lon):bool{
		if($lon < 72.004 || $lon > 137.8347)
			return true;
		if($lat < 0.8293 || $lat > 55.8271)
			return true;
		return false;
	}

	/**
	 * 转换维度
	 *
	 * @param float $x
	 * @param float $y
	 * @return float
	 */
	static function transformLat($x, $y):float{
		$ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y
			+ 0.2 * sqrt(abs($x));
		$ret += (20.0 * sin(6.0 * $x * self::PI) + 20.0 * sin(2.0 * $x * self::PI)) * 2.0 / 3.0;
		$ret += (20.0 * sin($y * self::PI) + 40.0 * sin($y / 3.0 * self::PI)) * 2.0 / 3.0;
		$ret += (160.0 * sin($y / 12.0 * self::PI) + 320 * sin($y * self::PI / 30.0)) * 2.0 / 3.0;
		return $ret;
	}

	/**
	 * 转换经度
	 *
	 * @param float $x
	 * @param float $y
	 * @return float
	 */
	static function transformLon($x, $y):float{
		$ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1
			* sqrt(abs($x));
		$ret += (20.0 * sin(6.0 * $x * self::PI) + 20.0 * sin(2.0 * $x * self::PI)) * 2.0 / 3.0;
		$ret += (20.0 * sin($x * self::PI) + 40.0 * sin($x / 3.0 * self::PI)) * 2.0 / 3.0;
		$ret += (150.0 * sin($x / 12.0 * self::PI) + 300.0 * sin($x / 30.0
					* self::PI)) * 2.0 / 3.0;
		return $ret;
	}

	/**
	 * 火星系(GCJ-02)-to-百度系(BD-09)
   * 将 GCJ-02 坐标转换成 BD-09 坐标
	 *
	 * @param float $gg_lat
	 * @param float $gg_lon
	 * @return array
	 */
	static function gcj02ToBD09(float $gg_lat, float $gg_lon):array{
		$x = $gg_lon;
		$y = $gg_lat;
		$z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * self::PI);
		$theta = atan2($y, $x) + 0.000003 * cos($x * self::PI);
		$bd_lon = $z * cos($theta) + 0.0065;
		$bd_lat = $z * sin($theta) + 0.006;
		return array($bd_lat, $bd_lon);
	}

	/**
	 * 百度系(BD-09)-to-WGS-84坐标系
	 *
	 * @param double $bd_lat
	 * @param double $bd_lon
	 * @return array
	 */
	static function bd09ToGps84(double $bd_lat, double $bd_lon):array{
		$gcj02 = self::bd09ToGcj02($bd_lat, $bd_lon);
		return self::gcjToGps84($gcj02[0], $gcj02[1]);
	}

	/**
	 * 百度系(BD-09)-to-火星系(GCJ-02)
   * 将 BD-09 坐标转换成GCJ-02坐标
	 *
	 * @param float $bd_lat
	 * @param float $bd_lon
	 * @return array
	 */
	static function bd09ToGcj02(float $bd_lat, float $bd_lon):array{
		$x = $bd_lon - 0.0065;
		$y = $bd_lat - 0.006;
		$z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * self::PI);
		$theta = atan2($y, $x) - 0.000003 * cos($x * self::PI);
		$gg_lon = $z * cos($theta);
		$gg_lat = $z * sin($theta);
		return array($gg_lat, $gg_lon);
	}

	/**
	 * 火星系(GCJ-02)-to-WGS-84坐标系
	 *
	 * @param float $lat
	 * @param float $lon
	 * @return array
	 **/
	static function gcjToGps84(float $lat, float $lon):array{
		$gps = self::transform($lat, $lon);
		$latitude = $lat * 2 - $gps[0];
		$longitude = $lon * 2 - $gps[1];
		return array($latitude, $longitude);
	}

	/**
	 * 变换坐标
	 *
   * -e.g: phpunit("Position::transform",[23.45833,116.397128]);
	 * @param double $lat
	 * @param double $lon
	 * @return array
	 */
	static function transform(double $lat, double $lon):array{
		if(self::outOfChina($lat, $lon)){
			return array($lat, $lon);
		}
		$dLat = self::transformLat($lon - 105.0, $lat - 35.0);
		$dLon = self::transformLon($lon - 105.0, $lat - 35.0);
		$radLat = $lat / 180.0 * self::PI;
		$magic = sin($radLat);
		$magic = 1 - self::EE * $magic * $magic;
		$sqrtMagic = sqrt($magic);
		$dLat = ($dLat * 180.0) / ((self::A * (1 - self::EE)) / ($magic * $sqrtMagic) * self::PI);
		$dLon = ($dLon * 180.0) / (self::A / $sqrtMagic * cos($radLat) * self::PI);
		$mgLat = $lat + $dLat;
		$mgLon = $lon + $dLon;
		return array($mgLat, $mgLon);
	}
    /**
     * 根据两点间的经纬度计算距离（单位为KM）
     *
     * 地球半径：6378.137 KM
     * 
     * Dev::dump(Tools::getDistance());
     * Dev::dump(Tools::getDistance( 120.149911, 30.282324, 120.155428, 30.244007 ));
     * Dev::dump(Tools::getDistance( 112.45972, 23.05116, 103.850070, 1.289670 ));
     * 
     * @param float $lon1 经度1  正负180度间
     * @param float $lat1 纬度1  正负90度间
     * @param float $lon2 经度2
     * @param float $lat2 纬度2
     *
     * @return float
     */
    static function getDistance(float $lon1=0, float $lat1=0, float $lon2=0, float $lat2=0): float{
      if($lat1 > 90 || $lat1 < -90 || $lat2 > 90 || $lat2 < -90){
          throw new Exception("经纬度参数无效");
      }
      $radLat1 = deg2rad($lat1);
      $radLat2 = deg2rad($lat2);
      $radLon1 = deg2rad($lon1);
      $radLon2 = deg2rad($lon2);
      $s = 2 
          * asin(
              min(1,
                  sqrt(
                      pow(
                          sin(($radLat1 - $radLat2) / 2), 2
                      )
                      + cos($radLat1) 
                      * cos($radLat2) 
                      * pow(
                          sin(($radLon1 - $radLon2) / 2), 2
                      )
                  )
              )
          ) * 6378.137;
      return round( abs($s) , 6);
  }

  /**
   * 获取商户半径x公里的正方区域四个点
   *
   * @param float $lon 经度 
   * @param float $lat 纬度 
   * @param integer $distance 半径大小 单位km
   * @return array
   */
  static function merchantRadiusAxies(float $lon, float $lat, float $distance = 3):array{   
      // 球面(地球)半径：6378.137 KM
      $half = 6378.137;
      $dlon = rad2deg( 2 * asin(sin($distance / (2 * $half)) / cos(deg2rad($lat))));
      $dlat = rad2deg( $distance / $half );

      return [
          'lt' => ['lon' => round($lon - $dlon, 10), 'lat' => round($lat + $dlat,10)],
          'rt' => ['lon' => round($lon + $dlon, 10), 'lat' => round($lat + $dlat,10)],
          'rb' => ['lon' => round($lon + $dlon, 10), 'lat' => round($lat - $dlat,10)],
          'lb' => ['lon' => round($lon - $dlon, 10), 'lat' => round($lat - $dlat,10)],
      ];
  }


  /**
   * 计算平面坐标轴俩点{P1 与 P2}间的距离
   *
   *  -e-: Dev::dump(Tools::mathAxedDistance(1,2,4,6));
   * @param float $x1
   * @param float $y1
   * @param float $x2
   * @param float $y2
   * @return float
   */
  static function mathAxedDistance(float $x1 =0, float $y1 =0, float $x2 =0, float $y2 =0):float{
      return round( sqrt( pow($x2 - $x1, 2) + pow($y2 - $y1,2)), 6);
  }
}

<?php
/**
 * @name 数组操作
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);
namespace Vipkwd\Utils;
use Vipkwd\Utils\Tools;
use Vipkwd\Utils\Validate;
class Ip{
    /**
     * 获取客户端IP
     *
     * -e.g: phpunit("Ip::getClientIp");
     * @return string
     */
    static function getClientIp():string {
      $unknown = 'unknown';
      if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown) ) {
          $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      } elseif ( isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown) ) {
          $ip = $_SERVER['REMOTE_ADDR'];
      }
      if(!$ip && Tools::isCli()){
          return "127.0.0.1";
      }
      /*
        处理多层代理的情况
        或者使用正则方式：$ip = preg_match("/[\d\.]{7,15}/", $ip, $matches) ? $matches[0] : $unknown;
      */
      if (false !== strpos($ip, ',')) $ip = reset(explode(',', $ip));
      return $ip;
    }
    /**
     * IPV4转长整型数字
     *
     * 注意：各数据库引擎或操作系统对于ip2long的计算结果可能有差异(超出 int类型的表示范围)。
     *      所以：建议以 bigint类型 存储本函数结果
     * 
     * -e.g: echo "内置函数实现:";
     * -e.g: phpunit("Ip::ip2long", ["127.0.0.1"]);
     * 
     * -e.g: echo "自定义函数实现:";
     * -e.g: phpunit("Ip::ip2long", ["127.0.0.1", false]);
     * 
     * @param string $ipv4
     * @param boolean $useNormal 是否使用内置函数
     * @return integer
     */
    static function ip2long(string $ipv4, bool $useNormal = true){
      if(Validate::ipv4($ipv4) === false){
          //ipv4不合法
          return Null;
      }
      $int = 0;
      if(function_exists('ip2long') && $useNormal){
          $int = ip2long($ipv4);
      }else{
          $ipv4 = explode(".", $ipv4);
          for($i=0;$i<4; $i++){
              $int += $ipv4[$i] * pow(256, 4 -$i -1);
          }
          unset($ipv4);
      }
      return sprintf("%u", $int) * 1;
  }

  /**
   * IPv4长整型转IP地址
   *
   * -e.g: $bigint=ip2long("127.0.0.1");
   * -e.g: echo "\$bigint --> ".$bigint;
   * 
   * -e.g: echo "内置函数实现:";
   * -e.g: phpunit("Ip::long2ip",[$bigint]);
   * 
   * -e.g: echo "自定义函数实现:";
   * -e.g: phpunit("Ip::long2ip",[$bigint, false]);
   * 
   * @param integer $bigint
   * @param boolean $useNormal
   * @return string
   */
  static function long2ip(int $bigint, bool $useNormal = true):string{
      if(function_exists('long2ip') && $useNormal){
          return long2ip($bigint);
      }else{
          //FFFFFF最大为4294967295
          $bigint = $bigint > 4294967295 ? 4294967295 : $bigint;
          $dec = dechex($bigint); //讲十进制转为十六进制
          //十六进制默认会忽略最左边的0，毕竟是0了，怎么算都是0，留着也没用
          //但中间的0会保留，而IP的十六进制最大为 FFFFFF
          //所有为防止7位IP的出现，我们只能手动补0，才能成双成对（2个一对）
          if(strlen($dec) < 8) {
              $dec = '0'.$dec; //如果长度小于8，最自动补0
          }
          $aIp=[];
          for($i = 0; $i < 8; $i += 2){
              $hex = substr($dec, $i, 2);
              //截取十六进制的第一位
              $ippart = substr($hex, 0, 1);
              if($ippart === '0') {
                  $hex = substr($hex, 1, 1);//如果第一位为0，说明原始数值只有1位，还是要拆散
              }
              $aIp[] = hexdec($hex); //将每段十六进制数转换我为十进制，即每个ip段的值
              unset($hex,$ippart);
          }
          return implode('.',$aIp);
      }
  }

  /**
   * 根据掩码计算IP区间（起始IP~结束IP）
   *
   * -e.g: phpunit("Ip::getIpRangeWithMask", ["192.168.1.1"]);
   * -e.g: phpunit("Ip::getIpRangeWithMask", ["192.168.1.1",25]);
   * -e.g: phpunit("Ip::getIpRangeWithMask", ["192.168.1.1/25"]);
   * 
   * @param string $ipv4 格式：192.168.1.1 或 192.168.1.0/24
   * @param integer $mask
   * @return array
   */
  static function getIpRangeWithMask(string $ipv4, int $mask = 24):array{
      $_ipv4 = $ipv4 = explode('/', preg_replace("/[^0-9\.\/]/","", $ipv4));

      if(!isset($ipv4[1]) || !$ipv4[1]){
          $_ipv4[1] = $ipv4[1] = $mask;
      }

      if( $ipv4[1] > 32 || $ipv4[1] < 0 || false === Validate::ipv4($ipv4[0]) ){
          return [];
      }
      $base = self::ip2long('255.255.255.255');
      $ipv4[0] = self::ip2long($ipv4[0]);
      $mask = pow(2, 32-intval($ipv4[1]))-1; //mask=0.0.0.255(int)
      $smask = $mask ^ $base; //smask=255.255.255.0(int)
      $min = $ipv4[0] & $smask;
      $max = $ipv4[0] | $mask;
      return [
          "input"     => implode('/', $_ipv4),
          "nat"       => self::long2ip($min),

          // 一个IP地址一共有32(4段 8位)位，其中一部分为网络位，一部分为主机位。
          // 网络位+主机位=32 子网掩码表示网络位的位数。如子网掩码为30位，那么主机位就为2位。
          // 因为2的2次方等于4，又因为每个子网中有2个IP地址(一个nat，一个broadcast)不能分配给主机，所以可以分配的IP地址为2个
          "total"     => $mask +1,
          "useful"    => $mask -1,
          "first"     => self::long2ip($min+1),
          "end"       => self::long2ip($max-1),
          "broadcast" => self::long2ip($max),
          "mask"      => self::long2ip($smask),
      ];
  }

  /**
   * 检测IP是否在某个掩码子网里
   * 
   * -e.g: phpunit("Ip::ipv4InMaskArea", ["192.168.1.138","192.168.1.1"]);
   * -e.g: phpunit("Ip::ipv4InMaskArea", ["192.168.1.138","192.168.1.1",24]);
   * -e.g: phpunit("Ip::ipv4InMaskArea", ["192.168.1.138","192.168.1.1/24"]);
   * -e.g: phpunit("Ip::ipv4InMaskArea", ["192.168.1.138","192.168.1.1/25"]);
   *
   * @param string $ipv4  "192.168.1.115"
   * @param string $maskArea 支持携带掩码("192.168.1.1/24")
   * @param integer $mask 0-32
   * @return boolean
   */
  static function ipv4InMaskArea(string $ipv4, string $maskArea, int $mask = 24):bool{
      $maskArea = explode('/', preg_replace("/[^0-9\.\/]/","", $maskArea));
      if(!isset($maskArea[1]) || !$maskArea[1]){
          //默认授权254台主机
          $maskArea[1] = $mask;
      }
      if( $maskArea[1] > 32 || $maskArea[1] < 0  || false === Validate::ipv4($ipv4)  || false === Validate::ipv4($maskArea[0]) ){
          return [];
      }

      $maskArea[1] = 32 - $maskArea[1] * 1;
      return (self::ip2long($ipv4) >> $maskArea[1]) == (self::ip2long($maskArea[0]) >> $maskArea[1]);
  }
}
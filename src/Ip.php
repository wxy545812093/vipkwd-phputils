<?php
/**
 * @name 数组操作
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);
namespace Vipkwd\Utils;
use Vipkwd\Utils\{Tools,Validate,Dev};
use Ip2Region;

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

        // if ($_SERVER['REMOTE_ADDR']) {
        //     $ip = $_SERVER['REMOTE_ADDR'];
        // } elseif (getenv("REMOTE_ADDR")) {
        //     $ip = getenv("REMOTE_ADDR");
        // } elseif ($_SERVER['HTTP_X_FORWARDED_FOR']) {
        //     $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        // } elseif ($_SERVER['HTTP_CLIENT_IP']) {
        //     $ip = $_SERVER['HTTP_CLIENT_IP'];
        // } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
        //     $ip = getenv("HTTP_X_FORWARDED_FOR");
        // } elseif (getenv("HTTP_CLIENT_IP")) {
        //     $ip = getenv("HTTP_CLIENT_IP");
        // } else {
        //     $ip = "";
        // }
      return $ip;
    }

    /**
     * IPV4转长整型数字
     *
     * 注意：各数据库引擎或操作系统对于ip2long的计算结果可能有差异(超出 int类型的表示范围)。
     *      所以：建议以 bigint类型 存储本函数结果
     * 
     * -e.g: phpunit("Ip::ip2long", ["127.0.0.1"]);
     * 
     * @param string $ipv4
     * @return integer
     */
    static function ip2long(string $ipv4="127.0.0.1"){
      if(Validate::ipv4($ipv4) === false){
          //ipv4不合法
          return Null;
      }
      $int = 0;
      if(function_exists('ip2long')){
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
     * -e.g: phpunit("Ip::long2ip",[$bigint]);
     * 
     * @param integer $bigint
     * @return string
     */
    static function long2ip(int $bigint):string{
        if(function_exists('long2ip')){
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
    static function getIpRangeWithMask(string $ipv4="127.0.0.1", int $mask = 24):array{
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
    static function ipv4InMaskArea(string $ipv4="192.168.1.138", string $maskArea="192.168.1.1", int $mask = 24):bool{
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

    /**
    * 获取本机IP(RC)
    *
    * -e.g: phpunit("Ip::getLocalIp");
    * 
    * @return string
    */
    static function getLocalIp():string{
        try{
            $preg = "/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";
            //获取操作系统为win2000/xp、win7的本机IP真实地址
            exec("ipconfig waitall", $out, $stats);
            if (!empty($out)) {
                if( strripos(Tools::getOS(),"window") >= 0){
                    foreach ($out AS $row) {
                        if (strstr($row, "IP") && strstr($row, ":") && !strstr($row, "IPv6")) {
                            $tmpIp = explode(":", $row);
                            if (preg_match($preg, trim($tmpIp[1]))) {
                                return trim($tmpIp[1]);
                            }
                        }
                    }
                }
            }

            exec("ifconfig", $out, $stats);
            if (!empty($out)) {
                if( strripos(Tools::getOS(),"linux") >= 0){
                    // Dev::dump([$out, $stats]);
                    $preg = "/(.*)inet ([0-9\.]+)(.*)broadcast(.*)/";
                    foreach($out as $row){
                        if(preg_match($preg, trim($row), $matches)){
                            return $matches[2];
                        }
                    }
                }
            }
        }catch(\Exception $e){

        }
        return "127.0.0.1";
    }

    /**
     * 构建随机IP地址
     * 
     * -e.g: phpunit("Ip::randomIp");
     * -e.g: phpunit("Ip::randomIp");
     * 
     * @return string
     */
    static function randomIp(): string {
        return Random::randomIp();
    }

    /**
     * 获取IP信息
     *
     * -e.g: phpunit("Ip::getIpInfo",["1.2.4.8"]);
     * -e.g: phpunit("Ip::getIpInfo", ["127.0.0.1"]);
     * 
     * @param string $ip
     * @return array
     */
    static function getIpInfo(string $ip): array{
        $qqwryPath = VIPKWD_UTILS_LIB_ROOT.'/support/qqwry.dat';
        $iplocation = new Helper_IpLocation($qqwryPath);
        $location = $iplocation->getlocation( $ip );
        $region = static::ip2region($ip);
        $addrParse = Tools::expressAddrParse($location['country']);

        ($region['isp'] == '-') && $region['isp'] = $location['area'];
        if($region['city'] == '-' && isset($addrParse['city']) && $addrParse['city']){
            $region['city'] = $addrParse['city'];
            $region['state'] = "中国";
        }elseif($region['city'] == '-'){
            $region['city'] = $location['country'];
        }
        ($region['province'] == '-' && $addrParse['province']) && $region['province'] = $addrParse['province'];
        $region['beginip'] = $location['beginip'];
        $region['endip'] = $location['endip'];
        unset($location, $addrParse);
        return $region;
    }

    private static function ip2region($ip){
        $info = (new Ip2Region)->btreeSearch($ip);
        if($info == null || (is_array($info) && !isset($info['region']))){
            return [
                'state' => '-',
                'region' => '-',
                'province' => '-',
                'city' =>  '-',
                'isp' => '-',
                'ip' => $ip
            ];
        }
        $info = explode('|', $info['region']);
        return [
            'state' => $info[0] ? $info[0] : '-',
            'region' => $info[1] ? $info[1] : '-',
            'province' => $info[2] ? $info[2] : '-',
            'city' => $info[3] ? $info[3] : '-',
            'isp' => $info[4] ? $info[4] : '-',
            'ip' => $ip
        ];
    }
}

/**
 * IP 地理位置查询类
 */ 
class Helper_IpLocation {
    /**
     * QQWry.Dat文件指针
     *
     * @var resource
     */ 
    private $fp; 
    /**
     * 第一条IP记录的偏移地址
     *
     * @var int
     */ 
    private $firstip; 
    /**
     * 最后一条IP记录的偏移地址
     *
     * @var int
     */ 
    private $lastip; 
    /**
     * IP记录的总条数（不包含版本信息记录）
     *
     * @var int
     */ 
    private $totalip; 
   
    /**
     * 构造函数，打开 QQWry.Dat 文件并初始化类中的信息
     *
     * @param string $filename
     */ 
    public function __construct(string $filename) { 
        $this->fp = 0;
        if(File::exists($filename)){
            if (($this->fp = fopen($filename, 'rb')) !== false) { 
                $this->firstip = $this->getlong(); 
                $this->lastip = $this->getlong(); 
                $this->totalip = ($this->lastip - $this->firstip) / 7; 
                //注册析构函数，使其在程序执行结束时执行 
                register_shutdown_function(array( 
                    &$this, 
                    '__destruct' 
                )); 
            }
        } 
    } 
   
    /**
     * 析构函数，用于在页面执行结束后自动关闭打开的文件。
     *
     */ 
    public function __destruct() { 
        if ($this->fp) { 
            fclose($this->fp); 
        } 
        $this->fp = 0; 
    } 
   
    /**
     * 返回读取的长整型数
     *
     * @access private
     * @return int
     */ 
    private function getlong() { 
        //将读取的little-endian编码的4个字节转化为长整型数 
        $result = unpack('Vlong', fread($this->fp, 4)); 
        return $result['long']; 
    } 
   
    /**
     * 返回读取的3个字节的长整型数
     *
     * @access private
     * @return int
     */ 
    private function getlong3() { 
        //将读取的little-endian编码的3个字节转化为长整型数 
        $result = unpack('Vlong', fread($this->fp, 3) . chr(0)); 
        return $result['long']; 
    } 
   
    /**
     * 返回压缩后可进行比较的IP地址
     *
     * @access private
     * @param string $ip
     * @return string
     */ 
    private function packip($ip) { 
        // 将IP地址转化为长整型数，如果在PHP5中，IP地址错误，则返回False， 
        // 这时intval将Flase转化为整数-1，之后压缩成big-endian编码的字符串 
        return pack('N', intval(ip2long($ip))); 
    } 
   
    /**
     * 返回读取的字符串
     *
     * @access private
     * @param string $data
     * @return string
     */ 
    private function getstring($data = "") { 
        $char = fread($this->fp, 1); 
        while (ord($char) > 0) { // 字符串按照C格式保存，以结束 
            $data .= $char; // 将读取的字符连接到给定字符串之后 
            $char = fread($this->fp, 1); 
        } 
        return iconv('gbk', 'utf-8', $data); 
    } 
   
    /**
     * 返回地区信息
     *
     * @access private
     * @return string
     */ 
    private function getarea() { 
        $byte = fread($this->fp, 1); // 标志字节 
        switch (ord($byte)) { 
            case 0: // 没有区域信息 
                $area = ""; 
                break; 
            case 1: 
            case 2: // 标志字节为1或2，表示区域信息被重定向 
                fseek($this->fp, intval($this->getlong3()) ); 
                $area = $this->getstring(); 
                break; 
            default: // 否则，表示区域信息没有被重定向 
                $area = $this->getstring($byte); 
                break; 
        } 
        return $area; 
    } 
   
    /**
     * 根据所给 IP 地址或域名返回所在地区信息
     *
     * @access public
     * @param string $ip
     * @return array
     */ 
    public function getlocation($ip) { 
        if (!$this->fp) 
            return null; // 如果数据文件没有被正确打开，则直接返回空 
        $location['ip'] = gethostbyname($ip); // 将输入的域名转化为IP地址 
        $ip = $this->packip($location['ip']); // 将输入的IP地址转化为可比较的IP地址 
        // 不合法的IP地址会被转化为255.255.255.255 
        // 对分搜索 
        $l = 0; // 搜索的下边界 
        $u = $this->totalip; // 搜索的上边界 
        $findip = $this->lastip; // 如果没有找到就返回最后一条IP记录（QQWry.Dat的版本信息） 
        while ($l <= $u) { // 当上边界小于下边界时，查找失败 
            $i = floor(($l + $u) / 2); // 计算近似中间记录 
            fseek($this->fp, intval($this->firstip + $i * 7) ); 
            $beginip = strrev(fread($this->fp, 4)); // 获取中间记录的开始IP地址 
            // strrev函数在这里的作用是将little-endian的压缩IP地址转化为big-endian的格式 
            // 以便用于比较，后面相同。 
            if ($ip < $beginip) { // 用户的IP小于中间记录的开始IP地址时 
                $u = $i - 1; // 将搜索的上边界修改为中间记录减一 
            } else { 
                fseek($this->fp, intval($this->getlong3()) ); 
                $endip = strrev(fread($this->fp, 4)); // 获取中间记录的结束IP地址 
                if ($ip > $endip) { // 用户的IP大于中间记录的结束IP地址时 
                    $l = $i + 1; // 将搜索的下边界修改为中间记录加一 
                } else { // 用户的IP在中间记录的IP范围内时 
                    $findip = $this->firstip + $i * 7; 
                    break; // 则表示找到结果，退出循环 
                } 
            } 
        } 
        //获取查找到的IP地理位置信息 
        fseek($this->fp, intval($findip) ); 
        $location['beginip'] = long2ip($this->getlong()); // 用户IP所在范围的开始地址 
        $offset = $this->getlong3(); 
        fseek($this->fp, (int)$offset);
        $location['endip'] = long2ip($this->getlong()); // 用户IP所在范围的结束地址 
        $byte = fread($this->fp, 1); // 标志字节 
        switch (ord($byte)) { 
            case 1: // 标志字节为1，表示国家和区域信息都被同时重定向 
                $countryOffset = $this->getlong3(); // 重定向地址 
                fseek($this->fp, (int)$countryOffset); 
                $byte = fread($this->fp, 1); // 标志字节 
                switch (ord($byte)) { 
                    case 2: // 标志字节为2，表示国家信息又被重定向 
                        fseek($this->fp, $this->getlong3()); 
                        $location['country'] = $this->getstring(); 
                        fseek($this->fp, $countryOffset + 4); 
                        $location['area'] = $this->getarea(); 
                        break; 
                    default: // 否则，表示国家信息没有被重定向 
                        $location['country'] = $this->getstring($byte); 
                        $location['area'] = $this->getarea(); 
                        break; 
                } 
                break; 
            case 2: // 标志字节为2，表示国家信息被重定向 
                fseek($this->fp, $this->getlong3()); 
                $location['country'] = $this->getstring(); 
                fseek($this->fp, $offset + 8); 
                $location['area'] = $this->getarea(); 
                break; 
            default: // 否则，表示国家信息没有被重定向 
                $location['country'] = $this->getstring($byte); 
                $location['area'] = $this->getarea(); 
                break; 
        } 
        if ($location['country'] == " CZ88.NET") { // CZ88.NET表示没有有效信息 
            $location['country'] = "未知"; 
        } 
        if ($location['area'] == " CZ88.NET") { 
            $location['area'] = ""; 
        } 
        return $location; 
    } 
}
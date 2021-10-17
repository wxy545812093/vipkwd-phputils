<?php

/**
 * @name 常用工具集合
 * @author devkeep <devkeep@skeep.cc>
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/aiqq363927173/Tools
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\Libs\QRcode;
// use Vipkwd\Utils\Libs\Cookie;
// use Vipkwd\Utils\Libs\Session;
use PHPMailer\PHPMailer\PHPMailer;
use Vipkwd\Utils\Validate;
use \Exception;
use \Closure;

class Tools {
    /**
     * 获取系统类型
     *
     * @return string
     */
    static public function getOS(): string{
        if(PATH_SEPARATOR == ':'){
            return 'Linux';
        }else{
            return 'Windows';
        }
    }

    /**
     * format 保留指定长度小数位
     *
     * @param int $input 数值
     * @param int $number 小数位数
     *
     * @return string
     */
    static public function format($input, int $number = 2): string{
        return sprintf("%." . $number . "f", $input);
    }

    /**
     * 对象转数组
     *
     * @param object|array $object 对象
     * 
     * @return array
     */
    static public function toArray($object): array {
        if(is_object($object)){
            $arr = (array)$object;
        }else if(is_array($object)){
            $arr = [];
            foreach($object as $k => $v){
                $arr[$k] = self::toArray($v);
            }
        }else{
            return Null;
        }
        unset($object);
        return $arr;
        //return json_decode(json_encode($object), true);
    }

    /**
     * 数组转无限级分类
     *
     * @param array $list 归类的数组
     * @param string $id 父级ID
     * @param string $pid 父级PID
     * @param string $child key
     * @param string $root 顶级ID(pid)
     *
     * @return array
     */
    static public function arrayToTree(array $list, string $pk = 'id', string $pid = 'pid', string $child = 'child', int $root = 0): array{
        $tree = [];
        if(is_array($list)){
            $refer = [];
            //基于数组的指针(引用) 并 同步改变数组
            foreach ($list as $key => $val){
                $list[$key][$child] = [];
                $refer[$val[$pk]] = &$list[$key];
            }

            foreach ($list as $key => $val){
                //是否存在parent
                $parentId = isset($val[$pid]) ? $val[$pid] : $root;

                if ($root == $parentId){
                    $tree[$val[$pk]] = &$list[$key];
                }else{
                    if (isset($refer[$parentId])){
                        $refer[$parentId][$child][] = &$list[$key];
                    }
                }
            }
        }
        return array_values($tree);
    }

    /**
     * 排列组合
     * 
     * @param array $input 排列的数组
     * 
     * @return array
     */
    static public function arrayArrange(array $input): array
    {
        $temp = [];
        $result = array_shift($input);

        while($item = array_shift($input))
        {
           $temp = $result;
           $result = [];

           foreach($temp as $v)
           {
                foreach($item as $val)
                {
                    $result[] = array_merge_recursive($v, $val);
                }
           }
        }

        return $result;
    }

    /**
     * 二维数组去重
     *
     * @param array $arr 数组
     * @param string $key 字段
     *
     * @return array
     */
    static public function arrayMultiUnique(array $arr, string $key = 'id'): array{
        $res = [];
        foreach ($arr as $value){
            if(!isset($res[$value[$key]])){
                $res[$value[$key]] = $value;
            }
        }
        return array_values($res);
    }

    /**
     * 二维数组排序
     *
     * @param array $array 排序的数组
     * @param string $keys 要排序的key
     * @param string $sort 排序类型 ASC、DESC
     *
     * @return array
     */
    static public function arrayMultiSort(array $array, string $keys, string $sort = 'desc'): array{
        $keysValue = [];

        foreach ($array as $k => $v){
            $keysValue[$k] = $v[$keys];
        }

        $orderSort = [
            'asc'  => SORT_ASC,
            'desc' => SORT_DESC,
        ];

        array_multisort($keysValue, $orderSort[$sort], $array);

        return $array;
    }

    /**
     * XML转数组
     *
     * @param string $xml xml
     *
     * @return array
     */
    static public function xmlToArray(string $xml): array{
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlString = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $result = json_decode(json_encode($xmlString), true);
        return $result;
    }

    /**
     * 数组转XML
     *
     * @param array $input 数组
     *
     * @return string
     */
    static public function arrayToXml(array $input): string{
        $str = '<xml>';
        foreach ($input as $k => $v){
            $str .= '<' . $k . '>' . $v . '</' . $k . '>';
        }
        $str .= '</xml>';
        return $str;
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
     * @param float $lng1 经度1  正负180度间
     * @param float $lat1 纬度1  正负90度间
     * @param float $lng2 经度2
     * @param float $lat2 纬度2
     *
     * @return float
     */
    static function getDistance(float $lng1=0, float $lat1=0, float $lng2=0, float $lat2=0): float{
        if($lat1 > 90 || $lat1 < -90 || $lat2 > 90 || $lat2 < -90){
            throw new Exception("经纬度参数无效");
        }
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
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
                            sin(($radLng1 - $radLng2) / 2), 2
                        )
                    )
                )
            ) * 6378.137;
        return round( abs($s) , 6);
    }

    /**
     * 获取商户半径x公里的正方区域四个点
     *
     * @param float $lng 经度 
     * @param float $lat 纬度 
     * @param integer $distance 半径大小 单位km
     * @return array
     */
    static function merchantRadiusAxies(float $lng, float $lat, float $distance = 3):array{   
        // 球面(地球)半径：6378.137 KM
        $half = 6378.137;
        $dlng = rad2deg( 2 * asin(sin($distance / (2 * $half)) / cos(deg2rad($lat))));
        $dlat = rad2deg( $distance / $half );

        return [
            'lt' => ['lng' => round($lng - $dlng, 10), 'lat' => round($lat + $dlat,10)],
            'rt' => ['lng' => round($lng + $dlng, 10), 'lat' => round($lat + $dlat,10)],
            'rb' => ['lng' => round($lng + $dlng, 10), 'lat' => round($lat - $dlat,10)],
            'lb' => ['lng' => round($lng - $dlng, 10), 'lat' => round($lat - $dlat,10)],
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

    /**
     * mt_rand增强版（兼容js版Math.random)
     *
     * @param integer $min
     * @param integer $max
     * @param boolean $decimal <false> 是否包含小数
     * @return string
     */
    static function mathRandom(int $min=0, int $max=1, bool $decimal= false){

        if($max < $min){
            throw new Exception("mathRandom(): max({$max}) is smaller than min({$min}).");
            return null;
        }
        $range = mt_rand($min, $max);
        if($decimal && $min < $max){
            $_ = lcg_value(); 
            while($_ < 0.1){
                $_ *= 10;
            }
            $range += $_;
            if($range > $max){
                $range -=1;
            }
        }
        return $range;
    }
    /**
     * get请求
     *
     * @param string $url URL地址
     * @param array $header 请求头 <[]>
     *
     * @return mixed
     */
    static public function get(string $url, array $header =[]){
        $ch = curl_init();

        if(!empty($header)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * Post请求
     *
     * @param string $url URL地址
     * @param string $param <""> 发送参数
     * @param string $dataType <form> 设定发送的数据类型
     * @param array $header 请求头 <[]>
     *
     * @return mixed
     */
    static public function post(string $url, string $param="", string $dataType = 'form', array $header = []){
        $ch = curl_init();
        $dataTypeArr = [
            'form' => ['content-type: application/x-www-form-urlencoded;charset=UTF-8'],
            'json' => ['Content-Type: application/json;charset=utf-8'],
        ];
        if(isset($dataTypeArr[$dataType])){
            $header[] = $dataTypeArr[$dataType][0];
        }

        if(!empty($header)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 扫描目录（递归）
     *
     * @param string $dir
     * @param callable|null $fileCallback  
     *                      以匿名回调方式对扫描到的文件处理；
     *                      匿名函数接收俩个参数： function($scanFile, $scanPath);
     *                      当匿名函数 return === false 时，将退出本函数所有层次的递归模式
     * @return boolean|null
     */
    static function dirScan(string $dir, ?callable $fileCallback=null):?bool{
        if(!is_dir($dir)){
            return $return;
        }
        $return = null;
        $fd = opendir($dir);
        while(false !== ($file = readdir($fd))){
            if($file != "." && $file != ".."){
                if(is_dir($dir."/".$file)){
                    $return = self::dirScan($dir."/".$file, $fileCallback);
                }else{
                    if(is_callable($fileCallback)){
                        $return = $fileCallback($file, $dir);
                    }
                }
                if($return === false ){
                    break;
                }
            }
        }
        @closedir($fd);
        return $return;
    }

    /**
     * 打印目录
     *
     * @param string $dir
     * @return void
     */
    static function dirTree(string $dir):array{
        if(!is_dir($dir)){
            return [];
        }
        $dir = rtrim($dir, "/");
        $path = array();
        $stack = array($dir);
        while($stack){
            $thisdir = array_pop($stack);
            if($dircont = scandir($thisdir)){
                $i=0;
                while(isset($dircont[$i])){
                    if($dircont[$i] !== '.' && $dircont[$i] !== '..'){
                        $current_file = $thisdir.DIRECTORY_SEPARATOR.$dircont[$i];
                        if(is_file($current_file)){
                            $path[] = "f:".$thisdir.DIRECTORY_SEPARATOR.$dircont[$i];
                        }elseif (is_dir($current_file)){
                            $path[] = "d:".$thisdir.DIRECTORY_SEPARATOR.$dircont[$i];
                            $stack[] = $current_file;
                        }
                    }
                    $i++;
                }
            }
        }
        return $path;
    }

    /**
     * 发送邮件
     * 
     * @param array  $form 发件人信息
     * @param array  $data 收件人信息
     *
     * @return mixed
     */
    static public function sendMail(array $form, array $data) {    
        $mail = new PHPMailer(true);       // 实例化PHPMailer对象
        $mail->CharSet = 'UTF-8';                               // 设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
        $mail->isSMTP();                                        // 设定使用SMTP服务
        $mail->SMTPDebug = 0;                                   // SMTP调试功能 0=关闭 1 = 错误和消息 2 = 消息
        $mail->SMTPAuth = true;                                 // 启用 SMTP 验证功能
        $mail->SMTPSecure = 'ssl';                              // 使用安全协议
        $mail->isHTML(true);

        // 发件人信息
        $mail->Host = $form['host'];                            // SMTP 服务器
        $mail->Port = $form['port'];                            // SMTP服务器的端口号
        $mail->Username = $form['username'];                    // SMTP服务器用户名
        $mail->Password = $form['password'];                    // SMTP服务器密码(授权码优先)
        $mail->SetFrom($form['address'], $form['title']);

        // 阿里云邮箱
        // $mail->Host = "smtp.aliyun.com";                          // SMTP 服务器
        // $mail->Port = 465;                                        // SMTP服务器的端口号
        // $mail->Username = "devkeep@aliyun.com";                   // SMTP服务器用户名
        // $mail->Password = "xxxxxxxxxxxx";                         // SMTP服务器密码
        // $mail->SetFrom('devkeep@aliyun.com', '项目完成通知');

        // 网易邮箱
        // $mail->Host = "smtp.163.com";                           // SMTP 服务器
        // $mail->Port = 465;                                      // SMTP服务器的端口号
        // $mail->Username = "devkeep@163.cc";                     // SMTP服务器用户名
        // $mail->Password = "xxxxxxxxx";                          // SMTP服务器密码
        // $mail->SetFrom('devkeep@163.cc', '系统通知');

        // QQ邮箱
        // $mail->Host = "smtp.qq.com";                            // SMTP 服务器
        // $mail->Port = 465;                                      // SMTP服务器的端口号
        // $mail->Username = "363927173@qq.com";                   // SMTP服务器用户名
        // $mail->Password = "xxxxxxxxxxxxxxxx";                   // SMTP服务器密码
        // $mail->SetFrom('devkeep@skeep.cc', '管理系统');

        // 设置发件人昵称 显示在收件人邮件的发件人邮箱地址前的发件人姓名
        $mail->FromName =  $form['nickname'] ?? $form['address'];
        // 设置发件人邮箱地址 同登录账号
        $mail->From = $form['address'];

        // 添加该邮件的主题
        $mail->Subject = $data['subject'];
        // 添加邮件正文
        $mail->MsgHTML($data['body']);
        // 收件人信息
        // 设置收件人邮箱地址(添加多个收件人 则多次调用方法即可)
        $mail->AddAddress($data['mail'], $data['name']);
        // $mail->addAddress('xxxxxx@163.com');

        // 是否携带附件
        if (isset($data['attachment']) && is_array($data['attachment'])){
            foreach ($data['attachment'] as $file) 
            {
                is_file($file) && $mail->AddAttachment($file);
            }
        }
        return $mail->Send() ? true : $mail->ErrorInfo;
    }

    /**
     * 生成二维码
     * 
     * outfile === false, header输出png
     * outfile !== false && saveAndPrint === true, 保存到 outfile指向地址 并header输出
     * outfile !== file && saveAndPrint !== true, 仅保存到 outfile指向地址
     * 
     * @param string  $text 二维码内容
     * @param boolean|string  $outFile 文件
     * @param string  $level 纠错级别 L:7% M:15% Q:25% H:30%
     * @param integer  $size 二维码大小
     * @param integer  $margin 边距常量
     * @param boolean  $saveAndPrint
     *
     * @return void
     */
    static public function qrcode(string $text, $outFile = false, string $level = "7%", int $size = 6, int $margin = 2, bool $saveAndPrint = false){
        QRcode::png($text, $outFile, $level, $size, $margin, $saveAndPrint);
        exit;
    }

 
    



    /**
     * 获取客户端IP
     *
     * @return string
     */
    static function getClientIp():string {
        $unknown = 'unknown';
        if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ( isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown) ) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        /*
        处理多层代理的情况
        或者使用正则方式：$ip = preg_match("/[\d\.]{7,15}/", $ip, $matches) ? $matches[0] : $unknown;
        */
        if (false !== strpos($ip, ',')) $ip = reset(explode(',', $ip));
        return $ip;
    }


    /**
     * session管理函数
     * 
     * $key 支持“.”号深度操作 如："user.id"
     * $key = null, 删除SESSION
     * $key = "" 返回全局SESSION
     * 要设置$key等于Null，请使用 null 而非 "null"
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    static function session($key = "", $value = "null"){
        if($key === null){
            $_SESSION = [];
            return true;
        }
        if($key == ""){
            return $_SESSION;
        }
        $key = trim($key,".");
        $keys =explode('.', $key);
        $sess = $_SESSION;
        unset($key);
        //设置
        if(!empty($keys) && $value !== "null"){
            krsort($keys);
            $tmp = [];
            foreach($keys as $arr_node_key){
                $__tmp = $tmp;
                if(empty($__tmp)){
                    $__tmp[$arr_node_key]=$value;
                    $tmp = $__tmp;
                }else{
                    $tmp = [];
                    $tmp[$arr_node_key] = $__tmp;
                }
                unset($__tmp);
            }
            $_SESSION = array_merge($_SESSION, $tmp);
            unset($tmp, $keys, );
            return true;
        }
        //获取
        foreach($keys as $sk){
            if(!is_array($sess) || !isset($sess[$sk])){
                $sess = NULL;
                break;
            }
            $sess = $sess[$sk];
            unset($sk);
        }
        unset($keys);
        return $sess;
    }

    /**
     * 获取配置文件内容
     * 
     * $key 支持“.”号深度访问数组 如："db.mysql.host"
     *
     * @param string $key
     * @param string $confDir 配置文件所在目录
     * @param string $confSuffix 配置文件后缀 <.php>
     * 
     * @return mixed
     */
    static function config(string $key, string $confDir, string $confSuffix=".php"){
        static $__config_;
        !is_array($__config_) && $__config_ = [];
        $key = trim($key, ".");
        $l = explode('.', $key);
        if(!isset($__config_[$l[0]])){
            $f =  rtrim($confDir, "/") . "/{$l[0]}.".ltrim($confSuffix, ".");
            file_exists($f) && $__config_[$l[0]] = require_once($f);
            unset($f);
        }
        $r = $__config_[$l[0]];
        unset($l[0]);
        foreach($l as $conf_arr_key){
            if( is_array($r) && isset($r[$conf_arr_key])){
                $r = $r[$conf_arr_key];
            }else{
                $r = NULL;
            }
            unset($conf_arr_key);
        }
        unset($key,$l);
        return $r;
    }
    
    /**
     * Cookie管理
     * 
     * @param string $name   cookie名称
     * @param mixed  $value  cookie值
     * @param int  $expires 有效期 （小于0：删除cookie, 大于0：设置cookie）
     * @return mixed
     */
    static function cookie(string $name = null, $value = null, int $expires = 0){
        
        $name && $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        
        $defaults = [
            // cookie 保存时间
            'expires'   => 86400 * 7,
            // cookie 保存路径
            'path'     => '/',
            // cookie 有效域名
            'domain'   => '',
            //  cookie 启用安全传输
            'secure'   => false,
            // httponly设置
            'httponly' => true,
            // samesite 设置，支持 'strict' 'lax'
            'samesite' => '',
        ];

        if($name && is_null($value) && $expires < 0 ){
            //删除
            return self::saveCookie(
                $name,
                "",
                time() - 86400,
                $defaults['path'],
                $defaults['domain'],
                $defaults['secure'],
                $defaults['httponly'],
                $defaults['samesite']
            );
        }else if($name && !is_null($value) ){
            //设置
            return self::saveCookie(
                $name,
                "$value",
                $expires > 0 ? $expires : $defaults['expires'] + time(),
                $defaults['path'],
                $defaults['domain'],
                $defaults['secure'],
                $defaults['httponly'],
                $defaults['samesite']
            );
        }
        if($name){
            return $_COOKIE[$name] ?? null;
        }
        return $_COOKIE;
    }

    /**
     * IPV4转长整型数字
     *
     * 注意：各数据库引擎或操作系统对于ip2long的计算结果可能有差异(超出 int类型的表示范围)。
     *      所以：建议以 bigint类型 存储本函数结果
     * @param string $ipv4
     * @param boolean $useNormal 是不使用内置函数
     * @return integer
     */
    static function ip2long(string $ipv4, bool $useNormal = false):int{
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
     * @param string $ipv4 格式：192.168.1.1 或 192.168.1.0/24
     * @param integer $mask
     * @return array
     */
    static function getIpRangeWithMask(string $ipv4, int $mask = 24):array{
        $_ipv4 = $ipv4 = explode('/', preg_replace("/[^0-9\.\/]/","", $ipv4));

        if(!isset($ipv4[1]) || !$ipv4[1]){
            $_ipv4[1] = $ipv4[1] = $mask;
        }
        if($ipv4[1] > 32 || false === Validate::ipv4($ipv4[0])){
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
        $maskArea[1] = 32 - $maskArea[1] * 1;
        return (self::ip2long($ipv4) >> $maskArea[1]) == (self::ip2long($maskArea[0]) >> $maskArea[1]);
    }

    /**
     * 生成随机MAC地址
     *
     * @param string $sep 分隔符
     * @return string
     */
    static function macAddr(string $sep=":"):string{
        $list = [];
        for($i=0;$i<6;$i++){
            $list[] = strtoupper(
                dechex(
                    floor(
                        self::mathRandom(0,1,true) * 256
                    )
                )
            );
        }
        return implode($sep, $list);
    }

    /**
     * 保存Cookie
     * 
     * @access public
     * @param  string $name cookie名称
     * @param  string $value cookie值
     * @param  int    $expire cookie过期时间
     * @param  string $path 有效的服务器路径
     * @param  string $domain 有效域名/子域名
     * @param  bool   $secure 是否仅仅通过HTTPS
     * @param  bool   $httponly 仅可通过HTTP访问
     * @param  string $samesite 防止CSRF攻击和用户追踪
     * @return void
     */
    private static function saveCookie(string $name, string $value, int $expire, string $path, string $domain, bool $secure, bool $httponly, string $samesite): void
    {
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            setcookie($name, $value, [
                'expires'  => $expire,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ]);
        } else {
            setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }
    
    /**
     * 字符串加密函数
     *
     * @param string $string 字符明文
     * @param string $key 密钥
     * @return string
     */
    static function encrypt(string $string, string $key=""):string{
        return self::vipkwdCrypt($string, "E", $key);
    }

    /**
     * 字符串解密函数
     *
     * @param string $string 密文
     * @param string $key 密钥
     * @return string
     */
    static function decrypt(string $string, string $key=""):string{
        return self::vipkwdCrypt($string, "D", $key);
    }

    /**
     * 获取Htpp头信息为数组
     * 
     * 获取 $_SERVER 所有以“HTTP_” 开头的 头信息
     * 
     * @return array
     */
    static function getHttpHeaders():array{
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if('HTTP_' == substr($key,0,5)) {
                $key = substr($key,5);
                $key = strtolower($key);
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * Discuz 经典加解密函数
     * 
     * ---------------------------------------------------
     *  --        致敬经典:本函数版权归原作者方所有          --
     * ---------------------------------------------------
     * 
     * 注意：建议使用时设置 discuz_auth_key 通用密钥
     *
     * @param string $string 明文或密文
     * @param string $operation DECODE表示解密,其它表示加密
     * @param string $key 密匙
     * @param integer $expiry 密文有效期 秒
     * 
     * @return string
     */
    static function authcode(string $string, string $operation = 'DECODE', string $key = '', int $expiry = 0):string {  
        $ckey_length = 6;
        $key = md5($key ? $key : $GLOBALS['discuz_auth_key'] ?? "@<<5G-H^0Ywz%.");
        $decode = strtolower($operation) == "decode" ? true : false;
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($decode ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);
        $string = $decode ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if($decode) {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * 保密手机号码
     *
     * @param string $mobile
     * @return string
     */
    static function encryptMobile(string $mobile):string{
		return preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $mobile);
	}









    private static function vipkwdCrypt(string $string, string $operation, string $key=''){
        $key=md5($key);
        $key_length=strlen($key);
        $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
        $string_length=strlen($string);
        $rndkey=$box=array();
        $result='';
        for($i=0;$i<=255;$i++){
            $rndkey[$i]=ord($key[$i%$key_length]);
            $box[$i]=$i;
        }
        for($j=$i=0;$i<256;$i++){
            $j=($j+$box[$i]+$rndkey[$i])%256;
            $tmp=$box[$i];
            $box[$i]=$box[$j];
            $box[$j]=$tmp;
        }
        for($a=$j=$i=0;$i<$string_length;$i++){
            $a=($a+1)%256;
            $j=($j+$box[$a])%256;
            $tmp=$box[$a];
            $box[$a]=$box[$j];
            $box[$j]=$tmp;
            $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
        }
        if($operation=='D'){
            if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8)){
                return substr($result,8);
            }else{
                return'';
            }
        }else{
            return str_replace('=','',base64_encode($result));
        }
    }
}

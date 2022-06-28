<?php

/**
 * @name (regexp)验证类
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

class Validate{

    use \Vipkwd\Utils\Libs\Random\Payment\ValidateTrait;

    static $reg_internat_mobile = "/^(((\+?0?\d{1,4})[\ \-])?(\d{5,11}))$/";
    static $reg_email = "/\w+([-+.]\w+)*@((\w+([-.]\w+)*)\.)[a-zA-Z]{2,5}$/";
    static $reg_telephone ="/^(((0[1-9]\d{1,2})[ \-]|\(0[1-9]\d{1,2}\))?\d{4}\ ?)(\d{3,4})(([\-|\ ]\d{1,6})?)$/";
    static $reg_zipcode = "/^[1-9]\d{5}(?!\d)$/";

    /**
     * 验证移动通讯号码（兼容：国际号码格式）
     *
     * @param string $str
     * @return boolean
     */
    static function internatMobile(string $str):bool{
        if( false === $result = self::mobileOfChina($str)){
            // match 组1：完整匹配
            // match 组2：带分隔符的区域码
            // match 组3：区域码
            // match 组4：mobile号码
            return self::exec(self::$reg_internat_mobile, $str);
        }
        return $result;
    }

    /**
     * 验证邮箱号码
     *
     * @param string $str
     * @return boolean
     */
    static function email(string $str):bool{
        return self::exec(self::$reg_email, $str);
    }

    /**
     * 验证手机或座机
     *
     * @param string $str
     * @return boolean
     */
    static function phone(string $str):bool{
        if( false === $result = self::mobileOfChina($str)){
            $result = self::telePhone($str);
        }
        return $result;
    }

    /**
     * 验证座机（兼容: 区号、1~6位分机号）
     * (010)12345678
     * 010 1234567
     * 2811369
     *
     * @param string $str
     * @return boolean
     */
    static function telephone(string $str):bool{
        return self::exec(self::$reg_telephone, $str);
    }

    /**
     * 验证大陆邮政编码
     *
     * @param string $str
     * @return boolean
     */
    static function zipCodeOfChina(string $str):bool{
        return self::exec(self::$reg_zipcode, $str);
    }

    /**
     * 验证大陆手机号 (兼容：地区码前缀)
     *
     * @param string $str
     * @param boolean $prefixSupport
     * @return boolean
     */
    static function mobileOfChina(string $str, $prefixSupport=true):bool{
        return $prefixSupport
                ? self::exec("/^((\+?0?86[\ \-]?)?1)[3-9]\d{9}$/", $str)
                : self::exec("/^1[3-9]\d{9}$/", $str);
    }

    /**
     * 验证大陆身份证号码
     *
     * @param string $str
     * @return boolean
     */
    static function idcardOfChina(string $str):bool{
        $pass = self::exec("/^(^[1-9]\d{7}((0[1-9])|(1[0-2]))(([0|1|2][1-9])|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0[1-9])|(1[0-2]))(([0|1|2][1-9])|3[0-1])((\d{4})|\d{3}[Xx])$)$/",$str);
        if($pass){
            $prov = [
                11 =>"北京",12 =>"天津",13 =>"河北",14 =>"山西",15 =>"内蒙古",21 =>"辽宁",22 =>"吉林",23 =>"黑龙江",31 =>"上海",32 =>"江苏",
                33 =>"浙江",34 =>"安徽",35 =>"福建",36 =>"江西",37 =>"山东",41 =>"河南",42 =>"湖北 ",43 =>"湖南",44 =>"广东",45 =>"广西",
                46 =>"海南",50 =>"重庆",51 =>"四川",52 =>"贵州",53 =>"云南",54 =>"西藏",61 =>"陕西",62 =>"甘肃",63 =>"青海",64 =>"宁夏",
                65 =>"新疆",71 =>"台湾",81 =>"香港",82 =>"澳门"//,91 =>"国外",
            ];
            $tip = "";
            if (!$str || !preg_match("/^[1-9]\d{16}(\d|X)$/i",$str) ) {
                // $tip = "身份证号格式错误";
                $pass = false;
            } else if (!isset($prov[ (substr($str, 0, 2)) ]) ) {
                // $tip = "地址编码错误";
                $pass = false;
            } else {
                //18位身份证需要验证最后一位校验位
                if (strlen($str) == 18) {
                    $str = str_split(str_replace('x', 'X',$str));
                    //∑(ai×Wi)(mod 11)
                    //加权因子
                    $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
                    //校验位
                    $parity = [1, 0, "X", 9, 8, 7, 6, 5, 4, 3, 2];
                    $sum = $ai = $wi = 0;
                    for ($i = 0; $i < 17; $i++) {
                        $ai = $str[$i];
                        $wi = $factor[$i];
                        $sum += $ai * $wi;
                    }
                    $last = $parity[ $sum % 11];
                    if ($parity[$sum % 11] != $str[17]) {
                        // $tip = "校验位错误";
                        $pass = false;
                    }
                }
            }
        }
        return $pass;
    }

    /**
     * 验证普通数字(兼容负数、小数)
     *
     * @param string $str
     * @return boolean
     */
    static function number(string $str):bool{
        return self::exec("/^(\-?\d+)(\.?\d+)?$/", $str);
    }

    /**
     * 验证日期（YYYY-MM-DD）
     *
     * @param string $str
     * @return boolean
     */
    static function date(string $str):bool{
        return self::exec("/^[1-3]\d{3}(\-|\/)?((0[1-9])|(1[0-2]))(\-|\/)?((0[1-9])|([1-2]\d)|(3[0-1]))$/", $str);
    }

    /**
     * 验证腾讯QQ号
     *
     * @param string $str
     * @return boolean
     */
    static function qqAccount(string $str):bool{
        return self::exec("/^[1-9]\d{4,11}$/",$str);
    }

    /**
     * 验证腾讯wechat账号
     *
     * @param string $str
     * @param integer $minLength
     * @param integer $maxLength
     * @return boolean
     */
    static function wechatAccount(string $str):bool{
        return self::mobileOfChina($str,false)
            || self::isEnChar($str, 6, 20)
            || self::isCnChar($str, 6, 20)
            || self::isChineseEnglishMixture($str, 6, 20);
    }

    /**
     * 验证支付宝账号
     *
     * @param string $str
     * @return boolean
     */
    static function alipayAccount(string $str):bool{
        return self::mobileOfChina($str,false) || self::email($str);
    }

    /**
     * 互联网通用账号规则（字母或中文开头）
     *
     * @param string $str
     * @param integer $minLength
     * @param integer $maxLength
     * @return boolean
     */
    static function webAccount(string $str, int $minLength=5, int $maxLength=18):bool{
        return self::isCnChar($str,$minLength, $maxLength)
            || self::isEnChar($str, $minLength, $maxLength)
            || self::isChineseEnglishMixture($str, $minLength,$maxLength);
    }
    /**
     * 验证url（|^[a-z]+://[^\s]*|i）
     *
     * @param string $str
     * @return boolean
     */
    static function isUrlRule(string $str):bool{
        return self::exec("/^[a-z]+://[^\s]*/i", $str);
    }

    /**
     * 验证url（http(s)?）
     *
     * @param string $str
     * @return boolean
     */
    static function isHttpUrl(string $str):bool{
        return self::exec("/^(?=^.{3,255}$)(http(s)?:\/\/)?(www\.)?[a-z0-9][-a-z0-9]{0,62}(\.[a-z0-9][-a-z0-9]{0,62})+(:([1-5]\d{0,4}|[6-9]\d{0,3}|6([0-4]]\d{3}|5[0-4]\d{2}|55[0-2]\d{2}|553[0-5])))?(\/[\w\.\-]+){1,62}(\/)?$/i",$str);
    }
    /**
     * 验证URL（?xx=xx&yy=）
     *
     * @param string $str
     * @return boolean
     */
    static function isHttpQueryUrl(string $str):bool{
        return self::exec("/^(?=^.{3,255}$)(http(s)?:\/\/)?(www\.)?[a-z0-9][-a-z0-9]{0,62}(\.[a-z0-9][-a-z0-9]{0,62})+(:([1-5]\d{0,4}|[6-9]\d{0,3}|6([0-4]]\d{3}|5[0-4]\d{2}|55[0-2]\d{2}|553[0-5])))?(\/[\w\.\-]+){1,62}(\/)?(\??(&\w+(=(\w+)?)?){1,48}|\?&?\w+(=(\w+)?)?(&\w+(=(\w+)?)?){1,47})$/i",$str);
    }

    /**
     * 验证英文域名合法性
     *
     * @param string $str
     * @return boolean
     */
    static function isDomain(string $str):bool{
        return self::exec("/^(?=^.{3,255}$)[a-z0-9][-a-z0-9]{0,62}(\.[a-z0-9][-a-z0-9]{0,62})+\.([a-z]{1,6})$/i", $str);
    }

    /**
     * 验证全部中文字符
     *
     * @param string $str
     * @param integer $minLength
     * @param integer $maxLength
     * @return boolean
     */
    static function isCnChar(string $str, int $minLength=5, int $maxLength=18):bool{
        self::parseMaxMinLength($minLength, $maxLength);
        return self::exec("/^[\u4e00-\u9fa5]{".$minLength.",".$maxLength."}$/", $str);
    }

    /**
     * 验证全部英文字符
     *
     * @param string $str
     * @param integer $minLength
     * @param integer $maxLength
     * @return boolean
     */
    static function isEnChar(string $str, int $minLength=5, int $maxLength=18):bool{
        self::parseMaxMinLength($minLength, $maxLength);
        return self::exec("/^[a-z][a-z0-9_\-]{".($minLength-1).",".($maxLength-1)."}$/i", $str);
    }

    /**
     * 汉英混合字符
     *
     * @param string $str
     * @param integer $minLength
     * @param integer $maxLength
     * @return boolean
     */
    static function isCnEnMixture(string $str, int $minLength=5,int $maxLength=18):bool{
        self::parseMaxMinLength($minLength, $maxLength);
        return self::exec("/^[a-z\u4e00-\u9fa5][a-z0-9_\-\u4e00-\u9fa5]{".($minLength-1).",".($maxLength-1)."}$/i", $str);
    }

    /**
     * 检测双字节字符
     * (包括汉字在内，可以用来计算字符串的长度(一个双字节字符长度计2，ASCII字符计1))
     *
     * @param string $str
     * @return boolean
     */
    static function isDoubleBitChar(string $str):bool{
        return self::exec("/[^\x00-\xff]{1,}/i",$str);
    }

    /**
     * 验证货币金额（支持定义小数位长度）
     *
     * -e.g: phpunit("Validate::isPrice", [mt_rand()]);
     * -e.g: phpunit("Validate::isPrice", [1.2]);
     * -e.g: phpunit("Validate::isPrice", [1]);
     * -e.g: phpunit("Validate::isPrice", [1.20]);
     * -e.g: phpunit("Validate::isPrice", [1.23]);
     * -e.g: phpunit("Validate::isPrice", [1.233,1,5]);
     * 
     * @param string $number
     * @param integer $minPointPlace <1> 最少小数长度 0.\d
     * @param integer $maxPointPlace <2> 最多小数长度 0.\d\d
     * @return boolean
     */
    static function isPrice(string $number, int $minPointPlace=1, int $maxPointPlace=2):bool{

        if($maxPointPlace <= 0){
            return self::exec("/^([0-9]+|[0-9]{1,3}(,[0-9]{3})*)$/",$number);
        }
        if($maxPointPlace < $minPointPlace){
            $maxPointPlace = $minPointPlace;
        }
        return self::exec("/^([0-9]+|[0-9]{1,3}(,[0-9]{3})*)(.[0-9]{".$minPointPlace.",".$maxPointPlace."})?$/",$number);
    }

    /**
     * 验证Ipv4
     *
     * ipv4("192.168.0.033") 		-> bool(false)
     *
     * -- 私有IP不通过
     * ipv4("192.168.0.33",true) 	-> bool(false)
     *
     * @param string $ip
     * @param boolean $excludePrivIp 排除私有IP范围内 <false>
     * @param boolean $excludeResIp 排除保留IP范围内 <false>
     * @return string|boolean
     */
    static function ipv4(string $ip, bool $excludePrivIp = false, bool $excludeResIp=false){
        return self::isIP(FILTER_FLAG_IPV4, $ip, $excludePrivIp, $excludeResIp);
    }

    /**
     * 验证Ipv6
     *
     * ipv6("2001:0db8:85a3:1319:8a2e:0370:7330") -> 2001:...:7330
     *
     * -- 16进制越界 -> bool(false)
     * ipv6("2001:0db8:85a3:1319:8a2g:0370:7330") -> bool(false)
     *
     * @param string $ip
     * @param boolean $excludePrivIp 排除私有IP范围内 <false>
     * @param boolean $excludeResIp 排除保留IP范围内 <false>
     * @return string|boolean
     */
    static function ipv6(string $ip, bool $excludePrivIp = false, bool $excludeResIp=false){
        return self::isIP(FILTER_FLAG_IPV6, $ip, $excludePrivIp, $excludeResIp);
    }

    /**
     * 判断是否为手机端
     *
     * @return boolean
     */
    static function isMobileDevice():bool{
        $result = false;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot','samsung', 'htc', 'sgh', 'lg', 'sharp',
                'sie-', 'philips', 'panasonic', 'alcatel','meizu', 'android', 'netfront', 'symbian',
                'ucweb', 'windowsce', 'palm', 'operamini','operamobi', 'openwave', 'nexusone', 'cldc','midp', 'wap', 'mobile'
            );
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                $result = true;
            }
        }
        return $result;
    }

    const HYPHENS = ['‐', '-', ' ']; // regular dash, authentic hyphen (rare!) and space

    /**
     * IMEI验证
     * -- LUHN算法
     * 
     * @param mixed $imei
     *
     * @return bool
     */
    static function imei($imei){
        $imei = self::unDecorate($imei, ['‐', '-', ' ']);
        // for IMEI only; IMEISV = EMEI+1, and not Luhn check
        $length = 15;
        // IMEISV?
        if ($length + 1 === strlen($imei)) {
            $expr = sprintf('/\\d{%d}/i', $length + 1);

            return boolval(preg_match($expr, $imei));
        }
        // IMEI?
        return self::Luhn($imei, $length, 2, 10);
    }

    /**
     * 验证境外信用卡
     *
     * -e.g: $number = \Vipkwd\Utils\Type\Random::creditCardNumber();
     * -e.g: phpunit("Validate::creditCard", [$number]);
     * -e.g: phpunit("Validate::creditCardValid", [$number]);
     * 
     * @param string $creditCard
     *
     * @return bool
     */
    static function creditCard(string $number):bool{
        if ('' === trim($number)) {
            return false;
        }
        if (!boolval(preg_match('/.*[1-9].*/', $number))) {
            return false;
        }
        //longueur de la chaine $number
        $length = strlen($number);
        //resultat de l'addition de tous les chiffres
        $tot = 0;
        for ($i = $length - 1; $i >= 0; --$i) {
            $digit = substr($number, $i, 1);
            if ((($length - $i) % 2) == 0) {
                $digit = (int) $digit * 2;
                ($digit > 9) && $digit = $digit - 9;
            }
            $tot += (int) $digit;
        }
        return ($tot % 10) == 0;
        /*
            $number = (string) $number;
            $length = strlen($number);
            $sum = 0;
            for ($i = $length - 1; $i >= 0; $i -= 2) {
                $sum += $number[$i];
            }
            for ($i = $length - 2; $i >= 0; $i -= 2) {
                $sum += array_sum(str_split( strval( $number[$i] * 2))) ;
            }
            return ($sum % 10) == 0;
        */

    }

    /**
     * MAC 地址验证
     *
     * Could be separated by hyphens or colons.
     * Could be both lowercase or uppercase letters.
     * Mixed upper/lower cases and hyphens/colons are not allowed.
     *
     * @see http://en.wikipedia.org/wiki/MAC_address#Notational_conventions
     *
     * @param string $macAddr
     *
     * @return bool
     */
    static function macAddr($macAddr):bool{
        $pattern = '/^(([a-f0-9]{2}-){5}[a-f0-9]{2}|([A-F0-9]{2}-){5}[A-Z0-9]{2}|([a-f0-9]{2}:){5}[a-z0-9]{2}|([A-F0-9]{2}:){5}[A-Z0-9]{2})$/';
        return boolval(preg_match($pattern, $macAddr));
    }

    /**
     * 执行自定义正则
     *
     * @param string $regexp
     * @param string $str
     *
     * @return boolean
     * @throws Exception
     */
    static function exec(string $regexp, string $str){
        $str = urldecode($str);
        try{
            return preg_match($regexp, $str) ? true : false;
        }catch(\Exception $e){
            //throw new \Exception($e->getMessage());
            return false;
        }
    }

    /**
     * Luhn算法通用验证器
     *
     * @param string $value
     * @param integer $length
     * @param integer $weight
     * @param integer $divider
     * @param array $hyphens
     * @return boolean
     */
    static function luhn(string $value, int $length, int $weight, int $divider, array $hyphens = ['‐', '-', ' ']): bool{
        $value = self::unDecorate($value, $hyphens);
        $digits = substr($value, 0, $length - 1);
        $check = substr($value, $length - 1, 1);
        $expr = sprintf('/\\d{%d}/i', $length);
        if (!preg_match($expr, $value)) {
            return false;
        }
        $sum = 0;
        $len = strlen($digits);
        for ($i = 0; $i < $len; ++$i) {
            if (0 === $i % 2) {
                $add = (int) substr($digits, $i, 1);
            } else {
                $add = $weight * (int) substr($digits, $i, 1);
                if (10 <= $add) { // '18' = 1+8 = 9, etc.
                    $strAdd = strval($add);
                    $add = intval($strAdd[0]) + intval($strAdd[1]);
                }
            }
            $sum += $add;
        }
        return 0 === ($sum + $check) % $divider;
    }

    /**
     * 验证是否为合法IP
     *
     *  FILTER_FLAG_NO_PRIV_RANGE
     *    	无法验证以下私有IPv4范围：10.0.0.0/8 , 172.16.0.0/12 和 192.168.0.0/16
     * 		无法验证从FD或FC开始的IPv6地址。
     *  FILTER_FLAG_NO_RES_RANGE
     *    	无法验证以下保留的IPv4范围：0.0.0.0/8 , 169.254.0.0/16 , 127.0.0.0/8 和 240.0.0.0/4
     *
     *
     * @param mixed $ipFlag  FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4
     * @param string $ip
     * @param boolean $excludePrivIp 排除私有IP范围内 <false>
     * @param boolean $excludeResIp 排除保留IP范围内 <false>
     * @return string|boolean
     */
    private static function isIP($ipFlag, string $ip, bool $excludePrivIp = false, bool $excludeResIp=false){
        $status = filter_var($ip, FILTER_VALIDATE_IP, $ipFlag);
        if($status !== false && $excludePrivIp === true){
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
        }else if($status !== false && $excludeResIp === true){
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
        }
        return $status;
    }


    private static function parseMaxMinLength(int &$minLength, int &$maxLength, int $prefixLength=0){
        $prefixLength < 0 && $prefixLength = 0;
        if($minLength < $prefixLength)
            $minLength = $prefixLength;
        if($maxLength < $minLength){
            $maxLength = $minLength;
        }
        if($prefixLength){
            $minLength-=$prefixLength;
            $maxLength-=$prefixLength;
        }
    }

    /**
     * @param mixed $input: null or string
     */
    private static function unDecorate($input, array $hyphens = []):string{
        $hyphensLength = count($hyphens);
        // removing hyphens
        for ($i = 0; $i < $hyphensLength; ++$i) {
            $input = str_replace($hyphens[$i], '', $input);
        }
        return $input;
    }
}
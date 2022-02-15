<?php
/**
 * @name 证件号码
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use \Exception;
use \Closure;
use Vipkwd\Utils\Tools;
class Idcard {
    // https://blog.csdn.net/claram/article/details/104271307

	/**
     * 中国公民身份证号码最小长度
     */
	const CHINA_ID_MIN_LENGTH = 15;
	/**
     * 中国公民身份证号码最大长度
     */
	const CHINA_ID_MAX_LENGTH = 18;
	/**
     * 最低年限
     */
	const MIN = 1930;
	/**
     * 省、直辖市代码表
     */
	static $cityCode = array ("11","12","13","14","15","21","22","23","31","32","33","34","35","36","37","41","42","43","44","45","46","50","51","52","53","54","61","62","63","64","65","71","81","82","91" );
	/**
     * 每位加权因子
     */
	static $power = array (7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2 );
	/**
     * 第18位校检码
     */
	static $verifyCode = array ("1","0","X","9","8","7","6","5","4","3","2" );
	/**
     * 国内身份证校验
     */
	static $cityCodes = array ("11"=>"北京","12"=>"天津","13"=>"河北","14"=>"山西","15"=>"内蒙古","21"=>"辽宁","22"=>"吉林","23"=>"黑龙江","31"=>"上海","32"=>"江苏","33"=>"浙江","34"=>"安徽","35"=>"福建","36"=>"江西","37"=>"山东","41"=>"河南","42"=>"湖北","43"=>"湖南","44"=>"广东","45"=>"广西","46"=>"海南","50"=>"重庆","51"=>"四川","52"=>"贵州","53"=>"云南","54"=>"西藏","61"=>"陕西","62"=>"甘肃","63"=>"青海","64"=>"宁夏","65"=>"新疆","71"=>"台湾","81"=>"香港","82"=>"澳门","91"=>"国外");

    static $twCityCodes = array("A" => ["台北市", 10], "B" => ["台中市", 11], "C" => ["基隆市", 12], "D" => ["台南市", 13], "E" => ["高雄市", 14], "F" => ["台北县", 15], "G" => ["宜兰县", 16], "H" => ["桃园县", 17], "I" => ["嘉义市", 34], "J" => ["新竹县", 18], "K" => ["苗栗县", 19], "L" => ["台中县", 20], "M" => ["南投县", 21], "N" => ["彰化县", 22], "O" => ["新竹市", 35], "P" => ["云林县", 23], "Q" => ["嘉义县", 24], "R" => ["台南县", 25], "S" => ["高雄县", 26], "T" => ["屏东县", 27], "U" => ["花莲县", 28], "V" => ["台东县", 29], "W" => ["金门县", 32], "X" => ["澎湖县", 30], "Y" => ["阳明山管理局", 31], "Z" => ["连江县", 33]);
    
    static $hkCityCodes = array("A"=>1,"B"=>2,"C"=>3,"D"=>4,"E"=>5,"F"=>6,"G"=>7,"H"=>8,"I"=>9,"J"=>10,"K"=>11,"L"=>12,"M"=>13,"N"=>14,"O"=>15,"P"=>16,"Q"=>17,"R"=>18,"S"=>19,"T"=>20,"U"=>21,"V"=>22,"W"=>23,"X"=>24,"Y"=>25,"Z"=>26);
	
    /**
     * 升级15位号码为18位
     * 
     * @param idCard 15位身份编码
     * @return 18位身份编码
     */
	static function conver15CardTo18($idCard) {
		$idCard18 = "";
		if (strlen ( $idCard ) != self::CHINA_ID_MIN_LENGTH) {
			return null;
		}
		if (self::isNum ( $idCard )) {
			// 获取出生年月日
            $sYear = '19' . substr ( $idCard, 6, 2 );
            $idCard18 = substr ( $idCard, 0, 6 ) . $sYear . substr ( $idCard, 8 );
            // 转换字符数组
            $iArr = str_split ($idCard18);
            if ($iArr != null) {
                $iSum17 = self::getPowerSum ( $iArr );
                // 获取校验位
                $sVal = self::getCheckCode18 ( $iSum17 );
                if (strlen ( $sVal ) > 0) {
                    $idCard18 .= $sVal;
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }
        return $idCard18;
    }
    
    /**
     * 验证身份证是否合法
     */
    static function validateIdCard($idCard) {
        $card = trim ( $idCard );
        if (self::validateIdCard18 ( $card )) {
            return true;
        }
        if (self::validateIdCard15 ( $card )) {
            return true;
        }

        if (($card = self::validateIdCard10 ($card))) {
            return $card[2] ? true :false;
        }
        return false;
    }
    
    /**
     * 验证-18位身份证号
     * -- 大陆居民身份证
     * -- 港澳台居民居住证
     * @param int $idCard 身份编码
     * @return boolean 是否合法
     */
    static function validateIdCard18($idCard) {

        if (strlen ( $idCard ) == self::CHINA_ID_MAX_LENGTH) {
            // 前17位
            $code17 = substr ( $idCard, 0, 17 );
            // 第18位
            $code18 = substr ( $idCard, 17, 1 );
            if (self::isNum ( $code17 )) {
                $iArr = str_split ( $code17 );
                if ($iArr != null) {
                    $iSum17 = self::getPowerSum ( $iArr );
                    // 获取校验位
                    $val = self::getCheckCode18 ( $iSum17 );
                    if (strlen ( $val ) > 0 && $val == $code18) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * 验证-15位身份证号
     * @param string $idCard 身份编码
     * @return boolean 是否合法
     */
    static function validateIdCard15($idCard) {
        if (strlen ( $idCard ) != self::CHINA_ID_MIN_LENGTH) {
            return false;
        }
        if (self::isNum ( $idCard )) {
            $proCode = substr ( $idCard, 0, 2 );
            if (! isset ( self::$cityCodes [$proCode] )) {
                return false;
            }
            //升到18位
            $idCard = self::conver15CardTo18($idCard);
            return self::validateIdCard18($idCard);
        } else {
            return false;
        }
        return true;
    }

    /**
     * 验证-台湾身份证号码
     * 
     * 首位数字(号码第2位)代表性别，男性为1、女性为2；最后一位数字是检验码
     * 通算值= 首字母对应的第一位验证码+ 首字母对应的第二位验证码 * 9 + 性别码 * 8 + 第二位数字 * 7 + 第三位数字 * 6 + 第四位数字 * 5 + 第五位数字 * 4 + 第六位数字 * 3 + 第七位数字 * 2 + 第八位数字 * 1
     * 最后一位数 =10- 通算值的末尾数。
     * 例如，A234567893，A对应的验证码是10，最后一位数是3。
     * 通算值= 1 + 0*9 + 2*8 + 3*7 + 4*6 + 5*5 + 6*4 + 7*3 + 8*2 + 9*1 = 157，通算值的末尾数是7。则10-7=3，与最后一位数（验证码）相同，身份证号码正确。
     * 反之，A234567890的最后一位是0，就不是有效字号。
     * 
     * @param string $idCard 身份证号码
     * @return bool 验证码是否符合
     */
    static function validateTWCard(string $idCard):bool{
        if(!preg_match("/^[A-Z](1|2)[0-9]{8}$/i", $idCard)){
            return false;
        }
        $start = substr($idCard, 0, 1);
        $mid = substr($idCard, 1);
        $end = substr($idCard, -1);
        $iStart = self::$twCityCodes[$start];
        $sum = intval($iStart[1] / 10) + ($iStart[1] % 10) * 9;
        $chars = str_split($mid);
        $iflag = 8;
        foreach ($chars as $c) {
            $sum += intval($c) * $iflag;
            $iflag --;
        }
        unset($start, $mid, $iStart, $chars, $iflag, $c);
        return ($sum % 10 == 0 ? -1 : (10 - $sum % 10)) == intval($end);
    }

    /**
     * 验证-香港身份证号码
     * <p>
     * 身份证前2位为英文字符，如果只出现一个英文字符则表示第一位是空格，对应数字58 前2位英文字符A-Z分别对应数字10-35
     * 最后一位校验码为0-9的数字加上字符"A"，"A"代表10
     * </p>
     * <p>
     * 将身份证号码全部转换为数字，分别对应乘9-1相加的总和，整除11则证件号码有效
     * </p>
     *
     * @param string $idCard 身份证号码
     * @return bool 验证码是否符合
     */
    static function validateHKCard(string $idCard):bool{
        $card = str_replace(['(',')',' '], '', $idCard);

        if(!preg_match("/^[A-Z]{1,2}[0-9]{6}[0-9A]$/i",$card)){
            return false;
        }
        $first = substr($card,0,1);
        $second= substr($card,1,1);
        if(in_array($second, array_keys(self::$hkCityCodes))){
            $sum = (ord($first) - 55) * 9 + (ord($second) - 55) * 8;
            $card = substr($card,1);
        }else{
            $sum = 522 + (ord($first) - 55) * 8;
        }
        
        $mid = substr($card, 1, 6);
        $end = substr($card, -1);
        $chars = str_split($mid);
        $iflag = 7;
        foreach ($chars as $c) {
            $sum += intval($c) * $iflag;
            $iflag--;
        }
        if ( strtolower($end) == "a") {
            $sum += 10;
        } else {
            $sum += intval($end);
        }
        return ($sum % 11) == 0;
    }

    /**
     * 验证 -澳门身份证号码
     *
     * @param string $idCard
     * @return boolean
     */
    static function validateMacaoCard(string $idCard):bool{
        $card = str_replace(['(',')',' ','/'], '', $idCard);
        if(!preg_match("/^[157][0-9]{6}[0-9]$/i",$card)){
            return false;
        }
        //TODO 校验
        return true;
    }


    /**
     * 港澳居民 -来往内地通行证
     *
     * @param string $idCard
     * @return boolean
     */
    static function validatePmHK(string $idCard):bool{
        return preg_match("/^[H|M][0-9]{10}$/i");
    }

    /**
     * 台湾居民 -来往大陆通行证
     * -- 简称电子台胞证、台胞卡
     *
     * @param string $idCard
     * @return boolean
     */
    static function validatePmTW(string $idCard):bool{
        return preg_match("/^[1-9][0-9]{7}$/i");
    }

    /**
     * 大陆居民 -往来台湾通行证
     *
     * @param string $idCard
     * @return boolean
     */
    static function validatePmCN(string $idCard):bool{
        return preg_match("/^[LT][0-9]{8}$/i");
    }

    /**
     * 获取年龄
     * 
     * -e.g: phpunit("Idcard::getAgeByIdCard", ["612426198901165783"]);
     * -e.g: phpunit("Idcard::getAgeByIdCard", ["441381199908191520"]);
     * 
     * @param string idCard 身份编号
     * @return int
     */
    static function getAgeByIdCard($idCard) {
        $iAge = 0;
        self::IDFixes($idCard);
        if (self::validateIdCard($idCard)){   
            $year = substr ( $idCard, 6, 4 );
            $iCurrYear = date ( 'Y', time () );
            $iAge = $iCurrYear - $year;
        }
        return $iAge;
    }
    
    /**
     * 获取性别
     * 
     * -e.g: phpunit("Idcard::getGenderByIdCard", ["612426198901165783"]);
     * -e.g: phpunit("Idcard::getGenderByIdCard", ["441381199908191520"]);
     * 
     * @param string $idCard 身份编号
     * @return string 性别(M-男，F-女，N-未知)
     */
    static function getGenderByIdCard($idCard):string{
        self::IDFixes($idCard);
        $sGender = "N";
        if (self::validateIdCard($idCard)){
            $sCardNum = substr ( $idCard, 16, 1 );
            if (( int ) $sCardNum % 2 != 0) {
                $sGender = "M";
            } else {
                $sGender = "F";
            }   
        }
        return $sGender;
    }

    /**
     * 获取户籍省份
     * 
     * -e.g: phpunit("Idcard::getProvinceByIdCard", ["612426198901165783"]);
     * -e.g: phpunit("Idcard::getProvinceByIdCard", ["441381199908191520"]);
     * 
     * @param string $idCard 身份编号
     * @return string
     */
    static function getProvinceByIdCard(string $idCard):string{
        self::IDFixes($idCard);
        if (! self::validateIdCard($idCard)) return "";

        $len = strlen ( $idCard );
        $sProvince = null;
        $sProvinNum = "";
        if ($len == self::CHINA_ID_MIN_LENGTH || $len == self::CHINA_ID_MAX_LENGTH) {
            $sProvinNum = substr ( $idCard, 0, 2 );
        }
        $sProvince = self::$cityCodes[$sProvinNum];
        return $sProvince;
    }

    /**
     * 获取生日
     *
     * -e.g: phpunit("Idcard::getBirthByIdCard", ["612426198901165783"]);
     * -e.g: phpunit("Idcard::getBirthByIdCard", ["441381199908191520"]);
     * 
     * @param string $idCard 身份编号
     * @return string (xxxx-xx-xx)
     */
    static function getBirthByIdCard(string $idCard):string{
        self::IDFixes($idCard);
        if (! self::validateIdCard($idCard)) return "";

        $chars = str_split(substr($idCard,6, 8),2);
        return implode('-', [$chars[0].$chars[1], $chars[2], $chars[3]]);
    }
    
    /**
     * 获取星座
     *
     * -e.g: phpunit("Idcard::getConstellationById", ["612426198901165783"]);
     * -e.g: phpunit("Idcard::getConstellationById", ["441381199908191520"]);
     * 
     * @param string $idCard 身份证号码
     * @return string
     */
    static function getConstellationById(string $idCard):string{
        self::IDFixes($idCard);
        if (! self::validateIdCard($idCard)) return "";
        return Lunar::getConstellation( self::getBirthByIdCard($idCard));
    }

    /**
     * 获取生肖
     *
     * -e.g: phpunit("Idcard::getZodiacById", ["612426198901165783"]);
     * -e.g: phpunit("Idcard::getZodiacById", ["441381199908191520"]);
     * 
     * @param string $idCard 身份证号码
     * @return string 生肖
     */
    static function getZodiacById(string $idCard):string{
        self::IDFixes($idCard);
        if (! self::validateIdCard($idCard)) return "";
        return Lunar::getYearZodiac(self::getBirthByIdCard($idCard));
    }

    /**
     * 获取干支
     * 
     * -e.g: phpunit("Idcard::getChineseEraById", ["612426198901165783"]);
     * -e.g: phpunit("Idcard::getChineseEraById", ["441381199908191520"]);
     * 
     * @param string $idCard
     * @return string 干支
     */
    static function getChineseEraById(string $idCard):string{
        self::IDFixes($idCard);
        if (! self::validateIdCard($idCard)) return "";
        return Lunar::getYearGZ(self::getBirthByIdCard($idCard));
    }

    /**
     * 数字验证
     * 
     * @param int $val
     * @return bool
     */
    private static function isNum($val):bool{
        return ($val == null || $val == "") ? false : (0 < preg_match ( '/^[0-9]*$/', $val ));
    }
    
    /**
     * 验证小于当前日期 是否有效
     * 
     * @param int $iYear 待验证日期(年)
     * @param int $iMonth 待验证日期(月 1-12)
     * @param int $iDate 待验证日期(日)
     * @return bool 是否有效
     */
    private static function valiDate(int $iYear, int $iMonth, int $iDate):bool{
        $year = date ( 'Y', time () );
        if ($iYear < self::MIN || $iYear >= $year) {
            return false;
        }
        if ($iMonth < 1 || $iMonth > 12) {
            return false;
        }
        switch ($iMonth) {
            case 4 :
            case 6 :
            case 9 :
            case 11 :
                $datePerMonth = 30;
                break;
            case 2 :
                $dm = (($iYear % 4 == 0 && $iYear % 100 != 0) || ($iYear % 400 == 0)) && ($iYear > self::MIN && $iYear < $year);
                $datePerMonth = $dm ? 29 : 28;
                break;
            default :
                $datePerMonth = 31;
        }
        return ($iDate >= 1) && ($iDate <= $datePerMonth);
    }



    /**
     * 将身份证的每位和对应位的加权因子相乘之后，再得到和值
     * 
     * @param array $iArr
     * @return int
     */
    private static function getPowerSum($iArr):int{
        $iSum = 0;
        $power_len = count ( self::$power );
        $iarr_len = count ( $iArr );
        if ($power_len == $iarr_len) {
            for($i = 0; $i < $iarr_len; $i ++) {
                for($j = 0; $j < $power_len; $j ++) {
                    if ($i == $j) {
                        $iSum = $iSum + $iArr [$i] * self::$power [$j];
                    }
                }
            }
        }
        return $iSum;
    }

    /**
     * 将power和值与11取模获得余数进行校验码判断
     * 
     * @param int $iSum
     * @return string 校验位
     */
    private static function getCheckCode18($iSum):string{
        $sCode = "";
        switch ($iSum % 11) {
            case 10 :
                $sCode = "2";
                break;
            case 9 :
                $sCode = "3";
                break;
            case 8 :
                $sCode = "4";
                break;
            case 7 :
                $sCode = "5";
            break;
            case 6 :
                $sCode = "6";
                break;
            case 5 :
                $sCode = "7";
                break;
            case 4 :
                $sCode = "8";
                break;
            case 3 :
                $sCode = "9";
                break;
            case 2 :
                $sCode = "x";
                break;
            case 1 :
                $sCode = "0";
                break;
            case 0 :
                $sCode = "1";
                break;
        }
        return $sCode;
    }

    /**
     * 验证10位身份编码是否合法
     * 
     * [0] - 台湾、澳门、香港 [1] - 性别(男M,女F,未知N) [2] - 是否合法(合法true,不合法false)
     *
     * @param idCard
     * @return array 信息数组
     */
    private function validateIdCard10(string $idCard):array{
        $idCard = trim($idCard);
        $card = str_replace(['(','/',')'], "", $idCard);
        if(strlen($card) < 8 || strlen($card) > 10){
            return null;
        }
        $res = ['-','N',false];
        if( true === ($res[2] = self::validateTWCard($card))){
            $res[0] = "tw";
            $res[1] = substr($card,1,1) == "1" ? "M" : "F";
        }else if( true === ($res[2] = self::validateMacaoCard($card))){
            $res[0] = "macao";
        }else if( true === ($res[2] = self::validateHKCard($card))){
            $res[0] = "hk";
        }
        return $res;
    }

    private static function IDFixes(string &$idCard):string{
        $len = strlen($idCard);
        if ($len < self::CHINA_ID_MIN_LENGTH){
            $idCard = "";
            return null;
        } else if ($len == self::CHINA_ID_MIN_LENGTH) {
            $idCard = self::conver15CardTo18($idCard);
        }
        return $idCard;
    }
}
<?php
/**
 * @name 构建各类有意义的随机数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\{Libs\RandomName,Tools};

class Random {

    /**
     * 构建一个随机浮点数
     * 
     * -e.g: phpunit("Random::float");
     * -e.g: phpunit("Random::float",[0,5,0]);
     * -e.g: phpunit("Random::float",[0,5,1]);
     * -e.g: phpunit("Random::float",[0,5,4]);
     * -e.g: phpunit("Random::float",[0,5,6]);
     * 
     * @param integer $min
     * @param integer $max
     * @param integer $decimal <0> 小数位数
     * @return float
     */
    static function float(int $min = -999999999, int $max = 999999999, int $decimal = 10): float {
        if($max < $min){
            throw new Exception("mathRandom(): max({$max}) is smaller than min({$min}).");
        }
        $range = mt_rand($min, $max);
        if($decimal > 0){
            $_ = lcg_value(); 
            while($_ < 0.1){
                $_ *= 10;
            }
            $range += floatval(substr( "$_". str_pad("0", $decimal, "0"),0, $decimal+2));
            if($range > $max){
                $range -=1;
            }
        }
        return floatval($range);
    }

    /**
     * 获取随机的时间
     * 
     * -e.g: phpunit("Random::date");
     * -e.g: phpunit("Random::date", ["Y-m-d H:i:s"]);
     * -e.g: phpunit("Random::date", ["Y-m-d H:i"]);
     * -e.g: phpunit("Random::date", ["Y/m/d H:i:s"]);
     * 
     * @param string $format PHP的时间日期格式化字符
     * @return false|string
     */
    static function date(string $format = 'Y-m-d H:i:s'): string {
        $timestamp = time() - mt_rand(0, 86400 * 3650);
        return date($format, $timestamp);
    }

    /**
     * 构建随机IP地址
     * 
     * -e.g: phpunit("Random::ip");
     * -e.g: phpunit("Random::ip");
     * 
     * @return string
     */
    static function ip(): string {
        $ipLong = [
            [607649792, 608174079], // 36.56.0.0-36.63.255.255
            [1038614528, 1039007743], // 61.232.0.0-61.237.255.255
            [1783627776, 1784676351], // 106.80.0.0-106.95.255.255
            [2035023872, 2035154943], // 121.76.0.0-121.77.255.255
            [2078801920, 2079064063], // 123.232.0.0-123.235.255.255
            [-1950089216, -1948778497], // 139.196.0.0-139.215.255.255
            [-1425539072, -1425014785], // 171.8.0.0-171.15.255.255
            [-1236271104, -1235419137], // 182.80.0.0-182.92.255.255
            [-770113536, -768606209], // 210.25.0.0-210.47.255.255
            [-569376768, -564133889], // 222.16.0.0-222.95.255.255
        ];
        $randKey = mt_rand(0, 9);
        return $ip = long2ip(mt_rand($ipLong[$randKey][0], $ipLong[$randKey][1]));
    }

    /**
     * 随机生成一个 URL 协议
     * 
     * -e.g: phpunit("Random::protocol");
     * -e.g: phpunit("Random::protocol");
     * 
     * @return string
     */
    static function protocol(): string {
        $proArr = [ 'http', 'ftp', 'gopher', 'mailto', 'mid', 'cid', 'news', 'nntp', 'prospero', 'telnet', 'rlogin', 'tn3270', 'wais' ];
        shuffle($proArr);
        return $proArr[0];
    }

    /**
     * 随机生成一个顶级域名
     * 
     * -e.g: phpunit("Random::tld");
     * -e.g: phpunit("Random::tld");
     * 
     * @return string
     */
    static function tld(): string {
        $tldArr = [
            'com', 'cn', 'xin', 'net', 'top', '在线',
            'xyz', 'wang', 'shop', 'site', 'club', 'cc',
            'fun', 'online', 'biz', 'red', 'link', 'ltd',
            'mobi', 'info', 'org', 'edu', 'com.cn', 'net.cn',
            'org.cn', 'gov.cn', 'name', 'vip', 'pro', 'work',
            'tv', 'co', 'kim', 'group', 'tech', 'store', 'ren',
            'ink', 'pub', 'live', 'wiki', 'design', '中文网',
            '我爱你', '中国', '网址', '网店', '公司', '网络', '集团', 'app'
        ];
        shuffle($tldArr);
        return $tldArr[0];
    }

    /**
     * 获取一个随机的域名
     * 
     * -e.g: phpunit("Random::domain");
     * -e.g: phpunit("Random::domain");
     * 
     * @return string
     */
    static function domain(): string {
        $len = mt_rand(6, 16);
        return strtolower(self::code($len,false)) . '.' . self::tld();
    }

    /**
     * 随机生成一个URL
     * 
     * -e.g: phpunit("Random::url");
     * -e.g: phpunit("Random::url");
     * 
     * @param string $protocol <""> 协议名称
     * @return string
     */
    static function url(string $protocol = ''): string {
        $protocol = $protocol ? $protocol : self::protocol();
        return $protocol . '://' . self::domain();
    }

    /**
     * 随机生成一个邮箱地址
     * 
     * -e.g: phpunit("Random::email");
     * -e.g: phpunit("Random::email");
     * 
     * @param string $domain <""> 可以指定邮箱域名
     * @return string
     */
    static function email(string $domain = ''): string {
        $len = mt_rand(6, 16);
        $domain = $domain ? $domain : self::domain();
        return strtolower(self::code($len,false)) . '@' . $domain;
    }

    /**
     * 随机生成一个大陆手机号
     * 
     * -e.g: phpunit("Random::mobilePhone");
     * -e.g: phpunit("Random::mobilePhone");
     * 
     * @return string
     */
    static function mobilePhone(): string {
        $prefixArr = [13,14,15,16,17,18,19];
        shuffle($prefixArr);
        return $prefixArr[0] . self::code(9, true);
    }

    /**
     * 随机创建一个身份证号码
     * 
     * -e.g: phpunit("Random::idcard");
     * -e.g: phpunit("Random::idcard");
     * 
     * @return string
     */
    static function idcard(bool $validate = false): string {
        $id = Idcard::createIdCard18();
        if($validate){
            if(!Validate::idcardOfChina($id)){
                return self::idcard(true);
            }
        }
        return $id;
    }

    /**
     * 生成随机密码
     * 
     * -e.g: phpunit("Random::password");
     * -e.g: phpunit("Random::password", [15]);
     * -e.g: phpunit("Random::password", [14, false]);
     * 
     * @param integer $maxLen <16> 生成的密码长度
     * @param boolean $specialChar <true> 是否包含特殊字符
     * @return string
     * 
     */
    static function password(int $maxLen = 16, bool $specialChar = true):string{
        $default = self::code(62,false);
        $specialChar && $default.= "`!\"?$?%^&*()_-+={[}]:;@'~#|\<,./>";
        $password = "";
        $len = strlen($default);
        while($maxLen--){
            $password .= substr(str_shuffle($default), mt_rand(0, $len-1), 1);
        }
        return $password;
    }

    /**
     * 随机生成简体字
     * 
     * -e.g: phpunit("Random::zhChar");
     * -e.g: phpunit("Random::zhChar",[4]);
     * -e.g: phpunit("Random::zhChar",[2]);
     * 
     * @param int $length <0>
     * @return string
     */
    static function zhChar(int $length=0): string
    {
        $s = '';
        for ($i = 0; $i < $length; $i++) {
            // 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
            $a = chr(mt_rand(0xB0, 0xD0)) . chr(mt_rand(0xA1, 0xF0));
            // 转码
            $s .= @iconv('GB2312', 'UTF-8', $a);
        }
        return $s;
    }

    /**
     * 生成随机字符(验证码)
     *
     * -e.g: phpunit("Random::code");
     * -e.g: phpunit("Random::code");
     * -e.g: phpunit("Random::code",[1]);
     * -e.g: phpunit("Random::code",[4]);
     * -e.g: phpunit("Random::code",[5]);
     * -e.g: phpunit("Random::code",[5,true]);
     * -e.g: phpunit("Random::code",[5,true]);
     * 
     * @param integer $len
     * @param boolean $onlyDigit <false> 是否纯数字，默认包含字母
     * @return string
     */
    static function code(int $len = 6, bool $onlyDigit = false):string{
        $char = '1234567890';
		if ($onlyDigit === false) {
			$char .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		}
        $char = str_repeat($char, $len);
        $_len = ($len % 3)+1;
        while($_len--){
            $char = str_shuffle($char);
        }
		return substr($char, mt_rand(0, strlen($char)-$len-1), $len);
	}


    /**
     * 随机生成马甲昵称
     *
     * -e.g: phpunit("Random::nickName");
     * -e.g: phpunit("Random::nickName");
     * -e.g: phpunit("Random::nickName");
     * 
     * @return string
     */
    static function nickName():string{
        return RandomName::getNickName();
    }

    /**
     * 随机生成女名
     *
     * -e.g: phpunit("Random::femaleName");
     * -e.g: phpunit("Random::femaleName",[false]);
     * 
     * @param boolean $surName <true> 是不包含复姓，如“上官” “司马”
     * @return string
     */
    static function femaleName(bool $surName = true):string{
        return RandomName::getFemaleName($surName);
    }   

    /**
     * 随机生成男名
     * 
     * -e.g: phpunit("Random::maleName");
     * -e.g: phpunit("Random::maleName",[false]);
     *
     * @param boolean $surName <true> 是否包含复姓，如“上官” “司马”
     * @return string
     */
    static function maleName(bool $surName = true):string{
        return RandomName::getMaleName($surName);
    }

}

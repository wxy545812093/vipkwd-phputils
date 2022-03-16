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
     * 随机构建IPv4地址
     * 
     * -e.g: phpunit("Random::ipv4");
     * -e.g: phpunit("Random::ipv4");
     * 
     * @return string
     */
    static function ipv4(): string {
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

        // return long2ip(mt_rand(0, 1) == 0 ? mt_rand(-2147483648, -2) : mt_rand(16777216, 2147483647));
    }

    /**
     * 随机构建内网ipv4
     *
     * -e.g: phpunit("Random::localIpv4");
     * -e.g: phpunit("Random::localIpv4");
     * -e.g: phpunit("Random::localIpv4",[null]);
     * -e.g: phpunit("Random::localIpv4",[10]);
     * -e.g: phpunit("Random::localIpv4",[192]);
     * -e.g: phpunit("Random::localIpv4",[192]);
     * 
     * @param integer|null $point <null> [10|192|null]
     * 
     * @return string
     */
    static function localIpv4(?int $point = null):string{
        ($point != 10 && $point != 192) && $point = mt_rand(10,11);

        if ( ($point % 10) === 0) {
            // 10.x.x.x range
            return long2ip(mt_rand(ip2long("10.0.0.0"), ip2long("10.255.255.255")));
        }

        // 192.168.x.x range
        return long2ip(mt_rand(ip2long("192.168.0.0"), ip2long("192.168.255.255")));
    }

    /**
     * 随机构建IPv6地址
     *
     * -e.g: phpunit("Random::ipv6");
     * -e.g: phpunit("Random::ipv6");
     * 
     * @return string
     */
    static function ipv6():string{
        $res = array();
        for ($i=0; $i < 8; $i++) {
            $res []= dechex(mt_rand(0, 65535));
        }
        return join(':', $res);
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
     * -e.g: phpunit("Random::url",["http"]);
     * -e.g: phpunit("Random::url",["https"]);
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
     * 随机验证码
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
     * 随机马甲昵称
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
     * 随机女性 姓名
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
     * 随机男性 姓名
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

    /**
     * 随机MAC地址
     * 
     * -e.g: phpunit("Random::macAddress");
     * -e.g: phpunit("Random::macAddress",["+"]);
     * -e.g: phpunit("Random::macAddress",["-"]);
     *
     * @param string $sep 分隔符
     * @return string
     */
    static function macAddress(string $sep=":"):string{
        $list = [];
        for($i=0;$i<6;$i++){
            $list[] = strtoupper(
                dechex(
                    floor(
                        self::mathRandom(0,1,9) * 256
                    )
                )
            );
        }
        return implode($sep, $list);
    }

    /**
     * 随机布尔值
     *
     * -e.g: phpunit("Random::boolean");
     * -e.g: phpunit("Random::boolean");
     * -e.g: phpunit("Random::boolean");
     * -e.g: phpunit("Random::boolean");
     * 
     * @return boolean
     */
    static function boolean():bool{
        return mt_rand(1, 100) <= 50;
    }

    /**
     * 随机Md5
     *
     * -e.g: phpunit("Random::md5");
     * -e.g: phpunit("Random::md5");
     * 
     * @return string
     */
    static function md5():string{
        return md5(mt_rand());
    }

    /**
     * 随机sha1
     *
     * -e.g: phpunit("Random::sha1");
     * -e.g: phpunit("Random::sha1");
     * 
     * @return string
     */
    static function sha1():string{
        return sha1(mt_rand());
    }

    /**
     * 随机sha256
     *
     * -e.g: phpunit("Random::sha256");
     * -e.g: phpunit("Random::sha256");
     * 
     * @return string
     */
    static function sha256():string{
        return hash('sha256', mt_rand());
    }

    /**
     * 随机币种
     *
     * -e.g: phpunit("Random::currencyCode");
     * -e.g: phpunit("Random::currencyCode");
     * 
     * @link https://en.wikipedia.org/wiki/ISO_4217
     *
     * With the following exceptions:
     * SVC has been replaced by the USD in 2001: https://en.wikipedia.org/wiki/Salvadoran_col%C3%B3n
     * ZWL has been suspended since 2009: https://en.wikipedia.org/wiki/Zimbabwean_dollar
     * 
     * @return string
     */
    static function currencyCode():string{
        $currencyCode = array(
            'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
            'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL',
            'BSD', 'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY',
            'COP', 'CRC', 'CUC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD',
            'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GHS', 'GIP',
            'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR',
            'ILS', 'INR', 'IQD', 'IRR', 'ISK', 'JMD', 'JOD', 'JPY', 'KES', 'KGS',
            'KHR', 'KMF', 'KPW', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR',
            'LRD', 'LSL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP',
            'MRU', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO',
            'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN',
            'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SDG',
            'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'SSP', 'STN', 'SYP', 'SZL',
            'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH',
            'UGX', 'USD', 'UYU', 'UZS', 'VES', 'VND', 'VUV', 'WST', 'XAF', 'XCD',
            'XOF', 'XPF', 'YER', 'ZAR', 'ZMW',
        );
        return $currencyCode[ mt_rand(0, 154) ];

    }

    /**
     * 随机Emoji表情
     *
     * -e.g: phpunit("Random::emoji");
     * -e.g: phpunit("Random::emoji");
     * 
     * @link https://en.wikipedia.org/wiki/Emoji#Unicode_blocks
     * 
     * @return void
     */
    static function emoji(){
        static $emoji = array(
            '\uD83D\uDE00', '\uD83D\uDE01', '\uD83D\uDE02', '\uD83D\uDE03',
            '\uD83D\uDE04', '\uD83D\uDE05', '\uD83D\uDE06', '\uD83D\uDE07',
            '\uD83D\uDE08', '\uD83D\uDE09', '\uD83D\uDE0A', '\uD83D\uDE0B',
            '\uD83D\uDE0C', '\uD83D\uDE0D', '\uD83D\uDE0E', '\uD83D\uDE0F',
            '\uD83D\uDE10', '\uD83D\uDE11', '\uD83D\uDE12', '\uD83D\uDE13',
            '\uD83D\uDE14', '\uD83D\uDE15', '\uD83D\uDE16', '\uD83D\uDE17',
            '\uD83D\uDE18', '\uD83D\uDE19', '\uD83D\uDE1A', '\uD83D\uDE1B',
            '\uD83D\uDE1C', '\uD83D\uDE1D', '\uD83D\uDE1E', '\uD83D\uDE1F',
            '\uD83D\uDE20', '\uD83D\uDE21', '\uD83D\uDE22', '\uD83D\uDE23',
            '\uD83D\uDE24', '\uD83D\uDE25', '\uD83D\uDE26', '\uD83D\uDE27',
            '\uD83D\uDE28', '\uD83D\uDE29', '\uD83D\uDE2A', '\uD83D\uDE2B',
            '\uD83D\uDE2C', '\uD83D\uDE2D', '\uD83D\uDE2E', '\uD83D\uDE2F',
            '\uD83D\uDE30', '\uD83D\uDE31', '\uD83D\uDE32', '\uD83D\uDE33',
            '\uD83D\uDE34', '\uD83D\uDE35', '\uD83D\uDE36', '\uD83D\uDE37',
        );
        return json_decode('"' . $emoji[mt_rand(0, count($emoji)-1) ] . '"');
    }

    /**
     * 随机维度
     *
     * -e.g: phpunit("Random::latitude");
     * -e.g: phpunit("Random::latitude");
     * 
     * @param integer $min
     * @param integer $max
     * @return float
     */
    static function latitude(int $min = -90, int $max = 90):float{
        return static::float($min, $max, 6);
    }

    /**
     * 随机经度
     *
     * -e.g: phpunit("Random::longitude");
     * -e.g: phpunit("Random::longitude");
     * 
     * @param integer $min
     * @param integer $max
     * @return float
     */
    static function longitude(int $min = -180, int $max = 180):float{
        return static::float($min, $max, 6);
    }

    /**
     * 随机英文字符
     *
     * -e.g: phpunit("Random::letter");
     * -e.g: phpunit("Random::letter",[4]);
     * -e.g: phpunit("Random::letter",[40]);
     * 
     * @return string
     */
    static function letter(int $len = 1):string{
        return static::_asciiLetter($len, 97, 122);
    }

    /**
     * 随机Ascii
     *
     * -e.g: phpunit("Random::ascii");
     * -e.g: phpunit("Random::ascii",[3]);
     * -e.g: phpunit("Random::ascii",[6]);
     * 
     * @param integer $len
     * @return string
     */
    static function ascii(int $len = 1):string{
        return static::_asciiLetter($len, 33, 126);
    }


    private static function _asciiLetter(int $len, int $sc, int $ec){
        $len < 1 && $len = 1;
        $str = '';
        do{
            $str .= chr(mt_rand($sc, $ec));
            $len --;
        }while($len > 0);
        return $str; 
    }
}

<?php
/**
 * @name 构建各类有意义的随机数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\{Tools,Str as VipkwdStr};

class Random {

    /**
     * 构建一个随机浮点数
     * 
     * -e.g: phpunit("Random::randomFloat");
     * -e.g: phpunit("Random::randomFloat",[0,5,0]);
     * -e.g: phpunit("Random::randomFloat",[0,5,1]);
     * -e.g: phpunit("Random::randomFloat",[0,5,4]);
     * -e.g: phpunit("Random::randomFloat",[0,5,6]);
     * 
     * @param integer $min
     * @param integer $max
     * @param integer $decimal <0> 小数位数
     * @return float
     */
    static function randomFloat(int $min = -999999999, int $max = 999999999, int $decimal = 10): float {
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
     * -e.g: phpunit("Random::randomDate");
     * -e.g: phpunit("Random::randomDate", ["Y-m-d H:i:s"]);
     * -e.g: phpunit("Random::randomDate", ["Y-m-d H:i"]);
     * -e.g: phpunit("Random::randomDate", ["Y/m/d H:i:s"]);
     * 
     * @param string $format PHP的时间日期格式化字符
     * @return false|string
     */
    static function randomDate(string $format = 'Y-m-d H:i:s'): string {
        $timestamp = time() - mt_rand(0, 86400 * 3650);
        return date($format, $timestamp);
    }

    /**
     * 构建随机IP地址
     * 
     * -e.g: phpunit("Random::randomIp");
     * -e.g: phpunit("Random::randomIp");
     * 
     * @return string
     */
    static function randomIp(): string {
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
     * -e.g: phpunit("Random::randomProtocol");
     * -e.g: phpunit("Random::randomProtocol");
     * 
     * @return string
     */
    static function randomProtocol(): string {
        $proArr = [ 'http', 'ftp', 'gopher', 'mailto', 'mid', 'cid', 'news', 'nntp', 'prospero', 'telnet', 'rlogin', 'tn3270', 'wais' ];
        shuffle($proArr);
        return $proArr[0];
    }

    /**
     * 随机生成一个顶级域名
     * 
     * -e.g: phpunit("Random::randomTld");
     * -e.g: phpunit("Random::randomTld");
     * 
     * @return string
     */
    static function randomTld(): string {
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
     * -e.g: phpunit("Random::randomDomain");
     * -e.g: phpunit("Random::randomDomain");
     * 
     * @return string
     */
    static function randomDomain(): string {
        $len = mt_rand(6, 16);
        return strtolower(Strs::randString($len)) . '.' . self::randomTld();
    }

    /**
     * 随机生成一个URL
     * 
     * -e.g: phpunit("Random::randomUrl");
     * -e.g: phpunit("Random::randomUrl");
     * 
     * @param string $protocol <""> 协议名称
     * @return string
     */
    static function randomUrl(string $protocol = ''): string {
        $protocol = $protocol ? $protocol : self::randomProtocol();
        return $protocol . '://' . self::randomDomain();
    }

    /**
     * 随机生成一个邮箱地址
     * 
     * -e.g: phpunit("Random::randomEmail");
     * -e.g: phpunit("Random::randomEmail");
     * 
     * @param string $domain <""> 可以指定邮箱域名
     * @return string
     */
    static function randomEmail(string $domain = ''): string {
        $len = mt_rand(6, 16);
        $domain = $domain ? $domain : self::randomDomain();
        return Strs::randString($len) . '@' . $domain;
    }

    /**
     * 随机生成一个大陆手机号
     * 
     * -e.g: phpunit("Random::randomPhone");
     * -e.g: phpunit("Random::randomPhone");
     * 
     * @return string
     */
    static function randomPhone(): string {
        $prefixArr = [13,14,15,16,17,18,19];
        shuffle($prefixArr);
        return $prefixArr[0] . VipkwdStr::randomCode(9, true);
    }

    /**
     * 随机创建一个身份证号码
     * 
     * -e.g: phpunit("Random::randomPhone");
     * -e.g: phpunit("Random::randomPhone");
     * 
     * @return string
     */
    static function randomId(): string {
        $prefixArr = [
            11, 12, 13, 14, 15,
            21, 22, 23,
            31, 32, 33, 34, 35, 36, 37,
            41, 42, 43, 44, 45, 46,
            50, 51, 52, 53, 54,
            61, 62, 63, 64, 65,
            71, 81, 82
        ];
        shuffle($prefixArr);

        $suffixArr = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'X'];
        shuffle($suffixArr);

        return $prefixArr[0] . '0000' . self::randomDate('Ymd') . Strs::randString(3, 1) . $suffixArr[0];
    }
}

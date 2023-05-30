<?php

/**
 * @name 构建各类有意义的随机数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Type;

use Vipkwd\Utils\Tools;
use \Vipkwd\Utils\Libs\Random\PersonName;
use \Vipkwd\Utils\Libs\Random\Payment;
use \Vipkwd\Utils\Idcard;
use \Vipkwd\Utils\Validate;
use \Exception;

class Random extends Payment
{

    /**
     * 构建一个随机浮点数
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::float");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::float",[0,5,0]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::float",[0,5,1]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::float",[0,5,4]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::float",[0,5,6]);
     * 
     * @param integer $min
     * @param integer $max
     * @param integer $decimal <0> 小数位数
     * @return float
     */
    static function float(int $min = -999999999, int $max = 999999999, int $decimal = 10): float
    {
        if ($max < $min) {
            throw new Exception("mathRandom(): max({$max}) is smaller than min({$min}).");
        }
        $range = mt_rand($min, $max);
        if ($decimal > 0) {
            $_ = lcg_value();
            while ($_ < 0.1) {
                $_ *= 10;
            }
            $range += floatval(substr("$_" . str_pad("0", $decimal, "0"), 0, $decimal + 2));
            if ($range > $max) {
                $range -= 1;
            }
        }
        return floatval($range);
    }

    /**
     * 获取随机的时间
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::date");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::date", ["Y-m-d H:i:s"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::date", ["Y-m-d H:i"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::date", ["Y/m/d H:i:s"]);
     * 
     * @param string $format PHP的时间日期格式化字符
     * @return false|string
     */
    static function date(string $format = 'Y-m-d H:i:s'): string
    {
        return DateTime::randomDate($format);
    }

    /**
     * 随机构建IPv4地址
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::ipv4");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::ipv4");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::ipv4", [3]);
     * 
     * @return string|array
     */
    static function ipv4(int $size = 1)
    {
        return self::maker($size, function ($size) {
            return long2ip(mt_rand(0, 1) == 0 ? mt_rand(-2147483648, -2) : mt_rand(16777216, 2147483647));
        });

        // $ipLong = [
        //     [607649792, 608174079], // 36.56.0.0-36.63.255.255
        //     [1038614528, 1039007743], // 61.232.0.0-61.237.255.255
        //     [1783627776, 1784676351], // 106.80.0.0-106.95.255.255
        //     [2035023872, 2035154943], // 121.76.0.0-121.77.255.255
        //     [2078801920, 2079064063], // 123.232.0.0-123.235.255.255
        //     [-1950089216, -1948778497], // 139.196.0.0-139.215.255.255
        //     [-1425539072, -1425014785], // 171.8.0.0-171.15.255.255
        //     [-1236271104, -1235419137], // 182.80.0.0-182.92.255.255
        //     [-770113536, -768606209], // 210.25.0.0-210.47.255.255
        //     [-569376768, -564133889], // 222.16.0.0-222.95.255.255
        // ];
        // $range = $ipLong[mt_rand(0, 9)];
        // return $ip = long2ip(mt_rand($range[0], $range[1]));
    }

    /**
     * 随机构建内网ipv4
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::localIpv4");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::localIpv4");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::localIpv4",[null]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::localIpv4",[10]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::localIpv4",[192]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::localIpv4",[192]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::localIpv4",[192, 10]);
     * 
     * @param integer|null $point <null> [10|192|null]
     * @param integer $size <1>
     * @return string|array
     */
    static function localIpv4(?int $point = null, int $size = 1)
    {
        return self::maker($size, function ($idx) use ($point) {
            ($point != 10 && $point != 192) && $point = mt_rand(10, 11);
            if (($point % 10) === 0) {
                // 10.x.x.x range
                return long2ip(mt_rand(ip2long("10.0.0.0"), ip2long("10.255.255.255")));
            }
            // 192.168.x.x range
            return long2ip(mt_rand(ip2long("192.168.0.0"), ip2long("192.168.255.255")));
        });
    }

    /**
     * 随机构建IPv6地址
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::ipv6");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::ipv6");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::ipv6", [3]);
     * 
     * @param integer $size <1>
     * @return string|array
     */
    static function ipv6(int $size = 1)
    {
        return self::maker($size, function ($idx) {
            $res = array();
            for ($i = 0; $i < 8; $i++) {
                $res[] = dechex(mt_rand(0, 65535));
            }
            return join(':', $res);
        });
    }
    /**
     * 随机生成一个 URL 协议
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::protocol");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::protocol");
     * 
     * @return string
     */
    static function protocol(): string
    {
        $proArr = ['http', 'ftp', 'gopher', 'mailto', 'mid', 'cid', 'news', 'nntp', 'prospero', 'telnet', 'rlogin', 'tn3270', 'wais'];
        shuffle($proArr);
        return $proArr[0];
    }

    /**
     * 随机生成一个顶级域名
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::tld");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::tld");
     * 
     * @return string
     */
    static function tld(): string
    {
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
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::domain");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::domain");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::domain", [3]);
     * 
     * @param integer $size <1>
     * @return string
     */
    static function domain(int $size = 1)
    {
        return self::maker($size, function ($idx) {
            $len = mt_rand(6, 16);
            return strtolower(self::code($len, false)) . '.' . self::tld();
        });
    }

    /**
     * 随机生成一个URL
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::url");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::url");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::url",["http"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::url",["https"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::url",["https", 3]);
     * 
     * @param string $protocol <""> 协议名称
     * @param integer $size <1>
     * @return string|array
     */
    static function url(string $protocol = '', int $size = 1)
    {
        return self::maker($size, function ($idx) use ($protocol) {
            $protocol = $protocol ? $protocol : self::protocol();
            return $protocol . '://' . self::domain();
        });
    }

    /**
     * 随机生成一个邮箱地址
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::email");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::email");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::email",['baidu.com']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::email",['baidu.com', 3]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::email",['', 3]);
     * 
     * @param string $domain <""> 可以指定邮箱域名
     * @param integer $size <1>
     * @return string|array
     */
    static function email(string $domain = '', $size = 1)
    {
        return self::maker($size, function ($idx) use ($domain) {
            $len = mt_rand(6, 16);
            $domain = $domain ? $domain : self::domain();
            return strtolower(self::code($len, false)) . '@' . $domain;
        });
    }

    /**
     * 大陆手机号
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::mobilePhone");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::mobilePhone");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::mobilePhone",[3]);
     * 
     * @param integer $size <1>
     * @return string|array
     */
    static function mobilePhone(int $size = 1)
    {
        return self::maker($size, function ($idx) {
            $prefixArr = [13, 14, 15, 16, 17, 18, 19];
            shuffle($prefixArr);
            return $prefixArr[mt_rand(0, 6)] . self::code(9, true);
        });
    }

    /**
     * 大陆身份证号码
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::idcard");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::idcard");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::idcard",[2, true]);
     * 
     * @param integer $size <1> 生成个数
     * @param boolean $valid <false> 验证格式
     * @param string|integer|null $prefixCode 指定地区码（证件号前2~6位）
     * @return string|array
     */
    static function idcard(int $size = 1, bool $valid = false, ?string $prefixCode = null)
    {
        return self::maker($size, function ($idx) use ($valid, $prefixCode) {
            $id = Idcard::createIdCard18($prefixCode);
            if ($valid) {
                if (!Validate::idcardOfChina($id)) {
                    return self::idcard(1, true, $prefixCode);
                }
            }
            return $id;
        });
    }

    /**
     * 生成密码
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::password");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::password", [15]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::password", [14, false]);
     * 
     * @param integer $len <16> 生成的密码长度
     * @param boolean $specialChar <true> 是否包含特殊字符
     * @return string
     * 
     */
    static function password(int $len = 16, bool $specialChar = true): string
    {
        $char = self::code(62, false);
        $specialChar && $char .= "`!\"?$?%^&*()_-+={[}]:;@'~#|\<,./>";

        return self::maker($len, function ($idx) use (&$char) {
            $char = str_shuffle($char);
            return $char[mt_rand(0, 9)];
        }, 'join');
    }

    /**
     * 简体字
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::zhCNChar");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::zhCNChar",[4]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::zhCNChar",[2]);
     * 
     * @param int $len <1>
     * @return string
     */
    static function zhCNChar(int $len = 1): string
    {
        return self::maker($len, function ($idx) {
            return @iconv(
                'GB2312',
                'UTF-8',
                // 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
                chr(mt_rand(0xB0, 0xD0)) . chr(mt_rand(0xA1, 0xF0))
            );
        }, 'join');
    }

    /**
     * 验证码
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::code");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::code");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::code",[1]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::code",[4]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::code",[5]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::code",[5,true]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::code",[5,true]);
     * 
     * @param integer $len <6>
     * @param boolean $onlyDigit <false> 是否纯数字，默认包含字母
     * @return string
     */
    static function code(int $len = 6, bool $onlyDigit = false): string
    {
        $char = '1234567890';
        if ($onlyDigit === false) {
            $char .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        }
        return self::maker($len, function ($idx) use (&$char) {
            $char = str_shuffle($char);
            return $char[mt_rand(0, 9)];
        }, 'join');
    }


    /**
     * 马甲昵称
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::nickName");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::nickName");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::nickName");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::nickName",[2]);
     * 
     * @param integer $len <1>
     * @return string|array
     */
    static function nickName(int $len = 1)
    {
        return self::maker($len, function ($idx) {
            return PersonName::getNickName();
        });
    }

    /**
     * 女性 姓名
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::femaleName");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::femaleName",[false]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::femaleName",[false, 3]);
     * 
     * @param boolean $surName <true> 是不包含复姓，如“上官” “司马”
     * @param integer $len <1>
     * @return string|array
     */
    static function femaleName(bool $surName = true, int $len = 1)
    {
        return self::maker($len, function ($idx) use ($surName) {
            return PersonName::getFemaleName($surName);
        });
    }

    /**
     * 男性 姓名
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::maleName");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::maleName",[false]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::maleName",[false,2]);
     *
     * @param boolean $surName <true> 是否包含复姓，如“上官” “司马”
     * @param integer $len <1>
     * @return string|array
     */
    static function maleName(bool $surName = true, int $len = 1)
    {
        return self::maker($len, function ($idx) use ($surName) {
            return PersonName::getMaleName($surName);
        });
    }

    /**
     * MAC地址
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::macAddress");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::macAddress",["+"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::macAddress",["-", 2]);
     *
     * @param string $sep 分隔符
     * @param integer $len <1>
     * @return string|array
     */
    static function macAddress(string $sep = ":", int $len = 1)
    {
        return self::maker($len, function () use ($sep) {
            $list = [];
            for ($i = 0; $i < 6; $i++) {
                $list[] = strtoupper(
                    dechex(
                        intval(
                            self::float(0, 1, 9) * 256
                        )
                    )
                );
            }
            return implode($sep, $list);
        });
    }

    /**
     * 布尔值
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::boolean");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::boolean");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::boolean");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::boolean");
     * 
     * @return boolean
     */
    static function boolean(): bool
    {
        return mt_rand(1, 100) <= 50;
    }

    /**
     * md5
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::md5");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::md5");
     * 
     * @return string
     */
    static function md5(): string
    {
        return md5(strval(mt_rand()));
    }

    /**
     * sha1
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::sha1");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::sha1");
     * 
     * @return string
     */
    static function sha1(): string
    {
        return sha1(strval(mt_rand()));
    }

    /**
     * sha256
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::sha256");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::sha256");
     * 
     * @return string
     */
    static function sha256(): string
    {
        return hash('sha256', strval(mt_rand()));
    }

    /**
     * 币种
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::currencyCode");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::currencyCode");
     * 
     * @link https://en.wikipedia.org/wiki/ISO_4217
     *
     * With the following exceptions:
     * SVC has been replaced by the USD in 2001: https://en.wikipedia.org/wiki/Salvadoran_col%C3%B3n
     * ZWL has been suspended since 2009: https://en.wikipedia.org/wiki/Zimbabwean_dollar
     * 
     * @return string
     */
    static function currencyCode(): string
    {
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
        return $currencyCode[mt_rand(0, 154)];
    }

    /**
     * emoji表情
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::emoji");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::emoji");
     * 
     * @link https://en.wikipedia.org/wiki/Emoji#Unicode_blocks
     * 
     * @return void
     */
    static function emoji()
    {
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
        return json_decode('"' . $emoji[mt_rand(0, count($emoji) - 1)] . '"');
    }

    /**
     * 维度
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::latitude");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::latitude");
     * 
     * @param integer $min
     * @param integer $max
     * @return float
     */
    static function latitude(int $min = -90, int $max = 90): float
    {
        return static::float($min, $max, 6);
    }

    /**
     * 经度
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::longitude");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::longitude");
     * 
     * @param integer $min
     * @param integer $max
     * @return float
     */
    static function longitude(int $min = -180, int $max = 180): float
    {
        return static::float($min, $max, 6);
    }

    /**
     * 英文字符
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::letter");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::letter",[4]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::letter",[40]);
     * 
     * @param integer $len <1>
     * @return string
     */
    static function letter(int $len = 1): string
    {
        return self::maker($len, function () {
            return chr(mt_rand(97, 122));
        }, 'join');
    }

    /**
     * Ascii字符
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::ascii");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::ascii",[3]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::ascii",[6]);
     * 
     * @param integer $len <1>
     * @return string
     */
    static function ascii(int $len = 1): string
    {
        return self::maker($len, function () {
            return chr(mt_rand(33, 126));
        }, 'join');
    }

    /**
     * 数字
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::digit");
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::digit",[3]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Random::digit",[6]);
     * 
     * @param integer $len <1>
     * @param integer $min <0>
     * @param integer $max <9>
     * @return string
     */
    static function digit(int $len = 1, int $min = 0, int $max = 9): int
    {
        $_max = pow(10, $len) - 1;
        ($len > 1 && $max < $_max) && $max = $_max;
        $min > $max && $min = $max;
        return intval(self::maker($len, function () use ($min, $max) {
            return mt_rand($min, $max);
        }, 'join'));
    }
    private static function maker($len = 1, callable $fn, string $format = null)
    {
        $len = intval($len);
        $len < 1 && $len = 1;
        $map = [];
        do {
            $map[] = $fn($len);
            $len--;
        } while ($len > 0);

        if (count($map) == 1) {
            return $map[0];
        }
        if ($format == 'join') {
            return implode('', $map);
        }
        return $map;
    }
}

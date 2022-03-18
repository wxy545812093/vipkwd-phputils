<?php
/**
 * @name 境外支付资产账户
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs\Random;

use Vipkwd\Utils\DateTime as VipkwdDate;
use \Vipkwd\Utils\Random;

class Payment{

    use Payment\BaseTrait;

    /**
     * 卡片信息组
     * 
     * -e.g: phpunit("Random::creditCard");
     * 
     * @param  boolean $valid <true>
     * @return array
     */
    static function creditCard($valid = true):array{
        $type = static::creditCardType();
        return array(
            'type'   => $type,
            'number' => static::creditCardNumber($type),
            'name'   => mt_rand(0,1) ? Random::femaleName() : Random::maleName(),
            'expirationDate' => static::creditCardExpirationDate($valid)
        );
    }

    /**
     * 生成IBAN号码
     * 
     * -e.g: phpunit("Random::ibanNumber");
     * -e.g: phpunit("Random::ibanNumber");
     * -e.g: phpunit("Random::ibanNumber");
     *
     * @link http://en.wikipedia.org/wiki/International_Bank_Account_Number
     * @param  string  $countryCode ISO 3166-1 alpha-2 country code
     * @param  string  $prefix      for generating bank account number of a specific bank
     * @param  integer $length      total length without country code and 2 check digits
     * @return string
     */
    static function ibanNumber($countryCode = null, $prefix = '', $length = null){
        if(is_null($countryCode)){
            $arr = array_keys(self::$ibanFormats);
            shuffle($arr);
            shuffle($arr);
            $countryCode = $arr[0];
        }
        $countryCode = strtoupper($countryCode);

        $format = !isset(static::$ibanFormats[$countryCode]) ? null : static::$ibanFormats[$countryCode];
        if ($length === null) {
            if ($format === null) {
                $length = 24;
            } else {
                $length = 0;
                foreach ($format as $part) {
                    list($class, $groupCount) = $part;
                    $length += $groupCount;
                }
            }
        }
        if ($format === null) {
            $format = array(array('n', $length));
        }

        $expandedFormat = '';
        foreach ($format as $item) {
            list($class, $length) = $item;
            $expandedFormat .=  str_repeat($class, $length);
        }

        $result = $prefix;
        $expandedFormat = substr($expandedFormat, strlen($result));
        foreach (str_split($expandedFormat) as $class) {
            switch ($class) {
                default:
                case 'c':
                    $result .= mt_rand(0, 100) <= 50 ? Random::digit() : strtoupper(Random::letter());
                    break;
                case 'a':
                    $result .= strtoupper(Random::letter());
                    break;
                case 'n':
                    $result .= Random::digit();
                    break;
            }
        }
        $checksum = self::ibanChecksum($countryCode . '00' . $result);
        return $countryCode . $checksum . $result;
    }

    /**
     * 生成 SWIFT/BIC 号码
     *
     * -e.g: phpunit("Random::swiftBicNumber");
     * -e.g: phpunit("Random::swiftBicNumber");
     * 
     * @example 'RZTIAT22263'
     * @link    http://en.wikipedia.org/wiki/ISO_9362
     * @return  string
     */
    static function swiftBicNumber(){
        return self::regexify("^([A-Z]){4}([A-Z]){2}([0-9A-Z]){2}([0-9A-Z]{3})?$");
    }

    /**
     * 随机信用卡种
     *
     * @example 'MasterCard'
     */
    private static function creditCardType(){
        return static::randomElement(static::$cardVendors);
    }

    /**
     * 生成信用卡号
     * 
     * -e.g: phpunit("Random::creditCardNumber");
     * -e.g: phpunit("Random::creditCardNumber");
     * 
     * @param string  $type  <null> Visa/MasterCard/American Express/Discover
     * @param boolean $formatted <false>
     * @param string $separator <'-'>.
     * @return string
     *
     * @example '4485480221084675'
     */
    
    private static function creditCardNumber(string $type = null, bool $formatted = false, string $separator = '-'):string{
        if (is_null($type)) {
            $type = static::creditCardType();
        }
        $mask = static::randomElement(static::$cardParams[$type]);
        $number = static::numerify($mask);
        $number .= static::computeCheckDigit($number);
        if ($formatted) {
            $p1 = substr($number, 0, 4);
            $p2 = substr($number, 4, 4);
            $p3 = substr($number, 8, 4);
            $p4 = substr($number, 12);
            $number = $p1 . $separator . $p2 . $separator . $p3 . $separator . $p4;
        }
        return $number;
    }

    /**
     * 信息卡时效年月
     * 
     * -e.g: phpunit("Random::creditCardExpirationDate");
     * 
     * @param boolean $valid <true>
     * @param string  $format
     * @return string
     * @example '04/13'
     */
    private static function creditCardExpirationDate(bool $valid = true, string $format = 'm/y'):string{
        if ($valid) {
            $instance = VipkwdDate::dateTimeBetween('now','36 months');
        }
        $instance = VipkwdDate::dateTimeBetween('-36 months', '36 months');
        return $instance->format(is_null($format) ? 'm/y' : $format);
    }
}
<?php
/**
 * @name --
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs\Random\Payment;

use Vipkwd\Utils\Type\DateTime as VipkwdDate;
use \Vipkwd\Utils\Type\Random;

trait BaseTrait {

    private static $cardVendors = array(
        'Visa', 'Visa', 'Visa', 'Visa', 'Visa',
        'MasterCard', 'MasterCard', 'MasterCard', 'MasterCard', 'MasterCard',
        'American Express', 'Discover Card', 'Visa Retired'
    );

    /**
     * @var array List of card brand masks for generating valid credit card numbers
     * @see https://en.wikipedia.org/wiki/Payment_card_number Reference for existing prefixes
     * @see https://www.mastercard.us/en-us/issuers/get-support/2-series-bin-expansion.html MasterCard 2017 2-Series BIN Expansion
     */
    private static $cardParams = array(
        'Visa' => array(
            "4539###########",
            "4556###########",
            "4916###########",
            "4532###########",
            "4929###########",
            "40240071#######",
            "4485###########",
            "4716###########",
            "4##############"
        ),
        'Visa Retired' => array(
            "4539########",
            "4556########",
            "4916########",
            "4532########",
            "4929########",
            "40240071####",
            "4485########",
            "4716########",
            "4###########",
        ),
        'MasterCard' => array(
            "2221###########",
            "23#############",
            "24#############",
            "25#############",
            "26#############",
            "2720###########",
            "51#############",
            "52#############",
            "53#############",
            "54#############",
            "55#############"
        ),
        'American Express' => array(
            "34############",
            "37############"
        ),
        'Discover Card' => array(
            "6011###########"
        ),
    );

    /**
     * @var array list of IBAN formats, source: @link https://www.swift.com/standards/data-standards/iban
     */
    private static $ibanFormats = array(
        'AD' => array(array('n', 4),    array('n', 4),  array('c', 12)),
        'AE' => array(array('n', 3),    array('n', 16)),
        'AL' => array(array('n', 8),    array('c', 16)),
        'AT' => array(array('n', 5),    array('n', 11)),
        'AZ' => array(array('a', 4),    array('c', 20)),
        'BA' => array(array('n', 3),    array('n', 3),  array('n', 8),  array('n', 2)),
        'BE' => array(array('n', 3),    array('n', 7),  array('n', 2)),
        'BG' => array(array('a', 4),    array('n', 4),  array('n', 2),  array('c', 8)),
        'BH' => array(array('a', 4),    array('c', 14)),
        'BR' => array(array('n', 8),    array('n', 5),  array('n', 10), array('a', 1),  array('c', 1)),
        'CH' => array(array('n', 5),    array('c', 12)),
        'CR' => array(array('n', 3),    array('n', 14)),
        'CY' => array(array('n', 3),    array('n', 5),  array('c', 16)),
        'CZ' => array(array('n', 4),    array('n', 6),  array('n', 10)),
        'DE' => array(array('n', 8),    array('n', 10)),
        'DK' => array(array('n', 4),    array('n', 9),  array('n', 1)),
        'DO' => array(array('c', 4),    array('n', 20)),
        'EE' => array(array('n', 2),    array('n', 2),  array('n', 11), array('n', 1)),
        'ES' => array(array('n', 4),    array('n', 4),  array('n', 1),  array('n', 1),  array('n', 10)),
        'FI' => array(array('n', 6),    array('n', 7),  array('n', 1)),
        'FR' => array(array('n', 5),    array('n', 5),  array('c', 11), array('n', 2)),
        'GB' => array(array('a', 4),    array('n', 6),  array('n', 8)),
        'GE' => array(array('a', 2),    array('n', 16)),
        'GI' => array(array('a', 4),    array('c', 15)),
        'GR' => array(array('n', 3),    array('n', 4),  array('c', 16)),
        'GT' => array(array('c', 4),    array('c', 20)),
        'HR' => array(array('n', 7),    array('n', 10)),
        'HU' => array(array('n', 3),    array('n', 4),  array('n', 1),  array('n', 15), array('n', 1)),
        'IE' => array(array('a', 4),    array('n', 6),  array('n', 8)),
        'IL' => array(array('n', 3),    array('n', 3),  array('n', 13)),
        'IS' => array(array('n', 4),    array('n', 2),  array('n', 6),  array('n', 10)),
        'IT' => array(array('a', 1),    array('n', 5),  array('n', 5),  array('c', 12)),
        'KW' => array(array('a', 4),    array('n', 22)),
        'KZ' => array(array('n', 3),    array('c', 13)),
        'LB' => array(array('n', 4),    array('c', 20)),
        'LI' => array(array('n', 5),    array('c', 12)),
        'LT' => array(array('n', 5),    array('n', 11)),
        'LU' => array(array('n', 3),    array('c', 13)),
        'LV' => array(array('a', 4),    array('c', 13)),
        'MC' => array(array('n', 5),    array('n', 5),  array('c', 11), array('n', 2)),
        'MD' => array(array('c', 2),    array('c', 18)),
        'ME' => array(array('n', 3),    array('n', 13), array('n', 2)),
        'MK' => array(array('n', 3),    array('c', 10), array('n', 2)),
        'MR' => array(array('n', 5),    array('n', 5),  array('n', 11), array('n', 2)),
        'MT' => array(array('a', 4),    array('n', 5),  array('c', 18)),
        'MU' => array(array('a', 4),    array('n', 2),  array('n', 2),  array('n', 12), array('n', 3),  array('a', 3)),
        'NL' => array(array('a', 4),    array('n', 10)),
        'NO' => array(array('n', 4),    array('n', 6),  array('n', 1)),
        'PK' => array(array('a', 4),    array('c', 16)),
        'PL' => array(array('n', 8),    array('n', 16)),
        'PS' => array(array('a', 4),    array('c', 21)),
        'PT' => array(array('n', 4),    array('n', 4),  array('n', 11), array('n', 2)),
        'RO' => array(array('a', 4),    array('c', 16)),
        'RS' => array(array('n', 3),    array('n', 13), array('n', 2)),
        'SA' => array(array('n', 2),    array('c', 18)),
        'SE' => array(array('n', 3),    array('n', 16), array('n', 1)),
        'SI' => array(array('n', 5),    array('n', 8),  array('n', 2)),
        'SK' => array(array('n', 4),    array('n', 6),  array('n', 10)),
        'SM' => array(array('a', 1),    array('n', 5),  array('n', 5),  array('c', 12)),
        'TN' => array(array('n', 2),    array('n', 3),  array('n', 13), array('n', 2)),
        'TR' => array(array('n', 5),    array('n', 1),  array('c', 16)),
        'VG' => array(array('a', 4),    array('n', 16)),
    );

    private static function randomElement(array $array = []){
        return $array[mt_rand(0, count($array) - 1)];
    }

    private static function numerify($string = '###'){
        // instead of using randomDigit() several times, which is slow,
        // count the number of hashes and generate once a large number
        $toReplace = array();
        if (($pos = strpos($string, '#')) !== false) {
            for ($i = $pos, $last = strrpos($string, '#', $pos) + 1; $i < $last; $i++) {
                if ($string[$i] === '#') {
                    $toReplace[] = $i;
                }
            }
        }
        if ($nbReplacements = count($toReplace)) {
            $maxAtOnce = strlen((string) mt_getrandmax()) - 1;
            $numbers = '';
            $i = 0;
            while ($i < $nbReplacements) {
                $size = min($nbReplacements - $i, $maxAtOnce);
                $numbers .= str_pad(strval(static::randomNumber($size)), $size, '0', STR_PAD_LEFT);
                $i += $size;
            }
            for ($i = 0; $i < $nbReplacements; $i++) {
                $string[$toReplace[$i]] = $numbers[$i];
            }
        }
        $string = self::replaceWildcard($string, '%', 'static::randomDigitNotNull');

        return $string;
    }

    private static function regexify($regex = ''){
        // ditch the anchors
        $regex = preg_replace('/^\/?\^?/', '', $regex);
        $regex = preg_replace('/\$?\/?$/', '', $regex);
        // All {2} become {2,2}
        $regex = preg_replace('/\{(\d+)\}/', '{\1,\1}', $regex);
        // Single-letter quantifiers (?, *, +) become bracket quantifiers ({0,1}, {0,rand}, {1, rand})
        $regex = preg_replace('/(?<!\\\)\?/', '{0,1}', $regex);
        $regex = preg_replace('/(?<!\\\)\*/', '{0,' . static::randomDigitNotNull() . '}', $regex);
        $regex = preg_replace('/(?<!\\\)\+/', '{1,' . static::randomDigitNotNull() . '}', $regex);
        // [12]{1,2} becomes [12] or [12][12]
        $regex = preg_replace_callback('/(\[[^\]]+\])\{(\d+),(\d+)\}/', function ($matches) {
            return str_repeat($matches[1], self::randomElement(range($matches[2], $matches[3])));
        }, $regex);
        // (12|34){1,2} becomes (12|34) or (12|34)(12|34)
        $regex = preg_replace_callback('/(\([^\)]+\))\{(\d+),(\d+)\}/', function ($matches) {
            return str_repeat($matches[1], self::randomElement(range($matches[2], $matches[3])));
        }, $regex);
        // A{1,2} becomes A or AA or \d{3} becomes \d\d\d
        $regex = preg_replace_callback('/(\\\?.)\{(\d+),(\d+)\}/', function ($matches) {
            return str_repeat($matches[1], self::randomElement(range($matches[2], $matches[3])));
        }, $regex);
        // (this|that) becomes 'this' or 'that'
        $regex = preg_replace_callback('/\((.*?)\)/', function ($matches) {
            return self::randomElement(explode('|', str_replace(array('(', ')'), '', $matches[1])));
        }, $regex);
        // All A-F inside of [] become ABCDEF
        $regex = preg_replace_callback('/\[([^\]]+)\]/', function ($matches) {
            return '[' . preg_replace_callback('/(\w|\d)\-(\w|\d)/', function ($range) {
                return implode('', range($range[1], $range[2]));
            }, $matches[1]) . ']';
        }, $regex);
        // All [ABC] become B (or A or C)
        $regex = preg_replace_callback('/\[([^\]]+)\]/', function ($matches) {
            return self::randomElement(str_split($matches[1]));
        }, $regex);
        // replace \d with number and \w with letter and . with ascii
        $regex = preg_replace_callback('/\\\w/', 'static::letter', $regex);
        $regex = preg_replace_callback('/\\\d/', 'static::digit', $regex);
        $regex = preg_replace_callback('/(?<!\\\)\./', 'static::ascii', $regex);
        // remove remaining backslashes
        $regex = str_replace('\\', '', $regex);
        // phew
        return $regex;
    }
    
    private static function letter(){ return Random::letter();}
    
    private static function digit(){ return Random::digit();}

    private static function ascii(){ return Random::ascii();}

    private static function randomNumber($nbDigits = null, $strict = false){
        if (!is_bool($strict)) {
            throw new \InvalidArgumentException('randomNumber() generates numbers of fixed width. To generate numbers between two boundaries, use numberBetween() instead.');
        }
        if (null === $nbDigits) {
            $nbDigits = static::randomDigitNotNull();
        }
        $max = pow(10, $nbDigits) - 1;
        if ($max > mt_getrandmax()) {
            throw new \InvalidArgumentException('randomNumber() can only generate numbers up to mt_getrandmax()');
        }
        if ($strict) {
            return mt_rand(pow(10, $nbDigits - 1), $max);
        }

        return mt_rand(0, $max);
    }

    private static function replaceWildcard($string, $wildcard = '#', $callback = 'Random::digit'){
        if (($pos = strpos($string, $wildcard)) === false) {
            return $string;
        }
        for ($i = $pos, $last = strrpos($string, $wildcard, $pos) + 1; $i < $last; $i++) {
            if ($string[$i] === $wildcard) {
                $string[$i] = call_user_func($callback);
            }
        }
        return $string;
    }

    private static function randomDigitNotNull(){
        return mt_rand(1, 9);
    }

    private static function computeCheckDigit($partialNumber){
        $checkDigit = self::creditCardChecksum($partialNumber . '0');
        if ($checkDigit === 0) {
            return 0;
        }
        return (string) (10 - $checkDigit);
    }

    private static function creditCardChecksum($number){
        $number = (string) $number;
        $length = strlen($number);
        $sum = 0;
        for ($i = $length - 1; $i >= 0; $i -= 2) {
            $sum += $number[$i];
        }
        for ($i = $length - 2; $i >= 0; $i -= 2) {
            $sum += array_sum(str_split( strval( $number[$i] * 2))) ;
        }
        return $sum % 10;
    }

    private static function ibanChecksum($iban){
        // Move first four digits to end and set checksum to '00'
        $checkString = substr($iban, 4) . substr($iban, 0, 2) . '00';

        // Replace all letters with their number equivalents
        $checkString = preg_replace_callback('/[A-Z]/', array('self','ibanAlphaToNumberCallback'), $checkString);

        // Perform mod 97 and subtract from 98
        $checksum = 98 - self::ibanMod97($checkString);

        return str_pad("$checksum", 2, '0', STR_PAD_LEFT);
    }

    private static function ibanMod97($number){
        $checksum = (int)$number[0];
        for ($i = 1, $size = strlen($number); $i < $size; $i++) {
            $checksum = (10 * $checksum + (int) $number[$i]) % 97;
        }
        return $checksum;
    }

    private static function ibanAlphaToNumberCallback($match){
        return ord($match[0]) - 55;
    }
}

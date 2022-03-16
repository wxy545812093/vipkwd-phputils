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

use \Vipkwd\Utils\Random;

trait ValidateTrait {
    
    use BaseTrait;

    /**
     * iban账号验证
     *
     * -e.g: $number = \Vipkwd\Utils\Random::ibanNumber();
     * -e.g: phpunit("Validate::ibanValid", [$number]);
     * 
     * @param string $iban
     * @return boolean
     */
    static function ibanValid(string $iban):bool{
        return self::ibanChecksum($iban) === substr($iban, 2, 2);
    }

    /**
     * 境外信用卡验证
     *
     * -e.g: $number = \Vipkwd\Utils\Random::creditCardNumber();
     * -e.g: phpunit("Validate::creditCardValid", [$number]);
     * -e.g: phpunit("Validate::creditCard", [$number]);
     * 
     * @param string $number
     * @return boolean
     */
    static function creditCardValid(string $number):bool{
        return self::creditCardChecksum($number) === 0;
    }
}

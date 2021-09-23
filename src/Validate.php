<?php

/**
 * @author vipkwd <service@vipwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

class Validate{
    /**
	 * 验证移动通讯号码（兼容：国际号码格式）
	 *
	 * @param string $data
	 * @return void
	 */
	static function internatMobile(string $data){
		if( false === $result = self::mobileOfChina($data)){
            // match 组1：完整匹配
            // match 组2：带分隔符的区域码
            // match 组3：区域码
            // match 组4：mobile号码
            return self::__execRegExp("/^(((\+?0?\d{1,4})[\ \-])?(\d{5,11}))$/", $data);
		}
		return $result;
	}

    /**
	 * 验证大陆手机号 (兼容：地区码前缀)
	 *
	 * @param string $data
	 * @return void
	 */
	static function mobileOfChina(string $data){
        return self::__execRegExp("/^((\+?0?86[\ \-]?)?1)[3-9]\d{9}$/", $data);
	}

    /**
	 * 验证大陆身份证号码
	 *
	 * @param string $data
	 * @return void
	 */
	static function idcard(string $data){
        return self::__execRegExp('#^(^[1-9]\d{7}((0[1-9])|(1[0-2]))(([0|1|2][1-9])|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0[1-9])|(1[0-2]))(([0|1|2][1-9])|3[0-1])((\d{4})|\d{3}[Xx])$)$#',$data);
	}

    /**
	 * 验证邮箱号码
	 *
	 * @param string $data
	 * @return void
	 */
	static function email(string $data){
		return self::__execRegExp("/\w+([-+.]\w+)*@((\w+([-.]\w+)*)\.)[a-zA-Z]{2,5}$/", $data);
	}

	/**
	 * 验证手机或座机
	 *
	 * @param string $data
	 * @return void
	 */
	static function phone(string $data){
		if( false === $result = self::mobileOfChina($data)){
			$result = self::telePhone($data);
		}
		return $result;
	}

	/**
	 * 验证座机（兼容: 括号前缀）
	 * (010)12345678
	 * 010 1234567
	 * 2811369
	 *
	 * @param string $data
	 * @return void
	 */
	static function telephone(string $data){
		return self::__execRegExp("/^(((0[1-9]\d{1,2})[ \-]|\(0[1-9]\d{1,2}\))?\d{4}\ ?)(\d{3,4})$/", $data);
	}	

	/**
	 * 验证大陆邮政编码
	 *
	 * @param string $data
	 * @return void
	 */
	static function zipcode(string $data){
		return self::__execRegExp("/^[1-9]\d{5}(?!\d)$/", $data);	
	}


	/**
	 * 验证普通数字(兼容负数、小数)
	 *
	 * @param string $data
	 * @return void
	 */
	static function number(string $data){
		return self::__execRegExp("/^(\-?\d+)(\.?\d+)?$/", $data);
	}

	/**
	 * 验证日期 ( YY-MM-DD )
	 *
	 * @param string $data
	 * @return void
	 */
	static function date(string $data){
		return self::__execRegExp("/^[2-3]\d{3}\-((0[1-9])|(1[0-2]))\-((0[1-9])|([1-2]\d)|(3[0-1]))$/", $data);
	}











    
    private function __execRegExp($regexp, $data){
        $data = urldecode($data);
        return preg_match($regexp, $data);
    }
}
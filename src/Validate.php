<?php

/**
 * @author vipkwd <service@vipkwd.com>
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
	 * @param string $str
	 * @return boolean
	 */
	static function internatMobile(string $str):bool{
		if( false === $result = self::mobileOfChina($str)){
            // match 组1：完整匹配
            // match 组2：带分隔符的区域码
            // match 组3：区域码
            // match 组4：mobile号码
            return self::exec("/^(((\+?0?\d{1,4})[\ \-])?(\d{5,11}))$/", $str);
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
		return self::exec("/\w+([-+.]\w+)*@((\w+([-.]\w+)*)\.)[a-zA-Z]{2,5}$/", $str);
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
		return self::exec("/^(((0[1-9]\d{1,2})[ \-]|\(0[1-9]\d{1,2}\))?\d{4}\ ?)(\d{3,4})(([\-|\ ]\d{1,6})?)$/", $str);
	}	

	/**
	 * 验证大陆邮政编码
	 *
	 * @param string $str
	 * @return boolean
	 */
	static function zipCodeOfChina(string $str):bool{
		return self::exec("/^[1-9]\d{5}(?!\d)$/", $str);	
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
        return self::exec("/^(^[1-9]\d{7}((0[1-9])|(1[0-2]))(([0|1|2][1-9])|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0[1-9])|(1[0-2]))(([0|1|2][1-9])|3[0-1])((\d{4})|\d{3}[Xx])$)$/",$str);
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
		return self::exec("/^[1-3]\d{3}(\-|\/)((0[1-9])|(1[0-2]))(\-|\/)((0[1-9])|([1-2]\d)|(3[0-1]))$/", $str);
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
	 * 验证Alibaba支付宝账号
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
	static function  isDomain(string $str):bool{
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
	 * @param string $number
	 * @param integer $minPointPlace
	 * @param integer $maxPointPlace
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
}
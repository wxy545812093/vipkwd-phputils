<?php
/**
 * @name 字符串处理函数包
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use \Exception;
use Vipkwd\Utils\Libs\RandomName;
class Str{

    /**
     * Hash对比（hash_equals函数)
     *
     * @param string $str1
     * @param string $str2
     * @return boolean
     */
    static function hashEquals(string $str1, string $str2):bool{
        // for php < 5.6.0
        if(!function_exists('hash_equals')){
            if(strlen($str1) != strlen($str2))
                return false;
            else {
                $res = $str1 ^ $str2;
                $ret = 0;
                for($i = strlen($res) - 1; $i >= 0; $i--)
                    $ret |= ord($res[$i]);
                return !$ret;
            }
        }
        return hash_equals($str1, $str2);
    }
    /**
     * HTML转实体符
     *
     * @param string $value
     * @param mixed $flags
     * @param string $encoding
     * @return string
     */
    static function htmlEncode(string $value, $flags=ENT_QUOTES, string $encoding ="UTF-8"):string {
        return htmlentities($value, $flags, $encoding);
    }

    /**
     * 字符XSS过滤
     *
     * @param string|array $str 待检字符 或 索引数组
     * @param boolean $DPI <false> 除常规过滤外，是否深度(额外使用正则)过滤。默认false仅常规过滤
     * @return string|array
     */
    static function removeXss($str, bool $DPI = false){
		if (!is_array($str)) {
			$str = trim($str);
			$str = strip_tags($str);
			$str = htmlspecialchars($str);
			if ($DPI === true) {
                $str = str_replace(array('"', "\\", "'", "/", "..", "../", "./", "//"), '', $str);
                $no = '/%0[0-8bcef]/';
                $str = preg_replace($no, '', $str);
                $no = '/%1[0-9a-f]/';
                $str = preg_replace($no, '', $str);
                $no = '/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]+/S';
                $str = preg_replace($no, '', $str);
			}
			return $str;
		}
		$keys = array_keys($str);
		foreach ($keys as $key) {
			$str[$key] = self::removeXss($str[$key], $DPI);
		}
        return $str;
	}

    /**
     * 获取纯文本内容(移除一切HTML元素)
     *
     * @param string $str
     * @return string
     */
    static function getContentText(string $str):string {
		$str = preg_replace("/<style .*?<\\/style>/is", "", $str);
		$str = preg_replace("/<script .*?<\\/script>/is", "", $str);
		$str = preg_replace("/<p .*?<\\/p>/is", "", $str);
		$str = preg_replace("/<br \\s*\\/>/i", "", $str);
		$str = preg_replace("/<\\/?p>/i", "", $str);
		$str = preg_replace("/<\\/?td>/i", "", $str);
		$str = preg_replace("/<\\/?div>/i", "", $str);
		$str = preg_replace("/<\\/?ul>/i", "", $str);
		$str = preg_replace("/<\\/?span>/i", "", $str);
		$str = preg_replace("/<\\/?li>/i", "", $str);
		$str = preg_replace("/ /i", " ", $str);
		$str = preg_replace("/ /i", " ", $str);
		$str = preg_replace("/&/i", "&", $str);
		$str = preg_replace("/&/i", "&", $str);
		$str = preg_replace("/</i", "<", $str);
		$str = preg_replace("/</i", "<", $str);
		$str = preg_replace("/“/i", '"', $str);
		$str = preg_replace("/&ldquo/i", '"', $str);
		$str = preg_replace("/‘/i", "'", $str);
		$str = preg_replace("/&lsquo/i", "'", $str);
		$str = preg_replace("/'/i", "'", $str);
		$str = preg_replace("/&rsquo/i", "'", $str);
		$str = preg_replace("/>/i", ">", $str);
		$str = preg_replace("/>/i", ">", $str);
		$str = preg_replace("/”/i", '"', $str);
		$str = preg_replace("/&rdquo/i", '"', $str);
		$str = strip_tags($str);
		$str = html_entity_decode($str, ENT_QUOTES, "utf-8");
		$str = preg_replace("/&#.*?;/i", "", $str);
		return $str;
	}

    /**
     * 生成随机字符(验证码)
     *
     * @param integer $len
     * @param boolean $onlyDigit <false> 是否纯数字，默认包含字母
     * @return string
     */
    static function randomCode(int $len = 6, bool $onlyDigit = false):string{      
        $char = '1234567890';
		if ($onlyDigit === false) {
			$char .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		}
		return substr(str_shuffle(str_repeat($char, $len)), 0, $len);
	}

    /**
     * 随机生成马甲昵称
     *
     * @return string
     */
    static function randomNickName():string{
        return RandomName::getNickName();
    }

    /**
     * 随机生成女名
     *
     * @param boolean $surName <true> 是不包含复姓，如“上官” “司马”
     * @return string
     */
    static function randomFemaleName(bool $surName = true):string{
        return RandomName::getFemaleName();
    }   

    /**
     * 随机生成男名
     *
     * @param boolean $surName <true> 是不包含复姓，如“上官” “司马”
     * @return string
     */
    static function randomMaleName(bool $surName = true):string{
        return RandomName::getMaleName();
    }

    /**
     * (中/英/混合)字符串截取(加强版)
     * 
     * :e: Vipkwd\Utils\Str::substrPlus('$omitted 末尾]】省略符 默认', 0, 13, "...")
     * @param string $str 待截取字符串
     * @param int $start <0> 从第几个字符(包含)开始截取
     * @param int $len <1> 截取长度
     * @param string $omitted <""> 自定义返回文本的后缀，如："..."
     * 
     * @return string
     */
    static function substrPlus(string $str, int $start = 0, int $len = 0, string $omitted=""):string{
        $rstr = '';//待返回字符串
        $str_length = self::strLenPlus( $str ); //字符串的字节数
        // $str_length = strlen( $str ); //字符串的字节数
        $i = 0;
        $n = 0;
        ($start < 0) && $start = $str_length - abs($start);
        ($len <= 0) && $len = $str_length;
        if(($start + $len) > $str_length){
            $len = $str_length - $start;
        }
        while ( ($n < ($start + $len)) ) {
            $temp_str = substr ( $str, $i, 1 );
            $ascnum = ord ( $temp_str ); //得到字符串中第$i位字符的ascii码
            if ($ascnum >= 224) {//如果ASCII位高与224，
                if($n >= $start)
                    $rstr = $rstr . substr ( $str, $i, 3 ); //根据UTF-8编码规范，将3个连续的字符计为单个字符
                $i += 3; //实际Byte计为3
                $n ++; //字串长度计1
            } elseif ($ascnum >= 192){ //如果ASCII位高与192，
                if($n >= $start)
                    $rstr = $rstr . substr ( $str, $i, 2 ); //根据UTF-8编码规范，将2个连续的字符计为单个字符
                $i += 2; //实际Byte计为2
                $n ++; //字串长度计1
            } elseif ($ascnum >= 65 && $ascnum <= 90) {//如果是大写字母，
                if($n >= $start)
                    $rstr = $rstr . substr ( $str, $i, 1 );
                $i ++; //实际的Byte数仍计1个
                $n ++; //但考虑整体美观，大写字母计成一个高位字符
            }elseif ($ascnum >= 97 && $ascnum <= 122) {
                if($n >= $start)
                    $rstr = $rstr . substr ( $str, $i, 1 );
                $i ++; //实际的Byte数仍计1个
                $n ++; //但考虑整体美观，大写字母计成一个高位字符
            } elseif ($ascnum > 0){
                if($n >= $start)
                    $rstr = $rstr . substr ( $str, $i, 1 );
                $i ++;
                $n ++;
            }else {//其他情况下，半角标点符号，
                if($n >= $start)
                    $rstr = $rstr . substr ( $str, $i, 1 );
                $i ++;
                $n += 1;//0.5;  
            }
        }
        if($omitted != ""){
            $omitted = trim($omitted);
        }
        // echo ": i:$i - n:$n";
        return $rstr.$omitted;
    }

    /**
     * 统计字符长度(加强版)
     *
     * @param [type] $str
     * @return int
     */
    static function strLenPlus($str): int{
        $i = 0;
        $n = 0;
        $str_length = strlen ( $str ); //字符串的字节数
        while ( $i <= $str_length ) {
            $temp_str = substr ( $str, $i, 1 );
            $ascnum = ord ( $temp_str ); //得到字符串中第$i位字符的ascii码
            if ($ascnum >= 224) {//如果ASCII位高与224
                $i += 3; //实际Byte计为3
                $n++; //字串长度计1
            } elseif ($ascnum >= 192){ //如果ASCII位高与192，
                $i += 2; //实际Byte计为2
                $n++; //字串长度计1
            } elseif ($ascnum >= 65 && $ascnum <= 90) {//如果是大写字母，
                $i += 1; //实际的Byte数仍计1个
                $n++; //但考虑整体美观，大写字母计成一个高位字符
            }elseif ($ascnum >= 97 && $ascnum <= 122) {
                $i += 1; //实际的Byte数仍计1个
                $n++; //但考虑整体美观，大写字母计成一个高位字符
            } else if($ascnum > 0){//其他情况下，半角标点符号
                $i += 1;
                $n++;
            }else{
                $i += 1;
            }
        }
        return $n;  
    }

    /**
     * 字符串填充(加强版)
     *
     * @param string $string
     * @param integer $length
     * @param string $padStr
     * @param int $padType
     * @return string
     */
    static function strPadPlus(string $string, int $length, string $padStr=" ", $padType=STR_PAD_RIGHT): string{
        //探测字符里的中文
		preg_match_all('/[\x7f-\xff]+/', $string, $matches);
		if(!empty($matches[0])){
			$rel_len = self::strLenPlus($string);
			//统计中文字的实际个数
			$zh_str_totals = self::strLenPlus(implode("",$matches[0]));
			//剩下的就是非中文字符个数
			$un_zh_str_totals = $rel_len - $zh_str_totals;
			//console下，一个中文处理为2个字符长度
			$zh_str_totals *=2;
			//计算字符总长度
			$rel_len = $un_zh_str_totals + $zh_str_totals;
			//生成计算长度的虚拟字符串
			$tmp_txt = str_pad("^&.!",$rel_len, "#");
			//实际字符串替换虚拟字符串（实现还原 外部字符）
			$string = str_replace(
                /* 用需求字符替换掉 常规填充字符中 的虚拟字符*/
                $tmp_txt,
                $string,
                /*常规填充*/
                str_pad($tmp_txt, $length, $padStr,$padType)
            );
			unset($rel_len, $zh_str_totals, $un_zh_str_totals, $tmp_txt);
		}else{
			$string = str_pad($string, $length, $padStr, $padType);
		}
        return $string;
    }

    /*
    markSearchWords("xxxx", "company", [
        "values" => [ "company" => ["%alipay","youtobe"] ],
        "operators" => ["company" => "like"]
        "operators" => ["company" => "like%"]
        "operators" => ["company" => "eq"]
    ]);
    */
    /**
     * 文本搜索高亮标注
     *
     * @param string $input
     * @param string $field
     * @param array $search
     * @return string
     */
    static function markSearchWords(string $input, string $field, array $search):string{
        $output = self::htmlEncode($input);
        if(isset($search['values'][$field]) && is_array($search['values'][$field])){
            // build one regex that matches (all) search words
            $regex = '/';
            $vali=0;
            $flag = strtoupper($search['operators'][$field]) =='LIKE' || strtoupper($search['operators'][$field]) == 'LIKE%';
            foreach($search['values'][$field] as $searchValue){
                if($flag){
                    // does the searchvalue have to occur at the start?
                    $regex .= '(?:'.($searchValue[0]=='%'?'':'^');
                }
                // the search value
                $regex .= preg_quote(trim($searchValue,'%'),'/');

                if($flag){
                    // does the searchvalue have to occur at the end?
                    $regex .= (substr($searchValue,-1)=='%'?'':'$').')';
                }
                if($vali++ < count($search['values'][$field]))
                    $regex .= '|';    // there is another search value, so we add a |
            }
            $regex .= '/u';
            // LIKE operator is not case sensitive, others are
            if($flag)
                $regex.= 'i';

            // split the string into parts that match and should be highlighted and parts in between
            // $fldBetweenParts: the parts that don't match (might contain empty strings)
            $fldBetweenParts = preg_split($regex, $input);
            // $fldFoundParts[0]: the parts that match
            preg_match_all($regex, $input, $fldFoundParts);

            // stick the parts together
            $output = '';
            foreach($fldBetweenParts as $index => $betweenPart){
                $output .= self::htmlEncode($betweenPart); // part that does not match (might be empty)
                if(isset($fldFoundParts[0][$index]) && $fldFoundParts[0][$index] != "")
                    $output .= '<u class="found">'.self::htmlEncode($fldFoundParts[0][$index]).'</u>'; // the part that matched
            }
        }
        return $output;
    }

}
<?php

/**
 * @name 字符串处理函数包
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Type;

use \Exception;
use Vipkwd\Utils\Libs\ZhToPinyin\V1 as ZhToPy;
use Vipkwd\Utils\Libs\ZhToPinyin\Tone as ZhToPyTone;

class Str
{

    /**
     * Hash对比（hash_equals函数)
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::hashEquals", ["11", "22"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::hashEquals", [false, false]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::hashEquals", [false, 0]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::hashEquals", ["abc", "abc"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::hashEquals", ['', 0]);
     *
     * @param string $str1
     * @param string $str2
     * @return boolean
     */
    static function hashEquals(string $str1, string $str2): bool
    {
        // for php < 5.6.0
        if (!function_exists('hash_equals')) {
            if (strlen($str1) != strlen($str2))
                return false;
            else {
                $res = $str1 ^ $str2;
                $ret = 0;
                for ($i = strlen("$res") - 1; $i >= 0; $i--)
                    $ret |= ord($res[$i]);
                return !$ret;
            }
        }
        return hash_equals($str1, $str2);
    }

    /**
     * HTML转实体符
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::htmlEncode", ["<&>$"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::htmlEncode", ["<&>$"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::htmlEncode", ["<&>$", ENT_QUOTES]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::htmlEncode", ["<&>$", ENT_QUOTES,"utf-8"]);
     *
     * @param string $value
     * @param mixed $flags <ENT_QUOTES>
     * @param string $encoding
     * @return string
     */
    static function htmlEncode(string $value, $flags = ENT_QUOTES, string $encoding = "UTF-8"): string
    {
        return htmlentities($value, $flags, $encoding);
    }

    /**
     * 字符XSS过滤
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::removeXss",["wa haha<div > div> <script>javascript</script> </div>"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::removeXss",["wa haha<div > div> <script >javascript</script> </div>",true]);
     *
     * @param string|array $str 待检字符 或 索引数组
     * @param boolean $DPI <false> 除常规过滤外，是否深度(额外使用正则)过滤。默认false仅常规过滤
     * @return string|array
     */
    static function removeXss($str, bool $DPI = false)
    {
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
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::getContentText",["wa haha<div > div> <script>javascript</script> </div>"]);
     *
     * @param string $str
     * @return string
     */
    static function getContentText(string $str): string
    {
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
     * (中/英/混合)字符串截取(加强版)
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::substrPlus",['$omitted 末尾]】省略符 默认', 0, 14]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::substrPlus",['$&3张三李】四王麻子', 0, 11, "..."]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::substrPlus",['$&3张三】李四王麻子', 0, 10, "..."]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::substrPlus",['$&3张】三李四王麻子', 0, 9, "..."]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::substrPlus",['$&3】张三李四王麻子', 0, 8, ">"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::substrPlus",['$&】3张三李四王麻子', 6, 2, ""]);
     *
     * @param string $str 待截取字符串
     * @param int $start <0> 从第几个字符(包含)开始截取
     * @param int $len <1> 截取长度
     * @param string $omitted <"..."> 自定义返回文本的后缀，如："..."
     *
     * @return string
     */
    static function substrPlus(string $str, int $start = 0, int $len = 0, string $omitted = "..."): string
    {
        // if (function_exists("mb_substr"))
        //     $slice = mb_substr($str, $start, $len, 'utf-8');
        // elseif (function_exists('iconv_substr')) {
        //     $slice = iconv_substr($str, $start, $len, 'utf-8');
        // } else {
        //     $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        //     $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        //     $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        //     $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        //     preg_match_all($re['utf-8'], $str, $match);
        //     $slice = join("", array_slice($match[0], $start, $len));
        // }
        // return $omitted ? $slice . $omitted : $slice;

        $rstr = ''; //待返回字符串
        $str_length = self::strLenPlus($str); //字符串的字节数
        // $str_length = strlen( $str ); //字符串的字节数
        $i = 0;
        $n = 0;
        ($start < 0) && $start = $str_length - abs($start);
        ($len <= 0) && $len = $str_length;
        if (($start + $len) > $str_length) {
            $len = $str_length - $start;
        }
        while (($n < ($start + $len))) {
            $temp_str = substr($str, $i, 1);
            $ascnum = ord($temp_str); //得到字符串中第$i位字符的ascii码
            if ($ascnum >= 224) { //如果ASCII位高与224，
                if ($n >= $start)
                    $rstr = $rstr . substr($str, $i, 3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
                $i += 3; //实际Byte计为3
                $n++; //字串长度计1
            } elseif ($ascnum >= 192) { //如果ASCII位高与192，
                if ($n >= $start)
                    $rstr = $rstr . substr($str, $i, 2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
                $i += 2; //实际Byte计为2
                $n++; //字串长度计1
            } elseif ($ascnum >= 65 && $ascnum <= 90) { //如果是大写字母，
                if ($n >= $start)
                    $rstr = $rstr . substr($str, $i, 1);
                $i++; //实际的Byte数仍计1个
                $n++; //但考虑整体美观，大写字母计成一个高位字符
            } elseif ($ascnum >= 97 && $ascnum <= 122) {
                if ($n >= $start)
                    $rstr = $rstr . substr($str, $i, 1);
                $i++; //实际的Byte数仍计1个
                $n++; //但考虑整体美观，大写字母计成一个高位字符
            } elseif ($ascnum > 0) {
                if ($n >= $start)
                    $rstr = $rstr . substr($str, $i, 1);
                $i++;
                $n++;
            } else { //其他情况下，半角标点符号，
                if ($n >= $start)
                    $rstr = $rstr . substr($str, $i, 1);
                $i++;
                $n += 1; //0.5;
            }
        }
        if ($omitted != "") {
            $omitted = trim($omitted);
        }
        // echo ": i:$i - n:$n";
        return $rstr . $omitted;
    }

    /**
     * 统计字符长度(加强版)
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::strLenPlus",['$&】3张三李四王麻子']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::strLenPlus",['&】3张三李四王麻子']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::strLenPlus",['】3张三李四王麻子']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::strLenPlus",['3张三李四王麻子']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::strLenPlus",['张三李四王麻子']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::strLenPlus",['三李四王麻子']);
     *
     * @param string $str
     * @return int
     */
    static function strLenPlus($str): int
    {
        $i = 0;
        $n = 0;
        $str_length = strlen($str); //字符串的字节数
        while ($i <= $str_length) {
            $temp_str = substr($str, $i, 1);
            $ascnum = ord($temp_str); //得到字符串中第$i位字符的ascii码
            if ($ascnum >= 224) { //如果ASCII位高与224
                $i += 3; //实际Byte计为3
                $n++; //字串长度计1
            } elseif ($ascnum >= 192) { //如果ASCII位高与192，
                $i += 2; //实际Byte计为2
                $n++; //字串长度计1
            } elseif ($ascnum >= 65 && $ascnum <= 90) { //如果是大写字母，
                $i += 1; //实际的Byte数仍计1个
                $n++; //但考虑整体美观，大写字母计成一个高位字符
            } elseif ($ascnum >= 97 && $ascnum <= 122) {
                $i += 1; //实际的Byte数仍计1个
                $n++; //但考虑整体美观，大写字母计成一个高位字符
            } else if ($ascnum > 0) { //其他情况下，半角标点符号
                $i += 1;
                $n++;
            } else {
                $i += 1;
            }
        }
        return $n;
    }

    /**
     * 字符串填充(加强版)
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::strPadPlus",['三李四王麻子', 10]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::strPadPlus",['三李四王麻子', 11]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::strPadPlus",['三李四王麻子', 12]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::strPadPlus",['三李四王麻子', 16]);
     *
     * @param string $str
     * @param integer $length
     * @param string $padStr
     * @param int $padType
     * @return string
     */
    static function strPadPlus(string $str, int $length, string $padStr = " ", $padType = STR_PAD_RIGHT): string
    {
        //探测字符里的中文
        preg_match_all('/[\x7f-\xff]+/', $str, $matches);
        if (!empty($matches[0])) {
            $rel_len = self::strLenPlus($str);
            //统计中文字的实际个数
            $zh_str_totals = self::strLenPlus(implode("", $matches[0]));
            //剩下的就是非中文字符个数
            $un_zh_str_totals = $rel_len - $zh_str_totals;
            //console下，一个中文处理为2个字符长度
            $zh_str_totals *= 2;
            //计算字符总长度
            $rel_len = $un_zh_str_totals + $zh_str_totals;
            //生成计算长度的虚拟字符串
            $tmp_txt = str_pad("^&.!", $rel_len, "#");
            //实际字符串替换虚拟字符串（实现还原 外部字符）
            $str = str_replace(
                /* 用需求字符替换掉 常规填充字符中 的虚拟字符*/
                $tmp_txt,
                $str,
                /*常规填充*/
                str_pad($tmp_txt, $length, $padStr, $padType)
            );
            unset($rel_len, $zh_str_totals, $un_zh_str_totals, $tmp_txt);
        } else {
            $str = str_pad($str, $length, $padStr, $padType);
        }
        return $str;
    }

    /**
     * 获取范围内随机数 位数不足补零
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::randNumber", [1, 10]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::randNumber", [90, 105]);
     *
     * @param integer $min 最小值
     * @param integer $max 最大值
     * @return string
     */
    static function randNumber(int $min, int $max): string
    {
        return sprintf("%0" . strlen("$max") . "d", mt_rand($min, $max));
    }

    /**
     * 自动转换字符集 支持数组转换
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::autoCharset", ["张三"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::autoCharset", ["张三","gbk","utf-8"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::autoCharset", ["张三","utf-8","gbk"]);
     *
     * @param string|array $str
     * @param string $fromCharset
     * @param string $toCharset
     * @return string
     */
    static function autoCharset($str, string $fromCharset = 'gbk', string $toCharset = 'utf-8'): string
    {
        $fromCharset = strtoupper($fromCharset) == 'UTF8' ? 'utf-8' : $fromCharset;
        $toCharset = strtoupper($toCharset) == 'UTF8' ? 'utf-8' : $toCharset;
        if (strtoupper($fromCharset) === strtoupper($toCharset) || empty($str) || (is_scalar($str) && !is_string($str))) {
            //如果编码相同或者非字符串标量则不转换
            return $str;
        }
        if (is_string($str)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($str, $toCharset, $fromCharset);
            } elseif (function_exists('iconv')) {
                return iconv($fromCharset, $toCharset, $str);
            } else {
                return $str;
            }
        } elseif (is_array($str)) {
            foreach ($str as $key => $val) {
                $_key = self::autoCharset($key, $fromCharset, $toCharset);
                $str[$_key] = self::autoCharset($val, $fromCharset, $toCharset);
                if ($key != $_key)
                    unset($str[$key]);
            }
            return $str;
        } else {
            return $str;
        }
    }

    /**
     * 文本搜索高亮标注
     *
     * -e.g: $str="uh~,这里不仅有alipay,youtube.com,还有10musume.com, alipay";
     * -e.g: $field="field1";
     * -e.g: $search=array();
     * -e.g: $search["values"]=[ "field1" => ["%alipay","u%","%com%","%youtu"] ];
     *
     * -e.g: $search["operators"]=["field1" => "like"];
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::markSearchWords",[$str, $field, $search]);
     *
     *
     * -e.g: $search["operators"]=["field1" => "like%"];
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::markSearchWords",[$str, $field, $search]);
     *
     *
     * -e.g: $search["operators"]=["field1" => "eq"];
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::markSearchWords",[$str, $field, $search]);
     *
     * @param string $str
     * @param string $field 搜索字段名
     * @param array $search 搜索模式配置
     * @return string
     */
    static function markSearchWords(string $str, string $field, array $search): string
    {
        $output = self::htmlEncode($str);
        if (isset($search['values'][$field]) && is_array($search['values'][$field])) {
            // build one regex that matches (all) search words
            $regex = '/';
            $vali = 0;
            $flag = strtoupper($search['operators'][$field]) == 'LIKE' || strtoupper($search['operators'][$field]) == 'LIKE%';
            foreach ($search['values'][$field] as $searchValue) {
                if ($flag) {
                    // does the searchvalue have to occur at the start?
                    $regex .= '(?:' . ($searchValue[0] == '%' ? '' : '^');
                }
                // the search value
                $regex .= preg_quote(trim($searchValue, '%'), '/');

                if ($flag) {
                    // does the searchvalue have to occur at the end?
                    $regex .= (substr($searchValue, -1) == '%' ? '' : '$') . ')';
                }
                if ($vali++ < count($search['values'][$field]))
                    $regex .= '|';    // there is another search value, so we add a |
            }
            $regex .= '/u';
            // LIKE operator is not case sensitive, others are
            if ($flag)
                $regex .= 'i';

            // split the string into parts that match and should be highlighted and parts in between
            // $fldBetweenParts: the parts that don't match (might contain empty strings)
            $fldBetweenParts = preg_split($regex, $str);
            // $fldFoundParts[0]: the parts that match
            preg_match_all($regex, $str, $fldFoundParts);

            // stick the parts together
            $output = '';
            foreach ($fldBetweenParts as $index => $betweenPart) {
                $output .= self::htmlEncode($betweenPart); // part that does not match (might be empty)
                if (isset($fldFoundParts[0][$index]) && $fldFoundParts[0][$index] != "")
                    $output .= '<u class="found">' . self::htmlEncode($fldFoundParts[0][$index]) . '</u>'; // the part that matched
            }
        }
        return $output;
    }

    /**
     * 检查字符串中是否包含某些字符串
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::contains", ["你好阿","你阿"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::contains", ["你好阿","你你"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::contains", ["你好阿","你好"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::contains", ["你好阿",["好","你"]]);
     *
     * @param string $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    static function contains(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ('' != $needle && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检查字符串是否以某些字符串结尾
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::endsWith", ["你好阿","阿"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::endsWith", ["你好阿","好阿"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::endsWith", ["你好阿","你好阿"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::endsWith", ["你好阿","你好俊阿"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::endsWith", ["你好阿","你好俊"]);
     * @param  string       $haystack
     * @param  string|array $needles
     * @return bool
     */
    static function endsWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle === mb_substr($haystack, -mb_strlen($needle))) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检查字符串是否以某些字符串开头
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::startsWith", ["你好阿","你好"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::startsWith", ["你好阿","你"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::startsWith", ["你好阿","你你你"]);
     *
     * @param  string       $haystack
     * @param  string|array $needles
     * @return bool
     */
    static function startsWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ('' != $needle && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 汉字转拼音
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::toPinyin", ["你好阿"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::toPinyin", ["你好阿","head"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::toPinyin", ["你好阿","all"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::toPinyin", ["你好阿","one"]);
     *
     * @param string $str utf8字符串
     * @param string $type  返回格式 [all:全拼音|head:首字母|one:仅第一字符首字母]
     * @param string $placeholder 无法识别的字符占位符
     * @param string $separator 分隔符
     * @param string $allow_chars 允许的非中文字符
     * 
     * @return string
     */
    static function toPinyin(string $str, string $type = 'head', string $placeholder = "*", string $separator = " ", string $allow_chars = "/[a-zA-Z\d]/"): string
    {
        $result = ZhToPy::encode($str, $type, $placeholder, $separator, $allow_chars);
        return strtolower($result); //返回结果转小写
    }

    /**
     * 汉字转拼音（声调版）
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::toPinyinTone", ["你好阿"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::toPinyinTone", ["你好阿","head"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::toPinyinTone", ["你好阿","all"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::toPinyinTone", ["你好阿","one"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::toPinyinTone", ["你好阿","all", false]);
     * 
     * @param string $str utf8字符串
     * @param string $type 返回格式(tone===true时，此参数为定参:all) [all:全拼音|head:首字母|one:仅第一字符首字母]
     * @param bool $tone <true> true 带声调输出，false 为不带声调
     * @param string $placeholder 无法识别的字符占位符
     * @param string $separator 分隔符
     * 
     * @return string
     */
    static function toPinyinTone(string $str, string $type = 'all', bool $tone = true, string $placeholder = "*", string $separator = " "): string
    {
        $plus = new ZhToPyTone($tone);
        $plus->separator($separator);
        $plus->placeholder($placeholder);
        $plus->retFormat($tone === true ? 'all' : $type); //带声调时只能是词输出(编码问题)
        return $plus->text($str);
    }


    /**
     * MD5值16位
     *
     * -e.g: echo 'md5("admin"); // string(32) "'.\md5('admin').'"';
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::md5_16",["admin"]);
     *
     * @param string $str
     * @return string
     */
    static function md5_16(string $str): string
    {
        return substr(md5($str), 8, 16);
    }

    /**
     * 检查字符串是否是UTF8编码
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::isUtf8", ["张三"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::isUtf8", ["123"]);
     *
     * @param string $str 字符串
     * @return Boolean
     */
    static function isUtf8(string $str): bool
    {
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) return false;
                elseif ($c >= 252) $bits = 6;
                elseif ($c >= 248) $bits = 5;
                elseif ($c >= 240) $bits = 4;
                elseif ($c >= 224) $bits = 3;
                elseif ($c >= 192) $bits = 2;
                else return false;
                if (($i + $bits) > $len) return false;
                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return true;
    }

    static function isAscii(string $str): bool
    {
        return in_array(mb_detect_encoding($str, array('ASCII', 'GB2312', 'GBK', 'UTF-8', 'ISO-8859-1')), ['ASCII', 'ISO-646']);
    }

    static function isLatin1(string $str): bool
    {
        return in_array(mb_detect_encoding($str, array('ASCII', 'GB2312', 'GBK', 'UTF-8', 'ISO-8859-1')), ['Latin1', 'ISO-8859-1']);
    }

    static function isGB2312(string $str): bool
    {
        return in_array(mb_detect_encoding($str, array('ASCII', 'GB2312', 'GBK', 'UTF-8', 'ISO-8859-1')), ['GB2312', 'EUC-CN']);
    }

    static function isGBK(string $str): bool
    {
        return in_array(mb_detect_encoding($str, array('ASCII', 'GB2312', 'GBK', 'UTF-8', 'ISO-8859-1')), ['GBK', 'CP936']);
    }

    static function isUtf82(string $str): bool
    {
        return in_array(mb_detect_encoding($str, array('ASCII', 'GB2312', 'GBK', 'UTF-8', 'ISO-8859-1')), ['UTF-8']);
    }

    static function getCharset(string $str): string
    {
        return mb_detect_encoding($str, array('ASCII', 'GB2312', 'GBK', 'UTF-8', 'ISO-8859-1', 'CP936'));
    }
    /**
     * 生成UUID
     *
     * -e.g: phpunit("Tools::uuid");
     * -e.g: phpunit("Tools::uuid",[false, "前缀：仅支持英文字符与数字,此设置无效"]);
     * -e.g: phpunit("Tools::uuid",[false, "99"]);
     * -e.g: phpunit("Tools::uuid",[true]);
     * -e.g: phpunit("Tools::uuid",[true, "0000"]);
     * -e.g: phpunit("Tools::uuid",[true, "00000000000000"]);
     * -e.g: phpunit("Tools::uuid",[true, "123456", '']);
     * -e.g: phpunit("Tools::uuid",[true, "12", '+']);
     * -e.g: phpunit("Tools::uuid",[true, "1234567890", '$']);
     *
     * @param bool $toUppercase <false>
     * @param string $prefix 前缀：仅支持英文字符与数字 <"">
     * @param string $separator 分隔符 <"-">
     * @return string
     */
    static function uuid(bool $toUppercase = false, string $prefix = '', string $separator = "-"): string
    {
        $prefix && $prefix = preg_replace("/[^\da-zA-Z]/", "", $prefix);
        $chars = md5(uniqid(strval(mt_rand()), true));
        $uuid = substr($prefix . substr($chars, 0, 8), 0, 8) . $separator;
        $uuid .= substr($chars, 8, 4) . $separator;
        $uuid .= substr($chars, 12, 4) . $separator;
        $uuid .= substr($chars, 16, 4) . $separator;
        $uuid .= substr($chars, 20, 12);
        return $toUppercase ? strtoupper($uuid) : strtolower($uuid);
    }

    /**
     * 检测字符串是否为JSON串
     *
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::isJson",['[{"url":"10musume.com"}]']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::isJson",['[]']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::isJson",['[{}]']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::isJson",['{"site":"91.com"}']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::isJson",['{}']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::isJson",['{<>}']);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::isJson",['{{}}']);
     *
     * @param string $str
     * @return boolean
     */
    static function isJson(string $str): bool
    {
        @json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 获取(最大)相似度文本(不支持中文)
     *
     * -e.g: $items = ["foo", "bar", "baz","你好"];
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::getSuggestion",[$items, "fo"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::getSuggestion",[$items, "barr"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::getSuggestion",[$items, "baz"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::getSuggestion",[$items, "好"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Str::getSuggestion",[$items, "你"]);
     *
     * @param array  $possibilities 查找列表
     * @param string $value 查找文字
     *
     * @return string|null
     */
    static function getSuggestion(array $possibilities, string $value): ?string
    {
        $best = null;
        $min = (strlen($value) / 4 + 1) * 10; // + .1;
        foreach (array_unique($possibilities) as $item) {
            if ($item !== $value && ($len = \levenshtein($item, $value, 10, 11, 10)) < $min) {
                $min = $len;
                $best = $item;
            }
        }
        return $best;
    }

    /**
     * 计算str1较str2的相似度
     * 
     * @param string $str1
     * @param string|array $str2
     * 
     * @return array ['sim'=> xx, 'perc' => xx]
     */
    static function getTextSamePercent(string $str1, $str2): array
    {
        if (is_array($str2)) {
            $list = [];
            foreach ($str2 as $str) {
                $sim = \similar_text($str, $str1, $perc);
                $list[] = [
                    'str1' => $str1,
                    'str2' => $str, 
                    'sim' => $sim,
                    'perc' => $perc
                ];
            }
            return $list;
        }
        $sim = \similar_text($str2, $str1, $perc);
        return [
            'str1' => $str1,
            'str2' => $str2,
            'sim' => $sim,
            'perc' => $perc
        ];
    }

    /**
     * 转换HTML代码为文本
     *
     * @param string $html
     * @return string
     */
    static function htmlToText(string $html): string
    {
        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    //字节转Emoji表情
    static function bytesToEmoji($cp)
    {
        $cp += 0;
        if ($cp > 0x10000) {       # 4 bytes
            $s = chr(0xF0 | (($cp & 0x1C0000) >> 18)) . chr(0x80 | (($cp & 0x3F000) >> 12)) . chr(0x80 | (($cp & 0xFC0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else if ($cp > 0x800) {   # 3 bytes
            $s = chr(0xE0 | (($cp & 0xF000) >> 12)) . chr(0x80 | (($cp & 0xFC0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else if ($cp > 0x80) {    # 2 bytes
            $s = chr(0xC0 | (($cp & 0x7C0) >> 6)) . chr(0x80 | ($cp & 0x3F));
        } else {                    # 1 byte
            $s = chr($cp);
        }
        return $s;
    }
}
<?php

/**
 * @name PHP汉字转拼音2.0
 * @version v2.0
 * @note 请开启 mb_string 扩展
 * 
 * @author vipkwd <service@vipkwd.com> 草札(www.caozha.com)
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @link https://gitee.com/caozha/caozha-pinyin
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Libs\ZhToPinyin;

use Vipkwd\Utils\Type\Arr;

class Tone
{

    private $_dict;
    private $_punctuation = false;
    private $_separator = ' ';
    private $_format = '';
    private $_callback = null;
    private $_placeholder = null;
    private $_retFormat = 'head';

    /**
     * @param bool $tone <true> true 带声调输出，false 为不带声调
     */
    public function __construct(bool $tone = true)
    {
        $tone = $tone ? 'tone' : 'latin';
        $this->_dict = file_get_contents(__DIR__ . "/Tone/{$tone}.dict", true);
    }

    /**
     * 是否清除原字符中的标点符号
     * 
     * @param bool $keep
     * 
     * @return void
     */
    public function punctuation(bool $keep = false)
    {
        $this->_punctuation = $keep ? true : false;
    }

    /**
     * 转换后的字之间分隔符，默认空格
     * 
     * @param string $separator
     * 
     * @return void
     */
    public function separator(string $separator = "")
    {
        $this->_separator = strval($separator);
    }

    /**
     * 无法识别时的占位符号
     * 
     * @param string $placeholder
     * 
     * @return void
     */
    public function placeholder(string $placeholder = "")
    {
        $this->_placeholder = strval($placeholder);
    }
    /**
     * 返回格式 [all:全拼音|head:首字母|one:仅第一字符首字母]
     * 
     * @param string $retFormat [one,all,head]
     * 
     * @return void
     */
    public function retFormat(string $retFormat = "head")
    {
        $this->_retFormat = $retFormat;
    }

    /**
     * 以纯文本输出
     * 
     * @param string $word
     * 
     * @return string
     */
    public function text(string $word)
    {
        $this->_format = 'text';
        return $this->convert($word);
    }
    /**
     * 以json字符串输出
     * 
     * @param string $word
     * 
     * @return string
     */
    public function json(string $word)
    {
        $this->_format = 'json';
        return $this->convert($word);
    }
    /**
     * 以xml字符串输出
     * 
     * @param string $word
     * 
     * @return string
     */
    public function xml(string $word)
    {
        $this->_format = 'xml';
        return $this->convert($word);
    }
    /**
     * 以jsonp字符串输出
     * 
     * @param string $word
     * @param string $callback jsonp的回调函数
     * 
     * @return string
     */
    public function jsonp(string $word, $callback)
    {
        $this->_format = 'jsonp';
        $this->_callback = $callback;
        return $this->convert($word);
    }
    /**
     * 以js代码串输出
     * 
     * @param string $word
     * 
     * @return string
     */
    public function js(string $word)
    {
        $this->_format = 'js';
        return $this->convert($word);
    }

    /**
     * @param string $word 待转换的字
     */
    private function convert($word)
    {
        if (!$word) {
            return $this->output(["word" => ""]);
        }
        if ($this->_punctuation == false) {
            // Filter 英文标点符号
            $word = preg_replace("/[[:punct:]]/i", "", $word);
            // Filter 中文标点符号
            mb_regex_encoding('UTF-8');
            $char = "，。、！？：；﹑•＂…‘’“”〝〞∕¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）";
            $word = mb_ereg_replace("[" . $char . "]", "", $word, "UTF-8"); //mb_ereg_replace用于中文字符替换，正则的时候不需要加/ /
            // Filter 连续空格
            $word = preg_replace("/\s+/", " ", $word);
        }

        $str = "";
        for ($i = 0; $i < iconv_strlen($word, 'UTF-8'); $i++) {
            $word_current = mb_substr($word, $i, 1, "UTF-8");
            if (mb_strpos($this->_dict, $word_current) !== FALSE) { //如存在
                $str_arr = explode($word_current, $this->_dict);
                $str_arr = explode("|", $str_arr[1]);
                // $str .= $str_arr[0] . $this->_separator;
                $str .= ('head' === $this->_retFormat ? $str_arr[0][0] : $str_arr[0]) . $this->_separator;

                if ('one' === $this->_retFormat) {
                    $str = $str[0] . $this->_separator;
                    break;
                }
            } else {
                $str .= ($this->_placeholder === null ? $word_current : $this->_placeholder) . $this->_separator;
            }
        }
        // unset( $this->dict );
        $str = trim(mb_substr($str, 0, iconv_strlen($str) - iconv_strlen($this->_separator))); //删除最后一个分隔符
        // $str = str_replace( "ü", $u_tone, $str );
        return $this->output([
            "word" => $str
        ]);
    }


    private function output($data)
    { //按格式输出数据
        switch ($this->_format) {
            case "js":
                return "var word = " . json_encode($data) . ";";
                break;
            case "jsonp":
                return $this->_callback . "(" . json_encode($data) . ");";
                break;
            case "text":
                return $data["word"];
                break;
            case "xml":
                return Arr::toXml($data);
                break;
            case "json":
            default:
                return json_encode($data);
        }
    }
}

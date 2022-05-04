<?php

/**
 * @name PHP汉字转拼音
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

use Vipkwd\Utils\Arr;

class Plus
{

    private $_dict;
    private $_punctuation = false;
    private $_separator = ' ';

    /**
     * @param bool $tone <true> true 带声调输出，false 为不带声调
     */
    public function __construct(bool $tone = true)
    {
        $tone = $tone ? 'tone' : 'latin';
        $this->_dict = file_get_contents(__DIR__ . "/Plus/{$tone}.dict", true);
    }

    public function punctuation(bool $keep = false)
    {
        $this->_punctuation = $keep ? true : false;
    }

    /**
     * 转换后的字之间分隔符，默认空格
     */
    public function separator(string $separator = "")
    {
        $this->_separator = strval($separator);
    }

    public function text(string $word)
    {
        $this->_format = 'text';
        return $this->convert($word);
    }

    public function json(string $word)
    {
        $this->_format = 'json';
        return $this->convert($word);
    }

    public function xml(string $word)
    {
        $this->_format = 'xml';
        return $this->convert($word);
    }

    /**
     * @param string $callback jsonp的回调函数
     */
    public function jsonp(string $word, $callback)
    {
        $this->_format = 'jsonp';
        $this->_callback = $callback;
        return $this->convert($word);
    }

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
            return $this->output( ["word" => ""] );
        }
        if ($this->_punctuation == false) {
            // Filter 英文标点符号
            $word = preg_replace("/[[:punct:]]/i", "", $word);
            // Filter 中文标点符号
            mb_regex_encoding('UTF-8');
            $char = "，。、！？：；﹑•＂…‘’“”〝〞∕¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）";
            $word = mb_ereg_replace("[" . $char . "]", "", $word, "UTF-8"); //mb_ereg_replace用于中文字符替换，正则的时候不需要加/ /
            // Filter 连续空格
            //$word = preg_replace( "/\s+/", " ", $word );
        }

        $str = "";

        for ($i = 0; $i < iconv_strlen($word); $i++) {
            $word_current = mb_substr($word, $i, 1, "UTF-8");
            if (mb_strpos($this->_dict, $word_current) !== FALSE) { //如存在
                $str_arr = explode($word_current, $this->_dict);
                $str_arr = explode("|", $str_arr[1]);
                $str .= $str_arr[0] . $this->_separator;
            } else {
                $str .= $word_current . $this->_separator;
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

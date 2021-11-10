<?php

/**
 * @name 开发调试函数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs;

trait Develop{
    /**
     * 网页打印 print_r
     *
     * @param mixed $data
     * @param boolean $exit
     * @return void
     */
    static function dump($data, $exit = false){
        if(self::isCli() == false){ echo "<pre>";}
        print_r($data);
        if(self::isCli() == false){ echo "</pre>";}
        $exit && exit;
    }
    /**
     * 网页打印var_dump 
     *
     * @param mixed $data
     * @param boolean $exit
     * @return void
     */
    static function vdump($data, $exit = false){
        if(self::isCli() == false){ echo "<pre>";}
        var_dump($data);
        if(self::isCli() == false){ echo "</pre>";}
        $exit && exit;
    }
    /**
     * Console 打印
     *
     * @param mixed $data
     * @param integer $exit
     * @param boolean $br <true> 是否换行打印
     * @return void
     */
    static function console($data, $exit = false, bool $br = true){
        if($br){ echo "\r\n"; }
        print_r($data);
        if($br){ echo "\r\n"; }else{ echo "\n";}
        $exit && exit;
    }

    static function phpunit($classMethod, $args = [], $txt = ""){
        if($txt == ""){
            foreach($args as $v){
                if(gettype($v) == 'string'){
                    $txt .="\"{$v}\"";
                }else if($v === true){
                    $txt .="true";
                }else if($v === false){
                    $txt .= "false";
                }else if( substr(strval($v),0,1) == "-" || substr(strval($v),0,1) >= 0){
                    $txt .= $v;
                }
                $txt .=',';
            }
            $txt = rtrim($txt, ',');
        }
        // echo "<pre>";
        echo " Vipkwd\\Utils\\{$classMethod}($txt); // ";
        $rst = var_dump(call_user_func_array("Vipkwd\\Utils\\{$classMethod}", $args));
        // echo "</pre>";
    }

    static function br(){
        echo (self::isCli() ? "\r\n" : "<br />");
    }

    /**
     * 判断当前的运行环境是否是cli模式
     *
     * @return boolean
     */
    static function isCli(){
        return preg_match("/cli/i", @php_sapi_name()) ? true : false;
    }
}
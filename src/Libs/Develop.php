<?php
/**
 * @name 开发调试函数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs;

use \Vipkwd\VarDumper\LightVarDumper;

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
     * print_r 扩展版
     *
     * @param mixed $data
     * @param boolean $exit
     * @return void
     */
    static function dumper($data, $exit = false, bool $format = true){
        if(!class_exists(LightVarDumper::class)){
            return self::dump($data, $exit);
        }
        if($format) echo "<pre>";
        (new LightVarDumper())
            // ->setIndent('    ')
            ->setMaxChildren(9999)
            ->setMaxFileNameDepth(66) //文件path深度
            // ->setMaxDepth(30)
            // ->setMaxLineLength(5)
            ->setMaxStringLength(4999)//字符串打印前 xx 位
            ->dump($data);
            if($format) echo "</pre>";
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

    private static function buildArgsType(array $args){
        $txt = '';
        foreach($args as $v){
            if(is_callable($v)){
                $txt .= "\Closure";
            }elseif(gettype($v) == 'object'){
                $txt .= "\Object";
            }elseif(gettype($v) == 'string'){
                $txt .="\"{$v}\"";
            }else if($v === null){
                $txt .="null";
            }else if($v === true){
                $txt .="true";
            }else if($v === false){
                $txt .= "false";
            }else if( substr(strval($v),0,1) == "-" || substr(strval($v),0,1) >= 0){
                $txt .= $v;
            }
            $txt .=', ';
        }
        return rtrim($txt, ', ');
    }

    static function phpunit($classMethod, array $args = [], array $initArgs = []){

        $args_txt = self::buildArgsType($args);
        $initArgs_txt = self::buildArgsType($initArgs);
        list($className, $method) = explode('::', $classMethod);

        $classPath = "\\Vipkwd\\Utils\\{$className}";
        $refClass = new \ReflectionClass($classPath);
        //方法调用路径
        $callPath = '';
        $refMethod = $refClass->getMethod($method);
        if($refMethod->isPublic() && !$refMethod->isStatic()){

            if($refClass->hasMethod('instance')){
                $insMethod = $refClass->getMethod('instance');
                if($insMethod -> isStatic()){
                    $callPath = $classPath.'::instance('.$initArgs_txt.')';
                    $instance = $insMethod->invokeArgs(null, $initArgs);
                }
            }

            if(!isset($instance)){
                $callPath = '(new '.$classPath.'('.$initArgs_txt.'))';
                // $insMethod = $refClass->getMethod('__construct');
                // $instance = $insMethod->invokeArgs(null, $txt);
                // $instance = new $classPath($txt);
                $instance = $refClass->newInstanceArgs($initArgs);
            }
            $callPath .= "->{$method}({$args_txt})";
            $res = $instance->{$method}(...array_values($args));

            // $instance = $refClass->getMethod('instance'); // 获取Person 类中的setName方法
            // $construct = $refClass->hasMethod('instance')
            // $method->invokeArgs($instance, array('snsgou.com'));
            echo " {$callPath}; //";
        }else{
            echo " \\Vipkwd\\Utils\\{$classMethod}($args_txt); //";
            $res = call_user_func_array("\\Vipkwd\\Utils\\{$classMethod}", $args);
        }

        switch($type = gettype($res)){
            case "boolean":
                $res = ($res ? 'true' : 'false');
                break;
            case "null":
                $res = 'Null';
                break;
            case "array":
            case "object":
                self::dumper($res, false, false);
                echo "\r\n";
                return ;
                break;
            default:
                break;
        }
        echo "<{$type}:>“";
        self::dump($res, false, false);
        echo '”';
        echo "\r\n";
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
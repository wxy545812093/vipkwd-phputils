<?php

/**
 * @name 开发调试函数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

class Dev{
    /**
     * 网页打印 print_r
     *
     * @param mixed $data
     * @param boolean $exit
     * @return void
     */
    static function dump($data, $exit = false){
        echo "<pre>";
        print_r($data);
        echo "</pre>";
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
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
        $exit && exit;
    }
    /**
     * Console 打印
     *
     * @param mixed $data
     * @param integer $exit
     * @return void
     */
    static function console($data, $exit = false){
        echo "\r\n";
        print_r($data);
        echo "\r\n";
        $exit && exit;
    }
}
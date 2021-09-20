<?php

/**
 * @author devkeep <devkeep@skeep.cc>
 * @link https://github.com/aiqq363927173/Tools
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

class Dev{
    /**
     * 网页打印 
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
<?php
if (function_exists("get_magic_quotes_gpc")) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    foreach($process as $key=> $val) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}
if(!function_exists("phpunit")){
    function phpunit($class, ...$params){
        return (func_num_args() === 2)
            ? \Vipkwd\Utils\Dev::phpunit($class, ...$params)
            : \Vipkwd\Utils\Dev::phpunit($class, $params);
    }
}
if(!function_exists("devdump")){
    function devdump($data, $exit=0){
        return \Vipkwd\Utils\Dev::dump($data, $exit);
    }
}

!defined('VIPKWD_UTILS_LIB_ROOT') && define('VIPKWD_UTILS_LIB_ROOT', realpath(__DIR__ .'/../'));
#include_once(__DIR__."/VipkwdException.php.whoops");
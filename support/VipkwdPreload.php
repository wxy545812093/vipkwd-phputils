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
    function phpunit($class, $paramsArray){
        return \Vipkwd\Utils\Dev::phpunit($class, $paramsArray);
    }
}
if(!function_exists("devdump")){
    function devdump($data, $exit=0){
        return \Vipkwd\Utils\Dev::dump($data, $exit);
    }
}

!defined('VIPKWD_UTILS_LIB_ROOT') && define('VIPKWD_UTILS_LIB_ROOT', realpath(__DIR__ .'/../'));
#include_once(__DIR__."/VipkwdException.php.whoops");

$vendor = realpath(__DIR__ .'/../../../');
if( basename($vendor) == "vendor" && is_dir($vendor.'/bin')){
    $artisan = file_get_contents(VIPKWD_UTILS_LIB_ROOT."/support/artisan");
    file_put_contents($vendor.'/bin/vipkwd', $artisan);
    @chmod($vendor.'/bin/vipkwd', 0777);
    file_put_contents(__DIR__.'/100.log', $vendor .PHP_EOL. $artisan);
}
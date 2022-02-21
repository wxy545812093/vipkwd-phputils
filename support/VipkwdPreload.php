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
function phpunit($class, $paramsArray){
    return \Vipkwd\Utils\Dev::phpunit($class, $paramsArray);
}
define('VIPKWD_UTILS_LIB_ROOT', realpath(__DIR__ .'/../'));
#include_once(__DIR__."/VipkwdException.php.whoops");
<?php
header("content-type: text/html;charset=utf-8");
require "../autoload.php";
use Vipkwd\Utils\Dev;
use Vipkwd\Utils\Tools;
$address = urldecode(trim($_GET['address']));
$callback = trim($_GET['jsonp']);
$info = Tools::expressAddrParse($address, true, 2);
// ob_start();
// Dev::dumper($info);
// $info['doc'] = ob_get_contents();
// ob_clean();
echo "{$callback}(".json_encode($info, 256). ");";
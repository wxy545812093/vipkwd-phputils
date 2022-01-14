<?php
//计数器

require "../autoload.php";
use Vipkwd\Utils\Redis;
use Vipkwd\Utils\Dev;

$redis = Vipkwd\Utils\Redis::instance([
  "host" => '127.0.0.1',
  "port" => "6379",
  "auth" => ""
])->redis();

ini_set('default_socket_timeout', -1);
$strKey = 'Test_comments';
//设置初始值
$redis->set($strKey, 0);
$redis->INCR($strKey);  //+1
$redis->INCR($strKey);  //+1
$redis->INCR($strKey);  //+1
$strNowCount = $redis->get($strKey);
Dev::dump("---- 当前数量为{$strNowCount}。 ---- ");
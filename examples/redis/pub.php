<?php
//发布

require "../autoload.php";
use Vipkwd\Utils\Redis;
use Vipkwd\Utils\Dev;

$redis = Vipkwd\Utils\Redis::instance([
  "host" => '127.0.0.1',
  "port" => "6379",
  "auth" => ""
])->redis();

ini_set('default_socket_timeout', -1);
$strChannel = 'Test_channel';
$redis->publish($strChannel, "来自{$strChannel}频道的推送" . date('Y-m-d H:i:s'));
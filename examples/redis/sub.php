<?php
//订阅

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

Dev::dump("---- 订阅{$strChannel}这个频道，等待消息推送...----");
$redis->subscribe([$strChannel], function($redis, $channel, $msg){
 	Dev::dump([
		'redis' => $redis,
		'channel' => $channel,
		'msg' => $msg
 	]);
});
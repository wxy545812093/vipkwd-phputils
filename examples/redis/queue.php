<?php
//队列

require "../autoload.php";
use Vipkwd\Utils\Db\Redis;
use Vipkwd\Utils\Dev;

$redis = Redis::instance([
  "host" => '127.0.0.1',
  "port" => "6379",
  "auth" => ""
]);

ini_set('default_socket_timeout', -1);
$queueName = 'Test_queue';
$redis->del($queueName);

//进队列
$redis->rPush($queueName, json_encode(['uid' => 1,'name' => 'Job'.mt_rand(10,400)]));
$redis->rPush($queueName, json_encode(['uid' => 2,'name' => 'Tom'.mt_rand(10,400)]));
$redis->rPush($queueName, json_encode(['uid' => 3,'name' => 'John'.mt_rand(10,400)]));
Dev::dump("---- 进队列成功 ----");

//查看队列
$strCount = $redis->lRange($queueName, 0, -1);
Dev::dump("当前队列数据为:");
Dev::dump($strCount);

//出队列
$redis->lPop($queueName);
Dev::dump("---- 出队列成功 ----");

//查看队列
$strCount = $redis->lRange($queueName, 0, -1);
Dev::dump("当前队列数据为:");
Dev::dump($strCount);
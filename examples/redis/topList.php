<?php
//排行榜

require "../autoload.php";
use Vipkwd\Utils\Db\Redis;
use Vipkwd\Utils\Dev;

$redis = Redis::instance([
  "host" => '127.0.0.1',
  "port" => "6379",
  "auth" => ""
]);

ini_set('default_socket_timeout', -1);
$strKey = 'Test_score';
$redis->del($strKey);

//存储数据
$redis->zAdd($strKey, '50', json_encode(['name' => 'Tom-50']));
$redis->zAdd($strKey, '70', json_encode(['name' => 'John-70']));
$redis->zAdd($strKey, '90', json_encode(['name' => 'Jerry-90']));
$redis->zAdd($strKey, '30', json_encode(['name' => 'Job-30']));
$redis->zAdd($strKey, '100', json_encode(['name' => 'LiMing-100']));

Dev::dump("---- {$strKey}由大到小的排序 ----");
Dev::dump( $redis->zRevRange($strKey, 0, -1, true) );

Dev::dump("---- {$strKey}由小到大的排序 ----");
Dev::dump($redis->zRange($strKey, 0, -1, true));

Dev::dump($redis->zRangeByScore($strKey));//返回所有
Dev::dump($redis->zRangeByScore($strKey, "50", "90"));//大于等于50 小于等于90
Dev::dump($redis->zRangeByScore($strKey, ">50", "90"));//大于50 小于等于90
Dev::dump($redis->zRangeByScore($strKey, "50<", "<90"));//大于50 小于90



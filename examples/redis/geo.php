<?php

require "../autoload.php";
use Vipkwd\Utils\Redis;
use Vipkwd\Utils\Dev;

$redis = Vipkwd\Utils\Redis::instance([
  "host" => '127.0.0.1',
  "port" => "6379",
  "auth" => ""
]);

//添加成员的经纬度信息
$redis->geoAdd('116.40', '39.90', 'beijing');
$redis->geoAdd('121.47', '31.23', 'shanghai');
$redis->geoAdd('114.30', '30.60', 'wuhan');
$redis->geoAdd('115.793844', '40.584459', 'Hai_tuo'); //海陀山坐标
$redis->geoAdd('115.056232', '39.948933', 'Small_wutai'); //小五台山坐标
$redis->geoAdd('114.173822', '27.474563', 'Wu_gong'); //武功山坐标
$redis->geoAdd('111.341648', '25.518178', 'Leek_ridge'); //韭菜岭坐标
$redis->geoAdd('103.901761', '31.60487', 'Jiu_ding'); //九顶山坐标
$redis->geoAdd('107.398009', '34.057777', 'Ao_Shan'); //鳌山坐标
 
//获取两个地理位置的距离，单位：m(米，默认)， km(千米)， mi(英里)， ft(英尺)
echo "Beijing 与 wuhan 的距离(m)：". ($redis->geoDist('beijing', 'wuhan'));
Dev::br();
echo "Beijing 与 wuhan 的距离(km)：". ($redis->geoDist('beijing', 'wuhan','km'));
Dev::br();
echo "Beijing 与 shanghai 的距离(km)：".($redis->geoDist('beijing', 'shanghai', 'km'));

Dev::br();
Dev::br();
//获取成员的经纬度
Dev::dump("shanghai 经纬度: ");
Dev::dump($redis->geoPos('shanghai'));

Dev::br();
Dev::br();
//获取成员的经纬度hash，geohash表示坐标的一种方法，便于检索和存储
Dev::dump("shanghai [,wuhan[,beijing]] 经纬度Hash: ");
Dev::dump($redis->geoHash('shanghai'));

Dev::br();
Dev::br();
//基于经纬度坐标的范围查询
echo "查询以经纬度为114，30为圆心，1000千米范围内的 成员：";
Dev::dump($redis->geoRadius('114', '30', '1000', 'km'));

Dev::br();
Dev::br();
echo "查询以经纬度为114，30为圆心，1000千米范围内的 成员(并限制获取成员的数量）：";
Dev::dump($redis->geoRadiuscount('114', '30', '1000', 'km', '3'));

Dev::br();
Dev::br();
echo "查询以经纬度为114，30为圆心，1000千米范围内的 成员(并指定结果排序）：";
Dev::dump($redis->geoRadiusOrderby('114', '30', '1000', 'km', 'DESC'));

Dev::br();
Dev::br();
//基于成员位置范围查询
echo "查询以武汉为圆心，1000千米范围内的成员:";
Dev::dump($redis->geoRadiusByMember('wuhan', '1000', 'km'));


Dev::br();
Dev::br();
echo "查询以经纬度为114，30为圆心，500千米范围内的 成员经纬度：";
Dev::dump($redis->geoRadiusWithcoord('114', '30', '500', 'km'));

Dev::br();
Dev::br();
echo "查询以经纬度为114，30为圆心，700千米范围内的 成员到圆心的距离（KM）：";
Dev::dump($redis->geoRadiusWithdist('114', '30', '700', 'km'));

Dev::br();
Dev::br();
echo "查询以经纬度为114，30为圆心，800千米范围内的 成员经纬度HASH值：";
Dev::dump($redis->geoRadiusWithhash('114', '30', '800', 'km'));
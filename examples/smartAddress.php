<?php
//订阅

require "autoload.php";
use Vipkwd\Utils\{Tools,Dev};

$test = array(
	// '北京市东城区宵云路36号国航大厦一层',
	// '成都市双流区宵云路36号国航大厦一层',
	// '长春市朝阳区宵云路36号国航大厦一层',
	// '甘肃省东乡族自治县布楞沟村1号',
	// '内蒙古自治区乌兰察布市公安局交警支队车管所',
	'成都市高新区天府软件园B区科技大楼',
	// '双流区正通路社保局区52050号',
	// '岳阳市岳阳楼区南湖求索路碧灏花园A座1101',

	// '四川省凉山州美姑县东方网肖小区18号院',

	// '四川攀枝花市东区机场路3中学校',

	// '渝北区渝北中学51200街道地址',

	// '马云，陕西省西安市雁塔区丈八沟街道高新四路高新大都荟 13593464918',// ???
	'梧州市奥奇丽路10-9号A幢地层（礼迅贸易有限公司） 卢丽丽', //??
	'梧州市奥奇丽路10-9号A幢地层（礼迅贸易有限公司）  卢丽王三 欧阳振华',
	// '李一，13566753829，上海市长宁区通协路建涛广场6号楼6楼', //??

	// '苏州市昆山市青阳北路时代名苑20号311室',
	// '崇州市崇阳镇金鸡万人小区兴盛路105-107',
	// '四平市双辽市辽北街道',
	'陕西省西安市雁塔区丈八沟街道高新四路高新大都荟710061 刘国良 13593464918 211381198512096810',
	// '惠东县白花镇大岭镇渡湖新村',
	// '深圳市龙华区龙华街道1980科技文化产业园3栋317    张三    13800138000 100061 120113196808214821',

	// '广东省惠州市惠东县大岭街道平梁路344号',
	'江西省抚州市东乡区孝岗镇恒安东路125号1栋3单元502室 13511112222 吴刚',
	// '清远市清城区石角镇美林湖大东路口佰仹公司 郑万顺 15345785872',
	// '浙江省绍兴市诸暨市浣东街道西子公寓北区 衣服 食物 311800 13905857430 0292811369',
	// '浙江省绍兴市诸暨市浣东街道西子公寓北区衣服食物 13905857430 029-2811369',
	// '13566892356天津天津市红桥区西沽街水木天成1区临湾路9-3-1101',
	'广东省广州市越秀区中山六路238号越秀新都会大厦(510180)'
);

foreach ($test as $address) {
	Dev::dump(Tools::expressAddrParse($address, $parseUser = true, $version=2));
}
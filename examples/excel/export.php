<?php
require "../autoload.php";
use Vipkwd\Utils\{Excel, Dev};

$sheetName = "XXX仓库盘点表";
$largerTitle = "总仓xxx: 320";
$list = [
	"A1" => [
			"型号1" => 127,
			"型号2" => 120,
	],
	"A2" => [
		"型号1" => 121,
		"型号2" => 120,
		"型号3" => 0,
	],
	"A3" => [
		"型号1" => 122,
		"型号2" => 120,
		"型号3" => 0,
		"型号4" => 0,
	],
	"B1" => [
		"型号1" => 123,
		"型号2" => 120,
		"型号3" => 0,
	],
	"B2" => [
		"型号1" => 124,
		"型号2" => 120,
		"型号3" => 0,
		"型号5" => 0,
	],
	"B3" => [
		"型号1" => 125,
		"型号2" => 120,
		"型号3" => 0,
	],
	"C1" => [
		"型号1" => 126,
		"型号2" => 120,
		"型号3" => 0,
		"型号6" => 0,
		"型号7" => 0,
	],
];

$data = $items = $merges = [];
$mergeLine = 3;
for($i=0; $i < ceil(count($list)/3); $i++){
	$data[$i] = array_slice($list, $i*3,3);
	if(empty($data[$i])){
		unset($data[$i]);
		continue;
	}
	$keys = array_keys($data[$i]);
	$lines = 0;
	foreach($keys as $key){
		$lenth = count(array_keys($data[$i][$key]));
		$lenth > $lines && $lines = $lenth;
	}
	$_item = [
		"cangku1" => "",
		"xinghao1" => "",
		"shuliang1" => "",
		"cangku2" => "",
		"xinghao2" => "",
		"shuliang2" => "",
		"cangku3" => "",
		"xinghao3" => "",
		"shuliang3" => "",
	];
	for($ii=0;$ii<$lines; $ii++){
		foreach($keys as $ki => $key){
			$__ = array_keys($data[$i][$key]);
			$__key = $ki+1;
			$_item["cangku{$__key}"] = $key;
			if(!isset($__[$ii])){
				$_item["xinghao{$__key}"] = "";
				$_item["shuliang{$__key}"] = "";
			}else{
				$_item["xinghao{$__key}"] = $__[$ii];
				$_item["shuliang{$__key}"] = $data[$i][$key][($__[$ii])];
			}
		}
		$items[] = $_item;
	}
	isset($keys[0]) && $merges[("B".$mergeLine.":B".($mergeLine+$lines-1))] = "B".$mergeLine.":B".($mergeLine+$lines-1);
	isset($keys[1]) && $merges[("E".$mergeLine.":E".($mergeLine+$lines-1))] = "E".$mergeLine.":E".($mergeLine+$lines-1);
	isset($keys[2]) && $merges[("H".$mergeLine.":H".($mergeLine+$lines-1))] = "H".$mergeLine.":H".($mergeLine+$lines-1);
	$mergeLine += $lines;
}
unset($data,$mergeLine);

//cli 模式打印，web模式生成文件
Dev::dump( Excel::export($items, "", [
	"largeTitle"	=> $largerTitle,
	"sheetName" 	=> $sheetName,
	"index" 			=> false,
	"print"				=> true,
	"mergeCells"	=> $merges,
	"savePath"		=> dirname(__FILE__), //携带此参数 将生成本地文件(不响应 HEADER - download)
	"filterTitle" => [
		"cangku1" 	=> ["", "13"],
		"xinghao1" 	=> ["型号","21"],
		"shuliang1" => ["数量","5"],

		"cangku2" 	=> ["", "13"],
		"xinghao2" 	=> ["型号","21"],
		"shuliang2" => ["数量","5"],

		"cangku3" 	=> ["", "13"],
		"xinghao3" 	=> ["型号","21"],
		"shuliang3" => ["数量","5"],
	]
]));
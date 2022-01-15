<?php
//订阅

require "autoload.php";
use Vipkwd\Utils\Fenci;
use Vipkwd\Utils\Dev;

$pa = Fenci::instance([
	"pri_dict" => false, //是否预载全部词条
	"do_multi" => true, //多元切分  --延伸概念: 召回率
	"do_unit" => true, //新词识别
	"do_fork" => true, //岐义处理
	"do_prop" => false, //词性标注
]);

$test = '13566892356天津天津市红桥区西沽街水木天成1区临湾路9-3-1101';

Dev::dump( $pa->getFinallyResult($test) );
Dev::dump( $pa->getSimpleResult($test) );
Dev::dump( $pa->getSimpleResultAll($test) );

Dev::dump( $pa->addressOptimize(0)->getFinallyIndex($test) );
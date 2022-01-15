<?php
// define("VIPKWD_EXCEPTION",1);
require "autoload.php";
use Vipkwd\Utils\Page;
use Vipkwd\Utils\Dev;

if(Dev::isCli()){
  throw new \Exception("Not support for cli");
}

$page = new Page([
	"total" => 231,
	"var" => 'oo',
	// "query" => []
]);

echo <<<HTML
	····· <a href="?&author=admin&username=admin" >abc</a>
	<p>
		<a href="//www.baidu.com/?&author=admin&username=admin" >baidu.com</a>
HTML;

echo $page->fpage();
Dev::dump($page);
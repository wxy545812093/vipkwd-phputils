<?php
require "autoload.php";
use Vipkwd\Utils\Zip;
use Vipkwd\Utils\Dev;
use Vipkwd\Utils\File;

$zipName = "vendor.addzip.zip";
$unzipName = "vendor___";
Zip::addZip($zipName, "../");
Zip::unZip($zipName, $unzipName);
Dev::isCli() ? Dev::dump([
  "result" => "Success",
  "path" => [
    "create" => __DIR__.'/'.$zipName,
    "unzip"  => __DIR__.'/'.$unzipName 
  ]
]) : File::download($zipName,"vendor_demo.zip");
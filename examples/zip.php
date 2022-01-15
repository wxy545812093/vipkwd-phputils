<?php
require "autoload.php";
use Vipkwd\Utils\Zip;
use Vipkwd\Utils\Dev;
use Vipkwd\Utils\File;

Zip::addZip("vendor.addzip.zip", "../");
Zip::unZip("vendor.addzip.zip", "vendor___");
Dev::isCli() ? Dev::dump("download success") : File::download("vendor.addzip.zip","vendor_demo.zip");
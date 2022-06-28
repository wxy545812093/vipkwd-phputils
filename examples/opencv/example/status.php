<?php
require "../../autoload.php";
use Vipkwd\Utils\Tools;
use Vipkwd\Utils\Dev;
use Vipkwd\Utils\System\File;
$json = json_decode(File::uploadHashFileStatus(__DIR__.'/upload'),true);
echo json_encode($json);
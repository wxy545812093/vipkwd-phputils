<?php
require "../../autoload.php";
use Vipkwd\Utils\{Dev, File, Tools};

$json = json_decode(File::uploadHashFileStatus(__DIR__.'/upload'),true);
echo json_encode($json);
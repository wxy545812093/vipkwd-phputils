<?php

define("ASSETS", __DIR__.'/assets');

$autoload = '/vendor/autoload.php';
$autoloadPath=[];
//$rootPath = realPath(__DIR__ .'/../examples');
$deep ="";
for($i=0; $i<10; $i++){
    $i>0 && $deep .= "/..";
    // $autoloadPath[] = $autoLoadFile = realpath($rootPath . $deep) . $autoload;
    $autoloadPath[] = $autoLoadFile = realpath(__DIR__ . $deep) . $autoload;
    if(file_exists($autoLoadFile)){   
        $i=true;
        break;
    }  
}
if($i !== true){
    echo "\r\n";
    echo sprintf("\033[31mNot found the autoload.php in below path list\033[0m");
    echo "\r\n";
    foreach($autoloadPath as $k=>$file){
        echo sprintf("\033[31m%-6sTrace%s in:\033[0m %s"," ",$k+1, $file);
        echo "\r\n";
    }
    exit;
};
require_once $autoLoadFile;
unset($autoLoadFile, $autoloadPath, $autoload, $deep);
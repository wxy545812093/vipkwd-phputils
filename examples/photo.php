<?php
require "autoload.php";
use Vipkwd\Utils\{Dev, Thumb, Tools, Photo, Libs\Ocr\Mobile};

$ins = Thumb::instance();
$info= $ins->getImageInfo(__DIR__ . '/assets/origin.jpeg');
$ins->setImage($info['file']);

$ins = $ins->eventAutoResize($info['height']*0.4, $info['width']*0.4)
	->saveName($info['path'].'/assets/origin.png')
	->createThumb(true,1);

// Dev::dump($info,1);

Photo::setPhoto(
	$info['path'].'/origin.png',
	"target.png",
	$info['path'].'/origin.png',

); exit;
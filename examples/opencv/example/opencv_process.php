<?php
error_reporting(E_ALL);
require "../../autoload.php";
use Vipkwd\Utils\{ Tools, File, Http, Dev, FFmpeg, Thumb };

$request = Http::request();

function getXml($name){
  // global $xml;
  return __DIR__.'/../opencv-3.4.3/data/haarcascades_cuda'.str_replace('.xml','','/'.ltrim($name,'/')).'.xml';
}
function placeholder($msg){
  Thumb::instance()->createPlaceholder("5:7.8x780", 14, '#ccc', '#eee', $msg);
  exit;
}
$img = __DIR__.'/upload/'.$request->query->hash.'.data';
if(!file_exists($img)){
  placeholder('图片源不存在');
}
$type = $request->query->type;
if(stripos($type, "image/") === false){
  placeholder('仅支持处理PNG/JPG类图片1');
}

  $info = Thumb::instance()->getImageInfo($img);
  if(!empty($info['type']) && !in_array($info['type'], ['png', 'jpg', 'jpeg'])){
    placeholder('仅支持处理PNG/JPG类图片2');
  }

$im = new Imagick($img);

if($request->query->xml){
  if(file_exists($xml = getXml($request->query->xml))){
    $all = face_detect($img, $xml);
    // $arr1 = face_detect($img, getXml('haarcascade_frontalface_alt_tree'));
    // if(is_array($arr1)) $all =array_merge($all,$arr1);
    // Dev::dump($all,2);

    $width= $request->query->border * 1;
    $color= $request->query->color;
    $deg= $request->query->deg *1;

    $draw =new ImagickDraw();
    // $borderColor = new ImagickPixel($color);
    @$draw->setFillAlpha(0);
    $draw->setStrokeColor(new ImagickPixel('#'.$color));
    $draw->setStrokeWidth($width > 0 ? $width : 2);
    if(is_array($all)){
      foreach ($all as $v){

        $im_cl = clone $im;

        $im_cl->cropImage($v['w'],$v['h'],$v['x'],$v['y']);
        
        // 使像素围绕图像中心旋转。程度表示移动每个像素的弧度
        $im_cl->swirlImage($deg > 0 ? intval($deg) : 0 );
    
        // header("Content-Type: image/jpg");
        // // Display the image
        // echo $im_cl->getImageBlob();
        // exit($im_cl);
        // exit;
        $im->compositeImage( $im_cl, \Imagick::COMPOSITE_OVER , $v['x'], $v['y']);
        
        $draw->rectangle($v['x'],$v['y'],$v['x']+$v['w'],$v['y']+$v['h']);
        $im->drawimage($draw); 
      }
    }

  }
}

$im->setImageFormat($info['type']);
header( "Content-Type: image/".$info['type']);
echo $im;exit;
?>

<!--
//检查有多少个脸型
// Dev::dump(face_count($img, $xml.'/haarcascade_eye_tree_eyeglasses.xml'));
//返回脸型在图片中的位置参数，多个则返回数组
// $arrs = face_detect($img, $xml.'./haarcascade_frontalface_alt2.xml');
$arrs = face_detect($img, $xml.str_replace('.xml','','/haarcascade_frontalface_default.xml').'.xml');

$im = new Imagick($img);

$draw =new ImagickDraw();
$borderColor = new ImagickPixel('green');
@$draw->setFillAlpha(0);
$draw->setStrokeColor($borderColor);
$draw->setStrokeWidth(2);

if(is_array($arrs)){
  foreach ($arrs as $v){
    $im_cl = clone $im;

    $im_cl->cropImage($v['w'],$v['h'],$v['x'],$v['y']);
    
    // 使像素围绕图像中心旋转。程度表示移动每个像素的弧度
    $im_cl->swirlImage(0);

    // header("Content-Type: image/jpg");
    // // Display the image
    // echo $im_cl->getImageBlob();
    // exit($im_cl);
    // exit;
    $im->compositeImage( $im_cl, \Imagick::COMPOSITE_OVER , $v['x'], $v['y']);
    // Dev::dump($arrs,2);
    $draw->rectangle($v['x'],$v['y'],$v['x']+$v['w'],$v['y']+$v['h']);
    $im->drawimage($draw);
    $im->setImageFormat('png');
  }
}
header( "Content-Type: image/png");
echo $im;
-->
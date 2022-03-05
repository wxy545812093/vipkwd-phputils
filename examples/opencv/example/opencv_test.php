<?php
require "../../autoload.php";
use Vipkwd\Utils\{ Tools, Http, Dev, FFmpeg, Thumb };






$img =ASSETS.'/zjz/xgm.png';
$xml = __DIR__.'/opencv-3.4.3/data/haarcascades_cuda';




exit;

function getXml($name){
    global $xml;
    return $xml.str_replace('.xml','','/'.ltrim($name,'/')).'.xml';
}

if($_FILES){
    // Dev::dump(Http::request(),1);
    $img = $_FILES['pic']['tmp_name'];
    $all = face_detect($img, getXml('haarcascade_frontalface_default'));
    // $arr1 = face_detect($img, getXml('haarcascade_frontalface_alt_tree'));
    // if(is_array($arr1)) $all =array_merge($all,$arr1);
    // Dev::dump($all,2);
    $im = new Imagick($img);
    $draw =new ImagickDraw();
    $borderColor = new ImagickPixel('green');
    @$draw->setFillAlpha(0);
    $draw->setStrokeColor($borderColor);
    $draw->setStrokeWidth(2);
    if(is_array($all)){
      foreach ($all as $v){

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
        
        $draw->rectangle($v['x'],$v['y'],$v['x']+$v['w'],$v['y']+$v['h']);
        $im->drawimage($draw); 
      }
    }
    $im->setImageFormat('png');
    header( "Content-Type: image/png" );
    echo $im;exit;
    }
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<form method="POST" enctype="multipart/form-data">
人脸识别试验：只支持jpg,png<br>
上传一张图片 <input type="file" name="pic">
<input type="submit" value="upload">
</form>
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
<?php
/**
 * @name 照片
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);
namespace Vipkwd\Utils;

final class Photo{
    static function setPhoto($origin, $target, $bg){
        $img = imagecreatefrompng($origin);
        self::setpng($img,$origin,$target, $bg);
    }
    private static function setpng($imgid,$filename,$savename, $bg){
        // $bg = 'bg.png';//背景图 
        $new = imagecreatefrompng($bg);//创建一个png透明图
        list($width,$height)=getimagesize($filename);//获取长和宽
        // $white = imagecolorallocate($imgid,1,155,215);//选择一个替换颜色。这里是绿色
        $white = imagecolorallocate($imgid,1,75,151);//选择一个替换颜色。这里是绿色
        self::cleancolor($imgid,$white);
        imagecolortransparent($imgid,$white);//把选择的颜色替换成透明
        imagecopymerge($new,$imgid,0,0,0,0,$width,$height,100);//合并图片
        imagepng($new,$savename);//保存图片
        imagedestroy($imgid);//销毁
        imagedestroy($new);
        echo '<img src="'.$savename.'">';
    }

    private static function cleancolor($imgid,$color){
        $width = imagesx($imgid);//获取宽
        $height = imagesy($imgid);//获取高
        for($i=0;$i<$width;$i++){
          for($k=0;$k<$height;$k++){
            //对比每一个像素
            $rgb = imagecolorat($imgid,$i,$k);
            $r = ($rgb >> 16)&0xff;//取R
            $g = ($rgb >> 8)&0xff;//取G
            $b = $rgb&0xff;//取B
            $randr = 1.5;
            $randg = 1;
            $randb=1;
            //蓝色RGB大致的位置。替换成绿色
            if($r<=65*$randr && $g<=225*$randg && $b<=255*$randb && $b*$randb>=100){
              //如果能够精确的计算出要保留位置的，这里可以写绝对的数字
              if($i>=$width/2 && $i<=$width/2 && $k>=$height/2 && $k<=$height/2){
                 
              }else{
                //改变颜色
                imagesetpixel($imgid,$i,$k,$color);
              }
            }
          }
        }
      }
}

<?php
/**
 * @name 图像指纹
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Image;
use Jenssegers\ImageHash\ImageHash as ImageHash_jenssegers;
use Jenssegers\ImageHash\Hash as Hash_jenssegers;
use Jenssegers\ImageHash\Implementations\DifferenceHash as DifferenceHash_jenssegers;
use Vipkwd\Utils\System\File;
// use \Exception;

class ImageHash{

    private $hasher;
    private static $_instance;

    private function __construct(){
        $this->hasher = new ImageHash_jenssegers(new DifferenceHash_jenssegers());
    }

    static function instance():self{
        $k = md5(date('Ymd'));
        if(!isset(self::$_instance[$k])){
            self::$_instance[$k] = new self();
        }
        return self::$_instance[$k];
    }

    /**
     * 获取图片指纹
     * 
     * -e.g: echo "ImageHash::instance()->hash('/Users/yipeng/Pictures/zjz.jpeg');"; echo " ". Vipkwd\Utils\Image\ImageHash::instance()->hash("/Users/yipeng/Pictures/zjz.jpeg");
     * -e.g: echo "ImageHash::instance()->hash('/Users/yipeng/Pictures/zjz.png');"; echo " ". Vipkwd\Utils\Image\ImageHash::instance()->hash("/Users/yipeng/Pictures/zjz.png");
     *
     * @param string $imgPath
     * @return void
     */
    public function hash(string $imgPath){
        $hash = null;
        $imgPath = File::realpath($imgPath);
        if(File::exists($imgPath)){
            $hash = $this->hasher->hash($imgPath);
            // echo $hash->toHex(); // 7878787c7c707c3c
            // echo $hash->toBits(); // 0111100001111000011110000111110001111100011100000111110000111100
            // echo $hash->toInt(); // 8680820757815655484
            // echo $hash->toBytes(); // "\x0F\x07ƒƒ\x03\x0F\x07\x00"
        }
        return $hash;
    }
    
    /**
     * 计算俩图相似度
     *
     * -e.g: echo "ImageHash::instance()->distance('zjz.jpeg','zjz.png');"; echo " ". Vipkwd\Utils\Image\ImageHash::instance()->distance("/Users/yipeng/Pictures/zjz.jpeg","/Users/yipeng/Pictures/WX20220123-212530@2x.png");
     * 
     * @param string $imgPath1
     * @param string $imgPath2
     * @return void
     */
    public function distance(string $imgPath1, string $imgPath2){
        $imgPath1 = File::realpath($imgPath1);
        $imgPath2 = File::realpath($imgPath2);
        if(File::exists($imgPath1) && File::exists($imgPath2)){
            return bcsub("100", ''.$this->hash($imgPath1)->distance($this->hash($imgPath2)),2);
        }
        return 0;
    }
    
    public function hashToHex(Object $hash):string{
        return $hash->toHex(); //7878787c7c707c3c
    }
    public function hashToBits(Object $hash):string{
        return $hash->toBits(); //0111100001111000011110000111110001111100011100000111110000111100
    }

    public function hashToInt(Object $hash):int{
        return $hash->toInt(); //8680820757815655484
    }

    public function hashToBytes(Object $hash):string{
        return $hash->toBytes(); //"\x0F\x07ƒƒ\x03\x0F\x07\x00"
    }

    public function hashFromHex(string $hex){
        return Hash_jenssegers::fromHex($hex);
    }
    public function hashFromBin(string $bin){
        return Hash_jenssegers::fromInt($bin);
    }
    public function hashFromInt(string $int){
        return Hash_jenssegers::fromBin($int);
    }


}
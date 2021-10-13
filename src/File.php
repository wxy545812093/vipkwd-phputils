<?php
/**
 * @name 文件操作函数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\Dev;

class File{

    private static $byteUnits = [
        "Byte","KB","MB","GB","TB",
        "PB","EB","ZB","YB","DB","NB"
    ];
    private static $downloadSpeed = "1mb";

    /**
     * path转unix风格
     *
     * @param string $path
     * @return string
     */
    static function pathToUnix(string $path):string{
        if($path != ""){
            $path = str_replace('\\','/', $path);
            $path = realpath($path);
        }
        return $path;
    }

    /**
     * 获取path名(不含fileName部分)
     *
     * @param string $path
     * @param integer $levels
     * @return string
     */
    static function dirname(string $path, int $levels =1):string{
        return dirname($path, $levels);
    }

    /**
     * 获取文件名(不含path部分)
     *
     * @param string $path
     * @param string $suffix <"">
     * @return string
     */
    static function basename(string $path, string $suffix = ""):string{
        return self::filename($path, $suffix, false);
    }
    
    /**
     * 获取文件名(增强版 basename函数）
     *
     * @param string $path
     * @param string|array $suffix <"">
     * @param boolean $autoCase <false>
     * @return string
     */
    static function filename(string $path, $suffix = "", bool $autoCase=false):string{
        $filename = basename($path);
        if($suffix == "") return $filename;
        if(is_string($suffix)){
            if($suffix != ""){
                $flag = ($autoCase === false) ? strpos($filename, $suffix, 0) : stripos($filename, $suffix, 0);
                $flag !== false && $filename = str_replace($suffix, "", $filename);
            }
        }else if(is_array($suffix)){
            foreach($suffix as $ext){
                if($ext != ""){
                    if($autoCase === false){
                        $filename = str_replace($ext, "", $filename);
                    }else{
                        $_filename = preg_replace("|{$ext}|i","", $filename);
                        ($_filename != null) && $filename = $_filename;
                    }
                }
            }
        }
        return $filename;
    }

    /**
     * 获取扩展名
     *
     * @param string $path
     * @return string
     */
    static function getExtension(string $path):string{
        $pathinfo = pathinfo($path);
        return $pathinfo['extension'];
    }

    /**
     * 返回规范化的绝对路径名
     *
     * @param string $path
     * @param boolean $pathToUnix <false> 是否响应Unix风格化path
     * @return string
     */
    static function realpath(string $path, bool $pathToUnix = false):string{
        $path = realpath($path);
        $pathToUnix === true && $path = self::pathToUnix($path);
        return $path;
    }

    /**
     * 文件下载
     * 
     * @param string $filename 要下载的文件路径
     * @param string $rename <null>文件名称,为空则与下载的文件名称一样
     * @param boolean $breakpoint <false> 是否开启断点续传
     * 
     * @return void 
     */
    static public function download(string $filename, $rename=null, bool $breakpoint = false){
        // 验证文件
        if(!is_file($filename)||!is_readable($filename)) 
        {
            return false;
        }

        // 获取文件大小
        $fileSize = filesize($filename);

        // 获取header range信息
        if($breakpoint && isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])){ 
            $range = $_SERVER['HTTP_RANGE']; 
            $range = preg_replace('/[\s|,].*/', '', $range); 
            $range = explode('-', substr($range, 6)); 
            if(count($range)<2){ 
              $range[1] = $fileSize; 
            } 
            $range = array_combine(array('start','end'), $range); 
            if(empty($range['start'])){ 
              $range['start'] = 0; 
            } 
            if(empty($range['end'])){ 
              $range['end'] = $fileSize; 
            }
        }

        // 重命名
        !isset($rename) && $rename = $filename;

        // 字节流
        header('HTTP/1.1 200 OK');
        header('Accept-Length:' . $fileSize);
        header('Content-Length:'. $fileSize);
        header('cache-control:public');
        header('Content-Type:application/octet-stream');
        header('Content-Disposition: attachment;filename='.basename($rename));
 
        // 校验是否限速(超过1M自动限速,同时下载速度设为1M)
        $limit = 1 * 1024 * 1024;

        if( $fileSize <= $limit )
        {
            readfile($filename);
        }
        else
        {
            // 读取文件资源
            $file = fopen($filename, 'rb');

            // 强制结束缓冲并输出
            ob_end_clean();
            ob_implicit_flush();
            header('X-Accel-Buffering: no');

            // 读取位置标
            $count = 0;

            if( $breakpoint && isset($range)){ // 使用续传
                header('HTTP/1.1 206 Partial Content');
                header('Accept-Ranges:bytes');
                // 剩余长度
                header(sprintf('Content-Length:%u',$range['end']-$range['start']));
                // range信息
                header(sprintf('Content-Range:bytes %s-%s/%s', $range['start'], $range['end'], $fileSize));

                // 读取位置标
                // file指针跳到断点位置
                fseek($file, sprintf('%u', $range['start']));
                $count = $range['start'];
            }

            // 下载
            while (!feof($file) && $fileSize - $count > 0) 
            {
                $res = fread($file, $limit);
                $count += $limit;
                echo $res;
                flush();//输出缓冲
                //ob_flush();
                // usleep(mt_rand(500,1500));
                sleep(1);
            }
            ($file!=null) && fclose($file);
        }
        exit();
    }

    /**
     * 字节数转 filesize量化单位
     *
     * 1 Byte  =  8 Bit
     * 1 KB  =  1,024 Bytes
     * 1 MB  =  1,024 KB  =  1,048,576 Bytes
     * 1 GB  =  1,024 MB  =  1,048,576 KB  =  1,073,741,824 Bytes
     * 1 TB  =  1,024 GB  =  1,048,576 MB  =  1,073,741,824 KB  =  1,099,511,627,776 Bytes
     * 1 PB  =  1,024 TB  =  1,048,576 GB  =  1,125,899,906,842,624 Bytes
     * 1 EB  =  1,024 PB  =  1,048,576 TB  =  1,152,921,504,606,846,976 Bytes
     * 1 ZB  =  1,024 EB  =  1,180,591,620,717,411,303,424 Bytes
     * 1 YB  =  1,024 ZB  =  1,208,925,819,614,629,174,706,176 Bytes
     * @param integer $size 字节数
     * @param integer $pointLength <10> 小数点长度
     * @return string
     */
    static public function byteFormat(int $size, int $pointLength = 10 ):string{
        $pos = 0;
        while ( $size >= 1024 ) {
            $size /= 1024;
            $pos ++;
        }
        return round( $size, $pointLength ) . " " . self::$byteUnits[$pos];
    }

    /**
     * filesize量化单位转 字节数
     *
     * @param string $str 量化单位 如： 1.2GB
     * @param boolean $toInt <false> 是否进一法舍去字节小数（转换结果 可能存在 0.xxx字节的小数）
     * @return integer
     */
    static function formatByte(string $str, bool $toInt=false){
        $size = doubleval($str);
        $unit = substr(str_replace(" ","",$str), strlen("$size"));
        $unit = strtoupper($unit);
        //没有单位，默认按字节处理;
        if($unit == "" || $unit == "BYTE" || $unit == "B"){
            return $size;
        }
        $unitIdx = array_search($unit, self::$byteUnits);
        if($unitIdx === false){
            //单位不能识别，默认按字节处理;
            return  $size;
        }
        while($unitIdx > 0){
            $size *= 1024;
            $unitIdx --;
        }
        unset($unit, $unitIdx, $str);
        if($toInt === true){
            return ceil($size);
        }
        return $size;
    }
}
<?php
/**
 * @name 文件系统函数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

class Filesystem{

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

    


}
<?php

/**
 * @name 文件操作函数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\System;

use PhpShardUpload\{ShardUpload, ShardUploadStatus, FileManage};
// use PhpShardUpload\Components\FileDownload;
use Vipkwd\Utils\Tools;
use Vipkwd\Utils\Dev;
use Vipkwd\Utils\Http;
use Vipkwd\Utils\Image\Thumb as VipkwdThumb;
use Vipkwd\Utils\Libs\Upload as VipkwdUpload;

class File
{

    private static $byteUnits = [
        "Byte", "KB", "MB", "GB", "TB",
        "PB", "EB", "ZB", "YB", "DB", "NB"
    ];
    private static $downloadSpeed = 1024; //1MB

    /**
     * path转unix风格
     *
     * @param string $path
     * @param bool $forceUnix 强制Unix风格
     * @return string
     */
    static function pathToUnix(string $path, bool $forceUnix = false): string
    {
        if ($path) {
            $path = str_replace('\\', '/', $path);
            $path = self::normalizePath($path, $forceUnix);
            if (false != ($_p = realpath($path))) {
                $path = $_p;
            }
        }
        return $path ?? "";
    }

    /**
     * 获取path名(不含fileName部分)
     *
     * @param string $path
     * @param integer $levels
     * @return string
     */
    static function dirname(string $path, int $levels = 1): string
    {
        return dirname($path, $levels);
    }

    /**
     * 获取文件名(不含path部分)
     *
     * @param string $path
     * @param string $suffix <"">
     * @return string
     */
    static function basename(string $path, string $suffix = ""): string
    {
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
    static function filename(string $path, $suffix = "", bool $autoCase = false): string
    {
        $filename = basename($path);
        if ($suffix == "") return $filename;
        if (is_string($suffix)) {
            if ($suffix != "") {
                $flag = ($autoCase === false) ? strpos($filename, $suffix, 0) : stripos($filename, $suffix, 0);
                $flag !== false && $filename = str_replace($suffix, "", $filename);
            }
        } else if (is_array($suffix)) {
            foreach ($suffix as $ext) {
                if ($ext != "") {
                    if ($autoCase === false) {
                        $filename = str_replace($ext, "", $filename);
                    } else {
                        $_filename = preg_replace("|{$ext}|i", "", $filename);
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
    static function getExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * 根据图片二进流判断文件类型
     * @param $image
     * @return int|string
     */
    protected static function check_image_type($image): string
    {
        $bits = array(
            'jpg' => "\xFF\xD8\xFF",
            'gif' => "GIF",
            'png' => "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a",
            'bmp' => 'BM',
        );
        foreach ($bits as $type => $bit) {
            if (substr($image, 0, strlen($bit)) === $bit) {
                return $type;
            }
        }
        return 'jpg';
    }
    /**
     * 返回规范化的绝对路径名
     *
     * @param string $path
     * @param boolean $pathToUnix <false> 是否响应Unix风格化path
     * @return string
     */
    static function realpath(string $path, bool $pathToUnix = true): string
    {
        $path = self::normalizePath($path);
        if ($pathToUnix) {
            return self::pathToUnix($path);
        }
        if (false != ($p = realpath($path))) {
            $path = $p;
        }
        return $path;
    }

    /**
     * 单文件上传
     *
     * @param array $options
     *                  --max_size integer <10 * 1024 * 1024>  限制可上传文件大小(单位)
     *                  --upload_dir string <"upfiles/"> 保存目录
     *                  --type array <["jpg","gif","bmp","jpeg","png"]> 允许扩展
     *                  --file_name_prefix string <''> 文件名前缀
     *                  --save_name string <''> 指定文件名(包含扩展名)
     * @param string $uploadKey <file> $_FILES[?]
     * @return array|strings
     */
    static function upload($options = [], $uploadKey = "file"): ?array
    {
        return (new VipkwdUpload)->upload($uploadKey, $options);
    }

    /**
     * 文件下载(支持限速)
     *
     * @param string $localFilePath 要下载的文件路径
     * @param string $rename <null>文件名称,为空则与下载的文件名称一样
     * @param integer $downloadSpeed <1024>下载限速 单位KB，必须大于0
     * @param boolean $breakpoint <true> 是否开启断点续传
     *
     * @return void
     */
    static function download(string $localFilePath, $rename = null, int $downloadSpeed = 1024, bool $breakpoint = true)
    {
        // 验证文件
        if (!is_file($localFilePath) || !is_readable($localFilePath)) {
            return false;
        }
        // if (!is_file($localFilePath)) {
        //     header("HTTP/1.1 400 Invalid Request");
        //     exit("<h3>File Not Found</h3>");
        // }
        set_time_limit(0);
        // 获取文件大小
        $fileSize = filesize($localFilePath);

        // 获取header range续传信息
        if ($breakpoint && isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])) {
            $range = preg_replace('/[\s|,].*/', '', $_SERVER['HTTP_RANGE']);
            $range = explode('-', substr($range, 6));
            if (count($range) < 2) {
                $range[1] = $fileSize;
            }
            $range = array_combine(array('start', 'end'), $range);
            if (empty($range['start'])) {
                $range['start'] = 0;
            }
            if (empty($range['end'])) {
                $range['end'] = $fileSize;
            }

            $range['start'] *= 1;
            $range['end'] *= 1;
            // file_put_contents('98k.txt', json_encode([
            //     'range' => $range,
            //     'http_range' => $_SERVER['HTTP_RANGE'],
            // ]) . "\r\n", FILE_APPEND);
        }

        // 重命名
        (!isset($rename) || !$rename) && $rename = $localFilePath;

        // 字节流

        $ua = $_SERVER["HTTP_USER_AGENT"]; //判断是什么类型浏览器
        $encoded_filename = str_replace("+", "%20", urlencode(basename($rename)));
        //解决下载文件名乱码
        if (preg_match("/MSIE/", $ua) || preg_match("/Trident/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . basename($rename) . '"');
        } else if (preg_match("/Chrome/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . basename($rename) . '"');
        }

        // 校验是否限速(文件超过0.5M自动限速为 0.5Mb/s )
        // $limit = ($downloadSpeed > 0 ? Tools::format($downloadSpeed, 1) : 1) * 1024 * 1024;
        $limit = self::toBytes(($downloadSpeed > 0 ? ($downloadSpeed < (1024 * 8) ? $downloadSpeed : self::$downloadSpeed) : self::$downloadSpeed) . "KB", true);

        if ($fileSize <= $limit) {
            readfile($localFilePath);
        } else {


            // 强制结束缓冲并输出
            // if (extension_loaded('zlib')) {
            //     ob_end_flush();
            // }
            // ob_implicit_flush();

            // ini_set('output_buffering', 'Off');
            // ini_set('zlib.output_compression', 'Off');

            $lastModified = gmdate('D, d M Y H:i:s', filemtime($localFilePath)) . ' GMT';
            $etag = sprintf('vk/"%s:%s"', md5($lastModified . hash_file('md5', $localFilePath)), $fileSize);
            // headers 
            header(sprintf('Last-Modified: %s', $lastModified));
            header(sprintf('ETag: %s', $etag));
            header('Content-Type:application/octet-stream');
            // header('X-Accel-Buffering: no');
            // 读取文件资源
            $fileHandler = fopen($localFilePath, 'rb');

            // file_put_contents('98k.txt', json_encode($_SERVER ?? []) . "\r\n", FILE_APPEND);

            if ($breakpoint && isset($range)) { // 使用续传
                header('HTTP/1.1 206 Partial Content');
                header('Accept-Ranges:bytes');
                // 剩余长度 
                header(sprintf('Content-Length:%u', $range['end'] - $range['start']));
                // range信息 
                header(sprintf('Content-Range:bytes %s-%s/%s', $range['start'], $range['end'], $fileSize));
                // fp指针跳到断点位置 
                fseek($fileHandler, $range['start']);
            } else {
                // header('cache-control:public');
                header('HTTP/1.1 200 OK');
                header('Content-Length:' . $fileSize);
            }

            while (!feof($fileHandler)) {
                echo fread($fileHandler, $limit);
                ob_flush();
                flush();
                sleep(1); // 用于测试,减慢下载速度 
            }

            ($fileHandler != null) && fclose($fileHandler);

            // 下载
            // // while (!feof($fileHandler) && $fileSize - $count > 0 && !connection_aborted()) {
            // while (!feof($fileHandler) && !connection_aborted()) {
            //     // $count += $limit;
            //     echo fread($fileHandler, $limit);
            //     ob_flush();
            //     flush(); //输出缓冲
            //     sleep(1);
            // }
            // // ob_end_clean();
            // ($fileHandler != null) && fclose($fileHandler);
        }
        exit;
    }

    /**
     * Nginx x-sendfile文件下载
     *
     * @param string $filePath 要下载的文件路径
     * @param string $accelPath nginx控制的路劲
     * @param integer $speed <50>下载限速 单位KB，必须大于0
     * @param string $origin_name 下载文件名(默认同源文件名)
     *
     * @return void
     */
    static function downloadWidthXSendfile(string $filePath, string $accelPath, int $speed = 50, string $origin_name = '')
    {
        // 文件不存在
        if (!is_file($filePath)) {
            if (!is_file(File::realpath($accelPath . '/' . $filePath))) {
                throw new \Exception('文件不存在，可能已被删除');
            }
        } else {
            $filePath = str_replace($accelPath, '', $filePath);
        }
        $_file_path = '/' . ltrim(ltrim($filePath, '/'), '\\');
        $_file_path = File::pathToUnix($_file_path);
        $_file_limit_size = self::toBytes(($speed > 0 ? ($speed < 1024 ? $speed : 500) : 50) . "KB");

        // 启用 nginx X-Accel 下载
        header('Content-Type: application/octet-stream');
        $encoded_fname = rawurlencode($origin_name ? $origin_name : basename($_file_path));
        header('Content-Disposition: attachment;filename="' . $encoded_fname . '";filename*=utf-8' . "''" . $encoded_fname);

        header('X-Accel-Redirect: ' . $_file_path);
        header('X-Accel-Buffering: yes');
        header('X-Accel-Charset: utf-8');
        //header("Accept-Ranges: none");//单线程 限制多线程

        // 不限速下载
        if ($speed !== "") {
            header('X-Accel-Limit-Rate:' . $_file_limit_size);
        }
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
    static function bytesTo(int $size, int $pointLength = 10): string
    {
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        return round($size, $pointLength) . self::$byteUnits[$pos];
    }

    /**
     * filesize量化单位转 字节数
     *
     * @param string $str 已量化单位 如： 1.2GB
     * @param boolean $toInt <false> 是否进一法舍去字节小数（转换结果 可能存在 0.xxx字节的小数）
     * @return integer
     */
    static function toBytes(string $str, bool $toInt = false)
    {
        $str = str_replace(" ", "", $str);
        $size = doubleval($str);
        $unit = substr($str, strlen("$size"));
        $unit = strtoupper($unit);
        //没有单位，默认按字节处理;
        if ($unit == "" || $unit == 'BYTE' || $unit == 'B') {
            return $size;
        }
        (strlen($unit) == 1) && $unit .= "B";
        if (false === ($pos = array_search($unit, self::$byteUnits))) {
            //单位不能识别，默认按字节处理;
            return  $size;
        }
        $size *= pow(1024, $pos);
        unset($unit, $str, $pos);
        if ($toInt === true) {
            return intval(ceil($size));
        }
        return round($size);
    }


    /**
     * [分片/秒传组件]: 上传
     *
     * 需配合 /example/hashFileUpload 组件使用
     *
     * @param string $savePath 文件保存目录
     * @return string
     */
    static function uploadHashFile(string $savePath = "./"): string
    {
        $shard = new ShardUpload(
            $_FILES['data'],
            $_POST['index'],
            $_POST['total'],
            $_POST['shardSize'], //分块大小
            $_POST['size'], //总大小
            $_POST['md5Hash'],
            $_POST['sha1Hash'],
            rtrim(self::normalizePath($savePath), "/") . "/"
        );
        $response = $shard->upload();
        // header('Content-Type:application/json;charset=utf-8');
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    /**
     * [分片/秒传组件]: 查看上传状态
     *
     * 需配合 /example/hashFileUpload 组件使用
     * @param string $savePath 文件保存目录
     * @return string
     */
    static function uploadHashFileStatus(string $savePath = "./"): string
    {
        $shard = new ShardUploadStatus(
            $_POST['total'],
            $_POST['shardSize'], //文件分块大小
            $_POST['size'], //文件总大小
            $_POST['md5Hash'],
            $_POST['sha1Hash'],
            $savePath = rtrim(self::normalizePath($savePath), "/") . "/"
        );
        $response = $shard->getUploadStatus();
        if ($response['status'] == 1) {
            $manage = new \PhpShardUpload\FileManage($_POST['md5Hash'], $_POST['sha1Hash'], $savePath);
            // $response['data']['path'] = ; //已成功上传的文件路径
            $file = $manage->getUploadSuccessFilePath();

            // $info = VipkwdThumb::instance()->getImageInfo($file);
            $info['file'] = $file = substr($file, strripos($file, Http::request()->base));
            $file = explode('/', $file);
            array_pop($file);
            $info['path'] = implode('/', $file);

            $response['data']['info'] = $info;
            // $response['sever'] = Http::request();
        }
        // header('Content-Type:application/json;charset=utf-8');
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    /**
     * [分片/秒传组件]: 下载
     *
     * 需配合 /example/hashFileUpload 组件使用
     * @return void
     */
    static function downloadHashFile(string $savePath = "./", ?string $hash = null, ?string $saveName = null)
    {
        $hash = $hash ? $hash : (empty($_GET['hash']) ? '' : $_GET['hash']);
        $hash = trim(urldecode($hash));
        if (strlen($hash) !== ((300 >> 2) - 3)) {
            return Http::sendCode(404);
        }
        //下载文件名称
        $name = $saveName ? $saveName : (empty($_GET['name']) ? '' : urldecode($_GET['name']));
        $savePath = rtrim(self::normalizePath($savePath), "/") . "/";
        $manage = new FileManage(substr($hash, 0, 2 << 4), substr($hash, 2 << 4), $savePath);
        $manage->download($name);
    }

    /**
     * 获取服务器支持最大上传文件大小(bytes)
     *
     * response: -1 没有上传大小限制
     *
     * @return float
     */
    static function fileUploadMaxSize()
    {

        $parse_size = function ($size) {
            // Remove the non-unit characters from the size.
            $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
            // Remove the non-numeric characters from the size.
            $size = preg_replace('/[^0-9\.]/', '', $size);
            if ($unit) {
                // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
                return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
            } else {
                return round($size * 1);
            }
        };

        $max1 = $parse_size(ini_get('post_max_size'));
        $max2 = $parse_size(ini_get('upload_max_filesize'));
        $max3 = $parse_size(ini_get('memory_limit'));
        if ($max1 > 0 && ($max1 <= $max2 || $max2 == 0) && ($max1 <= $max3 || $max3 == -1))
            return $max1;
        elseif ($max2 > 0 && ($max2 <= $max1 || $max1 == 0) && ($max2 <= $max3 || $max3 == -1))
            return $max2;
        elseif ($max3 > -1 && ($max3 <= $max1 || $max1 == 0) && ($max3 <= $max2 || $max2 == 0))
            return $max3;
        else
            return -1; // no limit
    }

    /**
     * 递归获取指定目录下文件
     *
     * @param string $dir
     * @param bool $exposeDirectory <false> 是否导出目录(默认不含目录)
     * @return array
     */
    static function getFiles($dir, bool $exposeDirectory = false)
    {
        $files = [];
        $dir  = self::pathToUnix($dir . '/');
        $each = function ($_dir) use (&$each, &$files, $exposeDirectory, $dir) {
            $it = new \FilesystemIterator($_dir);
            /**@var $file \SplFileInfo */
            foreach ($it as $file) {
                if ($file->isDir()) {
                    $exposeDirectory && $files[] = [
                        'type' => 'd',
                        'struct' =>  str_replace($dir .DIRECTORY_SEPARATOR, '', self::pathToUnix($file->getPathname())),
                        'path' => self::pathToUnix($file->getPathname()),
                        'name' => $file->getFilename()
                    ];
                    $each($file->getPathname());
                } else {
                    $files[] = [
                        'type' => 'f',
                        'struct' =>  str_replace($dir .DIRECTORY_SEPARATOR, '', self::pathToUnix($file->getPathname())),
                        'path' => self::pathToUnix($file->getPathname()),
                        'name' => $file->getFilename()
                    ];
                }
            }
        };
        $each($dir);
        return $files;
    }

    /**
     * 递归指定目录下所有的文件(包括子目录)
     *
     * @param string   $dir
     * @param callable $callback
     */
    public static function each($dir, callable $callback)
    {
        $each = function ($dir) use (&$each, $callback) {
            $it = new \FilesystemIterator($dir);
            /**@var $file \SplFileInfo */
            foreach ($it as $file) {
                if ($file->isDir()) {
                    if ($each($file->getPathname()) === false) {
                        return false;
                    }
                } else {
                    if ($callback($file) === false) {
                        return false;
                    }
                }
            }
            return true;
        };
        $each($dir);
    }

    /**
     * 删除文件或目录
     *
     * @param string $dir 目錄或文件
     * @return bool
     */
    static function delete($dirOrFile)
    {
        $each = function ($dir) use (&$each) {
            $flag = true;
            if (!is_dir($dir)) return $flag;
            $it = new \FilesystemIterator($dir);
            /**@var $file \SplFileInfo */
            foreach ($it as $file) {
                if ($file->isDir()) {
                    if ($each($file->getPathname()) === true) {
                        if (!@rmdir($file->getPathname()))
                            $flag = false;
                    } else {
                        $flag = false;
                    }
                } else {
                    if (!@unlink($file->getPathname()))
                        $flag = false;
                }
            }
            return $flag;
        };
        $dirOrFile = is_array($dirOrFile) ? $dirOrFile : [$dirOrFile];

        foreach ($dirOrFile as $path) {
            $path = str_replace('\\', '/', $path);
            if ($each($path) === true) {
                if (is_file($path)) {
                    @unlink($path);
                } else if (!is_dir($path) || @rmdir($path)) {
                    // return true;
                }
            }
        }
        return false;
    }

    /**
     * 检测文件是否是 图片文件
     *
     * @param string $filename
     * @return boolean
     */
    static function isImage(string $filename): bool
    {
        if (!self::exists($filename)) {
            return false;
        }
        $mimetype = exif_imagetype($filename);
        switch ($mimetype) {
            case IMAGETYPE_GIF:
            case IMAGETYPE_JPEG:
            case IMAGETYPE_PNG:
            case IMAGETYPE_BMP:
            case IMAGETYPE_SWF:
            case IMAGETYPE_PSD:
            case IMAGETYPE_BMP:
            case IMAGETYPE_JPC:
            case IMAGETYPE_JP2:
            case IMAGETYPE_JB2:
            case IMAGETYPE_IFF:
            case IMAGETYPE_WBMP:
            case IMAGETYPE_XBM:
                // case MAGETYPE_JPX:
                // case IMAGETYPE_SWC:
                // case IMAGETYPE_TIFF_II://（Intel 字节顺序）
                // case IMAGETYPE_TIFF_MM://（Motorola 字节顺序）
                return true;
        }
        return false;
    }

    /**
     * 检测标准路径下的文件是否存在
     *
     * @param string $filepath
     * @return void
     */
    static function exists(string &$filepath)
    {
        $filepath = self::realpath($filepath);
        return file_exists($filepath);
    }

    /**
     * 检测$path是否为绝对路径
     *
     * -e.g: phpunit("File::isAbsolutePath", ["./www"]);
     * -e.g: phpunit("File::isAbsolutePath", ["/backup"]);
     * -e.g: phpunit("File::isAbsolutePath", ["/backup/22/../"]);
     * -e.g: phpunit("File::isAbsolutePath", ["/backup/22/../../../"]);
     * -e.g: phpunit("File::isAbsolutePath", ["backup/./../../../"]);
     *
     * @param string $path
     * @return boolean
     */
    static function isAbsolutePath(string &$path): bool
    {
        $path = self::normalizePath($path);
        return (bool)preg_match('#([a-z]:)?[/\\\\]|[a-z][a-z0-9+.-]*://#Ai', $path);
    }

    /**
     * 规范化路径中的 .. 和目录分隔符 .
     *
     * -e.g: phpunit("File::normalizePath",["/file/."]);
     * -e.g: phpunit("File::normalizePath",["\\file\dx\.."]);
     * -e.g: phpunit("File::normalizePath",["/file/../.."]);
     * -e.g: phpunit("File::normalizePath",["file/../../bar"]);
     *
     * @param string $path
     * @param bool $forceUnix 强制Uninx风格
     * @return string
     */
    static function normalizePath(string $path, bool $forceUnix = false): string
    {
        //模糊检测网址
        if (strrpos($path, ':') > 0 && $forceUnix === false) {
            return $path;
        }
        $parts = $path === '' ? [] : preg_split('~[/\\\\]+~', $path);
        $res = [];
        foreach ($parts as $part) {
            if ($part === '..' && $res && end($res) !== '..' && end($res) !== '') {
                array_pop($res);
            } elseif ($part !== '.') {
                $res[] = $part;
            }
        }

        return empty($res)
            ? ($forceUnix ? '/' : DIRECTORY_SEPARATOR)
            : implode(($forceUnix ? '/' : DIRECTORY_SEPARATOR), $res);
    }


    /**
     * 连接并规范化路径数组串
     *
     * -e.g: phpunit("File::joinPaths",["a", "\b", "file.txt"]);
     * -e.g: phpunit("File::joinPaths",["/a/", "/b/"]);
     * -e.g: phpunit("File::joinPaths",["/a/", "/../b"]);
     *
     * @param string ...$paths
     * @return string
     */
    static function joinPaths(string ...$paths): string
    {
        return self::normalizePath(implode('/', $paths), true);
    }

    /**
     * 创建目录
     *
     * @param string $dir
     * @param integer $mode <0755>
     *
     * @throws \Exception  on error occurred
     * @return boolean
     */
    static function createDir(string $dir, int $mode = 0755): bool
    {
        try {
            $dir = self::normalizePath($dir);
            !is_dir($dir) && @mkdir($dir, $mode, true);
            return is_dir($dir);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 复制文件或目录
     *
     * @param string $origin 源目标
     * @param string $target 新目标
     * @param boolean $overwrite <true> 是否覆盖
     *
     * @throws \Exception  on error occurred
     * @return void
     */
    static function copy(string $origin, string $target, bool $overwrite = true): void
    {
        if (stream_is_local($origin) && !self::exists($origin)) {
            throw new \Exception(sprintf("File or directory '%s' not found.", self::normalizePath($origin)));
        } elseif (!$overwrite && self::exists($target)) {
            throw new \Exception(sprintf("File or directory '%s' already exists.", self::normalizePath($target)));
        } elseif (is_dir($origin)) {
            static::createDir($target);
            foreach (new \FilesystemIterator($target) as $item) {
                static::delete($item->getPathname());
            }

            foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($origin, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
                if ($item->isDir()) {
                    static::createDir($target . '/' . $iterator->getSubPathName());
                } else {
                    static::copy($item->getPathname(), $target . '/' . $iterator->getSubPathName());
                }
            }
        } else {
            static::createDir(self::dirname($target));
            if (
                ($s = @fopen($origin, 'rb'))
                && ($d = @fopen($target, 'wb'))
                && @stream_copy_to_stream($s, $d) === false
            ) { // @ is escalated to exception
                throw new \Exception(sprintf(
                    "Unable to copy file '%s' to '%s'. %s",
                    self::normalizePath($origin),
                    self::normalizePath($target),
                    Dev::getLastError()
                ));
            }
        }
    }

    /**
     * 移动文件/目录
     *
     * @param string $origin 源目标
     * @param string $target 新目标
     * @param boolean $overwrite <true> 是否覆盖
     *
     * @throws \Exception  on error occurred
     * @return void
     */
    static function rename(string $origin, string $target, bool $overwrite = true): void
    {
        if (!$overwrite && self::exists($target)) {
            throw new \Exception(sprintf("File or directory '%s' already exists.", self::normalizePath($target)));
        } elseif (!self::exists($origin)) {
            throw new \Exception(sprintf("File or directory '%s' not found.", self::normalizePath($origin)));
        } else {
            static::createDir(self::dirname($target));
            if (self::realpath($origin) !== self::realpath($target)) {
                static::delete($target);
            }
            if (!@rename($origin, $target)) { // @ is escalated to exception
                throw new \Exception(sprintf(
                    "Unable to rename file or directory '%s' to '%s'. %s",
                    self::normalizePath($origin),
                    self::normalizePath($target),
                    Dev::getLastError()
                ));
            }
        }
    }

    /**
     * 从文件读取内容
     *
     * @param string $file
     * @param string|null $method[get|post]
     * @throws \Exception  on error occurred
     * @return string
     */
    static function read(string $file, ?string $method = null): string
    {
        if ($method) {
            $opts = array('http' => array('method' => strtoupper($method), 'timeout' => 3,));
            $context = stream_context_create($opts);
            $content = @file_get_contents($file, false, $context);
        } else {
            $content = @file_get_contents($file); // @ is escalated to exception
        }

        if ($content === false) {
            throw new \Exception(sprintf(
                "Unable to read file '%s'. %s",
                self::normalizePath($file),
                Dev::getLastError()
            ));
        }
        return $content;
    }

    /**
     * 字符串写入到文件
     *
     * @param string $file
     * @param string $content
     * @param integer|null $mode <0666>
     *
     * @throws \Exception  on error occurred
     * @return int
     */
    static function write(string $file, string $content, ?int $mode = 0666): int
    {
        static::createDir(self::dirname($file));
        $int = @file_put_contents($file, $content);
        if ($int === false) { // @ is escalated to exception
            throw new \Exception(sprintf(
                "Unable to write file '%s'. %s",
                self::normalizePath($file),
                Dev::getLastError()
            ));
        }

        if ($mode !== null && !@chmod($file, $mode)) { // @ is escalated to exception
            throw new \Exception(sprintf(
                "Unable to chmod file '%s' to mode %s. %s",
                self::normalizePath($file),
                decoct($mode),
                Dev::getLastError()
            ));
        }
        return $int;
    }

    /**
     * 下载网络文件
     *
     * @param string $http_url
     * @param string $saveNameWithPath  保存文件名 /folder/path/filename
     * @return string|null
     */
    static function downloadHttpFile(string $http_url, string $saveNameWithPath): ?string
    {
        // 设置超时时间
        set_time_limit(24 * 60 * 60);
        if (false !== realpath($saveNameWithPath)) {
            $saveNameWithPath = realpath($saveNameWithPath);
            // Dev::dumper($saveNameWithPath,1);
        }
        $destination_folder = rtrim(dirname($saveNameWithPath), '/') . '/';
        $saveName = basename($saveNameWithPath);

        // 文件下载保存目录，默认为当前文件目录
        if (!is_dir($destination_folder)) {
            // 判断目录是否存在
            self::createDir($destination_folder);
            // 如果没有就建立目录
        }
        $newfname = rtrim($destination_folder, '/') . '/' . ($saveName ?? basename($http_url));
        // 取得文件的名称
        $file = fopen($http_url, "rb");
        // 远程下载文件，二进制模式
        if ($file) {
            // 如果下载成功
            $newf = fopen($newfname, "wb");
            // 远程文件内容
            if ($newf) {
                // 如果文件保存成功
                while (!feof($file)) {
                    // 判断附件写入是否完整
                    fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                    // 没有写完就继续
                }
            }
        }
        if ($file) {
            fclose($file);
            // 关闭远程文件
        }
        if ($newf) {
            fclose($newf);
            // 关闭本地文件
        }
        if (file_exists($newfname)) {
            return $newfname;
        }
        return null;
    }

    static function showImage(string $imagePath)
    {
        //获取mime信息
        $size = getimagesize($imagePath);
        //二进制方式打开文件
        $fp = fopen($imagePath, "rb");
        if ($size && $fp) {
            header("Content-type: {$size['mime']}");
            fpassthru($fp); // 输出至浏览器
            exit;
        }
    }

    /**
     * 向上查找指定文件
     *
     * @param string $file
     * @param boolean $except <false> 是否返回查找轨迹(PATH)
     * @param integer $level <5> 向后查找层级数
     * @return void
     */
    static function closestPath(string $file, bool $except = false, int $level = 5)
    {
        // $err = sprintf('%s::%s() expects parameter 1 to be absolute path string, relative path given', __CLASS__,__FUNCTION__);
        if (self::isAbsolutePath($file)) {
            $basePath = self::dirname($file);
            $file = self::basename($file);
        } else {
            $trace = debug_backtrace();
            $trace = array_pop($trace);
            $basePath = self::dirname($trace['file']);
            unset($trace);
        }
        $history = [];
        $deep = "";
        for ($i = 0; $i < $level; $i++) {
            $i > 0 && $deep .= "/..";
            $_h = array_merge([], $history);
            $history[] = $_file = self::realpath($basePath . $deep) . '/' . $file;
            if (array_pop($_h) === $_file) {
                unset($_h);
                break;
            }
            unset($_h);
            if (file_exists($_file)) {
                $i = true;
                break;
            }
        }
        if ($i === true) {
            unset($history, $level, $basePath, $file);
            return $_file;
        }
        if (Tools::isCli()) {
            $err = "\r\n";
            $err .= sprintf("\033[31mNot found the %s resource in below path list\033[0m", $file);
            $err .= "\r\n";
            foreach ($history as $k => $file) {
                $err .= sprintf("\033[31m%-6sTrace%s in:\033[0m %s", " ", $k + 1, $file);
                $err .= "\r\n";
            }
        } else {
            $err = sprintf("Not found the %s resource in below path list: [ %s ]", $file, implode(", ", $history));
        }
        unset($history, $level, $basePath, $file);
        if (!$except) {
            return null;
        }
        throw new \Exception($err);
    }

    /**
     * 读取指定INI文件
     * 
     * @param string $iniFile ini文件path
     * @return array|boolean
     */
    static function readIniFile(string $iniFile)
    {
        if (file_exists($iniFile)) {
            return parse_ini_file($iniFile, true);
        } else {
            return false;
        }
    }

    /**
     * 写入数组到指定ini文件
     * 
     * @param array $data
     * @param string $iniFile
     */
    static function writeIniFile($data, $iniFile)
    {
        $content = "";
        $common = '';
        $index = 0;
        foreach ($data as $key => $elem) {
            if (is_array($elem)) {
                $index++;
                $content .= "[{$key}]\n";
                foreach ($elem as $k2 => $v2) {
                    $content .= "{$k2} = {$v2}\n";
                }
                $content .= "\n";
            } else {
                $common .= "{$key} = {$elem}\n";
            }
        }
        if ($index > 0) {
            $content .= "[common]\n" . $common;
        } else {
            $content = $common;
        }
        unset($common, $index);

        if (!$handle = fopen($iniFile, 'w')) {
            return false;
        }
        if (!fwrite($handle, $content)) {
            return false;
        }
        fclose($handle);
        return true;
    }
}

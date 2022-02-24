<?php
/**
 * @name 文件操作函数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use PhpShardUpload\ShardUpload;
// use PhpShardUpload\Components\FileDownload;
use Vipkwd\Utils\{Tools,Dev};
use Vipkwd\Utils\Libs\Upload as VipkwdUpload;

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
        if($path){
            $path = str_replace('\\','/', $path);
            $path = realpath($path);
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
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * 返回规范化的绝对路径名
     *
     * @param string $path
     * @param boolean $pathToUnix <false> 是否响应Unix风格化path
     * @return string
     */
    static function realpath(string $path, bool $pathToUnix = true):string{
        return $pathToUnix ? self::pathToUnix($path) : realpath($path);
    }

    /**
     * 单文件上传
     *
     * @param string $uploadKey <file> $_FILES[?]
     * @param array $options
     *                  --max_size integer <10 * 1024 * 1024>  限制可上传文件大小(单位)
     *                  --upload_dir string <"upfiles/"> 保存目录
     *                  --type array <["jpg","gif","bmp","jpeg","png"]> 允许扩展
     *                  --file_name_prefix string <''> 文件名前缀
     * @return array|null
     */
    static function upload($uploadKey = "file", $options = []):?array{
        return (new VipkwdUpload)->upload($uploadKey, $options);
    }

    /**
     * 文件下载(支持限速)
     * 
     * @param string $filename 要下载的文件路径
     * @param string $rename <null>文件名称,为空则与下载的文件名称一样
     * @param integer $downloadSpeed <1>下载限速 单位MB，必须大于0
     * @param boolean $breakpoint <true> 是否开启断点续传
     * 
     * @return void 
     */
    static public function download(string $filename, $rename=null, int $downloadSpeed = 1, bool $breakpoint = true){
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
        (!isset($rename) || !$rename) && $rename = $filename;

        // 字节流
        header('HTTP/1.1 200 OK');
        header('Accept-Length:' . $fileSize);
        header('Content-Length:'. $fileSize);
        header('cache-control:public');
        header('Content-Type:application/octet-stream');
        header('Content-Disposition: attachment;filename='.basename($rename));
 
        // 校验是否限速(文件超过0.5M自动限速为 0.5Mb/s )
        $limit = ($downloadSpeed > 0 ? Tools::format($downloadSpeed,1) : 0.5) * 1024 * 1024;

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
        exit;
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
    static public function bytesTo(int $size, int $pointLength = 10 ):string{
        $pos = 0;
        while ( $size >= 1024 ) {
            $size /= 1024;
            $pos ++;
        }
        return round( $size, $pointLength ) . self::$byteUnits[$pos];
    }

    /**
     * filesize量化单位转 字节数
     *
     * @param string $str 已量化单位 如： 1.2GB
     * @param boolean $toInt <false> 是否进一法舍去字节小数（转换结果 可能存在 0.xxx字节的小数）
     * @return integer
     */
    static function toBytes(string $str, bool $toInt=false){
        $str = str_replace(" ","", $str);
        $size = doubleval($str);
        $unit = substr($str, strlen("$size"));
        $unit = strtoupper($unit);
        //没有单位，默认按字节处理;
        if($unit == "" || $unit == 'BYTE' || $unit == 'B'){
            return $size;
        }
        (strlen($unit) == 1) && $unit .= "B";
        if( false === ($pos = array_search($unit, self::$byteUnits) ) ) {
            //单位不能识别，默认按字节处理;
            return  $size;
        }
        $size *= pow(1024, $pos);
        unset($unit, $str, $pos);
        if($toInt === true){
            return ceil($size);
        }
        return round($size);
    }


    /**
     * [分片/秒传组件]: 上传
     * 
     * 需配合 /support/hashFileUpload 组件使用
     *
     * @param string $savePath 文件保存目录
     * @return string
     */
    static function uploadHashFile(string $savePath = "./"):string{
        $shard = new ShardUpload(
            $_FILES['data'], 
            $_POST['index'], 
            $_POST['total'], 
            $_POST['shardSize'], //分块大小
            $_POST['size'], //总大小
            $_POST['md5Hash'], 
            $_POST['sha1Hash'], 
            rtrim($savePath, "/")."/"
        );
        $response = $shard->upload();
        header('Content-Type:application/json;charset=utf-8');
        echo json_encode($response,JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * [分片/秒传组件]: 查看上传状态
     *
     * 需配合 /support/hashFileUpload 组件使用
     * @param string $savePath 文件保存目录
     * @return string
     */
    static function uploadHashFileStatus(string $savePath = "./"):string{
        $shard = new ShardUploadStatus(
            $_POST['total'], 
            $_POST['shardSize'], //文件分块大小
            $_POST['size'], //文件总大小
            $_POST['md5Hash'], 
            $_POST['sha1Hash'], 
            rtrim($savePath,"/")."/"
        );
        $response = $shard->getUploadStatus();
        if($response['status'] == 1){
        //$manage = new \PhpShardUpload\FileManage($md5Hash, $sha1Hash, $fileBaseDir);
        //   var_dump($manage->getUploadSuccessFilePath()); //已成功上传的文件路径
        }
        header('Content-Type:application/json;charset=utf-8');
        echo json_encode($response,JSON_UNESCAPED_UNICODE);
    }

    /**
     * [分片/秒传组件]: 下载
     *
     * 需配合 /support/hashFileUpload 组件使用
     * @return void
     */
    static function downloadHashFile(string $savePath = "./", string $saveName = ""){
        $md5Hash = trim($_GET['md5Hash']);
        $sha1Hash = trim($_GET['sha1Hash']);
        //下载文件名称
        $name= $saveName ? $saveName : (isset($_GET['name']) ? $_GET['name']:'');
        
        $savePath = rtrim($savePath, "/") . "/";

        $manage = new \PhpShardUpload\FileManage($md5Hash, $sha1Hash, $savePath);
        $manage->download($name);
    }

    /**
     * 获取服务器支持最大上传文件大小(bytes)
     * 
     * response: -1 没有上传大小限制
     *
     * @return float
     */
    static function fileUploadMaxSize() {

        $parse_size = function($size) {
            // Remove the non-unit characters from the size.
            $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
            // Remove the non-numeric characters from the size.
            $size = preg_replace('/[^0-9\.]/', '', $size);
            if ($unit) {
                // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
                return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
            } else {
                return round($size);
            }
        };

        $max1 = $parse_size(ini_get('post_max_size'));
        $max2 = $parse_size(ini_get('upload_max_filesize'));
        $max3 = $parse_size(ini_get('memory_limit'));
        if($max1>0 && ($max1<=$max2 || $max2==0) && ($max1<=$max3 || $max3==-1))
            return $max1;
        elseif($max2>0 && ($max2<=$max1 || $max1==0) && ($max2<=$max3 || $max3==-1))
            return $max2;
        elseif($max3>-1 && ($max3<=$max1 || $max1==0) && ($max3<=$max2 || $max2==0))
            return $max3;
        else
            return -1; // no limit
    }

    /**
	 * 获取指定目录下所有的文件(包括子目录)
	 *
	 * @param string $dir
	 * @return array
	 */
	static function getFiles($dir){
		$files = [];
		$each = function($dir) use (&$each, &$files){
			$it = new \FilesystemIterator($dir);
			/**@var $file \SplFileInfo */
			foreach($it as $file){
				if($file->isDir()){
					$each($file->getPathname());
				}else{
					$files[] = $file;
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
	public static function each($dir, callable $callback){
		$each = function($dir) use (&$each, $callback){
			$it = new \FilesystemIterator($dir);
			/**@var $file \SplFileInfo */
			foreach($it as $file){
				if($callback($file) === false){
					return false;
				}

				if($file->isDir()){
					if($each($file->getPathname()) === false){
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
	static function delete($dirOrFile){
		$each = function($dir) use (&$each){
			if(!is_dir($dir)) return true;
			$it = new \FilesystemIterator($dir);
			$flag = true;
			/**@var $file \SplFileInfo */
			foreach($it as $file){

				if($file->isDir()){
					if($each($file->getPathname()) === true){
						if(!@rmdir($file->getPathname()))
							$flag = false;
					}else{
						$flag = false;
					}
				}else{
					if(!@unlink($file->getPathname()))
						$flag = false;
				}
			}
			return $flag;
		};
		$dirOrFile = is_array($dirOrFile)? $dirOrFile : [$dirOrFile];

        foreach($dirOrFile as $path){
            $path = str_replace('\\','/', $path);
            if($each($path) === true){
                if(is_file($path)){
                    @unlink($path);
                }else if(!is_dir($path) || @rmdir($path)){
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
    static function isImage(string $filename):bool{
        if(!self::exists($filename)){
            return false;
        }
        $mimetype = exif_imagetype($filename);
        switch($mimetype){
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
    static function exists(string &$filepath){
        $filepath = self::realpath($filepath);
        return file_exists($filepath);
    }

    /**
     * 检测$path是否为绝对路径
     * 
     * -e.g: phpunit("File::isAbsolutePath", ["./www"]);
     * -e.g: phpunit("File::isAbsolutePath", ["/backup"]);
     * 
     * @param string $path
     * @return boolean
     */
    static function isAbsolutePath(string $path): bool{
		return (bool) preg_match('#([a-z]:)?[/\\\\]|[a-z][a-z0-9+.-]*://#Ai', $path);
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
     * @return string
     */
	static function normalizePath(string $path): string{
		$parts = $path === '' ? [] : preg_split('~[/\\\\]+~', $path);
		$res = [];
		foreach ($parts as $part) {
			if ($part === '..' && $res && end($res) !== '..' && end($res) !== '') {
				array_pop($res);
			} elseif ($part !== '.') {
				$res[] = $part;
			}
		}

		return $res === ['']
			? DIRECTORY_SEPARATOR
			: implode(DIRECTORY_SEPARATOR, $res);
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
	static function joinPaths(string ...$paths): string{
		return self::normalizePath(implode('/', $paths));
	}

    /**
     * 创建目录
     *
     * @param string $dir
     * @param integer $mode <0777>
     * 
	 * @throws \Exception  on error occurred
     * @return boolean
     */
    static function createDir(string $dir, int $mode = 0777): bool{
		try{
            !is_dir($dir) && @mkdir($dir, $mode, true);
            return is_dir($dir);
        }catch(\Exception $e){
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
	static function copy(string $origin, string $target, bool $overwrite = true): void{
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
    static function rename(string $origin, string $target, bool $overwrite = true):void{
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
     * 
	 * @throws \Exception  on error occurred
     * @return string
	 */
	static function read(string $file): string{
		$content = @file_get_contents($file); // @ is escalated to exception
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
     * @return void
     */
    static function write(string $file, string $content, ?int $mode = 0666): void{
		static::createDir(self::dirname($file));
		if (@file_put_contents($file, $content) === false) { // @ is escalated to exception
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
	}
}







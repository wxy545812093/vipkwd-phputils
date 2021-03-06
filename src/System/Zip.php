<?php
/**
 * @name PHP ZipArchive工具包
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\System;

use \ZipArchive;
use \Exception;
use \Closure;
use Vipkwd\Utils\Tools;
use File as vipkwdFile;

class Zip{

    /**
     * 文件打包（zipfile不存在将自动创建)
     *
     * @param string $zipFile 打包后的文件名
     * @param string|array $fileOrPaths 打包文件组(支持目录)
     * @param string|null $password 压缩包密码 不支持一切宽松等于(==)布尔False 的密码
     *
     * @return string|null
     */
    static public function addZip(string $zipFile, $fileOrPaths, ?string $password=""){

		return self::watchException(function()use($zipFile, &$fileOrPaths, $password){
            $zip = new ZipArchive();
            // 初始化
            // OVERWRITE zip覆盖模式
            // CREATE 追加模式
            $bool = $zip->open($zipFile, ZipArchive::CREATE|ZipArchive::OVERWRITE);
			if($bool === true){
                if($password){
                    if(!$zip->setPassword($password)){
                        throw new Exception('Set password failed');
                    }
                }
                $zip->setArchiveComment('vipkwd/utils');
                $zip->addFromString('pakg-license.txt', "This zip package create by PHP utils with \"vipkwd/utils\"

-- composer use:
--      composer require vipkwd/utils
--
--      include \"vendor/autoload.php\"
--      Vipkwd\Utils\System\Zip::addZip(\"demo.zip\", \".\");
--      //And a zip package was created;");
                $baseDir = null;
                if(is_string($fileOrPaths)){
                    $fileOrPaths = realpath($fileOrPaths);
                    is_dir($fileOrPaths) && $baseDir = $fileOrPaths;
                    $fileOrPaths =[$fileOrPaths];
                }
                foreach ($fileOrPaths as $file) {
                    if(is_dir($file)){
                        Tools::dirScan($file, function($_file, $_path) use(&$zip, $baseDir){
                            $filePath = $_path."/".$_file;
                            $baseDir === null && $zip->addFile($filePath, basename($_file));
                            $baseDir !== null && $zip->addFile($filePath,  str_replace($baseDir.'/', '', $filePath) );
                        });
                    }else{
                        //重命名
                        //$zip->renameName('currentname.txt','newname.txt');

                        // 添加文件并丢弃源目录结构
                        $zip->addFile($file, basename($file));
                    }
                    unset($file);
                }
				// 关闭Zip对象
				$zip->close();
				unset($fileOrPaths);
				return $zipFile;
            }
			unset($zip, $zipFile, $fileOrPaths);
			return null;
		});
    }

	/**
     * 解压压缩包
     *
     * @param string $zipFile 要解压的压缩包文件名
     * @param string $destPath 解压到指定目录
     * @param string|null $password 压缩包密码 不支持一切宽松等于(==)布尔False 的密码
     *
     * @return boolean|null
     */
    static public function unZip(string $zipFile, string $destPath, ?string $password = ""): ?bool{
        //检测要解压压缩包是否存在
        if(!is_file($zipFile)){
            return false;
        }
		return self::watchException(function()use(&$zipFile, &$destPath, $password){
			//检测目标路径是否存在
			if(!is_dir($destPath)){
				mkdir($destPath, 0777, true);
			}
            $zip = new ZipArchive();
            if($zip->open($zipFile)){
                if($password){
                    if(!$zip->setPassword($password)){
                        throw new Exception('Set password failed');
                    }
                }
                $zip->extractTo($destPath);
                $zip->close();
				unset($zipFile, $destPath);
				return true;
            }
			unset($zip);
			return false;
		});
    }

	/**
	 * 追加文件到zip
	 *
	 * @param string $zipFile 压缩包名
	 * @param string $appendFile 追加的文件名
	 * @param string $content 追加的文件内容
     * @param string|null $password 压缩包密码 不支持一切宽松等于(==)布尔False 的密码
	 * @return boolean|null
	 */
	static function append(string $zipFile, string $appendFile, string $content="",?string $password=""){

		return self::watchException(function()use(&$zipFile, &$appendFile, &$content, $password){
			$zip = new ZipArchive;
			if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                if($password){
                    if(!$zip->setPassword($password)){
                        throw new Exception('Set password failed');
                    }
                }
				$zip->addFromString($appendFile, $content);
				$zip->close();
				unset($zipFile, $appendFile, $content);
				return true;
            }
			unset($zip);
			return false;
		});
	}



    /**
     * Undocumented function
     *
     * @param Closure|null $closure
     * @return void
     */
	private static function watchException(?Closure $closure = null){
		try{
			$result = null;
			if(is_callable($closure)){
				$result = $closure();
			}
			if($result === false){
				throw new Exception("Could not open archive");
			}
			return $result;

        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
        return null;
	}
}
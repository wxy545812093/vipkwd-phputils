<?php
/**
 * @name PHP ZipArchive工具包
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use \ZipArchive;
use \Exception;
use \Closure;
use Vipkwd\Utils\Tools;

class Zip{

    /**
     * 文件打包（zipfile不存在将自动创建)
     *
     * :-e-: Vipkwd\Utils\Zip::addZip();
     * 
     * @param string $zipFile 打包后的文件名
     * @param string|array $fileOrPath 打包文件组
     * @param string|null $password 压缩包密码 不支持宽松等于(==)布尔False 的密码
     * 
     * @return string|null
     */
    static public function addZip(string $zipFile, $fileOrPath, ?string $password=""):?string{

		return self::watchException(function()use($zipFile, &$fileOrPath, $password){
            $zip = new ZipArchive();
            // 初始化
            // OVERWRITE zip覆盖模式
            // CREATE 追加模式
            $bool = $zip->open($zipFile, ZipArchive::CREATE|ZipArchive::OVERWRITE);
			if($bool === TRUE){
                if($password){
                    if(!$zip->setPassword($password)){
                        throw new Exception('Set password failed');
                    }
                }
                $zip->setArchiveComment('vipkwd/utils');
                $zip->addFromString('zipPackage-from.txt', "
                    This zip package create by PHP utils with \"vipkwd/utils\"

                    -- composer use:
                    --      composer require vipkwd/utils
                    --      
                    --      include \"vendor/autoload.php\"
                    --      Vipkwd\Utils\Tools::addZip(\"demo.zip\", \".\");
                    --      //And a zip package was created;
                ");
                if(is_string($fileOrPath)){
                    $fileOrPath =[$fileOrPath];
                }
                foreach ($fileOrPath as $file) {
                    if(is_dir($file)){
                        Tools::dirScan($file, function($_file, $_path) use(&$zip){
                            $zip->addFile($_path."/".$_file);
                        });
                    }else{
                        //重命名
                        //$zip->renameName('currentname.txt','newname.txt');

                        // 添加文件并丢弃源目录结构
                        // $zip->addFile($file, basename($file));

                        //添加文件并保留目录结构
                        $zip->addFile($file);
                    }
                }
				// 关闭Zip对象
				$zip->close();
				unset($fileOrPath);
				return $zipFile;
            }
			unset($zip, $zipFile, $fileOrPath);
			return false;
		});
    }

	/**
     * 解压压缩包
     * 
     * @param string $zipFile 要解压的压缩包
     * @param string $dest 解压到指定目录
     * @param string|null $password 压缩包密码 不支持宽松等于(==)布尔False 的密码
     * 
     * @return boolean|null
     */
    static public function unZip(string $zipFile, string $dest, ?string $password = ""): ?bool{
        //检测要解压压缩包是否存在
        if(!is_file($zipFile)){
            return false;
        }
		return self::watchException(function()use(&$zipFile, &$dest, $password){
			//检测目标路径是否存在
			if(!is_dir($dest)){
				mkdir($dest, 0777, true);
			}
            $zip = new ZipArchive();
            if($zip->open($zipFile)){
                if($password){
                    if(!$zip->setPassword($password)){
                        throw new Exception('Set password failed');
                    }
                }
                $zip->extractTo($dest);
                $zip->close();
				unset($zipFile, $dest);
				return true;
            }
			unset($zip);
			return false;
		});
    }

	/**
	 * 追加文件到zip
	 *
	 * @param string $zipFile
	 * @param string $appendFile
	 * @param string $content
     * @param string|null $password 压缩包密码 不支持宽松等于(==)布尔False 的密码
	 * @return boolean|null
	 */
	static function appendFileContent(string $zipFile, string $appendFile, string $content="",?string $password=""):?bool{

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
	}
}
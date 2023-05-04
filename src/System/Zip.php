<?php

/**
 * @name ZipArchive工具包
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\System;

use \ZipArchive;
use \Exception;
use \Closure;
use Vipkwd\Utils\Tools;
use Vipkwd\Utils\System\File;

class Zip
{

    /**
     * 压缩文件或目录
     *
     * @param string $zipFile 打包后的文件名
     * @param string|array $fileOrPaths 打包文件组(支持目录)
     * @param string|null $password 压缩包密码 不支持一切宽松等于(==)布尔False 的密码
     * @throw \Exception
     *
     * @return string|null
     */
    static function addZip(string $zipFile, $fileOrPaths, ?string $password = "")
    {

        return self::watchException(function () use ($zipFile, &$fileOrPaths, $password) {
            $zip = new ZipArchive();
            // 初始化
            // OVERWRITE zip覆盖模式
            // CREATE 追加模式
            $bool = $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($bool === true) {
                if ($password) {
                    if (!$zip->setPassword($password)) {
                        throw new Exception('密码设置失败');
                    }
                }
                $zip->setArchiveComment('vipkwd/utils');
                $zip->addFromString('license.txt', @file_get_contents(__DIR__ . '/zip_license.txt'));
                $baseDir = null;
                if (is_string($fileOrPaths)) {
                    $fileOrPaths = realpath($fileOrPaths);
                    is_dir($fileOrPaths) && $baseDir = $fileOrPaths;
                    $fileOrPaths = [$fileOrPaths];
                }

                foreach ($fileOrPaths as $file) {
                    if (is_dir($file)) {
                        Tools::dirScan($file, function ($_file, $_path) use (&$zip, $baseDir) {
                            $filePath = $_path . "/" . $_file;
                            $baseDir === null && $zip->addFile($filePath, basename($_file));
                            $baseDir !== null && $zip->addFile($filePath,  str_replace($baseDir . '/', '', $filePath));
                        });
                    } else {
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
            $bool === true && $zip->close();
            unset($zip, $zipFile, $fileOrPaths);
            return null;
        });
    }

    /**
     * 压缩目录下指定扩展文件
     *
     * @param string $zipFile 打包后的文件名
     * @param string $directory 打包文件所在目录
     * @param bool $subDirectory 是否读取子目录
     * @param array $exts ["*"] 文件扩展名
     * @param string|null $password 压缩包密码 不支持一切宽松等于(==)布尔False 的密码
     * @throw \Exception
     * 
     * @return string|null
     */
    static function addZipGlob(string $zipFile, string $directory, bool $subDirectory = false, array $exts = ["*"], ?string $password = "")
    {
        return self::watchException(function () use ($zipFile, $directory, $exts, $subDirectory, $password) {

            $directory = File::pathToUnix(File::realpath($directory));
            //检测要解压压缩包是否存在
            if (!is_dir($directory)) {
                throw new Exception("目录($directory)不存在");
            }
            if (!is_readable($directory)) {
                throw new Exception("目录($directory)没有读取权限");
            }
            $zipFile = File::pathToUnix(File::realpath($zipFile));
            $zip = new ZipArchive();
            $bool = $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($bool === true) {
                if ($password) {
                    if (!$zip->setPassword($password)) {
                        throw new Exception('密码设置失败');
                    }
                }
                $zip->setArchiveComment('vipkwd/utils');
                $zip->addFromString('license.txt', @file_get_contents(__DIR__ . '/zip_license.txt'));
                $_exts = "*.*";
                $exts = array_filter($exts, function (&$ext) {
                    $ext = trim($ext);
                    return $ext != '*';
                });

                //是否包含子目录
                if ($subDirectory) {
                    $directory = File::pathToUnix($directory . '/');
                    $match = false;
                    foreach (File::getFiles($directory, true) as $item) {

                        if (!empty($exts) && $item['type'] == 'f') {
                            $fileExt = File::getExtension($item['name']);
                            //过滤不需要打包的文件
                            if (!in_array($fileExt, $exts)) {
                                continue;
                            }
                        }
                        //添加空目录
                        if ($item['type'] == 'd' && trim($item['name'])) {
                            // $zip->addEmptyDir($item['struct']);
                        } elseif ($item['type'] == 'f') {
                            $zip->addFile($item['path'],  $item['struct']);
                            $match = true;
                        }
                    }
                    unset($directory, $exts, $password);
                } else {
                    if (($len = count($exts)) > 0) {
                        $_exts = $len > 1 ? '*.{' . implode(',', $exts) . '}' : '*.' . $exts[0];
                    }
                    $result = $zip->addGlob($directory . DIRECTORY_SEPARATOR . $_exts, GLOB_BRACE,  [
                        // "add_path" => '/',
                        'remove_path' => $directory,
                        // 'remove_all_path'  =>  TRUE
                    ]);
                    $match = !(is_array($result) && count($result) == 0);
                    unset($directory, $exts, $_exts, $password);
                }
                if ($match === false) {
                    File::delete($zipFile);
                    throw new Exception("目录下未匹配文件");
                }
                $zip->close();
                unset($zip);
                return $zipFile;
            }
            $bool === true && $zip->close();
            unset($zip, $directory, $exts, $password);
            return null;
        });
    }

    /**
     * 解压
     *
     * @param string $zipFile 要解压的压缩包文件名
     * @param string|null $destPath 解压到指定目录
     * @param string|null $password 压缩包密码 不支持一切宽松等于(==)布尔False 的密码
     * @throw \Exception
     *
     * @return boolean|null
     */
    static function unZip(string $zipFile, ?string $destPath = null, ?string $password = ""): ?bool
    {
        return self::watchException(function () use (&$zipFile, &$destPath, $password) {
            //检测要解压压缩包是否存在
            if (!is_file($zipFile)) {
                throw new Exception('压缩包文件不存在');
            }
            if(!$destPath){
                $destPath = dirname($zipFile);
            }
            //检测目标路径是否存在
            if (!is_dir($destPath)) {
                mkdir($destPath, 0777, true);
            }

            $zip = new ZipArchive();
            $bool = $zip->open($zipFile);

            $unset = function () use (&$zip, &$zipFile, &$destPath) {
                $zip->close();
                unset($zip, $zipFile, $destPath);
            };
            if ($bool === true) {
                if ($password) {
                    if (!$zip->setPassword($password)) {
                        $unset();
                        throw new Exception('密码设置失败');
                    }
                    if (!$zip->extractTo($destPath)) {
                        $unset();
                        throw new Exception('解压密码无效');
                    }
                } else {
                    $zip->extractTo($destPath);
                }
                $unset();
                unset($password);
                return true;
            }
            $bool === true && $zip->close();
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
     * @throw \Exception
     * 
     * @return boolean|null
     */
    static function appendFile(string $zipFile, string $appendFile, string $content = "", ?string $password = ""): ?bool
    {

        return self::watchException(function () use (&$zipFile, &$appendFile, &$content, $password) {
            //检测要解压压缩包是否存在
            if (!is_file($zipFile)) {
                throw new Exception('压缩包文件不存在');
            }
            $zip = new ZipArchive;
            $bool = $zip->open($zipFile, ZipArchive::OVERWRITE);
            if ($bool === true) {
                if ($password) {
                    if (!$zip->setPassword($password)) {
                        throw new Exception('密码设置失败');
                    }
                }
                $zip->addFromString($appendFile, $content);
                $zip->close();
                unset($zip, $zipFile, $appendFile, $content, $password);
                return true;
            }
            $bool === true && $zip->close();
            unset($zip);
            return false;
        });
    }

    /**
     * 批量追加文件到zip
     * 
     * @param string $zipFile
     * @param array $appendFiles key为包内path结构,value为添加的内容(支持: 存在的文件path、字符串、数字)
     *  ----------------------- ['/nginx/nginx.conf' => "/usr/local/nginx/conf/nginx.conf", 'a.log' => " ... somethings" ]
     * @param string|null $password 压缩包密码 不支持一切宽松等于(==)布尔False 的密码
     * @throw \Exception
     * 
     * @return bool|null
     */
    static function appendFiles(string $zipFile, array $appendFiles, ?string $password = ""): ?bool
    {
        return self::watchException(function () use (&$zipFile, &$appendFiles, $password) {
            //检测压缩包是否存在
            if (!is_file($zipFile)) {
                throw new Exception('压缩包文件不存在');
            }
            $zip = new ZipArchive;
            $bool = $zip->open($zipFile, ZipArchive::OVERWRITE);
            if ($bool === true) {
                if ($password) {
                    if (!$zip->setPassword($password)) {
                        throw new Exception('密码设置失败');
                    }
                }
                foreach ($appendFiles as $packPath => $item) {
                    if (is_file($item)) {
                        is_readable($item) && $zip->addFile($packPath, File::read($item));
                    } elseif (is_string($item) || is_numeric($item)) {
                        $zip->addFromString($packPath, $item);
                    }
                }
                $zip->close();
                unset($zip, $zipFile, $appendFiles, $password);
                return true;
            }
            $bool === true && $zip->close();
            unset($zip);
            return false;
        });
    }

    /**
     * 统计压缩包文件数量
     * 
     * @param string $zipFile
     * @param string $password
     * @throw \Exception
     * 
     * @return int|bool
     */
    static function fileCount(string $zipFile, ?string $password = '')
    {
        return self::watchException(function () use (&$zipFile, $password) {
            //检测要解压压缩包是否存在
            if (!is_file($zipFile)) {
                throw new Exception('压缩包文件不存在');
            }
            $zip = new ZipArchive;
            $bool = $zip->open($zipFile, ZipArchive::CREATE);
            if ($bool === true) {
                if ($password) {
                    if (!$zip->setPassword($password)) {
                        throw new Exception('密码设置失败');
                    }
                }
                $comment = $zip->count();
                $zip->close();
                unset($zip, $zipFile);
                return $comment;
            }
            $bool === true && $zip->close();
            unset($zip);
            return false;
        });
    }

    /**
     * 获取压缩包注解
     * 
     * @param string $zipFile
     * @param string $password
     * @throw \Exception
     * 
     * @return string|bool
     */
    static function getComment(string $zipFile, ?string $password = '')
    {
        return self::watchException(function () use (&$zipFile, $password) {
            //检测要解压压缩包是否存在
            if (!is_file($zipFile)) {
                throw new Exception('压缩包文件不存在');
            }
            $zip = new ZipArchive;
            $bool = $zip->open($zipFile, ZipArchive::CREATE);
            if ($bool === true) {
                if ($password) {
                    if (!$zip->setPassword($password)) {
                        throw new Exception('密码设置失败');
                    }
                }
                $comment = $zip->getArchiveComment();
                $zip->close();
                unset($zip, $zipFile);
                return $comment;
            }
            $bool === true && $zip->close();
            unset($zip);
            return false;
        });
    }

    /**
     * Undocumented function
     *
     * @param Closure|null $closure
     * 
     * @return null|Exception
     */
    private static function watchException(?Closure $closure = null)
    {
        try {
            $result = null;
            if (is_callable($closure)) {
                $result = $closure();
            }
            if ($result === false) {
                throw new Exception("压缩包读取失败");
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return null;
    }
}

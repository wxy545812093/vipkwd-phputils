<?php

/**
 * @name PHP Archive工具包
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\System;

// use \ZipArchive;
// use \Exception;
// use \Closure;
// use Vipkwd\Utils\Tools;
use Vipkwd\Utils\Libs\Zip\{UnZip as PhpUnZip, Zip as PhpZip};

class KZip
{
	/**
	 * 解压
	 * 
	 * @param string $zipFile 待解压的包
     * @param string $destPath 解压到指定目录
	 * 
	 * @return true
	 */
	static function unZip(string $zipFile, string $destPath): bool
	{
		$unzip = new PhpUnZip();
		foreach ($unzip->ReadFile($zipFile) as $row) {
			$file = $destPath . '/' . $row['path'] . '/' . $row['name'];
			$parentDir = dirname($file);
			!is_dir($parentDir) && mkdir($parentDir, 0777, true);
			if (@file_put_contents($file, $row['data']) !== false) {
				@touch($file, $row['time'], $row['time']);
			}
		}
		return true;
	}

	/**
	 * 压缩文件或目录
	 * 
	 * @param string $zipFile 生成(压缩到)zip文件名
	 * @param string $fileOrPath 文件或目录
	 * 
	 * @return int|false 成功则返回写入字节数
	 */
	static function addZip(string $zipFile, string $fileOrPath)
	{
		$zip = new PhpZip();
		if (is_dir($fileOrPath)) {
			$zip->addDir($fileOrPath);
		} else if (!is_file($fileOrPath)) {
			$zip->addFile(@file_get_contents($fileOrPath), basename($fileOrPath), filemtime($fileOrPath));
		} else {
			return false;
		}
		return @file_put_contents($zipFile, $zip->file());
	}
}

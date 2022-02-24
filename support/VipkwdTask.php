<?php
$width = 60;
echo str_pad("",$width,"-").PHP_EOL;
// echo "----".str_pad("任务构建",56,'·',STR_PAD_BOTH)."----".PHP_EOL;
$maps = json_decode(file_get_contents("http://dl.vipkwd.com/vipkwd-cdn/maps.php"),true);

$idx = 1;
foreach($maps as $file => $map){
	$sfile = __DIR__.'/'.$file;
	$key = null;
	if(file_exists($sfile)){
		$key = hash_file('md5', $sfile);
	}
	if($key != $map['hash']){
		echo TaskUtils9973200::pad($width, "-> {$idx} Update {$file} ### ");
		TaskUtils9973200::getFile($map['url'], dirname($sfile), basename($sfile));
		echo TaskUtils9973200::pad($width, "   ### (Completed)","###", '└');
		usleep(600);
	}else{
		echo TaskUtils9973200::pad($width, "-> {$idx} Update {$file} ### (Skiped)");
	}
	$idx++;
}
echo str_pad("", $width ,"-").PHP_EOL;

class TaskUtils9973200 {

	static function getFile($url, $folder = "./", $saveName=null){
		set_time_limit(24 * 60 * 60);
		// 设置超时时间
		$destination_folder = rtrim(realpath($folder),'/') . '/';
		// 文件下载保存目录，默认为当前文件目录
		if (!is_dir($destination_folder)) {
			// 判断目录是否存在
			self::mkdirs($destination_folder);
			// 如果没有就建立目录
		}
		$newfname = rtrim($destination_folder,'/'). '/' . ($saveName ?? basename($url));
		// 取得文件的名称
		$file = fopen($url, "rb");
		// 远程下载文件，二进制模式
		if ($file) {
			// 如果下载成功
			$newf = fopen($newfname, "wb");
			// 远在文件文件
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
		return true;
	}
	static function mkdirs($path, $mode = "0755"){
		if (!is_dir($path)) {
			// 判断目录是否存在
			mkdirs(dirname($path), $mode);
			// 循环建立目录
			mkdir($path, $mode);
			// 建立目录
		}
		return true;
	}

	static function pad($width, $text, $seper = '###', $prefix=''){
		$pad = implode('',array_pad([], ($width - strlen($text) - (strlen($prefix)/3) + strlen($seper)), "·"));
		return str_replace( $seper, $prefix . $pad ,$text).PHP_EOL;
	}
}
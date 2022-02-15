<?php
/**
 * @name 单文件上传
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs;
use Vipkwd\Utils\File;
use \Exception;

class Upload{
	
    public function __construct(){}

    private $file = null;

    private $options = [
        "max_size" => 10 * 1024 * 1024,

        //设置文件保存目录
        "upload_dir" => "upfiles/",

        //设置允许上传文件的类型
        "type" => ["jpg","gif","bmp","jpeg","png"], 
    ];
    
    /**
     * 单文件上传
     *
     * @param string $uploadKey <file> $_FILES[?]
     * @param array $options
     *                  --max_size integer <10 * 1024 * 1024>  限制可上传文件大小(单位)
     *                  --upload_dir string <"upfiles/"> 保存目录
     *                  --type array <["jpg","gif","bmp","jpeg","png"]> 允许扩展
     *                  --file_name_prefix string <''> 文件名前缀
     * @return string
     */
    public function upload($uploadKey = "file", $options = []){
        try{
            if(!isset($_FILES[$uploadKey])){
                return null;
            }
            $this->init($uploadKey, $options);
            
            // err01 
            if( true !== ($error = $this->__checkFileError())){
                return $error;
            }

            //err02 判断文件类型 
            if( true !== ($error = $this->_checkExtension())){
                return $error;
            }

            //err03 
            if( true !== ($error = $this->_checkUploadFileSize())){
                return $error;
            }
            
            if( !is_dir($this->options['upload_dir'])){
                @mkdir($this->options['upload_dir'], 0777, true);
            }
            
            //生成目标文件的文件名 else {
            $filename = str_split($this->file['name'], strrpos( $this->file['name'], "."));
            do {
                //设置随机数长度 
                $filename[0] = $this->random(16);
                $name = implode("",$filename);
                if(isset($this->options['file_name_prefix']) && $this->options['file_name_prefix']){
                    $file_name_prefix = str_replace(["\\",'/'],"", $this->options['file_name_prefix']);
                    $name = $file_name_prefix.$name;
                }
                $uploadfile= rtrim($this->options['upload_dir'],"/") .'/'. $name;

            }while(file_exists($uploadfile));

            $ret_code="ERR-FE004";
            $msg = "上传失败";
            $data = null;
            
            //$hash = hash_file("md5", $this->file['tmp_name']);

            if(is_uploaded_file($this->file['tmp_name'])) {
                if (move_uploaded_file($this->file['tmp_name'], $uploadfile)) {
                    $msg= "上传成功";
                    $ret_code=0;
                    $data = [
                        "size" => $this->file['size'],
                        "ext"  => $this->file['ext'],
                        "mime" => $this->file['type'],
                        "path" => $uploadfile,
                        "hash" => hash_file("md5", $uploadfile),
                        "name" => $name
                    ];
                }
            }
            return array('code' => $ret_code, "msg" => $msg, "data" => $data);
        }catch(Exception $e){
            return array('code' => "ERR-FE000", "msg" => $e->getMessage() );
        }
    }

    private function __checkFileError(){
        switch($this->file['error']) {
            case 1: return true; $msg="文件大小超出了服务器的空间大小"; break;
            case 2: return true; $msg="文件大小超出浏览器限制"; break;
            case 3: $msg="文件仅部分被上传"; break;
            case 4: $msg="文件不能为空"; break;//没有找到要上传的文件
            case 5: $msg="上传服务异常"; break;//服务器临时文件夹丢失
            case 6: $msg="文件写入出错"; break;//文件写入到临时文件夹出错
            default: return true;
        }
        return [
            "code" => "ERR-FE001",
            "msg" => $msg
        ];
    }

    private function _checkExtension(){

        $this->file['ext'] = strtolower( self::fileext($this->file['name']));

        if(!in_array( $this->file['ext'], $this->options['type']) ) {
            $text=implode(",", $this->options['type'] );
            //文件类型错误
            $page_result=$text;
            $ret_code="ERR-FE002";
            return array('code' => $ret_code,'msg'=>$page_result);
        }
        return true;
    }

    private function _checkUploadFileSize(){
        if( $this->file['size'] > $this->options['max_size']) {
            $msg= "文件大小不能超过“".File::bytesTo($maxSize)."”";
            $ret_code="ERR-FE003";
            return array('code' => $ret_code,'msg'=>$msg);
        }
        return true;   
    }

	//获取文件后缀名函数 
	private static function fileext($filename) {
		return strtolower(substr(strrchr($filename, '.'), 1));
	}

	//生成随机文件名函数 
	private static function random($length) {
		$hash = '';
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
		$max = strlen($chars) - 1;
		mt_srand( (int)((double)microtime() * 1000000 ));
		for ($i = 0; $i < $length; $i++) {
			$hash .= $chars[mt_rand(0, $max)];
		}
		return $hash;
	}

    private function init($uploadKey, $options){
        $this->file = $_FILES[$uploadKey];

        $this->options = array_merge($this->options, $options);

        $this->options['max_size'] = File::toBytes("".$this->options['max_size']);

        $maxSize = File::fileUploadMaxSize();
        if($this->options['max_size'] > $maxSize){
            $this->options['max_size'] = $maxSize;
        }

        // $this->options['upload_dir'] = realpath($this->options['upload_dir']);
        if(is_string($this->options['type'])){
            $this->options['type'] = explode(',', $this->options['type']);
        }
        $this->options['type'] = array_map(function($type){
            return strtolower($type);
        }, $this->options['type']);
    }

    private function compressed(){

        return null;

        //压缩图片
        $uploaddir_resize="upfiles_resize/";
        $uploadfile_resize=$uploaddir_resize.$name;
        //$pic_width_max=120;
        //$pic_height_max=90;
        //以上与下面段注释可以联合使用，可以使图片根据计算出来的比例压缩
        $file_type=$this->file['type'];
        
        if($this->file['size']) {
            if($file_type == "image/pjpeg"||$file_type == "image/jpg"|$file_type == "image/jpeg") {
                //$im = imagecreatefromjpeg($this->file['tmp_name']);
                $im = imagecreatefromjpeg($uploadfile);
            } elseif($file_type == "image/x-png") {
                //$im = imagecreatefrompng($this->file['tmp_name']);
                $im = imagecreatefromjpeg($uploadfile);
            } elseif($file_type == "image/gif") {
                //$im = imagecreatefromgif($this->file['tmp_name']);
                $im = imagecreatefromjpeg($uploadfile);
        } else//默认jpg {
            $im = imagecreatefromjpeg($uploadfile);
        }
        
        if($im) {
            self::resizeImage($im,$pic_width_max,$pic_height_max,$uploadfile_resize);
            ImageDestroy ($im);
        }
    }
               
    private function resizeImage($uploadfile,$maxwidth,$maxheight,$name) {
		//取得当前图片大小
		$width = imagesx($uploadfile);
		$height = imagesy($uploadfile);
		$i=0.5;

		//生成缩略图的大小
		if(($width > $maxwidth) || ($height > $maxheight)) {
			/*
                $widthratio = $maxwidth/$width;
                $heightratio = $maxheight/$height;
                if($widthratio < $heightratio) {
                    $ratio = $widthratio;
                } else{
                    $ratio = $heightratio;
                }
                $newwidth = $width * $ratio;
                $newheight = $height * $ratio;
            */
			$newwidth = $width * $i;
			$newheight = $height * $i;
			if( function_exists("imagecopyresampled") ) {
				$uploaddir_resize = imagecreatetruecolor($newwidth, $newheight);
				imagecopyresampled($uploaddir_resize, $uploadfile, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
			} else {
				$uploaddir_resize = imagecreate($newwidth, $newheight);
				imagecopyresized($uploaddir_resize, $uploadfile, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
			}
			ImageJpeg($uploaddir_resize,$name);
			ImageDestroy($uploaddir_resize);
		} else {
			ImageJpeg($uploadfile,$name);
		}
	}
}
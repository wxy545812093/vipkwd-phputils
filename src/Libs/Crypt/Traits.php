<?php
/**
 * @name Trait
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs\Crypt;
use \Exception;

trait Traits {
    private static $_instance = [];
    private $_key;//秘钥向量
    private $_iv;//混淆向量 ->偏移量
 
    private function __construct(string $key, string $iv) {
        $this->_key = $key;
        $this->_iv  = $iv;
    }

    /**
     * 实例化
     * 
     * @return class
     */
    static function instance(string $key, string $iv){
        if(strlen($iv) != self::$_ivLength ){
            throw new Exception("IV char supports only ".self::$_ivLength." bytes");
        }
        $_k = md5($key.$iv);
        if (!isset(self::$_instance[$_k]) || !self::$_instance[$_k] ) {
            self::$_instance[$_k] = new self("$key", "$iv");
        }
        return self::$_instance[$_k];
    }
    /**
     * 加密
     * @param string 要加密的字符串
     * @param boolean $trim <false> 去除base64尾部填充
     * @return string 加密成功返回加密后的字符串，否则返回false
     */
    public function encrypt(string $str, bool $trim = false){
        //if (strlen($str) % 4) {
            //$str = str_pad($str,strlen($str) + 4 - strlen($str) % 4, "\0");
        //}
        $data = openssl_encrypt($str, self::$_modeType, $this->_key, OPENSSL_RAW_DATA, $this->_iv);
        if($data === false){
            return false;
        }
        $data = base64_encode($data);
        return $trim ? rtrim(rtrim($data,"="),"=") : $data;
    }

    /**
     * 解密
     * @param string 要解密的字符串
     * @return mixed 加密成功返回加密后的字符串，否则返回false
     */
    public function decrypt(string $str){
        return openssl_decrypt(base64_decode($str),self::$_modeType, $this->_key, OPENSSL_RAW_DATA, $this->_iv);
    }
    
}
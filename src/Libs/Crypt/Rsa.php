<?php
/**
 * @name RSA 加密解密
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs\Crypt;

use \Exception;

class Rsa{

    private static $_instance = [];

    private $_pubkey = '-----BEGIN PUBLIC KEY-----
    MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCmkANmC849IOntYQQdSgLvMMGm
    8V/u838ATHaoZwvweoYyd+/7Wx+bx5bdktJb46YbqS1vz3VRdXsyJIWhpNcmtKhY
    inwcl83aLtzJeKsznppqMyAIseaKIeAm6tT8uttNkr2zOymL/PbMpByTQeEFlyy1
    poLBwrol0F4USc+owwIDAQAB
    -----END PUBLIC KEY-----';

    private $_prikey = '-----BEGIN PRIVATE KEY-----
    MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAKaQA2YLzj0g6e1h
    BB1KAu8wwabxX+7zfwBMdqhnC/B6hjJ37/tbH5vHlt2S0lvjphupLW/PdVF1ezIk
    haGk1ya0qFiKfByXzdou3Ml4qzOemmozIAix5ooh4Cbq1Py6202SvbM7KYv89syk
    HJNB4QWXLLWmgsHCuiXQXhRJz6jDAgMBAAECgYAIF5cSriAm+CJlVgFNKvtZg5Tk
    93UhttLEwPJC3D7IQCuk6A7Qt2yhtOCvgyKVNEotrdp3RCz++CY0GXIkmE2bj7i0
    fv5vT3kWvO9nImGhTBH6QlFDxc9+p3ukwsonnCshkSV9gmH5NB/yFoH1m8tck2Gm
    BXDj+bBGUoKGWtQ7gQJBANR/jd5ZKf6unLsgpFUS/kNBgUa+EhVg2tfr9OMioWDv
    MSqzG/sARQ2AbO00ytpkbAKxxKkObPYsn47MWsf5970CQQDIqRiGmCY5QDAaejW4
    HbOcsSovoxTqu1scGc3Qd6GYvLHujKDoubZdXCVOYQUMEnCD5j7kdNxPbVzdzXll
    9+p/AkEAu/34iXwCbgEWQWp4V5dNAD0kXGxs3SLpmNpztLn/YR1bNvZry5wKew5h
    z1zEFX+AGsYgQJu1g/goVJGvwnj/VQJAOe6f9xPsTTEb8jkAU2S323BG1rQFsPNg
    jY9hnWM8k2U/FbkiJ66eWPvmhWd7Vo3oUBxkYf7fMEtJuXu+JdNarwJAAwJK0YmO
    LxP4U+gTrj7y/j/feArDqBukSngcDFnAKu1hsc68FJ/vT5iOC6S7YpRJkp8egj5o
    pCcWaTO3GgC5Kg==
    -----END PRIVATE KEY-----';
 
    private function __construct(string $pub, string $pri) {
        $pub && $this->_pubkey = $this->fileToKey($pub,"pub");
        $pri && $this->_prikey = $this->fileToKey($pri,"pri");
    }

    /**
     * 实例化
     *
     * @param string $pubkey <""> 
     * @param string $prikey <"">
     * @return void
     */
    static function instance(string $pubkey="", string $prikey=""){
        $_k = md5($pubkey.$prikey."jsk");
        if (!isset(self::$_instance[$_k]) || !self::$_instance[$_k] ) {
            self::$_instance[$_k] = new self($pubkey, $prikey);
        }
        return self::$_instance[$_k];
    }

    /**
     * 设置私钥
     *
     * @param string $prikey
     * @return void
     */
    public function setPriKey(string $prikey){
        $this->fileToKey($prikey,"pri");
        return $this;
    }

    /**
     * 设置公钥
     *
     * @param string $pubkey
     * @return void
     */
    public function setPubKey(string $pubkey){
        $this->fileToKey($pubkey,"pub");
        return $this;
    }

    /**
     * 公钥加密
     *
     * @param string $str
     * @return void
     */
    public function encryptPubkey(string $str){
        return $this->_encrypt($str, "pub");
    }

    /**
     * 私钥解密
     *
     * @param string $str
     * @return void
     */
    public function decryptPrikey(string $str){
        return $this->_decrypt($str, "pri");
    }

    /**
     * 私钥加密
     *
     * @param string $str
     * @return void
     */
    public function encryptPrikey(string $str){
        return $this->_encrypt($str, "pri");
    }

    /**
     * 公钥解密
     *
     * @param string $str
     * @return void
     */
    public function decryptPubkey(string $str){
        return $this->_decrypt($str, "pub");
    }

    /**
     * 私钥签名
     *
     * @param string $str
     * @return string
     */
    public function sign(string $str):string{
        if($this->validateKey(false)){
            openssl_sign($str, $sign, $this->_prikey);
            return base64_encode($sign);
        }
        throw new Exception("私钥无效");
    }
    /**
     * 公钥验签
     *
     * @param string $str
     * @param string $sign
     * @return boolean
     */
    public function verify(string $str, string $sign):bool{
        if($this->validateKey(true)){
            return (bool)openssl_verify($str, base64_decode($sign), $this->_pubkey);
        }
        throw new Exception("公钥无效");
    }

    private function _encrypt($str, $type = "pri"){
        $state = false;
        if($type == "pub"){
            ( false != ($pub = $this->validateKey(true)) ) && $state = openssl_public_encrypt($str, $crypted, $pub);
        }else{
            ( false != ($pri = $this->validateKey(false)) ) && $state = openssl_private_encrypt($str, $crypted, $pri);
        }
        if(!$state){
            throw new Exception('加密失败,请检查RSA秘钥');
        }
        return base64_encode($crypted);
    }

    private function _decrypt($str, $type = "pub"){
        $str =base64_decode($str);
        $state = false;
        if($type == "pub"){
            ( false !== ($pub = $this->validateKey(true)) ) && $state = openssl_public_decrypt($str, $decrypted, $pub);
        }else{
            ( false !== ($pri = $this->validateKey(false)) ) && $state = openssl_private_decrypt($str, $decrypted, $pri);
        }
        if(!$state){
            throw new Exception('解密失败,请检查RSA秘钥');
        }
        return $decrypted;
    }

    private function validateKey($pubkey = true){
        return $pubkey ? openssl_pkey_get_public($this->_pubkey) : openssl_pkey_get_private($this->_prikey);
    }

    /**
     * 如果带入签名是文件则自动获取文件内容
     *
     * @param string $keytext
     * @param string $type  pub|pri
     * @return string
     */
    private function fileToKey(string $keytext, string $type):string{
        if(is_file($keytext)){
            $keytext = realpath($keytext);
            if($type == "pub"){
                return $this->_pubkey = file_get_contents($keytext);
            }
            return $this->_prikey = file_get_contents($keytext);
        }
        return $keytext;
    }
}

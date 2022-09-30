<?php

/**
 * @name RSA 加密解密
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Libs\Crypt;

use \Exception;

class Rsa
{

    private static $_instance = [];

    private $_pubkey = 'MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAMTJt8J/o//FSTCZqsyqO/knnAtuC3+tk0fxthsRclIbnt1RNfDLZWwoq0/+G56K9/7WYVcP9oWZnmzXEyydhqkCAwEAAQ==';
    private $_prikey = 'MIIBVAIBADANBgkqhkiG9w0BAQEFAASCAT4wggE6AgEAAkEAxMm3wn+j/8VJMJmqzKo7+SecC24Lf62TR/G2GxFyUhue3VE18MtlbCirT/4bnor3/tZhVw/2hZmebNcTLJ2GqQIDAQABAkAH2JcYDSjHyODrLCVQNbVgcMDa/887jvshUjTVjXOGbHuW5EecYAo3zUvBpeIu9PIizDvnhFzaAesEQ1VFh12VAiEA6Qjmhi0JyhqcoLmDOQDU7113xvODV+qsp94Xn5iAWQMCIQDYLl4Y0vnh36dSHNWfnntwA/2j6qBGlyCXgeEZ8rEz4wIhAKQxATvIv/0GgxU7oJmpXF7LHHmxWfm3/67HbR2l9cjBAiAAjC2E1pP3dH+R/6yy2M3rPLdZwPBi/WMBvzx4ulqkjQIgEUf5c6jboLbEUmzew+4yv6FhEAseP0ZQJ6k7ZoGBCJU=';

    private function __construct(string $pub, string $pri)
    {
        ($pub && $pub != "your pub key") && $this->_pubkey = $this->fileToKey($pub, "pub");
        ($pri && $pri != "your pri key") && $this->_prikey = $this->fileToKey($pri, "pri");
    }

    /**
     * 实例化
     *
     * @param string $pubkey <"">
     * @param string $prikey <"">
     * @return void
     */
    static function instance(string $pubkey = "", string $prikey = "")
    {
        $_k = md5($pubkey . $prikey . "jsk");
        if (!isset(self::$_instance[$_k]) || !self::$_instance[$_k]) {
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
    public function setPriKey(string $prikey)
    {
        ($prikey && $prikey != "your pri key") && $this->fileToKey($prikey, "pri");
        return $this;
    }

    /**
     * 设置公钥
     *
     * @param string $pubkey
     * @return void
     */
    public function setPubKey(string $pubkey)
    {
        ($pubkey && $pubkey != "your pub key") && $this->fileToKey($pubkey, "pub");
        return $this;
    }

    /**
     * 公钥加密
     *
     * @param string $str
     * @return void
     */
    public function encryptPubkey(string $str)
    {
        return $this->_encrypt($str, "pub");
    }

    /**
     * 私钥解密
     *
     * @param string $str
     * @return void
     */
    public function decryptPrikey(string $str)
    {
        return $this->_decrypt($str, "pri");
    }

    /**
     * 私钥加密
     *
     * @param string $str
     * @return void
     */
    public function encryptPrikey(string $str)
    {
        return $this->_encrypt($str, "pri");
    }

    /**
     * 公钥解密
     *
     * @param string $str
     * @return void
     */
    public function decryptPubkey(string $str)
    {
        return $this->_decrypt($str, "pub");
    }

    /**
     * 私钥签名
     *
     * @param string $str
     * @return string
     */
    public function sign(string $str): string
    {
        if ($this->validateKey(false)) {
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
    public function verify(string $str, string $sign): bool
    {
        if ($this->validateKey(true)) {
            return (bool)openssl_verify($str, base64_decode($sign), $this->_pubkey);
        }
        throw new Exception("公钥无效");
    }

    private function _encrypt($str, $type = "pri")
    {
        $state = false;
        if ($type == "pub") {
            (false != ($pub = $this->validateKey(true))) && $state = openssl_public_encrypt($str, $crypted, $pub);
        } else {
            (false != ($pri = $this->validateKey(false))) && $state = openssl_private_encrypt($str, $crypted, $pri);
        }
        if (!$state) {
            throw new Exception('加密失败,请检查RSA秘钥');
        }
        return base64_encode($crypted);
    }

    private function _decrypt($str, $type = "pub")
    {
        $str = base64_decode($str);
        $state = false;
        if ($type == "pub") {
            (false !== ($pub = $this->validateKey(true))) && $state = openssl_public_decrypt($str, $decrypted, $pub);
        } else {
            (false !== ($pri = $this->validateKey(false))) && $state = openssl_private_decrypt($str, $decrypted, $pri);
        }
        if (!$state) {
            throw new Exception('解密失败,请检查RSA秘钥');
        }
        return $decrypted;
    }

    private function validateKey($pubkey = true)
    {
        if ($pubkey) {
            $this->fileToKey($this->_pubkey, "pub");
            return openssl_pkey_get_public($this->_pubkey);
        } else {
            $this->fileToKey($this->_prikey, "pri");
            return openssl_pkey_get_private($this->_prikey);
        }
    }

    /**
     * 如果带入签名是文件则自动获取文件内容
     *
     * @param string $keytext
     * @param string $type  pub|pri
     * @return string
     */
    private function fileToKey(string $keytext, string $type): string
    {
        if (is_file($keytext)) {
            $keytext = file_get_contents(realpath($keytext));
        }
        $keytext = str_ireplace(['-----BEGIN PUBLIC KEY-----', "-----BEGIN PRIVATE KEY-----"], "", $keytext);
        $keytext = trim(str_ireplace(['-----END PUBLIC KEY-----', "-----END PRIVATE KEY-----"], "", $keytext));
        $keytext = str_replace(["\r\n", "\n"], "", $keytext);
        $keytext = ltrim(wordwrap($keytext, 64, "\n", true));

        if ($type == "pub") {
            $keytext = "-----BEGIN PUBLIC KEY-----\n" . $keytext . "\n";
            $keytext .= "-----END PUBLIC KEY-----";
            $this->_pubkey = $keytext;
        } else {
            $keytext = "-----BEGIN PRIVATE KEY-----\n" . $keytext . "\n";
            $keytext .= "-----END PRIVATE KEY-----";
            $this->_prikey = $keytext;
        }
        return $keytext;
    }
}
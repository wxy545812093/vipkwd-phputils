<?php
/**
 * @name 加解密组件
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\Libs\Crypt\Des;
use Vipkwd\Utils\Libs\Crypt\Aes;
use Vipkwd\Utils\Libs\Crypt\Rsa;

class Crypt{
    
    /**
     * DES加密
     *
     * @param string $data
     * @param string $key
     * @param string $iv8
     * @return void
     */
    static function encryptDes(string $data, string $key, string $iv8){
        return Des::instance($key, $iv8)->encrypt($data);
    }

    /**
     * DES解密
     *
     * @param string $data
     * @param string $key
     * @param string $iv8
     * @return void
     */
    static function decryptDes(string $data, string $key, string $iv8){
        return Des::instance($key, $iv8)->decrypt($data);
    }

    /**
     * AES加密
     *
     * @param string $data
     * @param string $key
     * @param string $iv16
     * @return void
     */
    static function encryptAes(string $data, string $key, string $iv16){
        return Aes::instance($key, $iv16)->encrypt($data);
    }

    /**
     * AES解密
     *
     * @param string $data
     * @param string $key
     * @param string $iv16
     * @return void
     */
    static function decryptAes(string $data, string $key, string $iv16){
        return Aes::instance($key, $iv16)->decrypt($data);
    }

    /**
     * [RSA一类] 公钥加密
     *
     * @param string $data
     * @param string $pubkey
     * @return void
     */
    static function encryptRsaPub(string $data, string $pubkey){
        return Rsa::instance()->setPubKey($pubkey)->encryptPubkey($data);
    }

    /**
     * [RSA一类] 私钥解密
     *
     * @param string $data
     * @param string $prikey
     * @return void
     */
    static function decryptRsaPri(string $data, string $prikey){
        return Rsa::instance()->setPriKey($prikey)->decryptPrikey($data);
    }

    /**
     * [RSA二类] 私钥加密
     *
     * @param string $data
     * @param string $prikey
     * @return void
     */
    static function encryptRsaPri(string $data, string $prikey){
        return Rsa::instance()->setPriKey($prikey)->encryptPrikey($data);
    }

    /**
     * [RSA二类] 公钥解密
     *
     * @param string $data
     * @param string $pubkey
     * @return void
     */
    static function decryptRsaPub(string $data, string $pubkey){
        return Rsa::instance()->setPubKey($pubkey)->decryptPubkey($data);
    }

    /**
     * [RSA三类] 私钥签名
     *
     * @param string $data 待加签数据
     * @param string $prikey
     * @return void
     */
    static function signRsa(string $data, string $prikey){
        return Rsa::instance()->setPriKey($prikey)->sign($data);
    }

    /**
     * [RSS三类] 公钥验签
     *
     * @param string $data 待验签数据
     * @param string $sign 待验证签名条
     * @param string $pubkey
     * @return void
     */
    static function verifyRsa(string $data, string $sign, string $pubkey){
        return Rsa::instance()->setPubKey($pubkey)->verify($data, $sign);
    }

}
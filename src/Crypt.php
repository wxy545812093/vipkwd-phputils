<?php
/**
 * @name 加解密组件
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\Libs\Crypt\{Des,Aes,Rsa};

class Crypt{
    
    /**
     * [A组]-DES. 加密
     * 
     * -e.g: phpunit("Crypt::desEncrypt", ["待加密字符串", "key", "iv_len_8"]);
     * 
     * @param string $data
     * @param string $key
     * @param string $iv8
     * @return void
     */
    static function desEncrypt(string $data, string $key, string $iv8){
        return Des::instance($key, $iv8)->encrypt($data);
    }

    /**
     * [A组]-DES. 解密
     * 
     * -e.g: $hash=\Vipkwd\Utils\Crypt::desEncrypt("待加密字符串", "key", "iv_len_8");
     * -e.g: echo 'Vipkwd\Utils\Crypt::desEncrypt("待加密字符串", "key", "iv_len_8");// '.$hash;
     * -e.g: phpunit("Crypt::desDecrypt", [$hash, "key", "iv_len_8"]);
     * 
     * @param string $data
     * @param string $key
     * @param string $iv8
     * @return void
     */
    static function desDecrypt(string $data, string $key, string $iv8){
        return Des::instance($key, $iv8)->decrypt($data);
    }

    /**
     * [B组]-AES. 加密
     * 
     * -e.g: phpunit("Crypt::aesEncrypt", ["待加密字符串", "key", "ivChar_length_16"]);
     * 
     * @param string $data
     * @param string $key
     * @param string $iv16
     * @return void
     */
    static function aesEncrypt(string $data, string $key, string $iv16){
        return Aes::instance($key, $iv16)->encrypt($data);
    }

    /**
     * [B组]-AES. 解密
     * 
     * -e.g: $hash=\Vipkwd\Utils\Crypt::aesEncrypt("待加密字符串", "key", "ivChar_length_16");
     * -e.g: echo 'Vipkwd\Utils\Crypt::aesEncrypt("待加密字符串", "key", "ivChar_length_16");// '.$hash;
     * -e.g: phpunit("Crypt::aesDecrypt", [$hash, "key", "ivChar_length_16"]);
     * 
     * @param string $data
     * @param string $key
     * @param string $iv16
     * @return void
     */
    static function aesDecrypt(string $data, string $key, string $iv16){
        return Aes::instance($key, $iv16)->decrypt($data);
    }

    /**
     * [C组]-RSA. 公钥加密
     * 
     * -e.g: phpunit("Crypt::rsaPubEncrypt", ["待加密字符串", "your pub key"]);
     * 
     * @param string $data
     * @param string $pubkey
     * @return void
     */
    static function rsaPubEncrypt(string $data, string $pubkey){
        return Rsa::instance()->setPubKey($pubkey)->encryptPubkey($data);
    }

    /**
     * [C组]-RSA. 私钥解密
     * 
     * -e.g: $hash=\Vipkwd\Utils\Crypt::rsaPubEncrypt("待加密字符串", "your pub key");
     * -e.g: echo 'Vipkwd\Utils\Crypt::rsaPubEncrypt("待加密字符串", "your pub key");// '.$hash;
     * -e.g: phpunit("Crypt::rsaPriDecrypt", [$hash, "your pri key"]);
     * 
     * @param string $data
     * @param string $prikey
     * @return void
     */
    static function rsaPriDecrypt(string $data, string $prikey){
        return Rsa::instance()->setPriKey($prikey)->decryptPrikey($data);
    }

    /**
     * [D组]-RSA. 私钥加密
     * 
     * -e.g: phpunit("Crypt::rsaPriEncrypt", ["待加密字符串", "your pri key"]);
     * 
     * @param string $data
     * @param string $prikey
     * @return void
     */
    static function rsaPriEncrypt(string $data, string $prikey){
        return Rsa::instance()->setPriKey($prikey)->encryptPrikey($data);
    }

    /**
     * [D组]-RSA. 公钥解密
     * 
     * -e.g: $hash=\Vipkwd\Utils\Crypt::rsaPriEncrypt("待加密字符串", "your pri key");
     * -e.g: echo 'Vipkwd\Utils\Crypt::rsaPriEncrypt("待加密字符串", "your pri key");// '.$hash;
     * -e.g: phpunit("Crypt::rsaPubDecrypt", [$hash, "your pub key"]);
     * 
     * @param string $data
     * @param string $pubkey
     * @return void
     */
    static function rsaPubDecrypt(string $data, string $pubkey){
        return Rsa::instance()->setPubKey($pubkey)->decryptPubkey($data);
    }

    /**
     * [R组]-RSA. 私钥签名
     * 
     * -e.g: phpunit("Crypt::rsaPriSign", ["待加密字符串", "your pri key"]);
     * 
     * @param string $data 待加签数据
     * @param string $prikey
     * @return void
     */
    static function rsaPriSign(string $data, string $prikey){
        return Rsa::instance()->setPriKey($prikey)->sign($data);
    }

    /**
     * [R组]-RSA. 公钥验签
     * 
     * -e.g: $hash=\Vipkwd\Utils\Crypt::rsaPriSign("待加密字符串", "your pri key");
     * -e.g: echo 'Vipkwd\Utils\Crypt::rsaPriSign("待加密字符串", "your pri key");// '.$hash;
     * -e.g: phpunit("Crypt::rsaPubVerifySign", ["待加密字符串", $hash, "your pub key"]);
     * 
     * @param string $data 待验签数据
     * @param string $sign 待验证签名条
     * @param string $pubkey
     * @return void
     */
    static function rsaPubVerifySign(string $data, string $sign, string $pubkey){
        return Rsa::instance()->setPubKey($pubkey)->verify($data, $sign);
    }
    
    /**
     * [F组]-RC4. 字符串加密
     * 
     * -e.g: phpunit("Crypt::rc4Encrypt", ["待加密字符串", "your key"]);
     * 
     * @param string $string 字符明文
     * @param string $key 密钥
     * @return string
     */
    static function rc4Encrypt(string $string, string $key=""):string{
        return self::cryptRC4($string, "E", $key);
    }

    /**
     * [F组]-RC4. 字符串解密
     * 
     * -e.g: $hash=\Vipkwd\Utils\Crypt::rc4Encrypt("待加密字符串", "your key");
     * -e.g: echo 'Vipkwd\Utils\Crypt::rc4Encrypt("待加密字符串", "your key");// '.$hash;
     * -e.g: phpunit("Crypt::rc4Decrypt", [$hash, "your key"]);
     * 
     * @param string $string 密文
     * @param string $key 密钥
     * @return string
     */
    static function rc4Decrypt(string $string, string $key=""):string{
        return self::cryptRC4($string, "D", $key);
    }


    /**
     * [P组]-加密登陆密码,防时序攻击
     *
     * @response 返回的字符长度：恒定60个字符
     * 
     * -e.g: phpunit("Crypt::passwordHash", ["your password"]);
     * 
     * @param string $password
     * @return string
     */
    static function passwordHash(string $password):string{
        return password_hash($password, PASSWORD_BCRYPT, ["cost"=>12]);
    }

    /**
     * [P组]-验证密码有效性
     * 
     * -e.g: $hash=\Vipkwd\Utils\Crypt::passwordHash("your password");
     * -e.g: echo 'Vipkwd\Utils\Crypt::passwordHash("your password");// '.$hash;
     * -e.g: phpunit("Crypt::passwordHashVerify", ["your password", $hash]);
     * 
     * @param string $password
     * @param string $hash
     * @return boolean
     */
    static function passwordHashVerify(string $password, string $hash):bool{
        return password_verify($password, $hash);
    }

    /**
     * Discuz 经典加解密函数
     * 
     * ---------------------------------------------------
     *  --      致敬经典:本函数版权归原作者方所有      --
     * ---------------------------------------------------
     * 
     * 注意：建议使用时设置 discuz_auth_key 通用密钥($GLOBALS['discuz_auth_key'])
     * 
     * -e.g: echo '//使用默认KEY';$hash=\Vipkwd\Utils\Crypt::authcode("your data","encode");
     * -e.g: echo 'Vipkwd\Utils\Crypt::authcode("your data","encode");// '.$hash;
     * -e.g: phpunit("Crypt::authcode", [$hash, "decode"]);
     * 
     * -e.g: echo '//使用自定义key';$hash=\Vipkwd\Utils\Crypt::authcode("your data","encode", "your key");
     * -e.g: echo 'Vipkwd\Utils\Crypt::authcode("your data","encode", "your key");// '.$hash;
     * -e.g: phpunit("Crypt::authcode", [$hash, "decode", "your key"]);
     * 
     * -e.g: echo '//设定过期时间(3s)'; $hash=\Vipkwd\Utils\Crypt::authcode("your data","encode", "your key", 3);
     * -e.g: echo 'Vipkwd\Utils\Crypt::authcode("your data","encode", "your key", 3);// '.$hash;
     * 
     * -e.g: sleep(1); echo '// 1: sleep(1)预期：可解密';
     * -e.g: phpunit("Crypt::authcode", [$hash, "decode", "your key"]);
     * -e.g: sleep(1);echo '// 2: sleep(1)预期：可解密';
     * -e.g: sleep(1);echo '// 3: sleep(1)预期：解密失败(响应空)';
     * -e.g: phpunit("Crypt::authcode", [$hash, "decode", "your key"]);
     * 
     * @param string $string 明文或密文
     * @param string $operation <DECODE> DECODE表示解密,其它表示加密
     * @param string $key <''> 密匙
     * @param integer $expiry <0> 密文有效期 秒
     * 
     * @return string
     */
    static function authcode(string $string, string $operation = 'DECODE', string $key = '', int $expiry = 0):string {  
        $ckey_length = 6;
        $key = md5($key ? $key : $GLOBALS['discuz_auth_key'] ?? "@<<5G-H^0Ywz%.");
        $decode = strtolower($operation) == "decode" ? true : false;
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($decode ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);
        $string = $decode ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if($decode) {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }

    private static function cryptRC4(string $string, string $operation, string $key=''){
        $key=md5($key);
        $key_length=strlen($key);
        $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
        $string_length=strlen($string);
        $rndkey=$box=array();
        $result='';
        for($i=0;$i<=255;$i++){
            $rndkey[$i]=ord($key[$i%$key_length]);
            $box[$i]=$i;
        }
        for($j=$i=0;$i<256;$i++){
            $j=($j+$box[$i]+$rndkey[$i])%256;
            $tmp=$box[$i];
            $box[$i]=$box[$j];
            $box[$j]=$tmp;
        }
        for($a=$j=$i=0;$i<$string_length;$i++){
            $a=($a+1)%256;
            $j=($j+$box[$a])%256;
            $tmp=$box[$a];
            $box[$a]=$box[$j];
            $box[$j]=$tmp;
            $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
        }
        if($operation=='D'){
            if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8)){
                return substr($result,8);
            }else{
                return'';
            }
        }else{
            return str_replace('=','',base64_encode($result));
        }
    }

}
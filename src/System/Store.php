<?php

/**
 * @name 存储管理
 * @author devkeep <devkeep@skeep.cc>
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/aiqq363927173/Tools
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\System;

// use Vipkwd\Utils\Libs\Cookie;
// use Vipkwd\Utils\Libs\Session;
use Vipkwd\Utils\Type\Str as VipkwdStr;

class Store{
    /**
     * session管理函数
     * 
     * $name 支持“.”号深度访问 如：“user.id”
     * $name = '?user.id' 检测数组“user”是否存在“id”键
     * $name === null, 删除SESSION
     * $name == false 返回全局SESSION
     * 要设置$name等于Null，请使用 null 而非 "null"
     *
     * -e.g: $store=[ 'user' => 'admin', 'amount' => [ 'balance' => 1000, 'alipay' => 120, ] ]
     * -e.g: phpunit('system.store::session', ['info', $store]);
     * -e.g: phpunit('system.store::session', ['info']);
     * 
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    static function session($name = "", $value = "#null@"){
        if (!isset($_SESSION)) {
            @session_start(); // 自动启动会话
        }    
        if($name === null){
            $_SESSION = [];
            return true;
        }
        $name = trim(str_replace(' ','',$name),".");

        if(!$name){
            return $_SESSION;
        }

        $checkHasKey = false;
        if( substr($name, 0, 1) == "?" ){
            $checkHasKey = true;
            $name = substr($name,1);
        }else{
            //设置
            if( $value != "#null@"){
                if($value === null){
                    if(!isset($_SESSION[$name])){
                        return false;
                    }
                    unset($_SESSION[$name]);
                }else{
                    $_SESSION[$name] = $value;
                }
                return true;
            }
        }

        //获取
        $sess = $_SESSION;
        $names = explode('.', $name);
        foreach($names as $sk){
            if(!is_array($sess) || !isset($sess[$sk])){
                $sess = $checkHasKey ? false : null;
                break;
            }
            $sess = $sess[$sk];
            unset($sk);
        }
        unset($names);
        return $sess;
    }

    /**
     * Cookie管理
     * 
     * -e.g: $store=[ 'user' => 'admin', 'amount' => [ 'balance' => 1000, 'alipay' => 120] ];
     * -e.g: phpunit('system.Store::cookie', ['info', $store]);
     * -e.g: phpunit('system.Store::cookie', ['info']);
     * -e.g: phpunit('system.Store::cookie', ['info', null]);
     * -e.g: phpunit('system.Store::cookie', ['info', null, 0]);
     * -e.g: phpunit('system.Store::cookie', ['info']);
     * 
     * @param string $name   cookie名称
     * @param mixed  $value  cookie值
     * @param int  $expire 有效期 （小于0：删除cookie, 大于0：设置cookie）
     * @return boolean|array|null|string
     */
    static function cookie(string $name = null, $value = null, int $expire = 0, $path = null, $domain = null, $secure = null, $httponly = false){
        $name && $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        $defaults = [
            // cookie 保存时间
            'expire'   => 86400 * 7,
            // cookie 保存路径
            'path'     => $path ?: '/',
            // cookie 有效域名
            'domain'   => $domain ?: null,
            //  cookie 启用安全传输
            'secure'   => $secure ? true : false,
            // httponly设置
            'httponly' => $httponly ? true : true,
            // samesite 设置，支持 'strict' 'lax'
            'samesite' => '',
        ];
        
        if( $name && is_null($value) && $expire < 0){
            //删除
            return self::saveCookie(
                $name,
                "",
                -1,
                $defaults['path'],
                $defaults['domain'],
                $defaults['secure'],
                $defaults['httponly'],
                $defaults['samesite']
            );
        }else if($name && !is_null($value) ){

            //失效时间兼容处理
            if($expire > 0){

                //整数 补时间戳
                $defaults['expire'] = ( $expire - time() >= 0) ? ($expire - time()) : $expire;
            }
            //设置
            return self::saveCookie(
                $name,
                is_array($value) ? 'base:'. base64_encode(json_encode($value)) : "$value",
                $defaults['expire'] + time(),
                $defaults['path'],
                $defaults['domain'],
                $defaults['secure'],
                $defaults['httponly'],
                $defaults['samesite']
            );
        }
        if($name){
            if(isset($_COOKIE[$name])){
                $data = $_COOKIE[$name];

                if(substr($data, 0, 5) == 'base:'){
                    $data = base64_decode(substr($data, 5));
                }

                if(VipkwdStr::isJson($data)){
                    return json_decode($data,true);
                }
                return $data;
            }
            return null;
        }
        return $_COOKIE;
    }

    /**
     * 保存Cookie
     * @access public
     * @param  string $name cookie名称
     * @param  string $value cookie值
     * @param  int    $expire cookie过期时间
     * @param  string $path 有效的服务器路径
     * @param  string $domain 有效域名/子域名
     * @param  bool   $secure 是否仅仅通过HTTPS
     * @param  bool   $httponly 仅可通过HTTP访问
     * @param  string $samesite 防止CSRF攻击和用户追踪
     * @return void
     */
    private static function saveCookie(string $name, string $value, int $expire, string $path, string $domain, bool $secure, bool $httponly, string $samesite){
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            return setcookie($name, $value, [
                'expires'  => $expire,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ]);
        } else {
            return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }
}

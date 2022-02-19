<?php

/**
 * @name 常用工具集合
 * @author devkeep <devkeep@skeep.cc>
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/aiqq363927173/Tools
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

// use Vipkwd\Utils\Libs\Cookie;
// use Vipkwd\Utils\Libs\Session;
use Vipkwd\Utils\Str as VipkwdStr,
    \Exception,
    \Closure;

class Store{
    /**
     * session管理函数
     * 
     * $key 支持“.”号深度访问 如："user.id"
     * $key = null, 删除SESSION
     * $key = "" 返回全局SESSION
     * 要设置$key等于Null，请使用 null 而非 "null"
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    static function session($key = "", $value = "#null@"){
        if($key === null){
            $_SESSION = [];
            return true;
        }
        if(!$key){
            return $_SESSION;
        }
        $key = trim(str_replace(' ','',$key),".");

        $exists = false;
        if( substr($key,0,1) == "?" ){
            $exists = true;
            $key = substr($key,1); 
        }else{
            //设置
            if( $key != "" && $value != "#null@"){
                if($value === null){
                    if(!isset($_SESSION[$key])){
                        return false;
                    }
                    unset($_SESSION[$key]);
                }else{
                    $_SESSION[$key] = $value;
                }
                return true;
            }
        }

        //获取
        $sess = $_SESSION;
        $keys = $key != "" ? explode('.', $key ) : [];
        foreach($keys as $sk){
            if(!is_array($sess) || !isset($sess[$sk])){
                $sess = null;
                break;
            }
            $sess = $sess[$sk];
            unset($sk);
        }
        unset($keys);
        return $sess;
    }

    /**
     * Cookie管理
     * 
     * @param string $name   cookie名称
     * @param mixed  $value  cookie值
     * @param int  $expires 有效期 （小于0：删除cookie, 大于0：设置cookie）
     * @return mixed
     */
    static function cookie(string $name = null, $value = null, int $expires = 0){
        $name && $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        $defaults = [
            // cookie 保存时间
            'expires'   => 86400 * 7,
            // cookie 保存路径
            'path'     => '/',
            // cookie 有效域名
            'domain'   => '',
            //  cookie 启用安全传输
            'secure'   => false,
            // httponly设置
            'httponly' => true,
            // samesite 设置，支持 'strict' 'lax'
            'samesite' => '',
        ];

        if($name && is_null($value) && $expires < 0 ){
            //删除
            return self::saveCookie(
                $name,
                "",
                time() - 86400,
                $defaults['path'],
                $defaults['domain'],
                $defaults['secure'],
                $defaults['httponly'],
                $defaults['samesite']
            );
        }else if($name && !is_null($value) ){
            //设置
            return self::saveCookie(
                $name,
                is_array($value) ? json_encode($value) : "$value",
                $expires > 0 ? $expires : $defaults['expires'] + time(),
                $defaults['path'],
                $defaults['domain'],
                $defaults['secure'],
                $defaults['httponly'],
                $defaults['samesite']
            );
        }
        if($name){
            if(isset($_COOKIE[$name])){
                if(VipkwdStr::isJson($_COOKIE[$name])){
                    return json_decode($_COOKIE[$name],true);
                }
                return $_COOKIE[$name];
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

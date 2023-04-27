<?php

/**
 * @name Cookie管理
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\System;

use Vipkwd\Utils\Type\Str as VipkwdStr;

class Cookie
{
    protected static $_defaults = [
        // cookie 保存时间
        'expire'   => 86400,
        // cookie 保存路径
        'path'     => '/',
        // cookie 有效域名
        'domain'   => null,
        //  cookie 启用安全传输
        'secure'   => false,
        // httponly设置
        'httponly' => false,
        // samesite 设置，支持 'strict' 'lax'
        'samesite' => '',
    ];

    /**
     * 获取cookie
     * 
     * @param string $name
     * @param mixed|null $default
     * 
     * @return string|array|mixed
     */
    static function get(string $name = null, $default = null)
    {
        if (!$name) {
            return isset($_COOKIE) ? $_COOKIE : null;
        }
        $name && $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;

        if (isset($_COOKIE[$name])) {
            $data = $_COOKIE[$name];

            if (substr($data, 0, 5) == 'bs64:') {
                $data = base64_decode(substr($data, 5));
            }
            if (VipkwdStr::isJson($data)) {
                return json_decode($data, true);
            }
            return $data;
        }
        return $default;
    }


    /**
     * 设置COOKIE
     * 
     * @param string $name
     * @param string|array $value
     * @param int|null $expire
     * @param string|null $path
     * @param string|null $domain
     * @param boolean $secure
     * @param boolean $httponly
     * @param string $samesite - 支持 'strict' 'lax'
     * 
     * @return boolean
     */
    static function set(string $name, $value, ?int $expire = null, $path = null, $domain = null, bool $secure = false, bool $httponly = false, string $samesite = ''): bool
    {
        if (!$name) return false;
        //设置
        return self::_saveCookie(
            $name,
            is_array($value) ? 'bs64:' . base64_encode(json_encode($value)) : "$value",
            is_null($expire) ? self::$_defaults['expire'] : time() + $expire,
            $path,
            $domain,
            $secure,
            $httponly,
            $samesite
        );
    }

    /**
     * 删除指定COOKIE
     * 
     * @param string $name
     * 
     * @return boolean
     */
    static function delete(string $name): bool
    {
        if (!$name) return false;
        if ($_COOKIE[$name]) unset($_COOKIE[$name]);
        return self::_saveCookie($name, "", -10);
    }

    /**
     * 删除服务端全部COOKIE
     * 
     * @return true
     */
    static function clearAll(): bool
    {
        if (isset($_COOKIE)) {
            unset($_COOKIE);
        }
        return true;
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
     * @return bool
     */
    private static function _saveCookie(string $name, string $value, int $expire, ?string $path = null, ?string $domain = null, bool $secure = false, bool $httponly = false, string $samesite = ''): bool
    {
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            return setcookie($name, $value, [
                'expires'  => $expire,
                'path'     => $path ?: self::$_defaults['path'],
                'domain'   => $domain ?: self::$_defaults['domain'],
                'secure'   => $secure ?: self::$_defaults['secure'],
                'httponly' => $httponly ?: self::$_defaults['httponly'],
                'samesite' => $samesite ?: self::$_defaults['samesite'],
            ]);
        } else {
            return setcookie(
                $name,
                $value,
                $expire,
                $path ?: self::$_defaults['path'],
                $domain ?: self::$_defaults['domain'],
                $secure ?: self::$_defaults['secure'],
                $httponly ?: self::$_defaults['httponly']
            );
        }
    }
}

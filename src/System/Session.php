<?php

/**
 * @name SESSION管理
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\System;

use \Closure;

class Session
{
    protected static $_start = false;

    private function __construct()
    {
        $this->setGcMaxLifetime(21600);
        $this->start();
        return true;
    }

    /**
     * 实例化
     * 
     */
    static function instance(): self
    {
        return new self;
    }

    /**
     * 启动session
     * 
     * @param string $sessionId 手动指定sessionId
     * 
     * @return true
     */
    static function start(string $sessionId = null): bool
    {
        if (self::$_start === true) return true;
        if (!isset($_SESSION)) {
            if ($sessionId) {
                self::id($sessionId);
            } else if (isset($_POST['session_id']) && ($sessionId = trim($_POST['session_id']))) {
                self::id($sessionId);
            }
            session_start();
        }
        header("Cache-control:private");
        self::$_start = true;
        return true;
    }

    /**
     * 设置SESSION
     * 支持"."号深度操作
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return true
     */
    static function set($key, $value = null): bool
    {
        $keys = self::__getKeys($key);
        if (!$key) return false;
        if (self::$_start === false) self::start();

        $__keys = [] + $keys;
        array_shift($__keys);
        krsort($__keys);
        $data = self::get(implode('.', $__keys), []);
        unset($__keys);

        $lastKeyIndex = array_key_last($keys);
        foreach ($keys as $i => $_key) {
            if ($_key == $keys[$lastKeyIndex]) {
                $_SESSION[$_key] = $i === 0 ? $value : $data;
                break;
            }
            if ($i === 0) {
                $data[$_key] = $value;
            } else {
                $data = [$_key => $data];
            }
            ksort($data);
        }
        unset($keys, $key, $data);
        return true;
    }

    /**
     * 获取指定key或全部SESSION
     * 支持"."号深度访问
     */
    static function get(?string $key = null, $default = null)
    {
        $keys = self::__getKeys($key, false);
        if ($key === null) return isset($_SESSION) ? $_SESSION : null;
        if (!$key) return null;
        if (self::$_start === false) self::start();
        $data = $_SESSION;
        foreach ($keys as $_key) {
            if (is_array($data) && isset($data[$_key])) {
                $data = $data[$_key];
            } else {
                $data = $default;
                break;
            }
        }
        unset($key, $keys, $default);
        return $data;
    }

    /**
     * 删除SESSION键
     * 
     * 支持"."号深度删除
     * 
     * @return true
     */
    static function delete(string $key): bool
    {
        $keys = self::__getKeys($key, false);
        if (!$key || count($keys) == 0) return false;
        if (count($keys) == 1) {
            if (!isset($_SESSION[$key])) return false;
            unset($_SESSION[$key]);
            return true;
        }
        $map = [];
        $data = $_SESSION ? $_SESSION : [];

        $lastKeyIndex = array_key_last($keys);

        array_key_first($keys);
        foreach ($keys as $i => $_key) {
            if (is_array($data) && isset($data[$_key])) {
                if ($_key == $keys[$lastKeyIndex]) {
                    unset($data[$_key]);
                }
            }
            $data = $data[$_key] ?? null;
            $map[$i] = [
                "k" => $_key,
                "v" => $data
            ];
        }
        krsort($map);
        $map = array_values($map);

        $fullData = [];
        foreach ($map as $i => &$node) {
            if ($i > 0) {
                $last = $map[$i - 1];
                if ($i === 1) {
                    unset($node['v'][$last['k']]);
                } else {
                    $node['v'][$last['k']] = $last['v'];
                }
                $fullData[$i] = $node;
                unset($last, $node);
                continue;
            }
        }
        $fullDataInedx = array_key_last($fullData);
        self::set($fullData[$fullDataInedx]['k'], $fullData[$fullDataInedx]['v']);
        unset($fullData, $map, $data, $lastKeyIndex, $fullDataInedx);
        return true;
    }

    /**
     * 清空/SESSION=[]
     * 
     * @return true
     */
    static function clearAll(): bool
    {
        $_SESSION = array();
        return true;
    }

    /**
     * 销毁/unset SESSION
     * 
     * @return true
     */
    static function destory(): bool
    {
        if (self::$_start === true) {
            unset($_SESSION);
            session_destroy();
        }
        return true;
    }

    /**
     * 暂停Session
     * 
     * @return true
     */
    static function pause(): bool
    {
        if (self::$_start === true) {
            session_write_close();
        }
        return true;
    }

    /**
     * 设置或者获取当前Session名
     * 
     * @param string|null $name session名称
     * 
     * @return string 返回之前的Session name
     */
    static function name(?string $name = null)
    {
        return isset($name) ? session_name($name) : session_name();
    }

    /**
     * 设置或者获取当前SessionID
     * 
     * @param string $id sessionID
     * 
     * @return string|null 返回sessionID
     */
    static function id($id = null): ?string
    {
        if (isset($id)) {
            return session_id($id);
        }

        if (session_id() != '') {
            return session_id();
        }
        if (self::useCookies()) {
            if (isset($_COOKIE[self::name()])) {
                return $_COOKIE[self::name()];
            }
        } else {
            if (isset($_GET[self::name()])) {
                return $_GET[self::name()];
            }
            if (isset($_POST[self::name()])) {
                return $_POST[self::name()];
            }
        }
        return null;
    }

    /**
     * 设置或者获取当前Session保存路径
     * 
     * @param string|null $path 保存路径名
     * 
     * @return string
     */
    static function path(?string $path = null)
    {
        return !empty($path) ? session_save_path($path) : session_save_path();
    }

    /**
     * 设置Session是否使用cookie
     * 
     * @param boolean $useCookies  是否使用cookie
     * 
     * @return boolean 返回之前设置状态
     */
    static function useCookies(?bool $useCookies = null): bool
    {
        $return = ini_get('session.use_cookies') ? true : false;
        if (isset($useCookies)) {
            ini_set('session.use_cookies', $useCookies ? 1 : 0);
        }
        return $return;
    }

    /**
     * 设置Session对象反序列化时候的回调函数
     * 
     * @param string $callback  回调函数方法名
     * 
     * @return boolean 返回之前设置状态
     */
    static function setCallback(Closure $callback = null): bool
    {
        $return = ini_get('unserialize_callback_func');
        if (!empty($callback)) {
            ini_set('unserialize_callback_func', $callback);
        }
        return $return;
    }

    /**
     * 检查Session值是否已经设置
     * 
     * @param string $name
     * 
     * @return boolean
     */
    static function exists(string $name): bool
    {
        $uniqid = md5(microtime() . '#_v.i.p.k.w.d_%' . pow(mt_rand(1000, 9000), 3));
        return $uniqid != self::get($name, $uniqid);
    }

    /**
     * 设置Session生命周期值
     * 
     * @param string $gc_maxlifetime
     * 
     * @return string 返回之前设置
     */
    static function setGcMaxLifetime(?int $gcMaxLifetime = null)
    {
        $return = ini_get('session.gc_maxlifetime');
        if (!isset($_SESSION)) {
            if (isset($gcMaxLifetime) && is_int($gcMaxLifetime) && $gcMaxLifetime >= 1) {
                ini_set('session.gc_maxlifetime', "$gcMaxLifetime");
            }
        }
        return $return;
    }

    public function __destruct()
    {
        $this->pause();
        return true;
    }

    /**
     * 解析Key键深度
     */
    private static function __getKeys(&$key, $krsort = true)
    {
        if ($key === null) {
            return [];
        }
        if (is_numeric($key) && intval($key) == $key) {
            return [$key];
        }
        $key = preg_replace("/\.+/", '.', trim(trim("$key"), '.'));
        $key = preg_replace("/\ +/", '', $key);
        $keys = explode('.', $key);
        $krsort && krsort($keys);
        return array_values($keys);
    }
}

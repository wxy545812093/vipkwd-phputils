<?php
/**
 * @name 日期操作
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);
namespace Vipkwd\Utils;

class Date{

    /**
	 * 转时间戳为xxx秒前
	 * 
     * -e.g: $timestamp = strtotime("-1 days");
     * -e.g: phpunit("Date::toBeforeString", [$timestamp]);
     * -e.g: $timestamp = strtotime("-35 days");
     * -e.g: phpunit("Date::toBeforeString", [$timestamp]);
     * 
     * @param int $timestamp
     * @return string
     */
    static function toBeforeString(int $timestamp): string {
        $now = time();
        $diff = $now - $timestamp;
        if ($diff <= 60) {
            return $diff . '秒前';
        } elseif ($diff <= 3600) {
            return floor($diff / 60) . '分钟前';
        } elseif ($diff <= 86400) {
            return floor($diff / 3600) . '小时前';
        } elseif ($diff <= 2592000) {
            return floor($diff / 86400) . '天前';
        } else {
            return '一个月前';
        }
    }

    /**
     * 获取随机的时间
     * 
     * -e.g: phpunit("Date::randomDate");
     * -e.g: phpunit("Date::randomDate", ["Y-m-d H:i:s"]);
     * -e.g: phpunit("Date::randomDate", ["Y-m-d H:i"]);
     * -e.g: phpunit("Date::randomDate", ["Y/m/d H:i:s"]);
     * 
     * @param string $format PHP的时间日期格式化字符
     * @return false|string
     */
    static function randomDate(string $format = 'Y-m-d H:i:s'): string {
        return Random::randomDate($format);
    }
}
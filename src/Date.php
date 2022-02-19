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

    private static $timeZone = 'PRC';
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
        return Random::date($format);
    }

    /**
     * 比较俩日期天数差
     *
     * 日期格式错误返回 false;
     * 
     * -e.g: phpunit("Date::diff", ["2018-12-01","2018-11-05"]);
     * -e.g: phpunit("Date::diff", ["2018-11-31","2018-11-05"]);echo " <-- 溢出日期自动修复";
     * -e.g: phpunit("Date::diff", ["2018-12-00","2018-11-05"]);echo " <-- 溢出日期自动修复";
     * -e.g: phpunit("Date::diff", ["2018-11-30","2018-11-05"]);
     * -e.g: phpunit("Date::diff", ["2018-11-05","2018-11-05"]);
     * -e.g: phpunit("Date::diff", ["2018-10-31","2018-11-05"]);
     * 
     * @param string $date1 Ymd格式 ，xxxx-xx-xx 或不带分隔符的 xxxxxxxx
     * @param string $date2 Ymd格式 ，xxxx-xx-xx 或不带分隔符的 xxxxxxxx
     * @return bool|int
     */
    static function diff(string $date1, string $date2){
        $date1 = self::cleanInvalidChar($date1);
        $date2 = self::cleanInvalidChar($date2);
        try{
            return (1 * self::dateTimeInstance($date2)->diff(self::dateTimeInstance($date1))->format('%r%a'));
        }catch(\Exception $e){
            return false;
        }
    }

    /**
     * 清理日期中的无效字符
     *
     * @param string $date
     * @return string
     */
    static function cleanInvalidChar(string $date):string{
        $date = preg_replace("/[^0-9]/",'',$date);
        if(strlen($date) != 8){
            return $date;
        }
        $chars = str_split($date,2);
        return implode( '-', [
            $chars[0]. $chars[1],
            $chars[2],
            $chars[3]
        ]);
    }

    /**
     * 检测是否为合法日期
     *
     * @param string $date
     * @return boolean
     */
    static function isRealDate(string &$date):bool{
        $date = self::cleanInvalidChar($date);
        try{
            $date = self::dateTimeInstance($date)->format('Y-m-d');
            return true;
        }catch(\Exception $e){
            return false;
        }
    }

    private static function dateTimeInstance($date){
        return new \DateTime($date, new \DateTimeZone(self::$timeZone));
    }
}
<?php
/**
 * @name 日期时间操作
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);
namespace Vipkwd\Utils;

class DateTime {

    private static $timezone = 'PRC';
    /**
	 * 转时间戳为xxx秒前
	 * 
     * -e.g: phpunit("DateTime::toAgo", ["-100 seconds"]);
     * -e.g: phpunit("DateTime::toAgo", ["-100 hours"]);
     * -e.g: phpunit("DateTime::toAgo", ["+100 hours"]);
     * -e.g: phpunit("DateTime::toAgo", ["35 days"]);
     * -e.g: phpunit("DateTime::toAgo", ["-35 days"]);
     * 
     * @param int $timestamp
     * @return string
     */
    static function toAgo($time): string {
        $time = self::getMaxTimestamp($time);
        $now = time();
        $diff = $now - $time;
        $suffix =  $diff > 0 ? "前" : "后";
        $diff=abs($diff);
        if($diff == 0){
            return "刚刚";
        }else if ($diff <= 60) {
            return $diff . '秒' .$suffix;
        } elseif ($diff <= 3600) {
            return floor($diff / 60) . '分钟' .$suffix;
        } elseif ($diff <= 86400) {
            $diff = floor($diff / 3600);
            if( ($t = bcdiv("$diff", "24", 0)) >= 1 ){
                return $t . '天' .$suffix;
            }
            return $diff . '小时' .$suffix;
        } elseif ($diff <= 2592000) {
            return floor($diff / 86400) . '天' .$suffix;
        } else {
            return '1月' .$suffix;
        }
    }

    /**
     * 返回时间
     *
     * -e.g: phpunit("DateTime::time");
     * -e.g: phpunit("DateTime::time",["-1 hours"]);
     * 
     * @param string $format
     * @param string $max
     * @return string
     */
    static function time(string $max = 'now'):string{
        return self::dateTimeInstance($max)->format("H:i:s");
    }

    /**
     * 月份英文表示
     *
     * -e.g: phpunit("DateTime::monthName");
     * 
     * @param string $date
     * @return string
     */
    static function monthName(string $date = "now"):string{
        return self::dateTimeInstance($date)->format('F');
    }

    /**
     * 日期
     *
     * -e.g: phpunit("DateTime::day");
     * 
     * @param string $max
     * @return string
     */
    static function day($max = 'now'):string {
        return self::dateTimeInstance($max)->format('d');
    }

    /**
     * 通用格式化接口
     *
     * -e.g: phpunit("DateTime::date");
     * 
     * @param string $format <'Y-m-d'>
     * @param string $max
     * @return string
     */
    static function date($format = 'Y-m-d', $max = 'now'):string{
        return self::dateTimeInstance($max)->format($format);
    }

    /**
     * 月份数字 "xx"
     *
     * -e.g: phpunit("DateTime::month");
     * 
     * @param string $max
     * @return string
     */
    static function month($max = 'now'):string{
        return self::dateTimeInstance($max)->format('m');
    }

    /**
     * 年份数字 "xxxx"
     *
     * -e.g: phpunit("DateTime::year");
     * 
     * @param string $date
     * @return string
     */
    static function year(string $date = "now"):string{
        return self::dateTimeInstance($date)->format('Y');
    }

    /**
     * 星期
     *
     * -e.g: phpunit("DateTime::week");
     * 
     * @param string $max
     * @return string
     */
    static function week($max = 'now'):string{
        return self::dateTimeInstance($max)->format('l');
    }

    /**
     * 获取随机 年-月-日
     * 
     * -e.g: phpunit("DateTime::randomDate");
     * -e.g: phpunit("DateTime::randomDate", ["H:i:s"]);
     * -e.g: phpunit("DateTime::randomDate", ["Y-m-d H:i"]);
     * -e.g: phpunit("DateTime::randomDate", ["Y/m/d H:i:s"]);
     * 
     * @param string $format PHP的时间日期格式化字符
     * @param integer $ago <"-50"> 过去50年里随机取一个(单位/时间)
     * @return false|string
     */
    static function randomDate(string $format = 'Y-m-d', int $ago = -50): string {
        $ago > 0 && $ago *= -1;
        return self::dateTimeBetween( $ago." years")->format($format);
    }

    /**
     * 比较俩日期天数差
     *
     * 日期格式错误返回 false;
     * 
     * -e.g: phpunit("DateTime::diff", ["2018-12-01","2018-11-05"]);
     * -e.g: phpunit("DateTime::diff", ["2018-11-31","2018-11-05"]); // <-- 溢出日期自动修复;
     * -e.g: phpunit("DateTime::diff", ["2018-12-00","2018-11-05"]); // <-- 溢出日期自动修复;
     * -e.g: phpunit("DateTime::diff", ["2018-11-30","2018-11-05"]);
     * -e.g: phpunit("DateTime::diff", ["2018-11-05","2018-11-05"]);
     * -e.g: phpunit("DateTime::diff", ["2018-10-31","2018-11-05"]);
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
     * -e.g: phpunit("DateTime::cleanInvalidChar", ["2001-22-03"]);
     * -e.g: phpunit("DateTime::cleanInvalidChar", ["2001-12-33"]);
     * -e.g: phpunit("DateTime::cleanInvalidChar", ["2001/12/33"]);
     * -e.g: phpunit("DateTime::cleanInvalidChar", ["2001年12月33日"]);
     * -e.g: phpunit("DateTime::cleanInvalidChar", ["2001年2月3日"]);
     * -e.g: phpunit("DateTime::cleanInvalidChar", ["2001年12月3日"]);
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
     * -e.g: phpunit("DateTime::isDate", ["+1 days"]);
     * -e.g: phpunit("DateTime::isDate", ["now"]);
     * -e.g: phpunit("DateTime::isDate", ["2002"]);
     * -e.g: phpunit("DateTime::isDate", ["20010203"]);
     * -e.g: phpunit("DateTime::isDate", ["2001-02-03"]);
     * -e.g: phpunit("DateTime::isDate", ["2001-22-03"]);
     * -e.g: phpunit("DateTime::isDate", ["2001-12-33"]);
     * 
     * @param string $date
     * @return boolean|string
     */
    static function isDate(string $date){
        $date = self::cleanInvalidChar($date);
        try{
           $date = (new \DateTime($date, new \DateTimeZone(self::$timezone)))->format('Y-m-d');
            return $date;
        }catch(\Exception $e){
            return false;
        }
    }

    /**
     * 日期返回 "1994-02-26T00:00:00+08:00"
     *
     * -e.g: phpunit("DateTime::iso8601",["2022-02-23"]);
     * 
     * @param string $date
     * @return string
     */
    static function iso8601(string $date):string{
        // \DateTime::ISO8601 -> 'c'
        return self::date(\DateTime::ISO8601, $date);
    }

    /**
     * 区间内随机时间转DateTime对象
     *
     * -e.g: phpunit("DateTime::dateTimeBetween");
     * -e.g: phpunit("DateTime::dateTimeBetween",["-2 months"]);
     * -e.g: phpunit("DateTime::dateTimeBetween",["-2 months", "-1 months"]);
     * 
     * @param string $startDate <'-30 years'> 
     * @param string $endDate <'now'>
     * 
     * @return \DateTime
     */
    static function dateTimeBetween(string $startDate = '-30 years', $endDate = 'now'):\DateTime{
        $startTimestamp = strtotime($startDate);
        $endTimestamp = self::getMaxTimestamp($endDate);

        if ($startTimestamp > $endTimestamp) {
            throw new \InvalidArgumentException('Start date must be anterior to end date.');
        }
        $timestamp = mt_rand($startTimestamp, $endTimestamp);

        return self::dateTimeInstance($timestamp);//->format('Y-m-d H:i:s');
    }

    /**
     * 时间转 DateTime对象
     *
     * -e.g: phpunit("DateTime::dateTimeInstance");
     * -e.g: phpunit("DateTime::dateTimeInstance",["-2 months"]);
     * -e.g: phpunit("DateTime::dateTimeInstance",["-2 months", 'Asia/Seoul' ]);
     * 
     * @param string $max
     * @param string|null $timezone
     * @return \DateTime
     */
    static function dateTimeInstance($max = 'now',?string $timezone = null):\DateTime{
        return new \DateTime(
            date('Y-m-d H:i:s', static::getMaxTimestamp($max)),
            new \DateTimeZone( $timezone ?? self::$timezone)
        );
    }

    private static function unixTime($max = 'now'):int{
        return mt_rand(0, static::getMaxTimestamp($max));
    }

    private static function getMaxTimestamp($max = 'now'){
        if (is_numeric($max)) {
            return (int) $max;
        }
        if ($max instanceof \DateTime) {
            return $max->getTimestamp();
        }
        return strtotime(empty($max) ? 'now' : $max);
    }
}
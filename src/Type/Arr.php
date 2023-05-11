<?php

/**
 * @name 数组操作
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Type;

use \Exception;

final class Arr
{

    use TraitName;
    /**
     * 是否为关联数组
     *
     * -e.g: $arr = [];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::isAssoc", [$arr]);
     * -e.g: $arr = [array()];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::isAssoc", [$arr]);
     *
     * @param array $array 数组
     * @return bool
     */
    static function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * 排列组合（适用多规格SKU生成）
     *
     * -e.g: $input=array();
     * -e.g: $input[]=[["id" => 1, "name" => "红色"], ["id" => 2, "name" => "黑色"], ["id" => 3, "name" => "蓝色"]];
     * -e.g: $input[]=[["id" => 4, "name" => "32G"], ["id" => 5, "name" => "64G"],];
     * -e.g: phpunit("Type\Arr::arrayToSku",[$input]);
     * 
     * @param array $input 排列的数组
     *
     * @return array
     */
    static function arrayArrRange(array $input): array
    {
        $temp = [];
        $result = array_shift($input);
        while ($item = array_shift($input)) {
            $temp = $result;
            $result = [];
            foreach ($temp as $v) {
                foreach ($item as $val) {
                    $result[] = array_merge_recursive($v, $val);
                }
            }
        }
        return $result;
    }

    /**
     * 数组转多规格SKU（arrayArrRange别名）
     *
     * -e.g: $input=array();
     * -e.g: $input[]=[["id" => 1, "name" => "红色"], ["id" => 2, "name" => "黑色"], ["id" => 3, "name" => "蓝色"]];
     * -e.g: $input[]=[["id" => 4, "name" => "32G"], ["id" => 5, "name" => "64G"],];
     * -e.g: phpunit("Type\Arr::arrayToSku",[$input]);
     *
     * @param array $input 排列的数组
     *
     * @return array
     */
    static function arrayToSku(array $input): array
    {
        return Arr::arrayArrRange($input);
    }

    /**
     * 判断数组中指定键是否为数组
     *
     * -e.g: echo '$arr=["id"=>134,"mobile"=>["131xxxx","132xxx"]]';
     * -e.g: $arr=["id"=>134,"mobile"=>["131xxxx","132xxx"]];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::hasKey", [$arr,"id"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::hasKey", [$arr,"mobile"]);
     *
     * @param string $field
     * @param array $array
     * @return boolean
     */
    static function hasKey(string $field, array $array): bool
    {
        if (!is_array($array)) {
            return false;
        }
        return array_key_exists($field, $array);
    }

    /**
     * 不区分大小写的in_array实现
     *
     * -e.g: $arr = ["A","b","as"];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::hasVal", ["a", $arr]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::hasVal", ["B", $arr]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::hasVal", ["s", $arr]);
     *
     * @param mixed $val
     * @param array $array
     * @param boolean $strict <true> 是否严格比较(===)
     * @return bool
     */
    static function hasVal($val, $array, $strict = true)
    {
        return $strict
            ? in_array($val, $array, true)
            : in_array(strtolower($val), array_map('strtolower', $array));
    }

    /**
     * hasVal方法的别名
     *
     * -e.g: $arr = ["A","b","as"];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::inArray", ["a", $arr]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::inArray", ["B", $arr]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::inArray", ["s", $arr]);
     *
     * @param mixed $val
     * @param array $array
     * @return void
     */
    static function inArray($val, $array)
    {
        return self::hasVal($val, $array, true);
    }

    /**
     * 读取数组(支持深度读取)
     *
     * -e.g: $arr = ["a"=>123, "b" => ["c"=>200, "d"=> ["e"=>300]]];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::get", [$arr, "a"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::get", [$arr, "b.d.e.not-exists-return-null"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::get", [$arr, "b.d.e.not-exists", "Undefined"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::get", [$arr, "b.d.e"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::get", [$arr, "b.c"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::get", [$arr, "b"]);
     *
     * @param array $array
     * @param string $key   简单模式: "province" 深度模式: "province.city.county.town.street"
     * @param mixed $default <null>
     * @return mixed
     */
    static function get(array $array, string $key, $default = null)
    {
        $key = explode(".", str_replace([' '], [''], trim($key, '.')));
        foreach ($key as $k) {
            if (is_array($array) && self::hasKey($k, $array)) {
                $array = $array[$k];
            } else {
                if (func_num_args() < 3) {
                    // throw new \Exception("Missing item '$k'.");
                }
                return $default;
            }
        }
        return $array;
    }

    /**
     * 检测数组各元素是否全部通过回调验证
     *
     * -e.g: $arr = [1, 30, 39, 29, 10, 13];
     * -e.g: $fnWithBoolean = function($val):bool{ return $val < 40; };
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::every", [$arr, $fnWithBoolean]);
     *
     * @param iterable $array
     * @param callable $callback
     * @return boolean
     */
    static function every(iterable $array, callable $callback): bool
    {
        foreach ($array as $k => $v) {
            if (!$callback($v, $k, $array)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 检测数组中至少一个元素通过回调验证
     *
     * -e.g: $fnWithBoolean = function($val):bool{ return $val < 5; };
     * -e.g: $arr = [1, 30, 39, 29, 10, 13];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::some", [$arr, $fnWithBoolean]);
     * -e.g:
     * -e.g: $arr = [10, 30, 39, 29, 10, 13];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::some", [$arr, $fnWithBoolean]);
     *
     * @param iterable $array
     * @param callable $callback
     * @return boolean
     */
    static function some(iterable $array, callable $callback): bool
    {
        foreach ($array as $k => $v) {
            if ($callback($v, $k, $array)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 将新数组(键值对)插入到数组指定Key后面
     *
     * -e.g: $arr1 = ["a"=>10, "b"=>20, "number"];
     * -e.g: $arr2 = ["a"=>10, "b"=>20, "number"];
     * -e.g: $arr3 = ["a"=>10, "b"=>20, "number"];
     *
     * -e.g: $inserted = ["c"=>999999,"d"=>888888];
     * -e.g: echo '\Vipkwd\Utils\Type\Arr::insertAfter($arr1, "b", $inserted); // '; \Vipkwd\Utils\Type\Arr::insertAfter($arr1, "b", $inserted);
     * -e.g: \Vipkwd\Utils\Dev::dump($arr1);
     *
     * -e.g: echo '\Vipkwd\Utils\Type\Arr::insertAfter($arr2, "a", $inserted); // '; \Vipkwd\Utils\Type\Arr::insertAfter($arr2, "a", $inserted);
     * -e.g: \Vipkwd\Utils\Dev::dump($arr2);
     *
     * -e.g: echo '\Vipkwd\Utils\Type\Arr::insertAfter($arr3, 0, $inserted); // '; \Vipkwd\Utils\Type\Arr::insertAfter($arr3, 0, $inserted);
     * -e.g: \Vipkwd\Utils\Dev::dump($arr3);
     *
     * @param array $array
     * @param string $key
     * @param array $inserted 新数组(键值对)
     * @return void
     */
    static function insertAfter(array &$array, $key, array $inserted)
    {
        if ($key === null || ($offset = self::getKeyOffset($array, $key)) === null) {
            $offset = count($array) - 1;
        }
        $array = array_slice($array, 0, $offset + 1, true)
            + $inserted
            + array_slice($array, $offset + 1, count($array), true);
    }

    /**
     * 将新数组(键值对)插入到数组指定Key前面
     *
     * -e.g: $arr = ["a"=>10, "b"=>20];
     * -e.g: $inserted = ["c"=>2.001,"d"=>2.40];
     * -e.g: echo '\Vipkwd\Utils\Type\Arr::insertBefore($arr, "b", $inserted); // '; \Vipkwd\Utils\Type\Arr::insertBefore($arr, "b", $inserted);
     * -e.g: \Vipkwd\Utils\Dev::dump($arr);
     *
     * -e.g: echo '\Vipkwd\Utils\Type\Arr::insertBefore($arr, "a", $inserted); // '; \Vipkwd\Utils\Type\Arr::insertBefore($arr, "a", $inserted);
     * -e.g: \Vipkwd\Utils\Dev::dump($arr);
     *
     * @param array $array
     * @param string $key
     * @param array $inserted 新数组(键值对)
     * @return void
     */
    static function insertBefore(array &$array, $key, array $inserted): void
    {
        $offset = $key === null ? 0 : (int) self::getKeyOffset($array, $key);
        $array = array_slice($array, 0, $offset, true)
            + $inserted
            + array_slice($array, $offset, count($array), true);
    }

    /**
     * 批量执行回调并以数组返回回调执行结果
     *
     * -e.g: $callbacks["+"] = function($a, $b, $c):int{return $a + $b + $c;};
     * -e.g: $callbacks["*"] = function($a, $b, $c):int{return $a * $b * $c;};
     * -e.g: $args = [2, 3, 4];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::invoke", [$callbacks, ...$args]);
     *
     * @param iterable $callbacks
     * @param mixed ...$args
     * @return array
     */
    static function invoke(iterable $callbacks, ...$args): array
    {
        $res = [];
        foreach ($callbacks as $k => $cb) {

            $res[$k] = is_callable($cb) ? $cb(...$args) : null;
        }
        return $res;
    }

    /**
     * 检测数组是否为索引数组(且从0序开始)
     *
     * -e.g: $arr1 = ["a", "b", "c"];
     * -e.g: $arr2 = [4 => 1, 2, 3];
     * -e.g: $arr3 = ["a" => 1, "b" => 2];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::isList",[$arr1]);echo "<-- \$arr1";
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::isList",[$arr2]);echo "<-- \$arr2";
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::isList",[$arr3]);echo "<-- \$arr3";
     *
     * @param mixed $value
     */
    static function isList($value): bool
    {
        return is_array($value) && (PHP_VERSION_ID < 80100
            ? !$value || array_keys($value) === range(0, count($value) - 1)
            : array_is_list($value)
        );
    }

    /**
     * isList方法的别名
     */
    static function isIndexList(array $arr): bool
    {
        return self::isList($arr);
    }

    /**
     * 将数组规范化为关联数组
     *
     * -- 索引数组范化后 值默认 NULL
     *
     * -e.g: $arr = [1 => "first", "a" => "second"];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::normalize",[$arr]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::normalize",[$arr, "vipkwd.com"]);
     *
     * @param array $array
     * @param mixed $filling <null>
     * @return array
     */
    static function normalize(array $array, $filling = null): array
    {
        $res = [];
        foreach ($array as $k => $v) {
            $res[is_int($k) ? $v : $k] = is_int($k) ? $filling : $v;
        }
        return $res;
    }

    /**
     * 从数组中删除并返回指定的键值
     *
     * -e.g: $arr = [1 => "foo", null => "bar"];
     * -e.g: \Vipkwd\Utils\Dev::dumper($arr, 0, 0);
     * -e.g: echo '\Vipkwd\Utils\Type\Arr::pick($arr, null)); // '; echo \Vipkwd\Utils\Type\Arr::pick($arr, null);
     * -e.g: echo '\Vipkwd\Utils\Type\Arr::pick($arr, "not-exists", "foobar")); // '; echo \Vipkwd\Utils\Type\Arr::pick($arr, "not-exists", "foobar");
     * -e.g: echo '\Vipkwd\Utils\Type\Arr::pick($arr, "not-exists")); // '; echo \Vipkwd\Utils\Type\Arr::pick($arr, "not-exists");
     * -e.g: \Vipkwd\Utils\Dev::dumper($arr, 0, 0);
     *
     * @param array $arr
     * @param string $key
     * @param mixed $default <null> key不存在时默认返回此值
     * @return mixed
     */
    static function pick(array &$arr, $key, $default = null)
    {
        if (array_key_exists($key, $arr)) {
            $value = $arr[$key];
            unset($arr[$key]);
            return $value;
        } elseif (func_num_args() < 3) {
            // throw new \Exception("Missing item '$key'.");
        }
        return $default;
    }

    /**
     * 重命名数组键名
     *
     * -e.g: $arr = ["a"=>10, "b"=>20];
     * -e.g: echo '\Vipkwd\Utils\Type\Arr::renameKey($arr, "a", "c"); // '; echo \Vipkwd\Utils\Type\Arr::renameKey($arr, "a", "c");
     * -e.g: \Vipkwd\Utils\Dev::dump($arr);
     *
     * @param array &$array
     * @param string $oldKey
     * @param string $newKey
     *
     * @return boolean
     */
    static function renameKey(array &$array, $oldKey, $newKey): bool
    {
        $offset = self::getKeyOffset($array, $oldKey);
        if ($offset === null) {
            return false;
        }
        $val = &$array[$oldKey];
        $keys = array_keys($array);
        $keys[$offset] = $newKey;
        $array = array_combine($keys, $array);
        $array[$newKey] = &$val;
        return true;
    }

    /**
     * 获取关联数组键的索引位置
     *
     * -e.g: $arr = ["a"=>10, "b"=>20];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::getKeyOffset", [$arr, "a"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::getKeyOffset", [$arr, "b"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::getKeyOffset", [$arr, "c"]);
     *
     * @param array $array
     * @param string $key
     * @return integer|null
     */
    static function getKeyOffset(array $array, $key): ?int
    {
        $v = array_search(self::toKey($key), array_keys($array), true);
        return $v === false ? null : intval($v);
    }

    protected static function toKey($value)
    {
        return key([$value => null]);
    }

    /**
     * 数组转对象
     *
     * -e.g: $arr = ["a"=>123, "b" => ["c"=>200, "d"=> ["e"=>300]]];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::toObject", [$arr, false]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::toObject", [$arr]);
     *
     * -e.g: $obj = \Vipkwd\Utils\Type\Arr::toObject($arr,true);
     * -e.g: echo 'echo $obj->b->d->e; // 300 <-- '; echo $obj->b->d->e;
     *
     * @param array $array
     * @param boolean $recursive <true> 是否深度递归
     * @return void
     */
    static function toObject(array $array, bool $recursive = true)
    {
        $obj = new static;
        foreach ($array as $key => $value) {
            $obj->$key = $recursive && is_array($value)
                ? self::toObject($value, true)
                : $value;
        }
        return $obj;
    }

    /**
     * 返回数组最后一项键值(空数组则返回null)
     *
     * -e.g: $arr = ["a"=>10, "b"=>20];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::last", [$arr]);
     *
     * @param array $array
     * @return mixed
     */
    static function last(array $array)
    {
        return count($array) ? end($array) : null;
    }

    /**
     * 返回数组第一项键值(空数组则返回null)
     *
     * -e.g: $arr = ["a"=>10, "b"=>20];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::first", [$arr]);
     *
     * @param  array $array
     * @return ?mixed
     */
    static function first(array $array)
    {
        return count($array) ? reset($array) : null;
    }

    /**
     * 返回匹配正则的键值项(类似 array_filter)
     *
     * -e.g: $arr = ["a"=>10, "b"=>2048 ,"c"=>"3a", "d"=>"a3",'url' => 'http://domain.com'];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::grep", [$arr, "/^\d+$/"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::grep", [$arr, "/^https?:\/\/\w+([\w\.\-]+){1,3}$/"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::grep", [$arr, "/^\d+/"]);
     *
     * @param string[] $array
     * @param string $pattern 合法正则表达式
     * @param bool|int $invert
     * @return string[]
     */
    static function grep(array $array, string $pattern, bool $invert = false): array
    {
        $flags = $invert ? PREG_GREP_INVERT : 0;
        return self::pcre('preg_grep', [$pattern, $array, $flags]);
    }

    /**
     * 将多维数组转换为平面(一维)数组
     *
     * -e.g: $arr=[1, 2, [3, 4, ["birthday"=> 19990909, 5, 6]]];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::flatten", [$arr]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::flatten", [$arr, true]);
     *
     * @param array $array
     * @param boolean $preserveKeys <false> 是否保留源Key
     * @return array
     */
    static function flatten(array $array, bool $preserveKeys = false): array
    {
        $res = [];
        $cb = $preserveKeys
            ? function ($v, $k) use (&$res): void {
                $res[$k] = $v;
            }
            : function ($v) use (&$res): void {
                $res[] = $v;
            };
        array_walk_recursive($array, $cb);
        return $res;
    }

    /**
     * 对数组各元素执行回调并返回(回调值)数组
     *
     * -e.g: $arr = ["a"=>1, "b"=>2 ,"c"=>"3a", "d"=>"a3"];
     * -e.g: $callback = function($v, $k, $arr):string{ $_v = intval($v);return ($_v > 0 && $_v%2 ===0) ? "{$v} :like Even" : $v;};
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::map", [$arr, $callback]);
     *
     * @param iterable $arr
     * @param callable $callback
     * @return array
     */
    static function map(iterable $arr, callable $callback): array
    {
        $res = [];
        foreach ($arr as $k => $v) {
            $res[$k] = $callback($v, $k, $arr);
        }
        return $res;
    }

    /**
     * 二维数组去重
     *
     * -e.g: $arr=[["id"=>1,"sex"=>"female"],["id"=>1,"sex"=>"male"],["id"=>2,"age"=>18]];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::deepUnique",[$arr, "id"]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::deepUnique",[$arr, "id", false]);
     *
     * @param array $array 数组
     * @param string $filterKey <"id"> 字段
     * @param boolean $cover <true> 是否覆盖（遇相同 “filterKey” 时，仅保留最后一个值）
     *
     * @return array
     */
    static function deepUnique(array $array, string $filterKey = 'id', bool $cover = true): array
    {
        $res = [];
        foreach ($array as $value) {
            ($cover || (!$cover && !isset($res[($value[$filterKey])]))) && $res[($value[$filterKey])] = $value;
        }
        return array_values($res);
    }

    /**
     * 二维数组排序
     *
     * -e.g: $arr=[["age"=>19,"name"=>"A"],["age"=>20,"name"=>"B"],["age"=>18,"name"=>"C"],["age"=>16,"name"=>"D"]];
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::deepSort", [$arr, "age", "asc"]);
     *
     * @param array $array 排序的数组
     * @param string $orderKey 要排序的key
     * @param string $orderBy <"desc"> 排序类型 ASC、DESC
     *
     * @return array
     */
    static function deepSort(array $array, string $orderKey, string $orderBy = 'desc'): array
    {
        $kv = [];
        foreach ($array as $k => $v) {
            $kv[$k] = $v[$orderKey];
        }
        array_multisort($kv, ($orderBy == "desc" ? SORT_DESC : SORT_ASC), $array);
        return $array;
    }

    /**
     * 数组转XML
     *
     * -e.g: $arr=[];
     * -e.g: $arr[]=["name"=>"张叁","roomId"=> "2-2-301", "carPlace"=> ["C109","C110"] ];
     * -e.g: $arr[]=["name"=>"李思","roomId"=> "9-1-806", "carPlace"=> ["H109"] ];
     * -e.g: $arr[]=["name"=>"王武","roomId"=> "9-1-807", "carPlace"=> [] ];
     * -e.g: $arr["key"]=["name"=>"王武","roomId"=> "9-1-807", "carPlace"=> [] ];
     * -e.g: echo "含语法填充:";
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::toXml", [$arr]);
     * -e.g: echo "无语法填充:";
     * -e.g: phpunit("Vipkwd\Utils\Type\Arr::toXml", [$arr, false]);
     *
     * @param array $input 数组
     * @param bool $xmlHeadSyntax <true> 是否填充xml语法头
     *
     * @return string
     */
    static function toXml(array $input, $xmlHeadSyntax = true): string
    {
        $toXml = function ($input) use (&$toXml) {
            if (is_array($input)) {
                $str = ' keys="' . count($input) . '">';
                foreach ($input as $k => $v) {
                    //索引数组填补 节点名称
                    if (($k > 0 && $k == intval($k)) || $k === 0) {
                        $k = "idx" . $k;
                    }
                    $str .= '<' . $k;
                    $str .= $toXml($v);
                    $str .= '</' . $k . '>';
                }
                return $str;
            }
            return '>' . $input;
        };
        $input = Obj::toArray($input);
        $str = ($xmlHeadSyntax ? '<?xml version="1.0" encoding="utf-8"?>' : '') . '<vipkwd';
        $str .= $toXml($input);
        $str .= '</vipkwd>';
        return $str;
    }

    /**
     * 提升数据列为键
     *
     * @param array $arr
     * @param string $column
     * @param boolean $cover <true> 遇相同column时，是否覆盖
     * @return array
     */
    static function columnToKey(array $arr, string $column, bool $cover = true): array
    {
        $data = [];
        foreach ($arr as $item) {
            if (isset($item[$column])) {
                if ($cover || ($cover !== true && !isset($data[$item[$column]]))) {
                    $data[$item[$column]] = $item;
                }
            }
            unset($item);
        }
        unset($arr, $column);
        return $data;
    }
}

/**
 * 私有
 */
trait TraitName
{
    /**
     * Invokes internal PHP function with own error handler.
     */
    private static function invokeSafe(string $function, array $args, callable $onError)
    {
        $prev = set_error_handler(function ($severity, $message, $file) use ($onError, &$prev, $function): ?bool {
            if ($file === __FILE__) {
                $msg = ini_get('html_errors')
                    ? self::htmlToText($message)
                    : $message;
                $msg = preg_replace("#^$function\\(.*?\\): #", '', $msg);
                if ($onError($msg, $severity) !== false) {
                    return null;
                }
            }
            return $prev ? $prev(...func_get_args()) : false;
        });

        try {
            return $function(...$args);
        } finally {
            restore_error_handler();
        }
    }

    private static function pcre(string $func, array $args)
    {
        $res = self::invokeSafe($func, $args, function (string $message) use ($args): void {
            // compile-time error, not detectable by preg_last_error
            throw new Exception($message . ' in pattern: ' . implode(' or ', (array) $args[0]));
        });

        if (($code = preg_last_error()) // run-time error, but preg_last_error & return code are liars
            && ($res === null || !in_array($func, ['preg_filter', 'preg_replace_callback', 'preg_replace'], true))
        ) {
            throw new Exception(preg_last_error_msg() . ' (pattern: ' . implode(' or ', (array) $args[0]) . ')', $code);
        }
        return $res;
    }

    /**
     * Converts given HTML code to plain text.
     */
    private static function htmlToText(string $html): string
    {
        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

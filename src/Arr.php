<?php
/**
 * @name 数组操作
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);
namespace Vipkwd\Utils;

class Arr{

    /**
	 * 是否为关联数组
	 * 
     * -e.g: $arr = [];
     * -e.g: phpunit("Arr::isAssoc", [$arr]);
     * -e.g: $arr = [array()];
     * -e.g: phpunit("Arr::isAssoc", [$arr]);
     * 
	 * @param array $arr 数组
	 * @return bool
	 */
	static function isAssoc(array $arr):bool{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	/**
	 * 不区分大小写的in_array实现
	 *
     * -e.g: $arr = ["A","b","as"];
     * -e.g: phpunit("Arr::in", ["a", $arr]);
     * -e.g: phpunit("Arr::in", ["B", $arr]);
     * -e.g: phpunit("Arr::in", ["s", $arr]);
     * 
	 * @param $value
	 * @param $array
	 * @return bool
	 */
	static function in($value, $array){
		return in_array(strtolower($value), array_map('strtolower', $array));
	}

    /**
     * 排列组合（适用多规格SKU生成）
     * 
     * @param array $input 排列的数组
     * 
     * @return array
     */
    static function arrayArrRange(array $input): array{
        $temp = [];
        $result = array_shift($input);
        while($item = array_shift($input)){
           $temp = $result;
           $result = [];
           foreach($temp as $v){
                foreach($item as $val){
                    $result[] = array_merge_recursive($v, $val);
                }
           }
        }
        return $result;
    }
    
    /**
     * 判断数组中指定键是否为数组
     *
     * -e.g: echo '$arr=["id"=>134,"mobile"=>["131xxxx","132xxx"]]';$arr=["id"=>134,"mobile"=>["131xxxx","132xxx"]];
     * -e.g: phpunit("Arr::isArray", [$arr,"id"]);
     * -e.g: phpunit("Arr::isArray", [$arr,"mobile"]);
     * 
     * @param array $arr
     * @param string $field
     * @return boolean
     */
    static function isArray(array $arr, string $field):bool{
        if(!is_array($arr)){
            return false;
        }
        if (!isset($arr[$field]) || !is_array($arr[$field])) {
            return false;
        }
        return true;
    }

    /**
     * 二维数组去重
     * 
     * -e.g: $arr=[["id"=>1,"sex"=>"female"],["id"=>1,"sex"=>"male"],["id"=>2,"age"=>18]];
     * -e.g: phpunit("Arr::deepUnique",[$arr, "id"]);
     * -e.g: phpunit("Arr::deepUnique",[$arr, "id", false]);
     * 
     * @param array $arr 数组
     * @param string $filterKey <"id"> 字段
     * @param boolean $cover <true> 是否覆盖（遇相同 “filterKey” 时，仅保留最后一个值）
     *
     * @return array
     */
    static function deepUnique(array $arr, string $filterKey = 'id', bool $cover=true): array{
        $res = [];
        foreach ($arr as $value){
            ($cover || ( !$cover && !isset($res[($value[$filterKey])]) ) ) && $res[($value[$filterKey])] = $value;
        }
        return array_values($res);
    }

    /**
     * 二维数组排序
     * 
     * -e.g: $arr=[["age"=>19,"name"=>"A"],["age"=>20,"name"=>"B"],["age"=>18,"name"=>"C"],["age"=>16,"name"=>"D"]];
     * -e.g: phpunit("Arr::deepSort", [$arr, "age", "asc"]);
     * 
     * @param array $array 排序的数组
     * @param string $orderKey 要排序的key
     * @param string $orderBy <"desc"> 排序类型 ASC、DESC
     *
     * @return array
     */
    static function deepSort(array $array, string $orderKey, string $orderBy = 'desc'): array{
        $kv = [];
        foreach ($array as $k => $v){
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
     * -e.g: phpunit("Arr::toXml", [$arr]);
     * -e.g: echo "无语法填充:";
     * -e.g: phpunit("Arr::toXml", [$arr, false]);
     * 
     * @param array $input 数组
     * @param bool $syntax <true> 是否填充xml语法头
     * 
     * @return string
     */
    static function toXml(array $input, $syntax = true): string{
        $toXml = function($input)use(&$toXml){
            if(is_array($input)){
                $str = ' len="'.count($input).'">';
                foreach ($input as $k => $v){
                    //索引数组填补 节点名称
                    if( ($k > 0 && $k == intval($k)) || $k === 0){
                        $k = "idx".$k;
                    }
                    $str .= '<' . $k;
                    $str .= $toXml($v);
                    $str .='</' . $k . '>';
                }
                return $str;
            }
            return '>'.$input;
        };
        $input = Obj::toArray($input);
        $str = ($syntax ? '<?xml version="1.0" encoding="utf-8"?>' : '').'<vipkwd';
        $str .= $toXml($input);
        $str .= '</vipkwd>';
        return $str;
    }

}
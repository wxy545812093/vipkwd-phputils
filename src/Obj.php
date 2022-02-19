<?php
/**
 * @name 对象操作
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);
namespace Vipkwd\Utils;

class Obj{

    /**
     * 对象转数组
     * 
     * -e.g: $data=(object)[ "a"=>50, "b"=>true, "c"=>null ];
     * -e.g: phpunit("Obj::toArray", [$data]);
     * 
     * @param object|array $object 对象
     * 
     * @return array
     */
    static function toArray($object){
        if(is_object($object)){
            $arr = (array)$object;
        }else if(is_array($object)){
            $arr = [];
            foreach($object as $k => $v){
                $arr[$k] = self::toArray($v);
            }
        }else{
            return $object;
        }
        unset($object);
        return $arr;
        //return json_decode(json_encode($object), true);
    }
}
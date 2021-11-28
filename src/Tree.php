<?php
/**
 * @name 数据树
 * 无限分类树（支持子分类排序）
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;
class Tree{
    //分类排序（升序）
    static public function sort($arr,$cols){
        //子分类排序
        foreach ($arr as $k => &$v) {
            if(!empty($v['sub'])){
                $v['sub']=self::sort($v['sub'],$cols);
            }
            $sort[$k]=$v[$cols];
        }
        if(isset($sort))
        array_multisort($sort,SORT_ASC,$arr);
        return $arr;
    }
    //横向分类树
    static public function hTree($arr, $pid=0,$tk='pid'){
        foreach($arr as $k => $v){
            if(isset($v[$tk]) && $v[$tk]==$pid){
                $v['sub']=self::hTree($arr,$v['id'], $tk);
                $data[]=$v;
            }
        }
        return isset($data)?$data:[];
    }
    //纵向分类树
    static public function vTree($arr,$pid=0,$state=true, $tk='pid'){
        static $data=[];
        $state && $data=[];
        foreach($arr as $k => $v){
            if(isset($v[$tk]) && $v[$tk]==$pid){
                $data[]=$v;
                self::vTree($arr,$v['id'],false);
            }
        }
        return $data;
    }
}
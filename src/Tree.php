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

        /**
     * 一维数组转无限级分类
     * 
     * -e.g: $list=[];
     * -e.g: $list[]=["id"=>1,    "pid"=>0,   "name"=>"中国大陆"];
     * -e.g: $list[]=["id"=>2,    "pid"=>1,   "name"=>"北京"];
     * -e.g: $list[]=["id"=>22,   "pid"=>1,   "name"=>"广东省"];
     * -e.g: $list[]=["id"=>54,   "pid"=>2,   "name"=>"北京市"];
     * -e.g: $list[]=["id"=>196,  "pid"=>22,  "name"=>"广州市"];
     * -e.g: $list[]=["id"=>1200, "pid"=>54,  "name"=>"海淀区"];
     * -e.g: $list[]=["id"=>3907, "pid"=>196, "name"=>"黄浦区"];
     * -e.g: phpunit("Arr::toTree", [$list, "id", "pid", "child", 0]);
     * 
     * @param array $list 归类的数组
     * @param string $pk <"id"> 父级ID
     * @param string $pid <"pid"> 父级PID
     * @param string $child <"child"> 子节点容器名称
     * @param string $rootPid <0> 顶级ID(pid)
     * 
     * @return array
     */
    static function listToTree(array $list, string $pk = 'id', string $pid = 'pid', string $child = 'child', int $rootPid = 0): array{
        $tree = [];
        if(is_array($list)){
            $refer = [];
            //基于数组的指针(引用) 并 同步改变数组
            foreach ($list as $key => $val){
                $list[$key][$child] = [];
                $refer[$val[$pk]] = &$list[$key];
            }
            foreach ($list as $key => $val){
                //是否存在parent
                $parentId = isset($val[$pid]) ? $val[$pid] : $rootPid;

                if ($rootPid == $parentId){
                    $tree[$val[$pk]] = &$list[$key];
                }else{
                    if (isset($refer[$parentId])){
                        $refer[$parentId][$child][] = &$list[$key];
                    }
                }
            }
        }
        return array_values($tree);
    }

    /**
     * 将层级数组遍历成一维数组
     * 
     * @param array $list
     * @param int $level
     * @param string $title
     * @return array
     */
    static function formatTree(array $list, int $level = 0, string $title = 'title'): array {
        $formatTree = [];
        foreach ($list as $key => $val) {
            $title_prefix = '';
            for ($i = 0; $i < $level; $i++) {
                $title_prefix .= "|---";
            }
            $val['level'] = $level;
            $val['namePrefix'] = $level == 0 ? '' : $title_prefix;
            $val['showName'] = $level == 0 ? $val[$title] : $title_prefix . $val[$title];
            if (!array_key_exists('child', $val)) {
                array_push($formatTree, $val);
            } else {
                $child = $val['child'];
                unset($val['child']);
                array_push($formatTree, $val);
                $middle = self::formatTree($child, $level + 1, $title); //进行下一层递归
                $formatTree = array_merge($formatTree, $middle);
            }
        }
        return $formatTree;
    }
}
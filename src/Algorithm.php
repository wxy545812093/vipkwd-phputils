<?php
/**
 * @name 经典排序/查找算法
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);
namespace Vipkwd\Utils;
class Algorithm{

    static $arrLen = 0;
    // 顺序查找
    // 二分查找
    // 插值查找
    // 斐波那契查找
    // 数表查找
    // 分块查找
    // 哈希查找 https://blog.csdn.net/yimixgg/article/details/88900038


    /**
     * 冒泡排序法
     *
     * @param array $indexedArr
     * @return array
     */
    static function bubbleSort(array $indexedArr):array{
        $len = count($indexedArr) - 1;
        for($i = 0; $i < $len; $i++){
            for($j = 0; $j < $len - $i; $j++){
                if( $indexedArr[$j] > $indexedArr[$j+1]){
                    $_tmp = $indexedArr[$j+1];
                    $indexedArr[$j+1] = $indexedArr[$j];
                    $indexedArr[$j] = $_tmp;
                    unset($_tmp);
                }
            }
        }
        return $indexedArr;
    }

    /**
     * 选择排序法
     *
     * @param array $indexedArr
     * @return array
     */
    static function selectionSort(array $indexedArr):array{
        $len = count($indexedArr);
        for($i =0; $i< $len - 1; $i++){
            $minIdx = $i;
            for($j = $i +1; $j < $len; $j++){
                //如果后一个数小于前一个数，则发起交换需求
                if( $indexedArr[$j] < $indexedArr[$minIdx]){
                    $minIdx = $j;
                }
            }
            //如果无交换需求则忽略
            if( $minIdx > $i ){
                $_tmp = $indexedArr[$i];
                $indexedArr[$i] = $indexedArr[$minIdx];
                $indexedArr[$minIdx] = $_tmp;
            }
        }
        return $indexedArr;
    }

    /**
     * 插入排序法
     *
     * @param array $indexedArr
     * @return array
     */
    static function insertionSort(array $indexedArr):array{
        $len = count($indexedArr);
        for($i = 1; $i < $len; $i++){
            $preIdx = $i - 1;
            $current = $indexedArr[$i];

            while( $preIdx >= 0 && $indexedArr[$preIdx] > $current){
                //后移一位
                $indexedArr[$preIdx +1 ] = $indexedArr[$preIdx];
                $preIdx --;
            }
            //($preIdx +1 != $i) && $indexedArr[$preIdx +1] = $current;
            $indexedArr[$preIdx +1] = $current;
        }
        return $indexedArr;
    }

    /**
     * 希尔排序法
     *
     * @param array $indexedArr
     * @return array
     */
    static function shellSort(array $indexedArr):array{
        $len = count($indexedArr);
        $_tmp = 0;
        $_idx = 1;
        // 动态定义间隔序列
        while($_idx < ($len / 3)){
            $_idx = $_idx *3 +1;
        }
        for(; $_idx > 0; $_idx = floor($_idx / 3) ){
            for($i = $_idx; $i < $len; $i++){
                $_tmp = $indexedArr[$i];
                for($j = $i - $_idx; $j>= 0 && $indexedArr[$j] > $_tmp; $j -= $_idx){
                    $indexedArr[$j + $_idx] = $indexedArr[$j];
                }
                $indexedArr[$j + $_idx] = $_tmp;
            }
        }
        return $indexedArr;
    }

    /**
     * 归并排序法
     *
     * @param array $indexedArr
     * @return array
     */
    static function mergeSort(array $indexedArr):array{
        $len = count($indexedArr);
        //杀鸡焉用牛刀？
        if($len < 2){
            return $indexedArr;
        }
        //粗暴：一分为二（一切的花里胡哨都是虚伪|高端的美味往往都来自于最普通的食材）
        $mid = floor($len / 2);
        $left = array_slice($indexedArr, 0, $mid);
        $right = array_slice($indexedArr, $mid);
        return self::__mergeSort_mergeIndexedArray( self::mergeSort($left), self::mergeSort($right));
    }

    /**
     * 快速排序法
     *
     * @param array $indexedArr
     * @return array
     */
    static function quickSort(array $indexedArr):array{
        $len = count($indexedArr);
        if($len < 2){
            return $indexedArr;
        }
        $mid = $indexedArr[0];
        $left = $right = [];
        for($i =1; $i<$len; $i++){
            if($indexedArr[$i] >= $mid){
                $right[] = $indexedArr[$i];
            }else{
                $left[] = $indexedArr[$i];
            }
        }
        return array_merge(self::quickSort($left), [$mid], self::quickSort($right));
    }

    /**
     * 堆排序法
     *
     * @param array $indexedArr
     * @return array
     */
    static function heapSort(array $indexedArr):array{
        self::$arrLen = count($indexedArr);
        self::buildMaxHeap($indexedArr);
        for($i = self::$arrLen -1; $i > 0; $i--){
            self::$arrLen -= 1;
            self::__heapSort_swap($indexedArr, 0, $i);
            self::__heapSort_heapify($indexedArr, 0);
        }
        return $indexedArr;
    }

    /**
     * 计数排序法
     *
     * @param array $indexedArr
     * @return array
     */
    static function countingSort(array $indexedArr):array{
        //$indexedArr = [1,5,8,16];
        $max = max($indexedArr);
        $space = [];
        //填充计数器
        for($i=0; $i < $max +1;$i++){
            $space[] = null;
        }
        $len = count($indexedArr);
        // 统计和元素(数字)出现的次数
        for($i =0; $i < $len; $i++){
            if(!array_key_exists($indexedArr[$i], $space)){
                $space[$indexedArr[$i]] = 0;
            }
            $space[ $indexedArr[$i] ] +=1;
        }
        $sortIdx = 0;
        foreach($space as $number => $counts){
            $counts !== null && $indexedArr[$sortIdx++] = $number;
            if($counts !== null){
                for($i =0; $i < $counts; $i++){
                    $indexedArr[$sortIdx++] = $number;
                }
            }
        }
        return $indexedArr;
    }

    /**
     * 桶排序法
     *
     * @param array $indexedArr
     * @param integer $buckerSize <5>
     * @return array
     */
    static function bucketSort(array $indexedArr, int $buckerSize = 5):array{
        $len = count($indexedArr);
        if($len == 0){
            return $indexedArr;
        }
        //初始化最大、最小值
        $min = $max = $indexedArr[0];
        for($i =1; $i < $len; $i++){
            //检测最大、最小值
            if($indexedArr[$i] < $min){
                $min = $indexedArr[$i];
            }else if($indexedArr[$i] > $max){
                $max = $indexedArr[$i];
            }
        }
        $buckerCount = floor(($max - $min) / $buckerSize) +1;
        $buckers = [];
        for($i =0; $i < $bucketCount; $i++){
            $buckers[$i] = [];
        }
        for($i=0;$i < $len; $i++){
            $lx = floor(($indexedArr[$i] - $min) / $buckerSize);
            $buckers[$lx][] = $indexedArr[$i];
        }
        $indexedArr = [];
        for($i=0; $i < $buckerCount; $i++){
            $_tmp = $buckers[$i];
            sort($_tmp);
            $indexedArr = array_merge($indexedArr, $_tmp);
        }
        return $indexedArr;
    }

    
    /**
     * 基数排序法
     *
     * @param array $indexedArr
     * @return array
     */
    static function radixSort(array $indexedArr):array{
        if(count($indexedArr)<= 1){
            return $indexedArr;
        }
        $max = max($indexedArr);
        $maxLen = strlen("$max");
        //由低位到高位比对
        for($i = $maxLen; $i >=1; $i--){
            $bucket = array_pad([], 10, []);
            //生成相同长度的字符序列便于按位校值
            array_walk($indexedArr, function(&$v)use($maxLen){
                $v = str_pad("$v", $maxLen," ", STR_PAD_LEFT);
            });
            foreach($indexedArr as $v){
                $n = $v[$i-1] == " " ? 0 : intval($v[$i-1]);
                // 
                $bucket[$n][] = trim($v) * 1;
            }
            $indexedArr = [];
            array_map(function($v)use(&$indexedArr){
                $indexedArr = array_merge($indexedArr, $v);
            },$bucket);
        }
        return $indexedArr;
    }


    /**
     * 合并索引数组
     *
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    private static function __mergeSort_mergeIndexedArray(array $arr1, array $arr2):array{
        $result = [];
        // 二者都不为空
        while( count($arr1) && count($arr2)){
            // 谁小谁先进入预置队列result
            if( $arr1[0] > $arr2[0]){
                $result[] = array_shift($arr2);
            }else{
                $result[] = array_shift($arr1);
            }
        }
        while(count($arr1)){
            $result[] = array_shift($arr1);
        }
        while(count($arr2)){
            $result[] = array_shift($arr2);
        }
        return $result;
    }

    private static function __heapSort_buildMaxHeap(array &$arr){
        for($i = floor(self::$arrLen / 2); $i >= 0; $i--){
            self::__heapSort_heapify($arr, $i);
        }
    }
    private static function __heapSort_swap(array &$arr, int $i, int $j){
        $_tmp = $arr[$i];
        $arr[$i] = $arr[$j];
        $arr[$j] = $_tmp;
    }
    private static function __heapSort_heapify(array &$arr, int $i){
        $left = $i *2 +1;
        $right = $i *2 +2;
        $max = $i;
        if( $left < self::$arrLen && $arr[$left] > $arr[$max]){
            $max = $left;
        }
        if( $right < self::$arrLen && $arr[$right] > $arr[$max]){
            $max = $right;
        }
        if( $max != $i){
            self::__heapSort_swap($arr, $i, $max);
            self::__heapSort_heapify($arr, $max);
        }
    }

}
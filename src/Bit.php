<?php
/**
 * @name 位操作运算
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use \Exception;

// https://www.cnblogs.com/xiaoqiangink/p/14366099.html
// https://www.cnblogs.com/xuey/p/8683100.html
class Bit{
	
	/**
	 * m向左位移n位
	 * 
	 * m << n
	 * 左移的特征：用来倍增（乘2^n,n为移动的数值）
	 * 
	 * @param integer $m
	 * @param integer $n
	 * @return void
	 */
	static function moveToLeft(int $m =0, int $n =0){
		// $m << $n;
		return $m * pow(2, $n);
	}

	/**
	 * m向右位移n位
	 * 
	 * m >> n
	 * 右移的特征：用来整除（除2^n,n为移动的数值）并舍去余数
	 * @param integer $m
	 * @param integer $n
	 * @return void
	 */
    static function moveToRight(int $m = 0, int $n = 0){
	   // $m >> $n;
	   //取商，舍去余数
	   return floor($m / pow(2, $n));
    }	

	/**
	 * 求俩数平均值(用位运算实现)
	 * 
	 * 对于两个整数x,y，如果用 (x+y)/2 求平均值，结果可能产生溢出，因为 x+y 可能会大于INT_MAX，
	 * 但是我们知道它们的平均值是肯定不会溢出的。
	 * 
	 * @param integer $x
	 * @param integer $y
	 * @return void
	 */
	static function avg(int $x = 0, int $y = 0){
		// if($x == ($x * 1) && $y == ($y * 1)){
			return ($x & $y) + (($x ^ $y) >> 1);
		// }
		// return null;
	}


	/**
	 * 一个整数x是不是2的幂
	 * 
	 *  要求：x >= 0
	 *
	 * @param integer $x
	 * @return boolean
	 */
	static function power2(int $x = 0):bool{
		if($x >= 0){
			return (($x & ($x +1) ) == 0 ) && ($x != 0);
		}
		return false;
	}

	/**
	 * 交换俩个整数变量(不用第三个变量)
	 *
	 * @param integer $a
	 * @param integer $b
	 * @return void
	 */
	static function swap(int &$a = 0, int &$b = 0):void{
		$a ^= $b;
		$b ^= $a;
		$a ^= $b;
	}

	/**
	 * 求绝对值(用位运算实现)
	 *
	 * @param integer $x
	 * @return void
	 */
	static function abs(int $x = 0){
		$a = 0;
		$a = $x >> 31;
		return ( $x ^ $a) - $a;
		//return ($x + $a) ^ $a;
	}

	/**
	 * 求相反数(用位运算实现)
	 *
	 * @param integer $x
	 * @return integer
	 */
	static function opposite(int $x = 0):int{
		return (~$x +1);
	}

	/**
	 * 位运算求证件性别(奇:男,偶:女)
	 * 
	 * 位与特征： 按为 全1为1，否则为0
	 * ·-------------------------------------------------------------------------·
	 * | &     0  |   1  |   2  |   3  |   4  |   5  |   6  |   7  |   8  |   9  |
	 * |   | 0000 | 0001 | 0010 | 0011 | 0100 | 0101 | 0110 | 0111 | 1000 | 1001 |
	 * |   |---------------------------------------------------------------------|
	 * | 1   0001 | 0001 | 0001 | 0001 | 0001 | 0001 | 0001 | 0001 | 0001 | 0001 |
	 * |                                                                         |
	 * |---|---------------------------------------------------------------------|
	 * |                                                                         |
	 * |   | 0000 | 0001 | 0000 | 0001 | 0000 | 0001 | 0000 | 0001 | 0000 | 0001 |
	 * | = |   0  |   1  |   0  |   1  |   0  |   1  |   0  |   1  |   0  |   1  |
	 * ·-------------------------------------------------------------------------·
	 * 
	 * -e-: Vipkwd\Utils\Bit::idSex("441323201309038020", 2);
	 * 
	 * @param string $id
	 * @param integer $type
	 * @param boolean $text
	 * @return void
	 */
	static function idSex(string $id, int $type = 2, bool $text = false){

		if($type === 2){
			//二代身份证
			$id = substr($id, -2,1);
		}else{
			// 一代身份证
			$id = substr($id,-1);
		}
		$sex = $id & 1;
		return $text ? ($sex ? "男" : "女") : $sex;


	}

}
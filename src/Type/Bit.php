<?php
/**
 * @name 位操作运算
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Type;

use \Exception;

// https://www.cnblogs.com/xiaoqiangink/p/14366099.html
// https://www.cnblogs.com/xuey/p/8683100.html
class Bit{
	
	/**
     * m 左移 n位
     * 
     * m << n
     * 左移的特征：用来倍增（m x pow(2,n) ,n为移动的数值）
     *  注1、n ≥ 0时，n将舍弃小数部分（如果有）,执行：左位移(乘法)
     *  注2、n < 0时，n将舍弃小数部分（如果有）,执行：右位移(除法)
     * 
     * -e.g: phpunit("Bit::shiftLeft",[16,2]);
     * -e.g: phpunit("Bit::shiftLeft",[4,2]);
     * -e.g: echo "//负数";
     * -e.g: phpunit("Bit::shiftLeft",[4,-2]);
     * -e.g: phpunit("Bit::shiftLeft",[4,-3]);
     * -e.g: phpunit("Bit::shiftLeft",[4,-4]);
     * -e.g: phpunit("Bit::shiftLeft",[4,-5]);
     * -e.g: phpunit("Bit::shiftLeft",[-4,2]);
     * -e.g: phpunit("Bit::shiftLeft",[-4,-1.3]);
     * -e.g: echo "//小数";
     * -e.g: phpunit("Bit::shiftLeft",[4,3.3]);
     * -e.g: phpunit("Bit::shiftLeft",[4,2.5]);
     * -e.g: phpunit("Bit::shiftLeft",[4,1.8]);
     * -e.g: phpunit("Bit::shiftLeft",[4,0.8]);
     * -e.g: phpunit("Bit::shiftLeft",[4,-1.3]);
     * 
     * @param integer $m
     * @param integer $n
     * @return void
     * 
     */
	static function shiftLeft(int $m =0, int $n =0){
		// $m << $n;
		return $m * pow(2, $n);
	}

	/**
     * m 右移 n位
     * 
     * m >> n
     * 右移的特征：用来整除（ m / pow(2,n), n为移动的数值）并舍去余数
     *  注1、n ≥ 0时，n将舍弃小数部分（如果有）,执行：左位移(乘法)
     *  注2、n < 0时，n将舍弃小数部分（如果有）,执行：右位移(除法)
     * 
     * -e.g: echo "// 16÷(2 ^ 2) =>4";
     * -e.g: phpunit("Bit::shiftRight",[16,2]);
     * -e.g: phpunit("Bit::shiftRight",[4,2]);
     * 
     * -e.g: echo "//负数执行乘法(左移) 4x(2 ^ abs(-2)) =>16";
     * -e.g: phpunit("Bit::shiftRight",[4,-2]);
     * -e.g: phpunit("Bit::shiftRight",[4,-3]);
     * -e.g: phpunit("Bit::shiftRight",[4,-4]);
     * -e.g: phpunit("Bit::shiftRight",[4,-5]);
     * 
     * -e.g: echo "// -4÷(2 ^ 2) => -1 ";
     * -e.g: phpunit("Bit::shiftRight",[-4,2]);
     * 
     * -e.g: echo "// 4÷(2 ^ int(3.3)) =>4÷8  =>0.5 =>0";
     * -e.g: phpunit("Bit::shiftRight",[4,3.3]);
     * 
     * -e.g: echo "// 4÷(2 ^ int(2.3)) =>4÷4  =>1";
     * -e.g: phpunit("Bit::shiftRight",[4,2.3]);
     * -e.g: phpunit("Bit::shiftRight",[4,1.3]);
     * 
     * -e.g: echo "//负数执行乘法(左移) 4x(2 ^ abs(-1.3)) => 4x(2 ^ 1) => 4x2  =>8";
     * -e.g: phpunit("Bit::shiftRight",[4,-1.3]);
     * 
     * -e.g: echo "//负数执行乘法(左移) -4x(2 ^ abs(-1.3)) => -4x(2 ^ 1) => -4x2  =>-8";
     * -e.g: phpunit("Bit::shiftRight",[-4,-1.3]);
     * 
     * @param integer $m
     * @param integer $n
     * @return void
     * 
     */
    static function shiftRight(int $m = 0, int $n = 0){
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
     * -e.g: phpunit("Bit::avg", [40.5,55]);
     * -e.g: phpunit("Bit::avg", [0.3,0.3]);
     * -e.g: phpunit("Bit::avg", [4,5]);
     * -e.g: phpunit("Bit::avg", [5,5]);
     * -e.g: phpunit("Bit::avg", [-1,2]);
     * 
     * @param integer $x
     * @param integer $y
     * @return void
     * 
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
     * -e.g: phpunit("Bit::power2", [4]);
     * -e.g: phpunit("Bit::power2", [5]);
     * 
     * @param integer $x
     * @return boolean
     * 
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
     * -e.g: $a=8;$b=3;
     * -e.g: echo "var_dump(\"\\\$a=\$a, \\\$b=\$b\");// "; \Vipkwd\Utils\Dev::vdump("\$a=$a, \$b=$b");
     * -e.g: \Vipkwd\Utils\Type\Bit::swap($a, $b); echo "";\Vipkwd\Utils\Dev::dump('Vipkwd\Utils\Type\Bit::swap($a, $b);');
     * -e.g: echo "var_dump(\"\\\$a=\$a, \\\$b=\$b\");// "; \Vipkwd\Utils\Dev::vdump("\$a=$a, \$b=$b");
     * 
     * @param integer $a
     * @param integer $b
     * @return void
     * 
     */
	static function swap(int &$a = 0, int &$b = 0):void{
		$a ^= $b;
		$b ^= $a;
		$a ^= $b;
	}

	/**
     * 求绝对值(用位运算实现)
     * 
     * -e.g: phpunit("Bit::abs", [-8]);
     * -e.g: phpunit("Bit::abs", [7]);
     * 
     * @param integer $x
     * @return void
     * 
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
     * -e.g: phpunit("Bit::opposite", [7]);
     * -e.g: phpunit("Bit::opposite", [-7]);
     * 
     * @param integer $x
     * @return integer
     *
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
     * -e.g: phpunit("Bit::idSex", ["120101198106165360"]);
     * -e.g: phpunit("Bit::idSex", ["120101198106165360",true]);
     * -e.g: phpunit("Bit::idSex", ["520201198907251373",true]);
     * -e.g: phpunit("Bit::idSex", ["632123820927051"]);
     * -e.g: phpunit("Bit::idSex", ["632123820927051",true]);
     * 
     * @param string $id 身份证号码
     * @param boolean $text <false> 是否响应为性别文本(男|女)
     * 
     * @return void
     * 
     */
	static function idSex(string $id, bool $text = false){

		if(strlen($id) == 18){
			//二代身份证
			$id = substr($id, -2,1);
		}elseif(strlen($id) == 15){
			// 一代身份证
			$id = substr($id,-1);
		}else{
			return ;
		}
		$sex = $id & 1;
		return $text ? ($sex ? "男" : "女") : $sex;
	}

}
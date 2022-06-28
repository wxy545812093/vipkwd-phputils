<?php
/*
BY:NODCLOUD.COM
*/
/**
 * @name 数学函数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Type;
class Math{
    private $base;

    /**
     * 入口(小数点保留位数)
     *
     * @param int $digit <10>
     */
    private function __construct(int $digit=10){
        bcscale($digit);
    }
    /**
     * 基础数值
     *
     * @param integer|float|numstr $number
     * @return self
     */
    private function chain($number):self{
        $this->base="$number";
        return $this;
    }

    /**
     * 基础数值
     * 
     * -e.g: phpunit("Math::instance", [10]);
     * -e.g: phpunit("Math::instance", [10.3]);
     * -e.g: phpunit("Math::instance", ["10"]);
     *
     * @param integer|float|numstr $number
     * @return self
     */
    static function instance($number):self{
        $that = new self(10);
        return $that->chain($number);
    }

    /**
     * bc加法运算
     * 
     * @param  $number
     * @return self
     */
    public function add($number):self{
        $this->base=bcadd($this->base,"$number");
        return $this;
    }
    /**
     * 减法运算
     * 
     * 被减数-减数=差
     *
     * @param int|float $number 减数
     * @return self
     */
    public function sub($number):self{
        $this->base=bcsub($this->base,$number);
        return $this;
    }
    /**
     * 乘法运算
     *
     * @param int|float $number
     * @return self
     */
    public function mul($number):self{
        $this->base=bcmul($this->base,$number);
        return $this;
    }
    /**
     * 除法运算
     * 
     * 被除数÷除数=商……余数
     *
     * @param int|float $number
     * @return self
     */
    public function div($number):self{
        $this->base=bcdiv($this->base,$number);
        return $this;
    }
    /**
     * 取余运算
     *
     * @param int|float $number
     * @return self
     */
    public function mod($number):self{
        $this->base=bcmod($this->base,$number);
        return $this;
    }
    /**
     * 四舍五入
     *
     * @param int $digit
     * @return self
     */
    public function round(int $digit):self{
        $this->base=round($this->base,$digit);
        return $this;
    }
    /**
     * 绝对值
     *
     * @return self
     */
    public function abs():self{
        $this->base=abs($this->base);
        return $this;
    }
    /**
     * 返回结果
     *
     * @return float
     */
    public function done():float{
        return floatval($this->base);
    }
}

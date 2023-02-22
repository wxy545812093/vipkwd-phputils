<?php

/**
 * @name 数学函数
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Type;

class Math
{
    private $base;

    /**
     * 入口(小数点保留位数)
     *
     * @param int $digit <10>
     */
    private function __construct(int $digit = 10)
    {
        bcscale($digit);
    }
    /**
     * 基础数值
     *
     * @param integer|float|numstr $number
     * @return self
     */
    private function chain($number): self
    {
        $this->base = trim("$number");
        return $this;
    }

    /**
     * 基础数值
     * 
     * -e.g: phpunit("Vipkwd\Utils\Type\Math::instance", [10]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Math::instance", [10.3]);
     * -e.g: phpunit("Vipkwd\Utils\Type\Math::instance", ["10"]);
     *
     * @param integer|float|numstr $number
     * @return self
     */
    static function instance($number): self
    {
        $that = new self(10);
        return $that->chain($number);
    }

    /**
     * bc加法运算
     * 
     * @param integer|float|numstr $number
     * @return self
     */
    public function add($number): self
    {
        if ($this->isNumerator($number) && !$this->isNumerator()) {
            $this->base = $this->toNumerator($this->base);
        }
        if ($this->isNumerator()) {
            $this->isNumerator($number) === false && $number = $this->toNumerator($number);
            list($leftTop, $leftBelow) = explode("/", $this->base);
            list($rightTop, $rightBelow) = explode("/", $number);

            if ($leftBelow == $rightBelow) {
                $this->base = ($leftTop + $rightTop) . '/' . $leftBelow;
            } else {
                // 蝴蝶算法 1/3 + 1/2 = (1*2 + 1*3) / (2*3) = 5/6
                $this->base = ($leftTop * $rightBelow + $rightTop * $leftBelow) . "/" . ($leftBelow * $rightBelow);
            }

            //求公倍数算法（分母同化）
            // if (($leftBelow % $rightBelow) === 0 && $leftBelow > $rightBelow) {
            //     // 左分母为最小公倍数
            //     $rightTop *= intval($leftBelow / $rightBelow);
            //     $this->base = $leftTop + $rightTop  . "/" . ($leftBelow);
            // } elseif (($rightBelow % $leftBelow) === 0 && $rightBelow > $leftBelow) {
            //     // 右分母为最小公倍数
            //     $leftTop *= intval($rightBelow / $leftBelow);
            //     $this->base = $leftTop + $rightTop  . "/" . ($rightBelow);
            // } else { //俩分母最小公倍数
            //     // $leftTop *= $rightBelow;
            //     // $rightTop *= $leftBelow;
            //     // $leftBelow = $rightBelow = $leftBelow * $rightBelow;
            //     $this->base = ($leftTop * $rightBelow) + ($rightTop * $leftBelow) . "/" . ($leftBelow * $rightBelow);
            // }
        } else {
            $this->base = bcadd($this->base, "$number");
        }
        return $this;
    }




    /**
     * 减法运算
     * 
     * 被减数-减数=差
     *
     * @param integer|float|numstr $number 减数
     * @return self
     */
    public function sub($number): self
    {
        // 当减数为分数时，被减数转分数
        if ($this->isNumerator($number) && !$this->isNumerator()) {
            $this->base = $this->toNumerator($this->base);
        }

        if ($this->isNumerator()) {
            $this->isNumerator($number) === false && $number = $this->toNumerator($number);
            list($leftTop, $leftBelow) = explode("/", $this->base);
            list($rightTop, $rightBelow) = explode("/", $number);

            if ($leftBelow == $rightBelow) {
                $this->base = ($leftTop - $rightTop) . '/' . $leftBelow;
            } else {
                // 蝴蝶算法 1/2 - 1/2 = (1*3 - 1*2) / (2*3) = 1/6
                $this->base = ($leftTop * $rightBelow - $rightTop * $leftBelow) . "/" . ($leftBelow * $rightBelow);
            }

            //求公倍数算法（分母同化）
            // if (($leftBelow % $rightBelow) === 0 && $leftBelow > $rightBelow) {
            //     // 左分母为最小公倍数
            //     $rightTop *= intval($leftBelow / $rightBelow);
            //     $this->base = ($leftTop - $rightTop)  . "/" . ($leftBelow);
            // } elseif (($rightBelow % $leftBelow) === 0 && $rightBelow > $leftBelow) {
            //     // 右分母为最小公倍数
            //     $leftTop *= intval($rightBelow / $leftBelow);
            //     $this->base = $leftTop - $rightTop  . "/" . ($rightBelow);
            // } else { //俩分母最小公倍数
            //     // $leftTop *= $rightBelow;
            //     // $rightTop *= $leftBelow;
            //     // $leftBelow = $rightBelow = $leftBelow * $rightBelow;
            //     $this->base = ($leftTop * $rightBelow) - ($rightTop * $leftBelow) . "/" . ($leftBelow * $rightBelow);
            // }
        } else {
            $this->base = bcsub($this->base, "$number");
        }
        return $this;
    }
    /**
     * 乘法运算
     *
     * @param integer|float|numstr $number
     * @return self
     */
    public function mul($number): self
    {
        if ($this->isNumerator($number) && !$this->isNumerator()) {
            $this->base = $this->toNumerator($this->base);
        }
        if ($this->isNumerator()) {
            $this->isNumerator($number) === false && $number = $this->toNumerator($number);
            list($leftTop, $leftBelow) = explode("/", $this->base);
            list($rightTop, $rightBelow) = explode("/", $number);
            $this->base = ($leftTop * $rightTop) . "/" . ($leftBelow * $rightBelow);
        } else {
            $this->base = bcmul($this->base, "$number");
        }
        return $this;
    }
    /**
     * 除法运算
     * 
     * 被除数÷除数=商……余数
     *
     * @param integer|float|numstr $number
     * @return self
     */
    public function div($number): self
    {
        if ($this->isNumerator($number) && !$this->isNumerator()) {
            $this->base = $this->toNumerator($this->base);
        }
        if ($this->isNumerator()) {
            $this->isNumerator($number) === false && $number = $this->toNumerator($number);
            list($leftTop, $leftBelow) = explode("/", $this->base);
            list($rightTop, $rightBelow) = explode("/", $number);

            // 蝴蝶算法 1/3 除以 1/2 = (1*2 / 1*3) = 2/3
            $this->base = ($leftTop * $rightBelow) . "/" . ($rightTop * $leftBelow);
        } else {
            $this->base = bcdiv($this->base, "$number");
        }
        return $this;
    }
    /**
     * 取余运算
     *
     * @param int|float $number
     * @return self
     */
    public function mod($number): self
    {
        // if($this->isNumerator($number) === true){
        //     throw new \Exception('The variable type $number must be an int or float');
        // }
        $this->base = bcmod($this->base, "$number");
        return $this;
    }
    /**
     * （非分数）四舍五入
     *
     * @param int $digit
     * @return self
     */
    public function round(int $digit): self
    {
        $this->isNumerator() === false && $this->base = round(floatval($this->base), $digit);
        return $this;
    }
    /**
     * 绝对值
     *
     * @return self
     */
    public function abs(): self
    {
        $this->base = abs($this->base);
        return $this;
    }
    /**
     * 返回常规结果
     * 
     * @param bool $numerator <false> 如果true时,当计算因子含有分数时，返回分数形式
     * @param bool|null $simple <null> 结果为分数时，执行分数化简; FALSE(可能)假分数；TRUE(强制)真分数
     *
     * @return float|string
     */
    public function done(bool $numerator = false, ?bool $simple = null)
    {
        if ($this->isNumerator()) {
            return $numerator === false ? $this->numeratorToNumber($this->base) : $this->numeratorDone($simple);
        }
        return floatval($this->base);
    }

    /**
     * 分数化简(14/6 -> 7/3 或 2又三分之1)
     * 
     * @param string $numerator 待化简分式
     * @param array|null $commonFactors <null> 以引用值形式返回公因数列表
     * @param bool $simple <false> 是否解为真分数( 真分数：二又二分之一；假分数：二分之五)
     * 
     * @return string 化简后的分数
     */
    static function numeratorToSimple($numerator, &$commonFactors = null, $simple = false)
    {
        (new self)->isNumerator($numerator);
        $numerator = str_replace(":", "/", "{$numerator}/1");
        list($top, $below) = explode("/", $numerator);
        $prefix = '';
        if (strrpos($top, ' ')) {
            $top = explode(' ', $top);
            $prefix = $top[0] . ' ';
            $top = $top[1];
        }
        $top *=1;
        $below *=1;
        //假分数转真分数
        if ($simple && $top > $below) {
            $mod = $top % $below;
            if ($mod === 0) {
                return $top . '/' . $below;
            }
            return intval(($top - $mod) / $below) . ' ' . $mod . '/' . $below;
        }
        $top = intval($top);
        $below = intval($below);
        $commonFactors = self::commonFactor($top, $below);
        return $prefix . $top . '/' . $below;
    }

    /**
     * 分数转数值(1/2 -> 0.5)
     * 
     * @return string
     */
    public function numeratorToNumber($number)
    {
        if ($this->isNumerator($number)) {
            list($top, $below) = explode("/", $number);
            if (bcmod($top, $below, 0) === "0") {
                return intval($top / $below);
            }
            return floatval(bcdiv($top, $below));
        }
        return floatval($number);
    }

    /**
     * 数字转 分数表达式 2 -> 2/1; 3.3 -> 3.3/1
     * 
     * @return string
     */
    private function toNumerator($number)
    {
        return "{$number}/1";
    }

    /**
     * 是否分数（比值）
     * 
     * @return boolean
     */
    private function isNumerator(&$number = null)
    {
        $v = false;
        if ($number === null || $number == "") {
            $v = true;
            $number = trim(str_replace(":", "/", $this->base));
        } else {
            $number = trim(str_replace(":", "/", "$number"));
        }
        //真分数转假分数  2又2分之1  -> 2分之5
        if (strrpos($number, ' ')) {
            $s = explode(' ', $number);
            list($top, $below) = explode('/', $s[1]);
            $top += floatval($s[0]) * floatval($below);
            $number = $top . '/' . $below;
        }
        $v && $this->base = $number;
        return !!strpos($number, "/");
    }

    /**
     * 分解公因式
     * 
     * @return array 公因数列表
     */
    private static function commonFactor(int &$a, int &$b)
    {
        static $commonFactors = [];
        static $min = 2;
        for ($num = $min; $num <= min($a, $b); $num++) {
            if (($a % $num === 0) && ($b % $num === 0)) {
                $min = $num;
                $a /= $num;
                $b /= $num;
                $commonFactors[] = $num;
                self::commonFactor($a, $b);
                break;
            }
        }
        return $commonFactors;
    }

    /**
     * 返回分数结果
     * 
     * @param bool|null $simple <null> 结果为分数时，执行分数化简; FALSE(可能)假分数；TRUE(强制)真分数
     * 
     * 
     * @return string
     */
    private function numeratorDone(?bool $simple = null)
    {
        if (!$this->isNumerator()) {
            $this->base = $this->toNumerator($this->base);
        }
        list($top, $below) = explode("/", $this->base);
        if ($simple !== null) {
            if ($simple === true && $below == '1') return $top;
            $zhishu = null;
            return self::numeratorToSimple($this->base, $zhishu, $simple);
        }
        return $this->base;
    }
}

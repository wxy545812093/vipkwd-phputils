<?php
/**
 * @name 中文分词组件
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;
use Vipkwd\Utils\Libs\PhpFenci\PhpFenci,\Exception;

// https://www.cnblogs.com/xiaoqiangink/p/14366099.html
// https://www.cnblogs.com/xuey/p/8683100.html
class Fenci {

    private $options = [];
    private static $_instance = [];
    private $_textOptimize = false;

    private function __construct($options){
        $this->options = array_merge([
            "pri_dict" => false, //是否预载全部词条
            "do_multi" => false, //多元切分
            "do_unit" => true, //新词识别
            "do_fork" => true, //岐义处理
            "do_prop" => false, //词性标注
        ], $options);
    }

    /**
     * 单例入口
     *
     * @param array $options
     * @return self
     */
    static function instance($options):self{
        $k = md5(json_encode($options));
        if(!isset(self::$_instance[$k])){
            self::$_instance[$k] = new self($options);
        }
        return self::$_instance[$k];
    }

    /**
     * 是否优化快递收件信息
     *
     * @param boolean $textOptimize <false>
     * @return void
     */
    public function addressOptimize($textOptimize = false):self{
        $this->_textOptimize = $textOptimize ? true : false;
        return $this;
    }

    /**
     * 获取最终结果字符串
     * ( 以“$septer”(默认 空格)分开后的分词结果 )
     *
     * @param string $str
     * @param string $septer <' '> 结果分隔符
     * @return array
     */
    public function getFinallyResult(string $str, string $septer = ' '):array{

        $this->phpAnalysis($str);

        // 输出分词结果
        return [
            "result" => $this->pa->GetFinallyResult($septer, $this->options['do_prop']),
            "unitWord" => $this->pa->unitWord ? $this->pa->foundWordStr : ""
        ];
    }

    /**
     * 获取粗分结果(不包含粗分属性)
     *
     * @param string $str
     * @return array
     */
    public function getSimpleResult(string $str):array{

        $this->phpAnalysis($str);

        // 输出分词结果
        return [
            "result" => $this->pa->GetSimpleResult($this->options['do_prop']),
            "unitWord" => $this->pa->unitWord ? $this->pa->foundWordStr : ""
        ];
    }

    /**
     * 获取粗分结果(包含粗分属性)
     * 
     * 粗分属性: 1、中文词句
     *          2、ANSI词汇（包括全角）
     *          3、ANSI标点符号（包括全角）
     *          4、数字（包括全角）
     *          5、中文标点或无法识别字符）
     * 
     * @param string $str
     * @return array
     */
    public function getSimpleResultAll(string $str):array{

        $this->phpAnalysis($str);

        // 输出分词结果
        return [
            "result" => $this->pa->GetSimpleResultAll($this->options['do_prop']),
            "unitWord" => $this->pa->unitWord ? $this->pa->foundWordStr : ""
        ];
    }

    /**
     * 获取索引hash数组
     *
     * @param string $str
     * @return array
     */
    public function getFinallyIndex(string $str):array{

        $this->phpAnalysis($str);

        // 输出分词结果
        return [
            "result" => $this->pa->GetFinallyIndex($this->options['do_prop']),
            "unitWord" => $this->pa->unitWord ? $this->pa->foundWordStr : ""
        ];
    }

    private function phpAnalysis(string $str):void{
        //关闭自动载入词典
        PhpFenci::$loadInit = false;

        $this->pa = new PhpFenci('utf-8', 'utf-8', $this->options['pri_dict']);

        // 手动载入词典
        $this->pa->LoadDict();

        // 执行分词
        $this->pa->SetSource( $this->charTextOptimize($str) );

        // 使用最大切分模式对二元词进行消岐
        $this->pa->differMax = $this->options['do_multi'];

        // 尝试合并单字(即是新词识别)
        $this->pa->unitWord = $this->options['do_unit'];

        // 岐义处理: 是否对结果进行优化
        $this->pa->StartAnalysis( $this->options['do_fork'] );
    }

    /**
     * 快递收件地址优化
     *
     * @param string $str
     * @return string
     */
    private function charTextOptimize(string $str):string{
        if($this->_textOptimize){
            $str = str_replace(["（","）"], ["(",")"], trim($str));
            $search = array(
                '收货地址',
                '收件地址',
                '详细地址',
                '地址',

                '收货人',
                '收件人',
                '取件人',
                '联系人',
                '收货',
                '取件',

                '所在地区',
                '邮政编码',
                '邮编',

                '联系电话',
                '电话',
                '手机号码',
                '手机',

                '身份证号码',
                '身份证号',
                '身份证',
                '：', ':',
                '；', ';',
                '，', ',',
                '。', '.'
            );
            $str = str_replace($search, '', $str);
            $str = preg_replace("/\ +/",' ', $str);
        }
        return $str;
    }
}
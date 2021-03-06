<?php
/**
 * @name PHP异步回调
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\Libs\AsyncCallback;
use Vipkwd\Utils\Tools;
use \Exception;

class Async{
	private static $instances = [];
	private $config;

	/**
	 * Class constructor.
	 */
	private function __construct(array $config = []){
		if(!defined('ROOT')){
			$file = realpath(__FILE__);
			$logPath = substr($file, strripos($file, "vendor"));
		}else{
			$logPath = ROOT;
		}
		$this->config = array_merge([
            "auth"      => 'ed4>>95*&^ee4a3>d02c8.#$%^&dc9b19!HGc2cdd~40^33fsdf."}*',
			"desKey"	=> "settimeout.vipkwd.com",
			"desIv"		=> "",
            "logFile"   => rtrim($logPath,'/').'/vipkwd-async.log',
			"debug"		=> false,
            "api"       => "",

			//任务标识
			"taskTag"	=> $_GET['taskTag'] ?? ""
        ], $config);
	}


	/**
     * 单例入口
     * 
     * @param array $config
     * @return self
     */
    static function instance(array $config = []):self{
		$k = md5(json_encode(array_merge(["_"=>0],$config)));
		if(!isset(self::$instances[$k])){
			self::$instances[$k] = new self($config);
		}
		return self::$instances[$k];
	}


	/**
     * 创建任务（设定任务分类标识）
     * 
     * @param string $tag 任务标识
     * @return self
     */
    public function createTask(string $tag):self{
		$this->config['taskTag'] = $tag;
		return $this;
	}

	/**
     * Js版setTimeout的（PHP）简易实现
     * 
     * eg: 每隔3秒调用一次全局函数 funcName ，共调用2次, 耗时：(seconeds + funtion 耗时) * limits
     * setTimeout("funcName", 3, 2)
     * 
     * 
     * eg: 每隔5秒调用一次函数 funcName,仅调用1次(三种写法)
     * setTimeout("funcName", 5)
     * setTimeout("funcName", 5, 1)
     * setTimeout("funcName", 5, [], 1)
     * 
     * setTimeout("funcName", 5, ["orderId"=>"xxxx"])
     * setTimeout("funcName", 5, ["orderId"=>"xxxx"], 2)
     * setTimeout("Demo::func", 5)
     * setTimeout("Namespace\Demo::func", 5)
     *
     * @param string $funcName 函数名（不支持匿名函数)
     *                         "$funcname"
     *                         "Demo::func"
     *                         "Namespace\Demo::func"
     * 
     * @param integer $seconds <10> 延时秒数
     * @param array|integer $args <[]> 
     *                          一维数组： 原样传递到$funName指向的全局函数。
     *                          非一维数组，强制转整型后覆盖第四个参数
     * @param integer $limits <1> 执行次数，默认执行1次
     * @return void
     */
    public function setTimeout(string $funcName, int $seconds = 10, $args = [], int $limits = 1){
        if(!is_array($args)){
            $limits = @intval($args);
            $args = [];
        }
        return (new AsyncCallback($this->config))->setTimeout($funcName, $seconds, $args, $limits);
		return $this;
    }
    
    /**
     * 任务监听入口
     * 
     * @param string|array $taskTags 索引数组：执行批量监听
     * @return boolean
     */
    public function taskWatch($taskTags = "") {

        return 400;
        
        /*
        if(is_string($taskTags)){
            $taskTags = [$taskTags];
        }
        $result = false;
        foreach($taskTags as $_ => $taskTag){
            $taskTag = strval($taskTag);
            if(true === self::checkHeaderTaskTag($taskTag) && $taskTag == @$_GET['taskTag']){
                $this->config['taskTag'] = $taskTag;
                new AsyncCallback($this->config);
                $result = true;
                break; 
            }
        }
        return $result;*/
    }

    private static function checkHeaderTaskTag(string $tagName ): bool{
        $headers = Tools::getHttpHeaders();
        return ($headers['tasktag'] ?? null) == $tagName;
    }

}
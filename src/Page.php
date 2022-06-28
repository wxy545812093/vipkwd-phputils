<?php
/**
 * @name 通用分页类
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

/*
$page = new Vipkwd\Utils\Page([
	"total" => 231,
	"var" => 'pg',

    //生成带有默认值并监听页面参数的变量
    // 如：href=xxxx/memberList.html?uid=101&age=28&ip=127.0.0.1    ==> xxx/memberList.html?pg=1&uid=101&age=28
    // 如：href=xxxx/memberList.html?uid=101&age=                   ==> xxx/memberList.html?pg=1&uid=101&age=
    // 如：href=xxxx/memberList.html?uid=101                        ==> xxx/memberList.html?pg=1&uid=101&age=18
    // 如：href=xxxx/memberList.html                                ==> xxx/memberList.html?pg=1&uid=100&age=18
	"query" => [
		"uid" => "100",
		"age" => 18,
    ],

    //生成不带默认值，并监听页面参数的变量
    // 如：href=xxxx/memberList.html?uid=101&age=28&ip=127.0.0.1    ==> xxx/memberList.html?pg=1&uid=101&age=28
    // 如：href=xxxx/memberList.html?uid=101&age=                   ==> xxx/memberList.html?pg=1&uid=101&age=
    // 如：href=xxxx/memberList.html?uid=101                        ==> xxx/memberList.html?pg=1&uid=101
    // 如：href=xxxx/memberList.html                                ==> xxx/memberList.html?pg=1
    "query" => [
        "uid"=>"",
        "age"=>""
    ],

    //不监听参数（生成的连接仅有页码“var”参数 ！！！）
    "query" => [],
]);
echo $page->fpage();
*/

namespace Vipkwd\Utils;
use Vipkwd\Utils\Type\Str;

class Page{

    private static $_instance = [];

	//数据表中总记录数
	private $total;

	//每页显示行数
	private $listRows;

	//SQL语句使用limit从句,限制获取记录个数
	private $limit;

	//自动获取url的请求地址
	private $uri;

	//总页数
	private $pageNum;

	//当前页  
	private $page;

    //在分页信息中显示内容，可以自己通过set()方法设置
	private $config = [
        'head' => "条", 
        'prev' => "上一页", 
        'next' => "下一页", 
        'first'=> "首页", 
        'last' => "末页"
	];

    private $options = [];

	//默认分页列表显示的个数
	private $listNum = 10;

    private static $field = false;
    
    /**
     * 构造函数
     *
     * @param array $options
     *                  -- total <0> 计算分页的总记录数
     *                  -- limit <20> 设置每页需要显示的记录数
     *                  -- viewLast <false> 是否初始化显示到最后一页
     *                  -- var <page> 指定$_GET捕获页码的变量名
     *                  -- query <[]> 一维关联数组,指定要监听外部查询字段；
     *                      - field1 => "default value" //携带默认值
     *                      - field2 => "" //不带默认值
     * @type public
     * @return object
     */
	public function __construct(array $options) {
        $this->options = array_merge([
            "total" => 0,
            "limit" => 20,
            "query" => [],
            "var"   => "page",
            "viewLast" => false
        ], $options);

        $this->uri = $this->getUri($this->options['query']);
		$this->total = intval($this->options['total']);
		$this->listRows = intval($this->options['limit']);
        
        $this->total = $this->total >=0 ? $this->total : 0;
        $this->listRows = $this->listRows >=0 ? $this->listRows : 20;
		$this->pageNum = ceil($this->total / $this->listRows);
        
        $this->options['var'] = preg_match("/^[A-Z][0-9A-Z_]+/i",$this->options['var']) ? $this->options['var'] : "page";
		/*以下判断用来设置当前面*/
		if(!empty($_GET[ $this->options['var'] ])) {
			$page = intval($_GET[ $this->options['var'] ]);
		} else {
			$page = $this->options['viewLast'] === false ? 1 : $this->pageNum;
		}
		if($this->total > 0) {
			$this->page = (preg_match('/\D/', "$page") ) ? 1 : $page;
		} else {
			$this->page = 1;
		}
        $this->page <= 0 && $this->page = 1;
        $this->page > $this->pageNum && $this->page = $this->pageNum;
		$this->limit = "LIMIT ".$this->setLimit();
	}

	/**
     * 链式设置显示分页的信息
     * 
     * @param  string  $param  是成员属性数组config的下标
     * @param  string  $value  用于设置config下标对应的元素值
     * @return  object \Page
     */
	public function config(string $param, string $value):object{
		if(array_key_exists($param, $this->config)) {
			$this->config[$param] = $value;
		}
		return $this;
	}

	/**
     * 组装并返回分页静态代码
     * 
     * @param integer 0-7的数字分别作为参数，用于自定义输出分页结构和调整结构的顺序，默认输出全部结构
     * @return string 分页信息内容
     */
	public function fpage():string{

		$arr = func_get_args();
		$html[0] = "<span class='p1'>&nbsp;共<b> {$this->total} </b>{$this->config["head"]}&nbsp;</span>";
		// $html[1] = "&nbsp;本页 <b>".$this->disnum()."</b> 条&nbsp;";
		if($this->total > 0){
            $html[2] = "<span class='p2'>&nbsp;本页{<b>{$this->start()}-{$this->end()}</b>}条&nbsp;</span>";
            $html[3] = "<span class='p3'>&nbsp;<b>{$this->page}/{$this->pageNum}</b>页&nbsp;</span>";
            $html[4] = "<span class='p4'>".$this->firstprev();
            $html[5] = $this->pageList();
            $html[6] = $this->nextlast()."</span>";
            $html[7] = "<span class='p5'>".$this->goPage()."</span>";
        }

        $fpage = '<div class="vipkwd-page" style="font:12px \'\5B8B\4F53\',san-serif;">';
        (count($arr) < 1) && $arr = array(0, 1,2,3,4,5,6,7);
        
        for ($i = 0; $i < count($arr); $i++){
            isset($html[$arr[$i]]) && $fpage .= $html[$arr[$i]];
        }
        
		$fpage .= '</div>';
		return $fpage;
	}

    /**
     * 获取私有成员属性limit和page的值
     *
     * @param string $args
     * @return mixed
     */
	public function __get(string $args){
		if($args == "limit" || $args == "page")
		    return $this->$args;
		return null;
	}

	private function setLimit() {
		if($this->page > 0)
		    return ($this->page-1)*$this->listRows.", {$this->listRows}";
		return 0;
	}

	
	private function getUri($query) {
        [$url] = explode("?", $_SERVER['REQUEST_URI']);

        if(self::$field === false){
		    $url .= "?".http_build_query($query);
            $arr = parse_url($url);

            if(isset($arr["query"])) {
                parse_str($arr["query"], $arrs);

                foreach($arrs as $k => $v){
                    //GET 覆盖预定义变量
                    if(isset($_GET[ $k ]) ){
                        $arrs[$k] = Str::htmlEncode( trim($_GET[$k]) );
                    }else{
                        //如果预定义变量 为空，则丢弃参数
                        if($v == ""){
                            unset($arrs[$k]);
                        }
                    }
                }
                if(isset($arrs[$this->options['var']])){
                    unset($arrs[$this->options['var']]);
                }
                $url = $arr["path"].'?'.http_build_query($arrs);
            }
        }else{
            $url .='?';
            $query = array_keys($query);
            foreach($query as $field){
                if(isset($_GET[$field])){
                    $url.= "&".$field."=".$_GET[$field];
                }
            }
        }
		if(strstr($url, '?')) {
			(substr($url, -1)!='?') && $url = $url.'&';
		} else {
			$url = $url.'?';
		}
        return $url;
	}

    /**
     * 获取当前页开始的记录数
     *
     * @return float
     */
	private function start(){
		if($this->total == 0)
		    return 0;
		return ($this->page-1) * $this->listRows+1;
	}
	
    /**
     * 获取当前页结束的记录数
     *
     * @return float
     */
	private function end(){
		return min($this->page * $this->listRows, $this->total);
	}

	/**
     * 获取上一页和首页的操作信息
     *
     * @return string
     */
	private function firstprev():string{
        $str = "";
		if($this->page > 1) {
			$str .= $this->createALink(1, $this->config["first"]);
			$str .= $this->createALink($this->page-1, $this->config["prev"]);
		}
        return $str;
	}
	
    /**
     * 获取页数列表信息
     *
     * @return string
     */
	private function pageList():string{
		$linkPage = "&nbsp;<b>";
		$inum = floor($this->listNum/2);

		/*当前页前面的列表 */
		for ($i = $inum; $i >= 1; $i--) {
			$page = $this->page-$i;
            ($page >= 1) && $linkPage .= $this->createALink($page);
		}
		/*当前页的信息 */
		($this->pageNum > 1) && $linkPage .= "<span style='padding:1px 2px;background:#BBB;color:white'>{$this->page}</span>&nbsp;";
		
        /*当前页后面的列表 */
		for ($i=1; $i <= $inum; $i++) {
			$page = $this->page+$i;
			if($page <= $this->pageNum){
                $linkPage .= $this->createALink($page);
            }else{
			    break;
            }
		}
		$linkPage .= '</b>';
		return $linkPage;
	}

    private function createALink($page, $text = ""){
        return "<a href='".$this->uri.$this->options['var']."={$page}'>".($text ? $text : $page)."</a>&nbsp;";
    }
	/**
     * 获取下一页和尾页的操作信息
     *
     * @return string
     */
	private function nextlast():string{
        $str = "";
		if($this->page != $this->pageNum) {
            $str .= $this->createALink($this->page+1, $this->config["next"]);
            $str .= $this->createALink($this->pageNum, $this->config["last"]);
		}
        return $str;
	}
	
    /**
     * 显示和处理表单跳转页面
     *
     * @return string
     */
	private function goPage():string{
		if($this->pageNum > 1) {
			return '&nbsp;<input style="width:32px;height:17px !important;height:18px;border:1px solid #CCCCCC;" type="number" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>'.$this->pageNum.')?'.$this->pageNum.':this.value;location=\''.$this->uri.$this->options['var'].'=\'+page+\'\'}" value="'.$this->page.'" id="vipkwd-page-input">
                <input style="cursor:pointer;width:30px;height:18px;padding: 1px 7px 2px; border-width: 1px; border-style: solid; border-color: rgb(216, 216, 216) rgb(209, 209, 209) rgb(186, 186, 186); border-image: initial;" type="button" value="GO" onclick="javascript:var page = document.getElementById(\'vipkwd-page-input\').value * 1;page= (page > '.$this->pageNum.') ? '.$this->pageNum.' : page ;location=\''.$this->uri.$this->options['var'].'=\'+page+\'\'">&nbsp;
            ';
		}
        return "";
	}

	/**
     * 获取本页显示的记录条数
     *
     * @return float
     */
	private function disnum(){
		if($this->total > 0) {
			return abs($this->end() - $this->start() +1);
        }
        return 0;
	}
}
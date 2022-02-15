<?php
/**
 * @name 生成mysql数据字典
 * @author vipkwd <service@vipkwd.com>
 * @link https://gitee.com/myDcool/PHP-DBDIC
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs;
use \Exception;

/**
 * 浏览器显示
 * DBdic::ini($dbhost, $dbname, $dbuser, $dbpwd)->outForBrowser();
 * 
 * 浏览器显示, 带左侧菜单
 * DBdic::ini($dbhost, $dbname, $dbuser, $dbpwd)->outForBrowserWithMenu();
 * 
 * 下载word文档
 * DBdic::ini($dbhost, $dbname, $dbuser, $dbpwd)->outForWord();
 * 
 * 只导出一个表
 * DBdic::ini($dbhost, $dbname, $dbuser, $dbpwd)->setExportTable('user')->outForBrowser();
 * 
 * 导出部分表, 支持正则
 * DBdic::ini($dbhost, $dbname, $dbuser, $dbpwd)->setExportTableArray(['user', 'goods_.*', 'product_\d{4}'])->outForBrowser();
 */
class DBdic{
    public $database = array(); //数据库配置
    public $tables = array(); //读取的表信息数组
    public $htmlTable = ''; //表格内容
	public $html = '';
	public $exportTables = array(); // 要导出的表
    public $menu = array(); //左侧表名的菜单
    
	public static function ini(string $host, string $dbname, string $user, string $pwd):self
	{
		return new self($host, $dbname, $user, $pwd);
	}
	
    private function __construct($host, $dbname, $user, $pwd)
    {
        // 配置数据库
        $this->database['DB_HOST'] = $host;
        $this->database['DB_NAME'] = $dbname;
        $this->database['DB_USER'] = $user;
        $this->database['DB_PWD'] = $pwd;
    }
	
	private function build():self
	{
		//链接MySQL
        $mysqli = mysqli_init();
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2); //超时2s
        $mysqli->options(MYSQLI_INIT_COMMAND, "set names utf8mb4;");
        $mysqli->real_connect($this->database['DB_HOST'], $this->database['DB_USER'], $this->database['DB_PWD'], $this->database['DB_NAME']) or die("Mysql connect is error.");
    
        // 取得所有表名
        $rs = $mysqli->query('show tables');
        $arrTableName = array_column($rs->fetch_all(), $value=0);

        // 取得所有表信息
        foreach ($arrTableName as $name) {
			
            $isExport = $this->isNeedExport($name);
			
			if (!$isExport) {
			    continue;
            }
        
            //表注释
            $sql = "select * from information_schema.tables where table_schema = '{$this->database['DB_NAME']}' and table_name = '{$name}' "; //查询表信息
            $rs = $mysqli->query($sql);
            $arrTableInfo = $rs->fetch_assoc();
        
            //各字段信息
            $sql = "select * from information_schema.columns where table_schema ='{$this->database['DB_NAME']}' and table_name = '{$name}' "; //查询字段信息
            $rs = $mysqli->query($sql);
            $arrColumnInfo = $rs->fetch_all(MYSQLI_ASSOC);
        
            //索引信息
            $sql = "show index from {$name}";
            $rs = $mysqli->query($sql);
			if (!empty($rs->num_rows)) {
				$arrIndexInfo = $rs->fetch_all(MYSQLI_ASSOC);
			} else {
				$arrIndexInfo = array();
			}
            
        
            $this->tables[] = array(
                'TABLE' => $arrTableInfo,
                'COLUMN' => $arrColumnInfo,
                'INDEX' => $this->getIndexInfo($arrIndexInfo)
            );
        }
    
        //组装HTML
        $html = '';
        foreach($this->tables as $k => $v)
        {
            //左侧菜单信息
            $this->menu[$k] = $v['TABLE']['TABLE_NAME'];
            
            //主要内容
            $html .= '<table align="center">';
            $html .= '<caption id="menu_'.$k.'"><h3>' . $v['TABLE']['TABLE_NAME'] . ' ' . $v['TABLE']['TABLE_COMMENT'] . '</h3></caption>';
            $html .= '<tbody><tr><th>字段名</th><th>数据类型</th><th>默认值</th><th>允许非空</th><th>索引/自增</th><th>备注(字段数: '. count($v['COLUMN']).')</th></tr>';
        
            foreach ($v['COLUMN'] AS $f) {
                $html .= '<tr>'.PHP_EOL;
                $html .= '<td class="c1">' . $f['COLUMN_NAME']      . '</td>'.PHP_EOL;
                $html .= '<td class="c2">' . $f['COLUMN_TYPE']      . '</td>'.PHP_EOL;
                $html .= '<td class="c3">' . $f['COLUMN_DEFAULT']   . '</td>'.PHP_EOL;
                $html .= '<td class="c4">' . $f['IS_NULLABLE']      . '</td>'.PHP_EOL;
                $html .= '<td class="c5">' . $f['COLUMN_KEY'].' '.$f['EXTRA']. '</td>'.PHP_EOL;
                $html .= '<td class="c6">' . $f['COLUMN_COMMENT']   . '</td>'.PHP_EOL;
                $html .= '</tr>'.PHP_EOL;
            }
        
			if (!empty($v['INDEX'])) {
				$html .= '<tr><th colspan="2">索引名</th><th colspan="4">索引顺序</th></tr>';
				foreach ($v['INDEX'] as $indexName => $indexContent) {
					$html .= '<tr>'.PHP_EOL;
					$html .= '<td class="c7" colspan="2">' . $indexName . '</td>'.PHP_EOL;
					$html .= '<td class="c8" colspan="4">' . implode(' > ', $indexContent) . '</td>'.PHP_EOL;
					$html .= '</tr>'.PHP_EOL;
				}
			}
            
            $html .= '</tbody></table><br>'.PHP_EOL;
        }
        $this->htmlTable = $html;
		
		return $this;
	}
    
	
	/**
     * 设置需要导出的表, 参数为单个表
     *
     * @param string $tableName
     * @return self
     */
    public function setExportTable(string $tableName):self
	{
		$this->exportTables[] = $tableName;
		return $this;
	}
	
	
	/**
     * 设置需要导出的表, 参数为数组
     *
     * @param array $arrTableName
     * @return self
     */
	public function setExportTableArray(array $arrTableName):self
	{
		$this->exportTables = $arrTableName;
		return $this;
	}
	
    /**
     * 判断当前表是否要导出
     *
     * @param string $tname
     * @return boolean
     */
	private function isNeedExport(string $tname):bool
    {
        $isExport = TRUE;
        if (!empty($this->exportTables)) {
        
            if (in_array($tname, $this->exportTables)) {
                //当前表在导出列表中
                $isExport = TRUE;
            } else {
                $isExport = FALSE;
                //正则匹配
                foreach ($this->exportTables as $tableName) {
                
                    if (strpos($tableName, '*') !== FALSE ||
                        strpos($tableName, '+') !== FALSE ||
                        strpos($tableName, '\\') !== FALSE ||
                        strpos($tableName, '[') !== FALSE ||
                        strpos($tableName, '(') !== FALSE ||
                        strpos($tableName, '{') !== FALSE
                    ) {
                        if (preg_match("/$tableName/", $tname, $matches) == 1) {
                            $isExport = TRUE;
                            break;
                        }
                    }
                }
            }
        }
        return $isExport;
    }
	
	/**
     * 整合单个表的所有索引(归纳复合索引)
     *
     * @param array $arrIndexInfo
     * @return array
     */
    private function getIndexInfo(array $arrIndexInfo):array
    {
        $index = array();
        foreach ($arrIndexInfo as $v) {
            $unique = ($v['Non_unique'] == 0) ? '(unique)' : '';
            // $index[$v['Key_name']][] = $v['Seq_in_index'].': '.$v['Column_name'].$unique;
            $index[$v['Key_name']][] = $v['Column_name'].$unique;
        }
        
        return $index;
    }
    
    /**
     * 获取HTML菜单(表名列表)
     *
     * @return string
     */
    private function getHtmlMenu():string
    {
        $html = '<div id="menu"><ul>';
        foreach ($this->menu as $k => $v) {
            $id = 'menu_'.$k;
            $html .= '<li><a href="#'.$id.'">'.$v.'</a></li>';
        }
        $html .= '</ul></div>';
        
        return $html;
    }
    
    /**
     * 输出到浏览器, 左侧有目录
     *
     * @return string
     */
    public function outForBrowserWithMenu():string
    {
        $this->build();
        header("Content-type:text/html;charset=utf-8");
        $html = '<html>
              <meta charset="utf-8">
              <title>'.$this->database['DB_NAME'].'数据字典</title>
              <style>
                ::-webkit-scrollbar {display:none}
                header {display: block; width: 90%; align-content: center}
                #menu {float: left; width: 20%; height: 2000px; overflow-y: scroll}
                a:link,a:visited {color:#000;text-decoration:none;}
                #content {float: left; width: 70%; height: 2000px; overflow-y: scroll}
				table { width: 90%; font-family: Consolas,verdana,arial; font-size:14px; color:#333333; border-width: 1px; border-color: #ddd; border-collapse: collapse; margin-bottom: 5px; }
				table caption { text-align:left; }
				table caption h3 {margin:5px}
				table th { border-width: 1px; padding: 8px; border-style: solid; border-color: #ddd; background-color: #f8f8f8; }
				table td { border-width: 1px; padding: 8px; border-style: solid; border-color: #ddd; background-color: #ffffff; }
				tr:hover td{ background-color:#f1f5fb; }
              </style>
              <body>';
        $html .= '<header><h1 style="text-align:center;">'.$this->database['DB_NAME'].'数据字典</h1>';
        $html .= '<p style="text-align:center;margin:20px auto;">生成时间：' . date('Y-m-d H:i:s') . '  总共：' . count($this->tables) . '个数据表</p></header>';
        $html .= $this->getHtmlMenu();
        $html .= '<div id="content">'.$this->htmlTable.'</div>';
        $html .= '</body></html>';
        
        echo $html;
        // return $this;
    }
    
    /**
     * 输出到浏览器, 表格宽度用百分比
     *
     * @return string
     */
    public function outForBrowser():string
    {
		$this->build();
		
		header("Content-type:text/html;charset=utf-8");
        $html = '<html>
              <meta charset="utf-8">
              <title>'.$this->database['DB_NAME'].'数据字典</title>
              <style>
				table { width: 50%; font-family: Consolas,verdana,arial; font-size:14px; color:#333333; border-width: 1px; border-color: #ddd; border-collapse: collapse; margin-bottom: 5px; }
				table caption { text-align:left; }
				table caption h3 {margin:5px}
				table th { border-width: 1px; padding: 8px; border-style: solid; border-color: #ddd; background-color: #f8f8f8; }
				table td { border-width: 1px; padding: 8px; border-style: solid; border-color: #ddd; background-color: #ffffff; }
				tr:hover td{ background-color:#f1f5fb; }
              </style>
              <body>';
        $html .= '<h1 style="text-align:center;">'.$this->database['DB_NAME'].'数据字典</h1>';
        $html .= '<p style="text-align:center;margin:20px auto;">生成时间：' . date('Y-m-d H:i:s') . '  总共：' . count($this->tables) . '个数据表</p>';
        $html .= $this->htmlTable;
        $html .= '</body></html>';
		
		$this->html = $html;
		echo $html;
		// return $this;
    }
    
    /**
     * 输出到word文档(定宽720px)
     *
     * @return string
     */
    public function outForWord():string
    {
		$this->build();
		
        /* 下载word */
		header("Content-type:text/html;charset=utf-8");
		header("Content-type: application/octet-stream");  
		header("Accept-Ranges: bytes");  
        header("Content-type:application/vnd.ms-word" );
		header('Cache-Control: max-age=0');
        header("Content-Disposition:attachment;filename={$this->database['DB_NAME']}数据字典.docx" );
    
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
				<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>
              <title>'.$this->database['DB_NAME'].'数据字典</title>
              <style>
                body,td,th {font-family:"宋体"; font-size:14px;}
                table,h1,p{width:720px;margin:0px auto;}
                table{border-collapse:collapse;border:1px solid #CCC;background:#efefef;}
                table caption{text-align:left; background-color:#fff; line-height:2em; font-size:14px; font-weight:bold; }
                table th{text-align:left; font-weight:bold;height:20px; line-height:20px; font-size:11px; border:1px solid #CCC;padding-left:5px;}
                table td{height:20px; font-size:11px; border:1px solid #CCC;background-color:#fff;padding-left:5px;}
                .c1{ width: 100px;}
                .c2{ width: 110px;}
                .c3{ width: 50px;}
                .c4{ width: 55px;}
                .c5{ width: 100px;}
                .c6{ width: 300px;}
                .c7{ width: 200px;}
                .c8{ width: 515px;}
              </style>
              <body>';
        $html .= '<h1 style="text-align:center;">'.$this->database['DB_NAME'].'数据字典</h1>';
        $html .= '<p style="text-align:center;margin:20px auto;">生成时间：' . date('Y-m-d H:i:s') . '  总共：' . count($this->tables) . '个数据表</p>';
        $html .= $this->htmlTable;
        $html .= '</body></html>';
		
		$this->html = $html;
		echo $html;
		// return $this;
    }
	
	private function out()
	{
		// echo $this->html;
	}

}
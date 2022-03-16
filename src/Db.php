<?php
/**
 * @name 数据库驱动
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;
use \PDO;
use \Exception;
use Vipkwd\Utils\Error;
use Vipkwd\Utils\Libs\Medoo;

class Db{

    protected static $instance = array();
    private $_fields = "*";
    private $_table = null;
    private $_join = null;
    private $_where = null;
    private $_data = null;
    private $_limit = null;
    private $_order = null;
    private $_group = null;
    private $_having = null;
    private $_beginDebug = false;
    private $_medoo = null;
    
    private function __construct(array $options){

        $options = array_merge([
            // [required]
            "type" => "mysql",
            "host" => "localhost",
            "database" => "name",
            "username" => "your_username",
            "password" => "your_password",



            // [optional] MySQL socket (shouldn't be used with server and port).
            // 'socket' => '/tmp/mysql.sock',

            // Initialized and connected PDO object.
	        //'pdo' => new PDO('mysql:dbname=test;host=127.0.0.1', 'user', 'password');,

            // 'dsn' => [
            //     // The PDO driver name for DSN driver parameter.
            //     'driver' => 'mydb',
            //     // The parameters with key and value for DSN.
            //     'server' => '12.23.34.45',
            //     'port' => '8886'
            // ],


            //'type' => 'sqlite',
            //'database' => 'my/database/path/database.db'
            //'database' => ':memory:'




            // [optional]
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'port' => 3306,
        
            // [optional] Table prefix, all table names will be prefixed as PREFIX_table.
            'prefix' => 'PREFIX_',
        
            // [optional] Enable logging, it is disabled by default for better performance.
            'logging' => true,

            // [optional]
            // Error mode
            // Error handling strategies when error is occurred.
            // PDO::ERRMODE_SILENT (default) | PDO::ERRMODE_WARNING | PDO::ERRMODE_EXCEPTION
            // Read more from https://www.php.net/manual/en/pdo.error-handling.php.
            'error' => PDO::ERRMODE_SILENT,
        
            // [optional]
            // The driver_option for connection.
            // Read more from http://www.php.net/manual/en/pdo.setattribute.php.
            'option' => [
                PDO::ATTR_CASE => PDO::CASE_NATURAL
            ],
        
            // [optional] Medoo will execute those commands after connected to the database.
            'command' => [
                'SET SQL_MODE=ANSI_QUOTES'
            ]
        ],$options);
        
        self::toRealPath($options, 'socket');
        self::toRealPath($options, 'database');
        //https://medoo.in/api/new
        $this->_medoo = new Medoo($options);
    }

    /**
     * 单例入口
     * @param array $options
     * @return object
     */
    public static function instance(array $options)
    {
        $hash = "db".md5(json_encode($options));
        if (!key_exists($hash, self::$instance)) {
            self::$instance[$hash] = new self($options);
        }
        return self::$instance[$hash];
    }

    /**
     * 获取标准PDO接口
     *
     * @return void
     */
    public function pdo(){
        return $this->_medoo->pdo;
    }

    /**
     * 切换/选择数据表
     *
     * @param string $tbName
     * @return Object
     */
    public function table(string $tbName):self{
        $this->_table = $tbName;
        return $this;
    }

    /**
     * 配置查询字段
     *
     * @param string|array $fields
     * @return Object
     */
    public function field($fields ="*"):self{
        if($fields !="*" && is_string($fields)){
            $fields = preg_replace("/\ +/", " ",$fields);
            $fields = explode(',', $fields);
        }
        array_walk($fields, function(&$value, $key){
            $value = trim($value);
        });
        $this->_fields = $fields;
        return $this;
    }
    /**
     * 设置操作过滤条件
     * https://medoo.in/api/where
     *
     * @param array $where
     * @return Object
     */
    public function where(array $where = []):self{
        $this->_where = $where;
        return $this;
    }

    /**
     * 配置链表关系
     *
     * @param array $join
     * @return Object
     */
    public function join(array $join = []):self{
        $this->_join = $join;
        return $this;
    }

    /**
     * 设置操作目标数据
     *
     * @param array $data
     * @return Object
     */
    public function data(array $data):self{
        $this->_data = $data;
        return $this;
    }

    /**
     * 按偏移量获取limit条数记录
     *
     * @param integer $limit <10>
     * @param integer $offset <0>
     * @return Object
     */
    public function limit(int $limit = 10,int $offset = 0):self{
        $this->_limit = [$offset, $limit];
        return $this;
    }


    /**
     * 按页码获取limit条数记录
     *
     * @param integer $pageNum 页码
     * @param integer $pageLimit 每页数据条数
     * @return Object
     */
    public function page(int $pageNum=1, $pageLimit=10):self{
        $pageNum = $pageNum <=1 ? 1 : $pageNum;
        $this->_limit = [ ($pageNum-1) * $pageLimit , $pageLimit];
        return $this;
    }

    /**
     * order by
     *
     * @param string|array $order
     * @return Object
     */
    public function order($order):self{
        $this->_order = $order;
        return $this;
    }
    /**
     * GROUP
     *
     * @param string|array $group
     * @return Object
     */
    public function group($group):self{
        $this->_group = $group;
        return $this;
    }

    /**
     * having
     *
     * @param array $havingArr
     * @return Object
     */
    public function having(array $havingArr):self{
        $this->_having = $havingArr;
        return $this;
    }

    /**
     * 多条查询，支持回调遍历获取
     *
     * @param callable|null $callback
     * @return array
     */
    public function select(callable $callback = null){
        $this->checkTable();
        return $this->watchException(function($that)use($callback){
            return $that->_cmd("select", true, $callback);
        });
    }

    /**
     * 返回条件内的一行数据
     *
     * @return void
     */
    public function get(){
        $this->checkTable();

        return $this->watchException(function($that){
            return $that->_cmd("get", true);
        });
    }

    /**
     * 插入数据到表中
     *
     * array数据将自动JSON化存储
     * 
     * @param string|null $primaryKey 主要为Oracle时指定主键
     * @return string
     */
    public function insert(string $primaryKey=null):string{
        if($this->_data == null){
            return $this->outputError("Missing data");
        }
        if(strtolower($this->_medoo->type) == 'oracle'){
            if($primaryKey == ""){
                $this->outputError("Oracle database, please specify the primary key field");
            }
            $this->_medoo->insert($this->_table, $this->_dataArrayToJson($this->_data), $primaryKey);
        }else{
            $this->_medoo->insert($this->_table, $this->_dataArrayToJson($this->_data));
        }
        return $this->lastInsertId();
    }

    /**
     * 批量插入数据到表中 
     *
     * array数据将自动JSON化存储
     * 
     * @return integer
     */
    public function insertAll():int{
        if($this->_data == null){
            return $this->outputError("Missing data");
        }
        $_data = array_filter($this->data, function($val, $key){
            if( "$key" == strval(intval($key)) && $key >= 0){
                return true;
            }
            return false;
        },1);

        if(empty($_data) || count($_data) != count($this->_data) ){
            return $this->outputError("Error in index array format");
        }
        foreach($_data as $k => $v){
            $_data[$k] = $this->_dataArrayToJson($v);
        }
        $result = $this->_medoo->insert($this->_table, $_data);
        unset($_data);
        return $result->rowCount *1;
    }

    /**
     * 返回最后插入的行ID
     *
     * @return void
     */
    public function lastInsertId(){
        return $this->_medoo->id();
    }

    /**
     * 修改表数据
     *
     * @param array $data
     * @return integer
     */
    public function update(array $data = []):int{
        if(!empty($data)){
            $this->_data = $data;
        }
        if($this->_data == null){
            return $this->outputError("Missing data");
        }

        if(empty($this->_where)){
            return $this->outputError("Missing the where condition"); 
        }
        $this->buildWhereCondition();
        $result=$this->_medoo->update($this->_table, $this->_dataArrayToJson($this->_data), $this->_where);
        return $result->rowCount() *1;
    }

    /**
     * 删除表中条件内的数据
     *
     * 危险的操作，操作前请考虑好
     * 
     * @return integer
     */
    public function delete():int{
        if(empty($this->_where)){
            return $this->outputError("Missing the where condition"); 
        }
        $this->buildWhereCondition();
        $result=$this->_medoo->delete($this->_table,$this->_where);
        return $result->rowCount() * 1;
    }

    /**
     * 将新的数据替换旧的数据
     *
     * @param array $columns
     * @return int
     */
    public function replace(array $columns):int{
        if(!empty($column)){

            if(empty($this->_where)){
                return $this->outputError("Missing the where condition"); 
            }
            $this->buildWhereCondition();
            $result = $this->_medoo->replace($this->_table, $column, $this->_where);
            return $result->rowCount;
        }
        return 0;
    }

    /**
     * 检测条件内数据是否存在
     *
     * @return boolean
     */
    public function has():bool{
        $this->checkTable();
        return $this->watchException(function($that){
            return $that->_cmd("has", false);
        });
    }

    /**
     * 随机获取条件内数据
     *
     * @return array
     */
    public function random():array{
        $this->checkTable();
        return $this->watchException(function($that){
            return $that->_cmd("rand", true);
        });
    }

    /**
     * 获取数据表中的行数
     *
     * @return integer
     */
    public function count():int{
        $this->checkTable();
        return $this->watchException(function($that){
            return $that->_cmd("count", false);
        });
    }

    /**
     * 获得某个列中的最大的值
     *
     * @return integer
     */
    public function max():int{
        $this->checkTable();
        return $this->watchException(function($that){
            return $that->_cmd("max", true);
        });
    }

    /**
     * 获得某个列中的最小的值
     *
     * @return integer
     */
    public function min():int{
        $this->checkTable();
        return $this->watchException(function($that){
            return $that->_cmd("min", true);
        });
    }

    /**
     * 获得某个列字段的平均值
     *
     * @return integer
     */
    public function avg():int{
        $this->checkTable();
        return $this->watchException(function($that){
            return $that->_cmd("avg", true);
        });
    }

    /**
     * 某个列字段相加
     *
     * @return integer
     */
    public function sum():int{
        $this->checkTable();
        return $this->watchException(function($that){
            return $that->_cmd("sum", true);
        });
    }

    /**
     * 启动一个事务
     * 
     * 不是每个数据库引擎都支持事务,你必须在使用前检查
     *
     * @param callable $callback 事务内执行的方法,如果返回false，则回滚事务
     * @return void
     */
    public function action(callable $callback):void{
        $that = $this;
        $this->_medoo->action(function($medoo) use($that, $callback){
            return $callback($that);
        });
        unset($that);
    }

    /**
     * 生成原始SQL表达式优化语句
     *
     * @param string $expression 如：'DATE_ADD(:today, INTERVAL 10 DAY)'
     * @param array $map 如： [ ':today' => $today ]
     * @return string
     */
    public function raw(string $expression, $map=[]):string{
        return $this->_medoo::raw($expression, $map);
    }

    /**
     * 开启调试模式
     *
     * @return void
     */
    public function beginDebug():void{
        $this->_beginDebug = true;
        $this->_medoo->beginDebug();
    }

    /**
     * 获取调试模式下SQL语句
     *
     * @return array
     */
    public function debugLog():array{

        if(!$this->_beginDebug){
            $this->outputError("Debugging mode must be turned on first");
        }

        return $this->_medoo->debugLog();
    }

    /**
     * 获取前序所有SQL
     *
     * @return array
     */
    public function log():array{
        return $this->_medoo->log();
    }

    /**
     * 获取最后一条查询语句
     *
     * @return string
     */
    public function last():string{
        return $this->_medoo->last();
    }
    
    /**
     * 获取数据库连接信息
     *
     * @return array
     */
    public function info():array{
        return $this->_medoo->info();
    }

    public function query($sql){
        return $this->_medoo->query($sql)->fetchAll();
    }

    private function _dataArrayToJson(array $data){
        $list = [];
        foreach($data as $k => $v){
            if(is_array($v)){
                $kk = str_ireplace('[JSON]','', $k);
                $list[ $kk."[JSON]" ] = $v;
                unset($data[$k], $kk);
            }
            unset($k, $v);
        }
        return array_merge($data, $list);
    }

    private function outputError(string $message){
        throw new Exception($message);
        exit;
    }

    private function checkTable():void{
        if(!$this->_table){
            $this->outputError("Missing Table");
        }
    }

    private function watchException(callable $function){
        try{
            return $function($this);
        }
        catch(Exception $e){
            return $this->outputError($e->getMessage());
        }
    }

    private function _cmd(string $command, bool $fieldsEnable=true, ?callable $callback=null){
        $this->buildWhereCondition();
        if($this->_join){
            if(!$fieldsEnable){
                return $this->_medoo->$command(
                    $this->_table,
                    $this->_join,
                    $this->_where
                );
            }
            return $this->_medoo->$command(
                $this->_table,
                $this->_join,
                $this->_fields,
                $this->_where,
                $callback
            );
        }
        if(!$fieldsEnable){
            return $this->_medoo->$command(
                $this->_table,
                $this->_where
            );
        }
        return $this->_medoo->$command(
            $this->_table,
            $this->_fields,
            $this->_where,
            $callback
        );
    }

    /**
     * where组装
     *
     * @return void
     */
    private function buildWhereCondition():void{
        if(!is_array($this->_where)) $this->_where = [];
        if($this->_limit) $this->_where['LIMIT'] = $this->_limit;
        if($this->_group) $this->_where['GROUP'] = $this->_group;
        if($this->_order) $this->_where['ORDER'] = $this->_order;
        if($this->_having) $this->_where['HAVING'] = $this->_having;
    }

    /**
     * 获取REALPATH
     *
     * @param array|string $path
     * @param string $arrKey
     * @return void
     */
    private static function toRealPath(&$path, $arrKey=""){
        if(is_array($path)){
            if($arrKey && isset($path[$arrKey]) && is_file($path[$arrKey])){
                $path[$arrKey]= realpath($path[$arrKey]);
                return $path[$arrKey];
            }
        }else{
            if(!empty($path) && is_file($path)){
                $path = realpath($path);
                return $path;
            }   
        }
        return "";
    }

    /**
     * destruct 关闭数据库连接
     */
    public function __destruct()
    {
        //$this->_medoo = null;
        // $this->_debug = false;
        // $this->_debugAndExit = false;
        $this->_where = [];

        //保留（不清空）前一次的操作表，简化下一次对同表操作时的链式语句
        //$this->_table = null;

        $this->_join = null;
        $this->_fields = "*";
        $this->_data = null;
        $this->_limit = null;
        $this->_order = null;
        $this->_group = null;
        $this->_having = null;
        $this->_beginDebug = false;
    }
}

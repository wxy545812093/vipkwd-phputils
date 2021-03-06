<?php
/**
 * @name Mysql
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Db;
use \PDO;
use \Exception;
use Vipkwd\Utils\Error;
use Vipkwd\Utils\Libs\Medoo;

class Mysql{

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
            'prefix' => '',

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
     * ????????????
     * @param array $options = [
     *      host=localhost,
     *      database=name,
     *      username=your_username,
     *      password=your_password,
     *      port=3306,
     *      prefix='',
     *      charset=utf8mb4
     * ]
     *
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
     * ????????????PDO??????
     *
     * @return void
     */
    public function pdo(){
        return $this->_medoo->pdo;
    }

    /**
     * ??????/???????????????
     *
     * @param string $tbName
     * @return Object
     */
    public function table(string $tbName):Object{
        $this->_table = $tbName;
        return $this;
    }

    /**
     * ??????????????????
     *
     * @param string|array $fields
     * @return Object
     */
    public function field($fields ="*"):Object{
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
     * ????????????????????????
     * https://medoo.in/api/where
     *
     * @param array $where
     * @return Object
     */
    public function where(array $where = []):Object{
        $this->_where = $where;
        return $this;
    }

    /**
     * ??????????????????
     *
     * @param array $join
     * @return Object
     */
    public function join(array $join = []):Object{
        $this->_join = $join;
        return $this;
    }

    /**
     * ????????????????????????
     *
     * @param array $data
     * @return Object
     */
    public function data(array $data):Object{
        $this->_data = $data;
        return $this;
    }

    /**
     * ??????????????????limit????????????
     *
     * @param integer $limit <10>
     * @param integer $offset <0>
     * @return Object
     */
    public function limit(int $limit = 10,int $offset = 0):Object{
        $this->_limit = [$offset, $limit];
        return $this;
    }


    /**
     * ???????????????limit????????????
     *
     * @param integer $page ??????
     * @param integer $limit ??????????????????
     * @return Object
     */
    public function page(int $page=1, $limit=10):Object{
        $page = $page <= 1 ? 1 : $page;
        $this->_limit = [ ($page-1) * $limit , $limit];
        return $this;
    }

    /**
     * order by
     *
     * @param string|array $order
     * @return Object
     */
    public function order($order):Object{
        $this->_order = $order;
        return $this;
    }
    /**
     * GROUP
     *
     * @param string|array $group
     * @return Object
     */
    public function group($group):Object{
        $this->_group = $group;
        return $this;
    }

    /**
     * having
     *
     * @param array $havingArr
     * @return Object
     */
    public function having(array $havingArr):Object{
        $this->_having = $havingArr;
        return $this;
    }

    /**
     * ???????????????????????????????????????
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
     * ??????????????????????????????
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
     * ?????????????????????
     *
     * array???????????????JSON?????????
     * 
     * @param string|null $primaryKey ?????????Oracle???????????????
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
     * ??????????????????????????? 
     *
     * array???????????????JSON?????????
     * 
     * @return integer
     */
    public function insertAll(){
        if($this->_data == null){
            return $this->outputError("Missing data");
        }

        // _data ??????????????????
        $_data = array_filter($this->_data, function($val, $key){
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
     * ????????????????????????ID
     *
     * @return void
     */
    public function lastInsertId(){
        return $this->_medoo->id();
    }

    /**
     * ???????????????
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
        return $result ? $result->rowCount() *1 : 0;
    }

    /**
     * ??????????????????????????????
     *
     * ???????????????????????????????????????
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
     * ???????????????????????????
     *  
     * $database->replace("account",
     *    [
     *      "type" => [ "user" => "new_user", "business" => "new_business" ],
     *      "groups" => [ "groupA" => "groupB" ]
     *    ],
     *    [ "user_id[>]" => 0 ]
     * );
     * 
     * UPDATE `account` SET type = REPLACE(`type`, 'user', 'new_user'), type = REPLACE(`type`, 'business', 'new_business'), groups = REPLACE(`groups`, 'groupA', 'groupB') WHERE `user_id` > 0
     * @param array $columns
     * @return void
     */
    public function replace(array $columns){
        if(!empty($columns)){

            if(empty($this->_where)){
                return $this->outputError("Missing the where condition"); 
            }
            $this->buildWhereCondition();
            $result = $this->_medoo->replace($this->_table, $columns, $this->_where);
            return $result->rowCount;
        }
        return 0;
    }

    /**
     * ?????????????????????????????????
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
     * ???????????????????????????
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
     * ???????????????????????????
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
     * ?????????????????????????????????
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
     * ?????????????????????????????????
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
     * ?????????????????????????????????
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
     * ?????????????????????
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
     * ??????????????????
     * 
     * ??????????????????????????????????????????,???????????????????????????
     *
     * @param callable $callback ????????????????????????,????????????false??????????????????
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
     * ????????????SQL?????????????????????
     *
     * @param string $expression ??????'DATE_ADD(:today, INTERVAL 10 DAY)'
     * @param array $map ?????? [ ':today' => $today ]
     * @return string
     */
    public function raw(string $expression, $map=[]):string{
        return $this->_medoo::raw($expression, $map);
    }


    /**
     * chunk??????????????????
     *
     * @param integer $limit <10>
     * @param callable $callback
     * @param integer $stime ????????????  ????????? ??????time()
     * @return integer ?????????
     */
    public function chunk(int $limit = 10, callable $callback, $stime = null):int{
        (!$stime || $stime <= 0) && $stime = time();

        if(!$callback || !is_callable($callback)){
            return 0;
        }
        $totals = $this->count();
        if($totals == 0){
            return time() - $stime;
        }
        $pages = ceil( $totals / $limit);
        $page = 1;
        while( $page <= $pages){
            $resultSet = $this->page($page, $limit)->select();
            if(false === call_user_func($callback, $resultSet)){
                unset($resultSet);
                break;
            }
            unset($resultSet);
            $page++;
        }
        unset($totals, $pages, $page, $callback, $limit);
        return time() - $stime;
    }


    /**
     * ??????????????????
     *
     * @return void
     */
    public function beginDebug():void{
        $this->_beginDebug = true;
        $this->_medoo->beginDebug();
    }

    /**
     * ?????????????????????SQL??????
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
     * ??????????????????SQL
     *
     * @return array
     */
    public function log():array{
        return $this->_medoo->log();
    }

    /**
     * ??????????????????????????????
     *
     * @return string
     */
    public function last():string{
        return $this->_medoo->last();
    }
    
    /**
     * ???????????????????????????
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
     * where??????
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
     * ??????REALPATH
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
     * destruct ?????????????????????
     */
    public function __destruct()
    {
        //$this->_medoo = null;
        // $this->_debug = false;
        // $this->_debugAndExit = false;
        $this->_where = [];

        //?????????????????????????????????????????????????????????????????????????????????????????????
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
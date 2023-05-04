<?php

/**
 * @name Mongo
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @link https://www.cnblogs.com/wujuntian/p/8352586.html
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Db;

use \MongoDB\Client;
use \Vipkwd\Utils\Dev;

use MongoDB\Driver\{Manager, BulkWrite, WriteConcern, Query, Command, WriteResult};
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Exception\InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex as MongoRegex;

class Mongo
{

    private $table;
    private $fields;
    private $filters;
    private $options;
    private $sorts;
    private $client;
    private $collection;

    private $limit;
    private $usePage;

    public function __construct($dsn = "127.0.0.1:27017", $username = '', $password = '')
    {
        $dsn = strtolower($dsn);
        (substr($dsn, 0, 10) != 'mongodb://') && $dsn = "mongodb://" . $dsn;
        $uri = "mongodb://username:password@host/database";
        if($username){
            $dsn = str_replace("mongodb://", "mongodb://{$username}@{$password}/", $dsn);
        }
        $this->client = new Client($dsn);
        $this->_init();
    }

    /**
     * ID字符串转BSON对象
     * 
     * @return ObjectId
     */
    static function mongoId(string $id): Object
    {
        return new ObjectId($id);
    }

    /**
     * 刷新查询对象（初始化connect）
     * 
     * @return self
     */
    public function flush()
    {
        $this->_init();
        return $this;
    }

    /**
     * 切换访问目标集合
     * @param string $table 格式：数据库.集合 如 demo.user db_name.collection_name
     * 
     * @return self
     */
    public function table(string $table): self
    {
        $this->flush();
        $this->table = $table;

        list($collection, $tb) = explode('.', $table);

        $this->collection = $this->client->selectCollection($collection, $tb);

        return $this;
    }

    /**
     * 配置响应字段
     *
     * @param string $fields 'field1,field2,field3'
     * @param boolean $exclude <false> 是否排除指定字段
     * 
     * @return self
     */
    public function field(string $fields = '*', $exclude = false)
    {
        $fields = str_replace(' ', '', $fields);
        if ($fields == '*' || $fields == '') {
            $this->fields = [];
            return $this;
        }

        $fields = explode(',', $fields);
        if (!empty($fields)) {
            $excludes = array_pad([], count($fields), $exclude ? 0 : 1);
            $fields = array_combine($fields, $excludes);
            unset($exclude, $excludes);
        }

        if (!empty($fields)) {
            // $this->fields = array_merge($this->fields, $fields);
            $this->fields = $fields;
        }
        return $this;
    }

    /**
     * 设置查询条件
     *
     * ->where("city", "北京")
     * ->where('user_id', null)
     * ->where(['eq', 'age', 18])
     * ->where(['=', 'sex', 1])
     * ->where(['=',"user_id", "231114"]);
     *
     * ->where([ ['=', 'sex', 1], ['=', 'field', 'value], ... ])
     *
     * ->where(['rex', "page_url", ["rule", 'g']]) //rule 不需要边界符合
     * ->where(['rex', "client_ip", ["127.*"]]) //匹配 client_id=127.xxx.xxx.xxx
     *
     * ->where(['>', "times", 1654660935 ]);
     * ->where(['<', "times", 1654660935 ]);
     * ->where(['!=', "times", 1654660935 ]);
     * ->where(['>=', "times", 1654660935 ]);
     * ->where(['<=', "times", 1654660935 ]);
     *
     * ->where(['or', [ ["=", 'page_url', 'pages/index/index'], ["rex", "page_url", ['pages/kefu/*', '']], ... ]])
     *
     * ->where(['range','times', [1653917590, 1653917650]]);// $max > field_value > $min
     * ->where(['between','times', [1653917590, 1653918650]]);// $max >= field_value >= $min
     * ->where(['notin','page_action', ["wx.onShow", "wx.onHide"]]);
     *
     * ->where(['in','page_action', ["wx.onShow", "wx.onHide"]]);
     * ->where(['page_action' =>["wx.onShow", "wx.onHide"]]);
     *
     * @return self
     */
    public function where()
    {
        $filters = func_get_args();
        $num = count($filters);
        if ($num > 1) {
            $filters = ['=', $filters[0], $filters[1]];
        } else if ($num === 0) {
            $filters = [];
        } else {
            $filters = $filters[0];
        }
        $this->filters = array_merge($this->filters, self::filter($filters));
        unset($filters);
        return $this;
    }

    /**
     * 分页数据
     *
     * @param int $page
     * @param int $limit 10
     * @param bool $next <false>
     * @return self|array
     *
     */
    public function page($page = 1, $limit = 10, bool $next = false)
    {
        $totals = $this->count();

        $endPage = intval(ceil($totals / $limit));
        $page = intval($page);

        if ($page > $endPage) {
            $page = $endPage;
        }
        ($page < 1) && $page = 1;

        $skip = ($page - 1) * $limit;

        $this->usePage = true;

        if ($next === false) {
            $data['page'] = $page;
            $data['pages'] = $endPage;
            $data['totals'] = $totals;

            $options = [];

            !empty($this->filters) && $options[] = [ '$match' => $this->filters ];
            !empty($this->sorts) && $options[] = [ '$sort' => $this->sorts ];//sort顺序不能变，否则会造成排序混乱，注意先排序再分页
            $options[] = ['$limit'=> $this->limit > 0 ? $this->limit : $limit];
            $options[] = ['$skip'=> $skip];


            //查询指定字段
            if (!empty($this->fields)) {
                $options[] = ['projection' => $this->fields];
            }

            $result = $this->collection->aggregate($options);
            $data['list'] = [];
            foreach ($result as $doc) {
                $doc = (array)$doc;
                if (isset($doc['_id'])) {
                    $doc['_id'] = ((array)$doc['_id'])['oid'];
                }
                $data['list'][] = $doc;
            }

            return $data;
        }
        return $this;
    }


    /**
     * 排序
     * @param string|array $sorts
     *
     * @return self
     */
    public function sort($sorts)
    {
        $this->sorts = ["_id" => 1];
        if (is_string($sorts)) {
            $fields = explode(',', $sorts);
            foreach ($fields as $field) {
                $field = explode(" ", trim($field));
                $field[1] = (!isset($field[1])) ? 1 : ((strtolower($field[1]) == 'desc' || intval($field[1]) <= 0) ? -1 : 1);
                if ($field[0] = trim($field[0])) {
                    $this->sorts[($field[0])] = $field[1];
                }
                unset($field);
            }
        } else if (is_array($sorts)) {
            // $this->sorts = [];
            foreach ($sorts as $field => $sort) {
                if ($field = trim($field)) {
                    $this->sorts[trim($field)] = (strtolower($sort) == 'desc' || intval($sort) <= 0) ? -1 : 1;
                }
            }
        }
        return $this;
    }

    /**
     * 总条数
     *
     * @return integer
     *
     */
    public function count()
    {
        return $this->collection->countDocuments(empty($this->filters) ? (object)[] : $this->filters, $this->options);
    }

    /**
     * 单条查询
     *
     * @return array
     */
    public function find()
    {
        $this->limit = 1;
        $items = $this->select();
        if (!empty($items)) {
            $items = $items[0];
        }
        return $items;
    }

    protected function findOneAndDelete(){
        return $this->collection->findOneAndDelete($this->filters, array_merge($this->options, ['limit' => 1]))->getDeletedCount();
    }

    /**
     * 查询
     *
     * @return array
     */
    public function select()
    {
        $calcOptions = ['sort' => $this->sorts];
        if ($this->limit > 0) {
            $calcOptions['limit'] = $this->limit;
        }
        //查询指定字段
        if (!empty($this->fields)) {
            $calcOptions['projection'] = $this->fields;
        }

        return $this->collection->find($this->filters, array_merge($this->options, $calcOptions) )->toArray();
    }

    /**
     * 字段排重
     * 
     * @return mixed[]
     */
    public function distinct($field){
        return $this->collection->distinct($field, $this->filters, $this->options);
    }


    /**
     * 写入数据，返回写入记录条数
     *
     * @param array $data
     *
     * @return string|null
     */
    public function insert(array $data){

        $ret = (array)$this->collection->insertOne($data)->getInsertedId();
        if (isset($ret['oid'])) {
            return $ret['oid'];
        }
        return '';
    }

    /**
     * 批量写入
     * 
     * @param array $datas
     * 
     * @return array
     */
    public function insertAll(array $datas){

        $ret = (array)$this->collection->insertMany($datas)->getInsertedIds();
        $items = [];
        foreach( $ret as $obj){
            $obj = (array)$obj;
            if (isset($obj['oid'])) {
                $items[] = $obj['oid'];
            }
        }
        return $items;
    }

    /**
     * 更新内容
     *
     * @param array $data
     * @param bool $upsert <false> true:记录不存在时自动写入
     *
     * @return int|null
     */
    public function update(array $data, bool $upsert = false)
    {
        return $this->collection->updateMany($this->filters, ['$set' => $data], ['multi' => true, 'upsert' => $upsert])->getModifiedCount();
    }

    protected function findOneAndUpdate(array $data, bool $upsert = false){
        return $this->collection->findOneAndUpdate($this->filters, ['$set' => $data], ['multi' => true, 'upsert' => $upsert]);
    }

    /**
     * 替换内容
     *
     * @param array $replacement
     * @param bool $upsert <false> true:记录不存在时自动写入
     *
     * @return int|null
     */
    public function replace(array $replacement, bool $upsert = false)
    {
        return $this->collection->replaceOne($this->filters, $replacement, array_merge($this->options, ['multi' => false, 'upsert' => $upsert]))->getModifiedCount();
    }

    protected function findOneAndReplace(array $replacement, bool $upsert = false){

        return $this->collection->findOneAndReplace($this->filters, ['$set' => $replacement], ['multi' => true, 'upsert' => $upsert]);

    }
    /**
     * 仅删除匹配记录中的第一条
     *
     * @return int|null
     */
    public function deleteOne()
    {
        return $this->collection->deleteOne($this->filters, array_merge($this->options, ['limit' => 1]))->getDeletedCount();
    }

    /**
     * 删除所有匹配记录条目()
     *
     * @return int|null
     */
    public function delete()
    {
        return $this->collection->deleteMany($this->filters, array_merge($this->options, ['limit' => 0]))->getDeletedCount();
    }

    /**
     * 设置预期受众记录条数
     * 
     * @return self
     */
    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 获取(集合全部)索引列表
     * 
     * @return \MongoDB\Model\IndexInfoIterator
     */
    public function indexList(){
        return $this->collection->listIndexes($this->options);
    }

    /**
     * 建立新索引
     * 
     * @param array|object $key
     * 
     * @return string
     */
    public function createIndex($key){

        return $this->collection->createIndex($key, $this->options);
    }

    /**
     * 批量添加索引
     * @param array[] $keys List of index specifications
     * 
     * @return string[]
     */
    public function createIndexs(array $keys){

        return $this->collection->createIndexs($keys, $this->options);
    }

    /**
     * 删除索引
     * 
     * @return array|object
     */
    public function dropIndex(string $indexName){

        return $this->collection->dropIndex($indexName, $this->options);
    }

    /**
     * 删除集合全部索引
     * 
     * @return array|object
     */
    public function dropAllIndex(){
        return $this->collection->dropIndexes($this->options);
    }

    /**
     * 删除集合
     * 
     * @return array|object
     */
    public function dropCollection(){
        return $this->collection->drop($this->options);
    }
    /**
     * 删除数据库
     * 
     * @return array|object
     */
    public function dropDatabase(string $dbName){
        $this->client->dropDatabase($dbName, $this->options);
    }

    /**
     * 查看数据库列表
     * 
     * @return \MongoDB\Model\DatabaseInfoIterator
     */
    public function showDatabase(string $dbName){
        $this->client->listDatabases($dbName, $this->options);
    }

    /**
     * Debug Info
     * 
     * @return array
     */
    public function info(){
        return $this->collection->__debugInfo();
    }
    /**
     * 获取当前集合名称
     *
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collection->getCollectionName();
    }

    /**
     * 获取当前数据库名
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->collection->getDatabaseName();
    }

    /**
     * 获取当前集合空间.
     *
     * @see https://docs.mongodb.org/manual/reference/glossary/#term-namespace
     * 
     * @return string
     */
    public function getNamespace()
    {
        return $this->collection->getNamespace();
    }

    protected function aggregate(){

        $options = [];

        !empty($this->filters) && $options[] = [ '$match' => $this->filters ];
        !empty($this->sorts) && $options[] = [ '$sort' => $this->sorts ];//sort顺序不能变，否则会造成排序混乱，注意先排序再分页

        if ($this->limit > 0) {
            $options[] = ['limit'=> $this->limit];
        }
        //查询指定字段
        if (!empty($this->fields)) {
            $options[] = ['projection' => $this->fields];
        }

        $options += $this->options;

        $result = $this->collection->aggregate($options);
        $items = [];
        foreach ($result as $doc) {
            $doc = (array)$doc;
            if (isset($doc['_id'])) {
                $doc['_id'] = ((array)$doc['_id']);//['oid'];
            }
            $items[] = $doc;
        }
        return $result;
    }

    private function _init()
    {
        $this->usePage = false; //默认当做常规查询（不使用分页查询方法)
        $this->limit = -1;
        // $this->fields = ["_id" => 1]; // 0 默认不查询_id字段
        $this->fields = []; // 0 默认不查询_id字段
        $this->filters = [];
        $this->options = [];
        $this->sorts = ["_id" => 1];
        // $this->table = 'test.test';
        $this->collection = null;
        // $this->lastQuery = null;不清空(保留上一次查询)
    }

    private static function filter($filters)
    {
        $filter = [];
        try {
            //标准模式 ["fps", "field", "value"]
            if (count($filters) == 3 && array_sum(array_keys($filters)) == 3 && !is_array($filters[2])) {
                return self::condition($filters);
            }

            //简化模式 ['or', ['city'=>'北京', 'pid'=> 2]]
            if (count($filters) === 2 && array_sum(array_keys($filters)) == 1 && !is_array($filters[0])) {
                // Dev::dump($filters,1);
                return self::condition($filters);
            }

            foreach ($filters as $k => $v) {

                //多维数组模式
                // [ ["fps1", "field1", "value1"], ["fps2", "field2", "value2"]...]
                if ($k > 0 || $k === 0) {

                    //子数组 ["fps1", "field1", "value1"]
                    if (is_array($v)) {
                        foreach ($v as $vv) {
                            //三维
                            if (is_array($vv)) {
                                $filter = array_merge($filter, self::condition($vv));
                                continue;
                            }
                            //二维是末端 ["fps1", "field1", "value1"]
                            $filter = array_merge($filter, self::condition($v));
                            break;
                        }
                    } else {
                        $filter = array_merge($filter, self::condition($filters));
                        break;
                    }

                    continue;
                }

                // ["id"=> [1,2,3,4,5]]
                if (is_array($v) && array_sum(array_keys($v)) > 0) {
                    $filter = array_merge($filter, self::condition(["in", $k, $v]));
                    continue;
                }
                $k === '_id' && $v = Mongo::mongoId($v);
                $filter[$k] = $v;
                // $filter[$k] = strval($v);
                // $filter = array_merge($filter, self::condition(["=", $k, $v]));
            }
        } catch (\Exception $e) {
            $errormsg = sprintf("Other error: %s (%d): %s(%d)/n", $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine());
            throw new \InvalidArgumentException($errormsg);
        }
        return $filter;
    }

    private static function condition($section)
    {
        $filter = [];
        try {
            $symbol = strtolower(str_replace(' ', '', $section[0]));
            $getField = function () use ($section) {
                return trim($section[1]);
            };
            $setFilter = function ($k, $v) use (&$filter) {
                if($k === "_id"){
                    if(is_array($v) && isset($v['$in'])){
                        $v['$in'] = array_map(function($id){
                            return Mongo::mongoId($id);
                        }, $v['$in']);

                    }else{
                        $v = Mongo::mongoId($v);
                    }
                }
                $filter[$k] = $v;
            };

            switch ($symbol) {
                case 'eq':
                case '=':
                    $setFilter($getField(), $section[2]);
                    break;

                case 'between': //['$gte' => 1,'$lte' => 9]
                    $setFilter($getField(), ['$gte' => $section[2][0], '$lte' => $section[2][1]]);
                    break;

                case 'range': //['$gt' => 1,'$lt' => 9]
                    $setFilter($getField(), ['$gt' => $section[2][0], '$lt' => $section[2][1]]);
                    break;

                case 'rex': //["field_name" => new MongoRegex("shi", 'i')]
                    if (is_array($section[2])) {
                        $setFilter($getField(), new MongoRegex($section[2][0], $section[2][1] ?? ''));
                    } else {
                        $setFilter($getField(), new MongoRegex($section[2]));
                    }
                    break;

                case 'or': //array('$or' => array(array('id' => 1), array('name' => 'java')))
                    $v = [];
                    foreach ($section[1] as $ok => $ov) {
                        $tmp = [];
                        if (is_array($ov)) {

                            // if (is_array($ov) && array_sum(array_keys($ov)) > 0 && (intval($ok) == 0 && is_string($ok))) {
                            //     $ov = ["in", $ok, $ov];
                            // }
                            // devdump($ov);
                            $tmp = self::condition($ov);
                            if (!empty($tmp)) {
                                $v[] = $tmp;
                            }
                            // break;
                        }
                        // $v[] = ["$ok" => $ov];
                        unset($tmp, $ok, $ov);
                    }
                    $filter['$or'] =  $v;
                    unset($v);
                    break;

                case '>':
                case 'gt':
                    $setFilter($getField(), ['$gt' => $section[2]]);
                    break;

                case '<':
                case 'lt':
                    $setFilter($getField(), ['$lt' => $section[2]]);
                    break;

                case '>=':
                case 'egt':
                case 'get':
                case 'gte':
                    $setFilter($getField(), ['$gte' => $section[2]]);
                    break;

                case '>=':
                case 'elt':
                case 'let':
                case 'lte':
                    $setFilter($getField(), ['$lte' => $section[2]]);
                    break;

                case '!=':
                case '<>':
                case '><':
                case 'neq':
                case 'ne':
                    $setFilter($getField(), ['$ne' => $section[2]]);
                    break;

                case 'all': //['$all' => array(1,2,9)]
                    $setFilter($getField(), ['$all' => $section[2]]);
                    break;

                case 'in': //['$in' => array(1,2,9)]
                    $setFilter($getField(), ['$in' => $section[2]]);
                    break;

                case 'notin': //['$nin' => array(1,2,9)]
                    $setFilter($getField(), ['$nin' => $section[2]]);
                    break;

                default:
                    break;
            }
        } catch (\Exception $e) {
            $errormsg = sprintf("Other error: %s (%d): %s(%d)/n", $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine());
            throw new \InvalidArgumentException($errormsg);
        }
        return $filter;
    }
}
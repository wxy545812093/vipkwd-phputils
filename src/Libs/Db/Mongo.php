<?php

/**
 * @name 数据库Mongo
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @link https://www.cnblogs.com/wujuntian/p/8352586.html
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Libs\Db;

use MongoDB\Driver\{Manager, BulkWrite, WriteConcern, Query, Command, WriteResult};
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Exception\InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex as MongoRegex;

use \Vipkwd\Utils\Dev;
// use Vipkwd\Utils\DateTime as VipkwdDate;
// use \Vipkwd\Utils\Random as VipkwdRandom;

class Mongo
{

    private $table;
    private $fields;
    private $filters;
    private $options;
    private $sorts;
    private $lastQuery;
    private $limit;
    private $usePage;

    public function __construct($dsn = "127.0.0.1:27017")
    {
        $dsn = strtolower($dsn);
        (substr($dsn, 0, 10) != 'mongodb://') && $dsn = "mongodb://" . $dsn;
        $this->manager = new Manager($dsn);
        $this->writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $this->_init();
    }

    static function mongoId(string $id): Object
    {
        return new ObjectId($id);
    }

    /**
     * 刷新查询对象（初始化一切查询、过滤条件）
     */
    public function flush()
    {
        $this->_init();
        return $this;
    }

    /**
     * 切换访问目标集合
     * @param string $table 格式：数据库.集合 如 demo.user db_name.collection_name
     */
    public function table(string $table): self
    {
        $this->_init();

        $this->table = $table;
        return $this;
    }

    /**
     * 配置响应字段
     *
     * @param string $fields 'field1,field2,field3'
     * @param boolean $exclude <false> 是否排除指定字段 
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
        $this->filters = array_merge($this->filters, MongoQueryParser::filter($filters));
        unset($filters);
        return $this;
    }

    /**
     *
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

        $this->options = array_merge([
            $this->options, [
                'skip'      => ($page - 1) * $limit,
                'limit'     => $limit
            ]
        ]);

        $this->usePage = true;

        if ($next === false) {
            $data['page'] = $page;
            $data['pages'] = $endPage;
            $data['totals'] = $totals;
            $data['list'] = $this->select();
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
        $this->sorts = [];
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
     * @param array $where
     *
     * @return int
     *
     */
    public function count()
    {
        $table = explode('.', $this->table);
        $command = new Command(['count' => $table[1], 'query' => empty($this->filters) ? (object)[] : $this->filters]);
        $result = $this->manager->executeCommand($table[0], $command);
        $this->lastQuery = $result;
        return $result ? $result->toArray()[0]->n : 0;
    }

    public function query(array $filters)
    {
        $query = new Query($filters, []);
        $result = $this->manager->executeQuery($this->table, $query);
        $items = [];
        foreach ($result as $doc) {
            $doc = (array)$doc;
            if (isset($doc['_id'])) {
                $doc['_id'] = ((array)$doc['_id'])['oid'];
            }
            $items[] = $doc;
        }

        $this->lastQuery = $result;
        return $items;
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
        $query = new Query($this->filters, array_merge($this->options, $calcOptions));
        $result = $this->manager->executeQuery($this->table, $query);
        $items = [];
        foreach ($result as $doc) {
            $doc = (array)$doc;
            if (isset($doc['_id'])) {
                $doc['_id'] = ((array)$doc['_id'])['oid'];
            }
            $items[] = $doc;
        }

        $this->lastQuery = $result;
        return $items;
    }

    /**
     * 写入数据，返回写入记录条数
     *
     * @param array $data
     *
     * @return int|null
     * @throw \Exception
     */
    public function insert(array $data)
    {
        $bulk = $this->__bulkWrite();
        $bulk->insert($data);
        if ($ret = $this->__executeBulkWrite($bulk)) {
            return $ret->getInsertedCount();
        }
        return 0;
    }

    /**
     * 更新内容
     *
     * @param array $data
     * @param bool $upsert <false> true:记录不存在时自动写入
     *
     * @return int|null
     * @throw \Exception
     */
    public function update(array $data, bool $upsert = false)
    {
        $bulk = $this->__bulkWrite();
        $bulk->update($this->filters, ['$set' => $data], ['multi' => true, 'upsert' => $upsert]);
        if ($ret = $this->__executeBulkWrite($bulk)) {
            return $ret->getModifiedCount();
        }
        return 0;
    }

    /**
     * 替换内容
     *
     * @param array $update
     * @param bool $upsert <false> true:记录不存在时自动写入
     *
     * @return int|null
     * @throw \Exception
     */
    public function replace(array $data, bool $upsert = false)
    {
        $bulk = $this->__bulkWrite();
        $bulk->update($this->filters, $data, ['multi' => false, 'upsert' => $upsert]);
        if ($ret = $this->__executeBulkWrite($bulk)) {
            return $ret->getModifiedCount();
        }
        return 0;
    }

    /**
     * 仅删除匹配记录中的第一条
     *
     * @return int|null
     * @throw \Exception
     */
    public function deleteOne()
    {
        $bulk = $this->__bulkWrite();
        $bulk->delete($this->filters, ['limit' => 1]);
        if ($ret = $this->__executeBulkWrite($bulk)) {
            return $ret->getDeletedCount();
        }
        return 0;
    }

    /**
     * 删除所有匹配记录条目
     *
     * @return int|null
     * @throw \Exception
     */
    public function delete()
    {
        $bulk = $this->__bulkWrite();
        $bulk->delete($this->filters);
        // $bulk->delete($this->filters, ['limit' => 0]);
        if ($ret = $this->__executeBulkWrite($bulk)) {
            return $ret->getDeletedCount();
        }
        return 0;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }


    public function lastQuery()
    {
        return $this->lastQuery;
    }

    /**
     *
     * @param BulkWrite $bulk
     * @return WriteResult
     * @throw \Exception
     */
    private function __executeBulkWrite(BulkWrite $bulk)
    {
        $errormsg = '';
        try {
            $result = $this->manager->executeBulkWrite($this->table, $bulk, $this->writeConcern);
        } catch (BulkWriteException $e) {

            $result = $e->getWriteResult();

            // Check if the write concern could not be fulfilled
            if ($writeConcernError = $result->getWriteConcernError()) {
                $errormsg = sprintf(
                    "Mongo Concern: %s (%d): %s/n",
                    $writeConcernError->getMessage(),
                    $writeConcernError->getCode(),
                    var_export($writeConcernError->getInfo(), true),
                    $writeConcernError->getFile(),
                );
            }

            // Check if any write operations did not complete at all
            foreach ($result->getWriteErrors() as $k => $writeError) {
                $errormsg .= '[' . ($k + 1) . ']' . sprintf(
                    "Mongo Operation#%d: %s (%d) %s/n",
                    $writeError->getIndex(),
                    $writeError->getMessage(),
                    $writeError->getCode(),
                    $writeError->getFile(),
                );
            }
        } catch (Exception $e) {
            $errormsg = sprintf("Other error: %s (%d): %s/n", $e->getMessage(), $e->getCode(), $e->getFile());
        }
        if (!empty($errormsg)) {
            throw new \Exception($errormsg);
        }
        $this->lastQuery = $result;
        return $result;
    }

    private function __bulkWrite()
    {
        return new BulkWrite;
    }

    private function _init()
    {
        $this->usePage = false; //默认当做常规查询（不使用分页查询方法)
        $this->limit = -1;
        // $this->fields = ["_id" => 1]; // 0 默认不查询_id字段
        $this->fields = []; // 0 默认不查询_id字段
        $this->filters = [];
        $this->options = [];
        $this->sorts = [];
        $this->table = 'test.test';
        // $this->lastQuery = null;不清空(保留上一次查询)
    }
}

class MongoQueryParser
{

    private static $fps = [
        "=" => '$eq', // ['age' => 5]
        "eq" => '$eq', // ['age' => 5]

        ">" => '$gt', // ['$gt' => 5]
        "gt" => '$gt', // ['$gt' => 5]

        ">=" => '$gte', //['$gte' => 2]
        "egt" => '$gte', //['$gte' => 2]
        "get" => '$gte', //['$gte' => 2]
        "gte" => '$gte', //['$gte' => 2]

        "<" => '$lt', //['$lt' => 5]
        "lt" => '$lt', //['$lt' => 5]

        "<=" => '$lte', // ['$lte' => 2]
        "elt" => '$lte', //['$lte' => 2]
        "let" => '$lte', //['$lte' => 2]
        "lte" => '$lte', //['$lte' => 2]

        "!=" => '$ne', //['$ne' => 9]
        "><" => '$ne', //['$ne' => 9]
        "<>" => '$ne', //['$ne' => 9]
        "neq" => '$ne', //['$ne' => 9]

        "all" => '$all', //['$all' => array(1,2,9)]
        "in" => '$in', //['$in' => array(1,2,9)]
        "notin" => '$nin', //['$nin' => array(1,2,9)]

        "rex" => '$rex', //MongoRegex   ["name" => new MongoRegex("/shi/$i")]
        "or" => '$or', //array('$or' => array(array('id' => 1), array('name' => 'java')))
        "range" => '$range', //['$gt' => 1,'$lt' => 9]
        "between" => '$between', //['$gte' => 1,'$lte' => 9]


        // // 欄位字串為
        // $querys = array("name"=>"shian");


        // // 數值等於多少
        // $querys = array("number"=>7);

        // // 數值大於多少
        // $querys = array("number"=>array('$gt' => 5));

        // // 數值大於等於多少
        // $querys = array("number"=>array('$gte' => 2));

        // // 數值小於多少
        // $querys = array("number"=>array('$lt' => 5));

        // // 數值小於等於多少
        // $querys = array("number"=>array('$lte' => 2));

        // // 數值介於多少
        // $querys = array("number"=>array('$gt' => 1,'$lt' => 9));

        // // 數值不等於某值
        // $querys = array("number"=>array('$ne' => 9));

        // // 使用js下查詢條件
        // $js = "function(){
        //     return this.number == 2 && this.name == 'shian';
        // }";
        // $querys = array('$where'=>$js);

        // // 欄位等於哪些值
        // $querys = array("number"=>array('$in' => array(1,2,9)));

        // // 欄位不等於哪些值
        // $querys = array("number"=>array('$nin' => array(1,2,9)));

        // // 使用正規查詢
        // $querys = array("name" => new MongoRegex("/shi/$i"));

        // // 或
        // $querys = array('$or' => array(array('number'=>2),array('number'=>9)));
    ];

    static function filter($filters)
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
            throw new InvalidArgumentException($errormsg);
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
                $k === '_id' && $v = Mongo::mongoId($v);
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
            throw new InvalidArgumentException($errormsg);
        }
        return $filter;
    }
}

/*
    $filter = array();
    $options = array(

        //Only return the following fields in the matching documents

        "projection" => array("title" => 1, "article" => 1,),

        "sort" => array("views" => -1,),  "modifiers" => array(
            '$comment'  => "This is a query comment", '$maxTimeMS' => 100,

        ),
    );
    $query = new MongoDB / Driver / Query($filter, $options);
    $manager = new MongoDB / Driver / Manager("mongodb://localhost:27017");

    $readPreference = new MongoDB / Driver / ReadPreference(MongoDB / Driver / ReadPreference::RP_PRIMARY);
    $cursor = $manager->executeQuery("databaseName.collectionName", $query, $readPreference);

    foreach ($cursor as $document) {

        var_dump($document);
    }

*/
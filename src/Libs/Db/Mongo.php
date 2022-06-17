<?php

/**
 * @name 数据库Mongo
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Libs\Db;

use MongoDB\Driver\{Manager, BulkWrite, WriteConcern, Query, Command, WriteResult};
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Exception\Exception;
use MongoDB\BSON\ObjectId;

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
    private $fps;

    public function __construct($dsn = "127.0.0.1:27017")
    {
        $dsn = strtolower($dsn);
        (substr($dsn, 0, 10) != 'mongodb://') && $dsn = "mongodb://" . $dsn;
        $this->manager = new Manager($dsn);
        $this->writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $this->_init();
    }

    static function MongoId(string $id): Object
    {
        return new ObjectId($id);
    }

    /**
     * 格式：数据库.集合 如 demo.user
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
     * @param string|array $fields
     */
    public function field($fields = [], $exclude = false)
    {
        if (is_string($fields)) {
            if ($fields == '*') {
                $this->fields = $fields = [];
                return $this;
            }
            $fields = explode(',',  str_replace(' ', '', $fields));
            if (!empty($fields)) {
                $excludes = array_pad([], count($fields), $exclude ? 0 : 1);
                $fields = array_combine($fields, $excludes);
                unset($exclude, $excludes);
            }
        }
        if (!empty($fields)) {
            $this->fields = array_merge($this->fields, $fields);
        }
        return $this;
    }

    /**
     * 设置查询条件
     * 
     * ->where("city", "北京")
     * ->where(['eq', 'age', 18])
     * ->where(['=', 'sex', 1])
     * 
     * ->where([ ['=', 'sex', 1], ['=', 'field', 'value] ])
     */
    public function where()
    {
        $filters = func_get_args();
        $num = count($filters);
        if ($num > 1) {
            $filters = ['=', $filters[0], $filters[1]];
        } else if ($num === 0) {
            $filters = [];
        }else{
            $filters = $filters[0];
        }
        // _id = self::MongoID($id);
        $this->filters = array_merge($this->filters, $this->__filter($filters));
        unset($filters);
        return $this;
    }

    /**
     *
     * 分页数据
     *
     * @param int $page
     * @param int $limit 10
     * @param bool $returnData false
     * @return self|array
     *
     */
    public function page($page = 1, $limit = 10, bool $returnData = false)
    {
        $count = $this->count();
        $endPage = ceil($count / $limit);
        if ($page > $endPage) {
            $page = $endPage;
        }
        ($page < 1) && $page = 1;
        $this->_page = $page;
        $this->options = [
            'skip'      => ($page - 1) * $limit,
            'limit'     => $limit
        ];

        if ($returnData) {
            $data['data'] = $this->select($this->options);
            $data['count'] = $count;
            $data['page'] = $endPage;
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
        $command = new Command(['count' => $table[1], 'query' => $this->filters]);
        $result = $this->manager->executeCommand($table[0], $command);
        return $result ? $result->toArray()[0]->n : 0;
    }

    /**
     * 查询
     * @param array options array()
     *
     * @return array
     */
    public function select($options = [])
    {
        $options = array_merge($this->options, [
            'projection' => $this->fields,
            'sort' => $this->sorts,
        ]);

        $query = new Query($this->filters, $options);
        $cursor = $this->manager->executeQuery($this->table, $query);
        $this->_page = 0;
        $items = [];
        foreach ($cursor as $doc) {
            $doc = (array)$doc;
            if (isset($doc['_id'])) {
                $doc['_id'] = ((array)$doc['_id'])['oid'];
            }
            $items[] = $doc;
        }
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
        $bulk = $this->__BulkWrite();
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
        $bulk = $this->__BulkWrite();
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
        $bulk = $this->__BulkWrite();
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
        $bulk = $this->__BulkWrite();
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
        $bulk = $this->__BulkWrite();
        $bulk->delete($this->filters);
        // $bulk->delete($this->filters, ['limit' => 0]);
        if ($ret = $this->__executeBulkWrite($bulk)) {
            return $ret->getDeletedCount();
        }
        return 0;
    }

    /**
     *
     * @param BulkWrite $bulk
     * @return WriteResult
     * @throw \Exception
     */
    private function __executeBulkWrite(BulkWrite $bulk): ?WriteResult
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
        return $result;
    }

    private function __BulkWrite()
    {
        return new BulkWrite;
    }

    private function __parseCondition($section)
    {
        $fps = [
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

            "rex" => '$gt', //MongoRegex   ["name" => new MongoRegex("/shi/$i")]
            "or" => '$or', //array('$or' => array(array('id' => 1), array('name' => 'java')))
            "range" => '$gt', //['$gt' => 1,'$lt' => 9]
            "between" => '$gt', //['$gte' => 1,'$lte' => 9]
            "in" => '$ne', //['$in' => array(1,2,9)]
            "notin" => '$ne', //['$nin' => array(1,2,9)]
        ];

        $filter = [];
        $symbol = $section[0];
        if (isset($fps[$symbol])) {

            $symbol = $fps[$symbol];
            $field = $section[1];

            $filter[$field] = [];
            if ($symbol != '$eq') {
                $filter[$field][$symbol] = $section[2];
            } else {
                $filter[$field] = $section[2];
            }
        }
        return $filter;
    }
    private function __filter($filters)
    {

        $filter = [];

        //标准数组模式
        if (count($filters) == 3 && array_sum(array_keys($filters)) == 3 && !is_array($filters[0])) {
            return $this->__parseCondition($filters);
        }
        foreach ($filters as $k => $v) {

            //数组模式
            if ($k > 0 || $k === 0) {

                //子数组
                if (is_array($v)) {
                    foreach($v as $vv){
                        if(is_array($vv)){
                            $filter = array_merge($filter, $this->__parseCondition($vv));
                            continue;
                        }
                        $filter = array_merge($filter, $this->__parseCondition($v));
                        break;
                    }
                } else {
                }

                continue;
            }
            // ["id"=> [1,2,3,4,5]]

            if(is_array($v) && array_sum(array_keys($v)) > 0){
                $filter = array_merge($filter, $this->__parseCondition(["in", $k, $v]));
                continue;
            }
            $filter[$k] = $v;
            // $filter = array_merge($filter, $this->__parseCondition(["=", $k, $v]));
        }
        return $filter;
    }


    private function _init()
    {
        $this->fields = ["_id" => 0];
        $this->filters = [];
        $this->options = [];
        $this->sorts = [];
        $this->table = 'test.test';
        $this->fps = [
            "=" => '$eq', // ['age' => 5]
            ">" => '$gt', // ['$gt' => 5]
            ">=" => '$gte', //['$gte' => 2]
            "<" => '$lt', //['$lt' => 5]
            "<=" => '$lte', // ['$lte' => 2]

            "!=" => '$ne', //['$ne' => 9]
            "><" => '$ne', //['$ne' => 9]
            "<>" => '$ne', //['$ne' => 9]

            "rex" => '$gt', //MongoRegex   ["name" => new MongoRegex("/shi/$i")]
            "or" => '$or', //array('$or' => array(array('id' => 1), array('name' => 'java')))
            "range" => '$gt', //['$gt' => 1,'$lt' => 9]
            "between" => '$gt', //['$gte' => 1,'$lte' => 9]
            "in" => '$ne', //['$in' => array(1,2,9)]
            "notin" => '$ne', //['$nin' => array(1,2,9)]


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
<?php
/**
 * @name Sqlite数据为驱动
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

class Sqlite{

    private $dbFile = '';

    public $dbh = null;

    public $charset = 'utf8';
    protected static $instance = array();

    private function __construct(string $dbname)
    {
        $this->dbFile = $dbname;
        try {
            $this->dbh = new \PDO('sqlite:' . $this->dbFile);
        } catch (PDOException $e) {
            try {
                $this->dbh = new \PDO('sqlite2:' . $this->dbFile);
            } catch (PDOException $e) {
                $this->outputError($e->getMessage());
            }
        }
    }

    /**
     * 单例模式
     * @param string $dbname
     * @return object
     */
    public static function db(string $dbname)
    {
        $md5 = "db".md5($dbname);
        if (!key_exists($md5, self::$instance)) {
            self::$instance[$md5] = new self($dbname);
        }
        return self::$instance[$md5];
    }

    /**
     * 新增数据
     * @param string $tab_name  数据表名
     * @param array $dataArr  需要插入的字段数组
     * @return int|void
     */
    public function insert(string $tab_name, array $dataArr)
    {
        if (is_array($dataArr) && count($dataArr) > 0) {
            $key_list = '';
            $value_list = '';
            foreach ($dataArr as $key => $val) {
                $key_list .= "'" . $key . "',";
                $value_list .= "'" . $val . "',";
            }
            $key_list = '(' . rtrim($key_list, ',') . ')';
            $value_list = '(' . rtrim($value_list, ',') . ')';
            $sql = "insert into $tab_name $key_list values $value_list";
            // echo $sql;
            $result = $this->dbh->exec($sql);
            $this->getPDOError();
            //$this->dbh->beginTransaction();//事务回gun
            return $result;
        }
        return;
    }

    /**
     * 更新插入数据
     * @param string $tab_name  数据表名
     * @param array $dataArr   需要插入的字段数组
     * @param string $type    插入类型，1: 单条，2：批量
     * @return int|void
     */
    public function replace($tab_name, $dataArr, $type = 1)
    {
        if (is_array($dataArr) && count($dataArr) > 0) {

            $key_list = '';
            $value_list = '';

            if ($type === 2) {
                $keysArr = array();
                foreach ($dataArr as $item) {
                    if (is_array($item) && count($item) > 0) {
                        $val_list = '';
                        foreach ($item as $key => $val) {
                            if (!in_array($key, $keysArr)) {
                                $keysArr[] = $key;
                            }
                            $val_list .= "'" . $val . "',";
                        }
                        $val_list = '(' . rtrim($val_list, ',') . '),';
                    }
                    $value_list .= $val_list;
                }

                foreach ($keysArr as $k) {
                    $key_list .= "'" . $k . "',";
                }

                $key_list = '(' . rtrim($key_list, ',') . ')';
                $value_list = rtrim($value_list, ',');
            } else {

                foreach ($dataArr as $key => $val) {
                    $key_list .= "'" . $key . "',";
                    $value_list .= "'" . $val . "',";
                }
                $key_list = '(' . rtrim($key_list, ',') . ')';
                $value_list = '(' . rtrim($value_list, ',') . ')';
            }

            $sql = "replace into $tab_name $key_list values $value_list";
            // echo $sql;
            $result = $this->dbh->exec($sql);
            $this->getPDOError();
            //$this->dbh->beginTransaction();//事务回gun
            return $result;
        }
        return;
    }

    /**
     * Query 查询
     *
     * @param string $strSql SQL语句
     * @param string $queryMode 查询方式(All or Row)
     * @param int $pdoMode 指定数据获取方式
     * @param boolean $debug
     * @return array
     */
    public function query(string $strSql, string $queryMode = 'All', int $pdoMode = PDO::FETCH_ASSOC, bool $debug = false)
    {
        if ($debug === true)
            $this->debug($strSql);
        
        $recordset = $this->dbh->query($strSql);
        $this->getPDOError();
        if ($recordset) {
            $recordset->setFetchMode($pdoMode);
            if ($queryMode == 'Row') {
                $result = $recordset->fetch();
            } else{
                $result = $recordset->fetchAll();
            }
        } else {
            $result = null;
        }
        return $result;
    }

    /**
     * Query 查询单条
     *
     * @param string $tab_name  数据表名
     * @param string $field  查询字段 支持数组或英文逗号分隔的字符串
     * @param string $whereStr  查询条件
     * @param boolean $debug
     * @return array
     */
    public function getOne(string $tab_name, $field = "*", string $whereStr = null, bool $debug = false)
    {
        $inquire_list = $field;
        // print_r($field);
        if (is_array($field) && count($field) > 0) {
            $inquire_list = '';
            foreach ($field as $val) {
                $inquire_list .= $val . ',';
            }
            $inquire_list = rtrim($inquire_list, ',');
        }

        $sql = "SELECT $inquire_list FROM $tab_name $whereStr";
        if ($debug === true) $this->debug($sql);
        $recordset = $this->dbh->query($sql);
        $this->getPDOError();

        $result = array();
        if ($recordset) {
            $recordset->setFetchMode(PDO::FETCH_ASSOC);
            $result = $recordset->fetch();
        }
        return $result;
    }

    /**
     * Query 查询多条
     *
     * @param string $tab_name  数据表名
     * @param string $field 查询字段数组
     * @param string $whereStr  查询条件
     * @param boolean $debug
     * @return array
     */
    public function getAll(string $tab_name, string $field = "*", string $whereStr = null, bool $debug = false)
    {
        $inquire_list = $field;
        //        print_r($field);
        if (is_array($field) && count($field) > 0) {
            $inquire_list = '';
            foreach ($field as $key => $val) {
                $inquire_list .= $val . ',';
            }
            $inquire_list = rtrim($inquire_list, ',');
        }

        $sql = "SELECT $inquire_list FROM $tab_name $whereStr";
        if ($debug === true) $this->debug($sql);
        $recordset = $this->dbh->query($sql);
        $this->getPDOError();

        $result = array();
        if ($recordset) {
            $recordset->setFetchMode(PDO::FETCH_ASSOC);
            $result = $recordset->fetchAll();
        }
        return $result;
    }

    /**
     * 更新数据
     * 
     * @param string $tab_name  数据表名
     * @param array $dataArr  需要更新的字段数组
     * @param string $whereStr  更新条件
     * @return int|void
     */
    public function update(string $tab_name, array $dataArr, string $whereStr)
    {
        if (is_array($dataArr) && count($dataArr) > 0) {

            $field_list = '';
            foreach ($dataArr as $key => $val) {
                $field_list .= $key . "='{$val}',";
            }
            $field_list = rtrim($field_list, ',');
            $sql = "UPDATE $tab_name SET $field_list $whereStr";
            $result = $this->dbh->exec($sql);
            $this->getPDOError();
            // $this->dbh->beginTransaction();//事务回gun
            return $result;
        }
        return 0;
    }


    /**
     * 删除数据
     * 
     * @param string $tab_name  数据表名
     * @param string $whereStr 过滤条件
     * @return mixed
     */
    public function delete(string $tab_name, string $whereStr = "")
    {
        if($whereStr != ""){
            $whereStr = " WHERE ".$whereStr;
        }
        $sql = "DELETE FROM {$tab_name}".$whereStr;
        $res = $this->dbh->exec($sql);
        return $res;
    }

    // ===============================================================================

    /**
     * 创建表
     * 
     * $sql sql语句
     * $tbName 表名
     */
    public function createTable(string $tbName, string $sql)
    {
        if (strlen(trim($tbName)) == 0)
            echo "table name is empty!";

        if (strlen(trim($sql)) > 0) {
            $this->dbh->exec($sql);
            $this->getPDOError();
        } else {
            echo "sql statement is empty!";
        }
    }

    /**
     * 统计记录条数
     *
     * @param string $tab_name
     * @param string $whereStr
     * @return integer
     */
    public function count(string $tab_name, string $whereStr = ''):int
    {
        $sql = "SELECT COUNT(1) as __t__ FROM {$tab_name} $whereStr";
        $rowsCountArr = $this->dbh->query($sql)->fetchAll();
        return $rowsCountArr[0]['__t__'];
    }

    /**
     * 统计记录条数(count方法的别名)
     *
     * @param string $tab_name
     * @param string $whereStr
     * @return integer
     */
    public function total(string $tab_name, string $whereStr = ''):int
    {
        return $this->count($tab_name, $whereStr);
    }

    /**
     * 获取最后插入的ID
     *
     * @return integer
     */
    public function lastInsertID():int
    {
        return $this->dbh->lastInsertId();
    }

    /**
     * 清空数据表
     *
     * @param string $tab_name
     * @return array
     */
    public function clearTab(string $tab_name):array
    {
        // $res1 = $this->dbh->exec("VACUUM");//清空“空闲列表”，把数据库尺寸压缩到最小。
        $res2 = $this->dbh->exec("DELETE FROM $tab_name");
        $res3 = $this->dbh->exec("DELETE FROM sqlite_sequence WHERE name = '$tab_name'");
        $this->getPDOError();
        // return array($res1, $res2, $res3);
        return array($res2, $res3);
    }

    /**
     * 捕获PDO错误信息
     */
    private function getPDOError()
    {
        if ($this->dbh->errorCode() != '00000') {
            $arrayError = $this->dbh->errorInfo();
            $this->outputError($arrayError[2]);
        }
    }

    /**
     * debug
     * 
     * @param mixed $debuginfo
     */
    public function debug($debuginfo)
    {
        var_dump($debuginfo);
        // exit();
    }

    /**
     * 输出错误信息
     *
     * @param String $strErrMsg
     * @throws Exception
     */
    private function outputError($strErrMsg)
    {
        throw new Exception('SQLite Error: ' . $strErrMsg);
    }

    /**
     * destruct 关闭数据库连接
     */
    private function __destruct()
    {
        $this->dbh = null;
    }
}
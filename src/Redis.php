<?php

/**
 * @name Redis驱动
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

// use Vipkwd\Utils\Dev;
/**
* redis操作类
* 说明，任何为false的串，存在redis中都是空串。
* 只有在key不存在时，才会返回false
* 这点可用于防止缓存穿透
*
*/
class Redis{
    private $redis;
    //当前数据库ID号
    protected $dbId=0;
    //当前权限认证码
    protected $auth;
    /**
     * 实例化的对象,单例模式.
     * @var Vipkwd\Utils\Redis
     */
    static private $_instance=array();
    private $k;
    //连接属性数组
    protected $attr=array(
        //连接超时时间，redis配置文件中默认为300秒
        'timeout'=>30,
        //选择的数据库。
        'db_id'=>0,
    );
    //什么时候重新建立连接
    protected $expireTime;
    protected $host;
    protected $port;

    private function __construct($config,$attr=array()) {
        $this->attr = array_merge($this->attr,$attr);
            try{
             $this->redis = new \Redis();
            }catch(\Exception $e){
             throw new \Exception($e->getMessage());
            }
        $this->port = $config['port'] ? $config['port'] : 6379;
        $this->host = $config['host'];
        $this->redis->connect($this->host, $this->port, $this->attr['timeout']);
        if(isset($config['auth']) && $config['auth']) {
            $this->auth($config['auth']);
            $this->auth = $config['auth'];
        }
        $this->expireTime = time() + $this->attr['timeout'];
    }

    /**
     * 单例入口
     * 
     * 为每个数据库建立一个连接
     * 如果连接超时，将会重新建立一个连接
     * 
     * @param array $config
     * @param int|array $attr <[]>
     * @return Vipkwd\Utils\Redis
     */
    static function instance(array $config, $attr = array()) {
        //如果是一个字符串，将其认为是数据库的ID号。以简化写法。
        if(!is_array($attr)) {
            $dbId = $attr;
            $attr = array();
            $attr['db_id'] = $dbId;
        }
        $attr['db_id'] = $attr['db_id'] ?? 0;
        $k = md5(serialize($config).$attr['db_id']);
        if( empty(static::$_instance) || !(static::$_instance[$k] instanceof self)) {
            //var_dump("s2");
            static::$_instance[$k] = new self($config,$attr);
            static::$_instance[$k]->k = $k;
            static::$_instance[$k]->dbId = $attr['db_id'];
            //如果不是0号库，选择一下数据库。
            if($attr['db_id'] != 0) {
             static::$_instance[$k]->select($attr['db_id']);
            }
        } elseif( time() > static::$_instance[$k]->expireTime) {
            static::$_instance[$k]->close();
            static::$_instance[$k] = new self($config,$attr);
            static::$_instance[$k]->k = $k;
            static::$_instance[$k]->dbId= $attr['db_id'];
            //如果不是0号库，选择一下数据库。
            if($attr['db_id']!=0) {
             static::$_instance[$k]->select($attr['db_id']);
            }
        }
        return static::$_instance[$k];
    }

    /**
     * 执行原生的redis操作
     * 
     * @return \Redis
     */
    public function redis() {
        return $this->redis;
    }

    /*****************hash表操作函数*******************/

        /**
         * 获取hash表字段的值
         * 
         * @param string $key 缓存key
         * @param string $field 字段
         * @return string|false
         */
        public function hGet(string $key,$field){
            return $this->redis->hGet($key,$field);
        }

        /**
         * 设定hash表一个字段的值
         * 
         * @param string $key 缓存key
         * @param string $field 字段
         * @param string $value 值
         * @return bool
         */
        public function hSet(string $key,$field,$value):bool{
            return $this->redis->hSet($key,$field,$value);
        }

        /**
         * 判断hash表field是否存在
         * 
         * @param string $key 缓存key
         * @param string $field 字段
         * @return bool
         */
        public function hExists(string $key,$field):bool{
            return $this->redis->hExists($key,$field);
        }


        /**
         * 删除hash表中字段(支持批量)
         * 
         * @param string $key 缓存key
         * @param string $field 字段 多字段英文","分隔
         * @return int
         */
        public function hdel(string $key,string $field):int{
            $fieldArr=explode(',',$field);
            $delNum=0;
            foreach($fieldArr as $row) {
                $row=trim($row);
                $delNum+=$this->redis->hDel($key,$row);
            }
            return $delNum;
        }

        /**
         * 返回hash表元素个数
         * 
         * @param string $key 缓存key
         * @return int|bool
         */
        public function hLen(string $key){
            return $this->redis->hLen($key);
        }

        /**
         * 设定hash表字段值,字段存在,返回false
         * 
         * @param string $key 缓存key
         * @param string $field 字段
         * @param string $value 值
         * @return bool
         */
        public function hSetNx(string $key,$field,$value):bool{
            return $this->redis->hSetNx($key,$field,$value);
        }

        /**
         * 设定hash表一个字段为多值
         * 
         * @param string $key
         * @param array $value
         * @return array|bool
         */
          protected function hMset(string $key,array $value=[]) {
            return $this->redis->hMset($key,$value);
        }

        /**
         * 获取hash表多个字段值
         * 
         * @param string $key
         * @param string $field 以','号分隔字段
         * @return array|bool
         */
        public function hMget(string $key, string $field) {
            $field=explode(',', $field);
            return $this->redis->hMget($key,$field);
        }
        /**
         * 设定hash表字段正负累加
         * 
         * @param string $key
         * @param string $field
         * @param int $value
         * @return bool
         */
        public function hIncrBy(string $key, string $field, int $value):bool{
            $value=intval($value);
            return $this->redis->hIncrBy($key,$field,$value);
        }

        /**
         * 返回hash表的所有字段
         * 
         * @param string $key
         * @return array|bool
         */
        public function hKeys(string $key) {
            return $this->redis->hKeys($key);
        }

        /**
         * 以索引数组形式返回hash表字段值
         * 
         * @param string $key
         * @return array|bool
         */
        public function hVals(string $key) {
            return $this->redis->hVals($key);
        }

        /**
         * 以关联数组形式返回hash表字段值
         * 
         * @param string $key
         * @return array|bool
         */
        public function hGetAll(string $key) {
            return $this->redis->hGetAll($key);
        }

    
     /*********************有序集合操作*********************/

        /**
         * 向有序集合添加一个元素
         * 如果value存在，则更新index的值
         * 
         * @param string $key
         * @param integer $index 序号
         * @param string $value 值
         * @return int
         */
        public function zAdd(string $key, int $index, string $value):int{
            return $this->redis->zAdd($key,$index,$value);
        }

        /**
         * 给$value成员的order值,增加$index,可以为负数
         * 
         * @param string $key
         * @param int $index 序号
         * @param string $value 值
         * @return 返回新的order
         */
          protected function zinCry(string $key, int $index, string $value) {
            return $this->redis->zinCry($key,$index,$value);
        }

        /**
         * 删除值为value的元素
         * 
         * @param string $key
         * @param string $value
         * @return bool
         */
        public function zRem(string $key, string $value) {
            return $this->redis->zRem($key,$value);
        }

        /**
         * 获取集合递增序后,start~end区间元素
         * 
         * @param string $key
         * @param int $start
         * @param int $end
         * @return array|bool
         */
        public function zRange(string $key, int $start, int $end) {
            return $this->redis->zRange($key,$start,$end);
        }

        /**
         * 获取集合递减后,start~end区间元素
         * 
         * @param string $key
         * @param int $start
         * @param int $end
         * @return array|bool
         */
        public function zRevRange(string $key, int $start, int $end) {
            return $this->redis->zRevRange($key,$start,$end);
        }


        /**
         * 获取集合Order递增后,start~end区间元素
         * min和max可以是-inf和+inf　表示最大值，最小值
         * @param string $key
         * @param int $start
         * @param int $end
         * @param array $option 参数
         *                   withscores=>true，表示数组下标为Order值，默认返回索引数组
         *                   limit=>array(0,1) 表示从0开始，取一条记录。
         * @return array|bool
         */
        public function zRangeByScore(string $key, int $start, int $end, array $option=[]) {
            return $this->redis->zRangeByScore($key,$start,$end,$option);
        }


        /**
         * 获取集合Order递减后,start~end区间元素
         * min和max可以是-inf和+inf　表示最大值，最小值
         * @param string $key
         * @param int $start
         * @param int $end
         * @param array $option 参数
         *                   withscores=>true，表示数组下标为Order值，默认返回索引数组
         *                   limit=>array(0,1) 表示从0开始，取一条记录。
         * @return array|bool
         */
        public function zRevRangeByScore(string $key, int $start, int $end, array $option=[]){
            return $this->redis->zRevRangeByScore($key,$start,$end,$option);
        }

        /**
         * 返回order值在start~end之间的数量
         *
         * @param string $key
         * @param integer $start
         * @param integer $end
         * @return array|bool
         */
        public function zCount(string $key, int $start, int $end){
            return $this->redis->zCount($key,$start,$end);
        }

        /**
         * 返回值为value的order值
         *
         * @param string $key
         * @param string $value
         * @return int
         */
        public function zScore(string $key, string $value) {
            return $this->redis->zScore($key,$value);
        }


        /**
         * 返回集合以score递增排序后指定成员序号
         * 
         * 排序号 从0开始
         * @param string $key
         * @param string $value
         */
        public function zRank(string $key, string $value) {
            return $this->redis->zRank($key,$value);
        }

        /**
         * 返回集合以score递减排序后指定成员序号
         * 
         * 排序号 从0开始
         * @param string $key
         * @param string $value
         * @return void
         */
        public function zRevRank(string $key,string $value) {
            return $this->redis->zRevRank($key,$value);
        }


        /**
         * 删除集合score在start~end(包括)区间元素
         * 
         * min和max可以是-inf和+inf　表示最大值，最小值
         * @param string $key
         * @param int $start
         * @param int $end
         * @return int
         */
        public function zRemRangeByScore(string $key,int $start,int $end):int{
            return $this->redis->zRemRangeByScore($key,$start,$end);
        }


        /**
         * 返回集合元素个数
         * 
         * @param string $key
         */
        public function zCard(string $key):int{
            return $this->redis->zCard($key);
        }

    /*********************队列操作命令************************/

        /**
         * 队列右侧插入元素
         * 
         * @param string $key
         * @param string $value 多值以空格分开
         * @return int
         */
        public function rPush(string $key,$value):int {
            return $this->redis->rPush($key,$value);
        }

        /**
         * 队列右侧插入元素,如key不存在则忽略操作
         * 
         * @param string $key
         * @param string $value
         * @return int
         */
        public function rPushx(string $key,string $value):int{
            return $this->redis->rPushx($key,$value);
        }

        /**
         * 在队列左侧插入一个元素
         * 
         * @param string $key
         * @param string $value
         * @return int
         */
        public function lPush(string $key,string $value):int{
            return $this->redis->lPush($key,$value);
        }

        /**
         * 队列左侧插入元素,如key不存在则忽略操作
         * 
         * @param string $key
         * @param string $value
         * @return int
         */
        public function lPushx(string $key,string $value):int{
            return $this->redis->lPushx($key,$value);
        }

        /**
         * 返回队列长度
         * 
         * @param string $key
         * @return int
         */
        public function lLen(string $key):int{
            return $this->redis->lLen($key);
        }

        /**
         * 返回队列指定区间的元素
         * 
         * @param string $key
         * @param int $start
         * @param int $end
         */
        public function lRange(string $key,int $start, int $end) {
            return $this->redis->lrange($key,$start,$end);
        }

        /**
         * 返回队列中指定索引的元素
         * 
         * @param string $key
         * @param int $index
         * @return mixed
         */
        public function lIndex(string $key, int $index) {
            return $this->redis->lIndex($key,$index);
        }

        /**
         * 设定队列中指定index的值
         * 
         * @param string $key
         * @param int $index
         * @param string $value
         * @return mixed
         */
        public function lSet(string $key, int $index,string $value) {
            return $this->redis->lSet($key,$index,$value);
        }

        /**
         * 删除值为vaule的count个元素
         * 
         * PHP-REDIS扩展的数据顺序与命令的顺序不太一样，不知道是不是bug
         * count>0 从尾部开始
         * >0　从头部开始
         * =0　删除全部
         * @param string $key
         * @param int $count
         * @param string $value
         * @return mixed
         */
        public function lRem(string $key, int $count, string $value) {
            return $this->redis->lRem($key,$value,$count);
        }

        /**
         * 删除并返回队列中的头元素
         * 
         * @param string $key
         */
        public function lPop(string $key) {
            return $this->redis->lPop($key);
        }

        /**
         * 删除并返回队列中的尾元素
         * 
         * @param string $key
         */
        public function rPop(string $key) {
            return $this->redis->rPop($key);
        }

    /*************redis字符串操作命令*****************/

        /**
         * 设置一个key
         * 
         * @param string $key
         * @param string $value
         * @return mixed
         */
        public function set(string $key,string $value) {
            return $this->redis->set($key,$value);
        }

        /**
         * 得到一个key
         * 
         * @param string $key
         * @return mixed
         */
        public function get(string $key) {
            return $this->redis->get($key);
        }

        /**
         * 设置一个有过期时间的key
         * 
         * @param string $key
         * @param int $expire
         * @param string $value
         * @return mixed
         */
        public function setex(string $key, int $expire, string $value) {
            return $this->redis->setex($key,$expire,$value);
        }

        /**
         * 设置一个key,如key不存在则忽略操作
         * 
         * @param string $key
         * @param string $value
         * @return mixed
         */
        public function setnx(string $key,string $value) {
            return $this->redis->setnx($key,$value);
        }

        /**
         * 批量设置key
         * 
         * @param string $arr
         * @return mixed
         */
          protected function mset(string $arr) {
            return $this->redis->mset($arr);
        }

    /*************redis　无序集合操作命令*****************/

        /**
         * 返回无序集合所有元素
         * 
         * @param string $key
         * @return mixed
         */
        public function sMembers(string $key) {
            return $this->redis->sMembers($key);
        }

        /**
         * 求2个集合的差集
         * 
         * @param string $key1
         * @param string $key2
         * @return mixed
         */
        public function sDiff(string $key1,string $key2) {
            return $this->redis->sDiff($key1,$key2);
        }


        /**
         * 添加集合。由于版本问题，扩展不支持批量添加。这里做了封装
         * 
         * @param string $key
         * @param string|array $value
         */
          protected function sAdd(string $key,$value) {
            if(!is_array($value))
            $arr=array($value); else
            $arr=$value;
            foreach($arr as $row)
            $this->redis->sAdd($key,$row);
        }

        /**
         * 返回无序集合的元素个数
         * 
         * @param string $key
         * @return int
         */
        public function scard(string $key):int{
            return $this->redis->scard($key);
        }

        /**
         * 从集合中删除一个元素
         * 
         * @param string $key
         * @param string $value
         */
        public function srem(string $key,string $value) {
            return $this->redis->srem($key,$value);
        }

    /*************redis管理操作命令*****************/

        /**
         * 选择(切换)数据库
         * 
         * @param int $dbId 数据库ID号
         * @return bool
         */
        public function select(int $dbId=0) {
            $this->dbId=$dbId;
            return $this->redis->select($dbId);
        }

        /**
         * 清空当前数据库
         * 
         * @return bool
         */
        public function flushDB() {
            return $this->redis->flushDB();
        }

        /**
         * 返回当前库状态
         * 
         * @return array
         */
        public function info() {
            return $this->redis->info();
        }

        /**
         * 同步保存数据到磁盘
         * 
         * @return mixed
         */
        public function save() {
            return $this->redis->save();
        }
        /**
         * 异步保存数据到磁盘
         * 
         * @return mixed
         */
        public function bgSave() {
            return $this->redis->bgSave();
        }
        /**
         * 返回最后保存到磁盘的时间
         * 
         * @return mixed
         */
        public function lastSave() {
            return $this->redis->lastSave();
        }

        /**
         * 获取key,支持*匹配多个字符,?一个字符
         * 
         * 只有单独"*"表示全部
         * @param string $key
         * @return array
         */
        public function keys(string $key){
            return $this->redis->keys($key);
        }

        /**
         * 删除指定key
         * 
         * @param string $key
         * @return mixed
         */
        public function del(string $key) {
            return $this->redis->del($key);
        }

        /**
         * 判断一个key值是否存在
         * 
         * @param string $key
         * @return mixed
         */
        public function exists(string $key) {
            return $this->redis->exists($key);
        }

        /**
         * 为key设定过期时间 单位为秒
         * 
         * @param string $key
         * @param int $expire
         * @return mixed
         */
        public function expire(string $key,int $expire) {
            return $this->redis->expire($key,$expire);
        }

        /**
         * 返回key还有多久过期，单位秒
         * @param string $key
         * @return int
         */
        public function ttl(string $key):int{
            return $this->redis->ttl($key);
        }

        /**
         * 设定key过期时间，time为时间戳
         * @param string $key
         * @param int $time
         * @return mixed
         */
        public function exprieAt(string $key, int $time) {
            return $this->redis->expireAt($key,$time);
        }

        /**
         * 关闭服务器链接
         */
        public function close() {
            return $this->redis->close();
        }

        /**
         * 关闭所有连接
         */
        public static function closeAll() {
            foreach(static::$_instance as $o) {
                if($o instanceof self)
                $o->close();
            }
        }

        /** 这里不关闭连接，因为session写入会在所有对象销毁之后。
        public function __destruct(){
           return $this->redis->close();
        }
         **/


        /**
         * 返回当前数据库key数量
         */
        public function dbSize() {
            return $this->redis->dbSize();
        }


        /**
         * 返回一个随机key
         */
        public function randomKey() {
            return $this->redis->randomKey();
        }


        /**
         * 得到当前数据库ID
         * @return int
         */
        public function getDbId() {
            return $this->dbId;
        }

        /**
         * 返回当前密码
         */
        public function getAuth() {
            return $this->auth;
        }

        public function getHost() {
            return $this->host;
        }

        public function getPort() {
            return $this->port;
        }

        public function getConnInfo() {
            return array(
            'host'=>$this->host,
            'port'=>$this->port,
            'auth'=>$this->auth
            );
        }
     
    /*********************事务的相关方法************************/

        /**
         * 监控key,就是key添加乐观锁
         * 在此期间如果key的值如果发生的改变，刚不能为key设定值
         * 可以重新取得Key的值。
         * @param string $key
         * @return mixed
         */
        public function watch(string $key) {
            return $this->redis->watch($key);
        }
        /**
         * 取消当前链接对所有key的watch
         * EXEC 命令或 DISCARD 命令先被执行了的话，那么就不需要再执行 UNWATCH 了
         */
        public function unwatch() {
            return $this->redis->unwatch();
        }

        /**
         * 开启一个事务(type=\Redis::MULTI)
         *
         * 事务的调用有两种模式Redis::MULTI和Redis::PIPELINE，
         * 默认是Redis::MULTI模式，
         * Redis::PIPELINE管道模式速度更快，但没有任何保证原子性有可能造成数据的丢失
         * @param int $type \Redis::MULTI
         * @return void
         */
        public function multi($type=\Redis::MULTI) {
            return $this->redis->multi($type);
        }

        /**
         * 执行一个事务
         * 
         * 收到 EXEC 命令后进入事务执行，事务中任意命令执行失败，其余的命令依然被执行
         * @return void
         */
        public function exec() {
            return $this->redis->exec();
        }

        /**
         * 回滚一个事务
         * 
         * @return void
         */
        public function discard() {
            return $this->redis->discard();
        }

        /**
         * 测试链接是否失效
         * 
         * 没有失效返回+PONG
         * 失效返回false
         */
        public function ping(){
            return $this->redis->ping();
        }

        /**
         * 设置redis权限
         *
         * @param string $auth
         * @return void
         */
        public function auth(string $auth) {
            return $this->redis->auth($auth);
        }
          
     /*********************自定义的方法,用于简化操作************************/
        /**
         * 得到一组的ID号
         * 
         * @param string $prefix
         * @param string $ids
         * @return array
         */
          protected function hashAll(string $prefix, string $ids):array{
            $ids=explode(',', $ids);
            $arr=array();
            foreach($ids as $id) {
                $key=$prefix.'.'.$id;
                $res=$this->hGetAll($key);
            $arr[$key]=$res;
            }
            return $arr;
        }

        /**
         * 向队列放入一条消息，使用0号库。
         *
         * @param string $listKey
         * @param string|array $msg
         * @param int $expire 有效期 秒
         * @return string
         */
        public function pushMessage(string $listKey, $msg, $expire=3600):string{
            if(is_array($msg)) {
                $msg = json_encode($msg);
            }
            $_key = "b".md5($msg);
            //如果消息已经存在，删除旧消息，已当前消息为准
            //echo $n=$this->lRem($listKey, 0, $_key)."\n";
            //重新设置新消息
            $this->lPush($listKey, $_key);
            $this->setex($_key, $expire, $msg);
            return $_key;
        }

        /**
         * 生成批量删除key的命令
         *
         * @param string $keys
         * @param int $dbId
         * @return string
         */
        public function buildDelCmd(string $keys,int $dbId=0):string{
            $redisInfo=$this->getConnInfo();
            $cmdArr=array(
            'redis-cli',
            '-a',
            $redisInfo['auth'],
            '-h',
            $redisInfo['host'],
            '-p',
            $redisInfo['port'],
            '-n',
            $dbId,
            );
            $redisStr=implode(' ', $cmdArr);
            $cmd="{$redisStr} KEYS \"{$keys}\" | xargs {$redisStr} del";
            return $cmd;
        }

     
     private function __clone() { }
}
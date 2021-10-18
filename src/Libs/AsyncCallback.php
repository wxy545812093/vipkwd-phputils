<?php
/**
 * @name 非阻塞式实现 JS 版 setTimeout
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs;

use Vipkwd\Utils\Tools;
use Vipkwd\Utils\Crypt;
use \Exception;

class AsyncCallback {
    private $auth;

    //标识异步调用已完成
    protected $_completed = false;
    private $options = [];

    public function __construct(array $options){
        if( !isset($options['desIv']) || strlen($options['desIv']) != 8){
            $options['desIv'] = substr(md5( $options['desKey']), 0, 8);
        }
        $this->options = $options;

        $this->setAuth();

        $this->listenTask();
    }

    private function createTaskId(){
        // [$m, $s] = explode(" ", microtime() ); 
        return "". md5(microtime() . mt_rand(10, 1000));
    }
    //js setTimeout
    public function setTimeout(string $funcName, int $timeout, array $args = [], int $count = 1){
        if($this->_completed === true){
            return;
        }
        $this->taskId = $this->createTaskId();
        if($timeout >= 0){
            $funcName = Crypt::encryptDes(
                str_replace(" ", "",$funcName ),
                $this->options['desKey'],
                $this->options['desIv']
            );
            $this->taskLog("启动任务", true);
            $data = [
                'async__ft_cmd'     => __FUNCTION__,
                'async__ft_delay'   => $timeout,
                'async__ft_event'   => $funcName,
                'async__ft_data'    => Crypt::encryptDes(json_encode($args), $this->options['desKey'], $this->options['desIv']),
                'async__ft_expired' => time() +3,
                "async__ft_limit"   => $count > 1 ? $count : 1,
                "async__ft_taskId"  => $this->taskId,
                "async__ft_taskTag" => $this->options['taskTag'],
            ];
            $this->dataFormat($data);
            $this->sock('setTimeout');
            return $this->taskId;
        }
        $this->taskLog("启动任务", false, "Request超时");
        return $this->taskId;
    }

    private function event_setTimeout(){
        $limits = $limit = $this->ft('limit') >= 1 ? $this->ft('limit') : 1;
        $_delay = intval($this->ft('delay'));
        $res = [];
        $funcName = Crypt::decryptDes(
            trim($this->ft('event')),
            $this->options['desKey'],
            $this->options['desIv']
        );

        if(strripos($funcName,"->")){
            $funcName = explode("->", $funcName);
            $funcName[0] = new $funcName[0]; 
        }else if(strripos($funcName,"::")){
            $funcName = explode("::", $funcName);
        }
        
        $this->options['debug'] && $this->taskLog("解码回调函数",true, $funcName);

        $args = json_decode(
            Crypt::decryptDes(
                $this->ft('data'),
                $this->options['desKey'],
                $this->options['desIv']
            ),
            true
        );
        $this->options['debug'] && $this->taskLog("解码回调参数",true, $args);
        try{
            while($limit > 0){
                $idx = ($limits-$limit+1);
                $this->taskLog("任务({$idx})等待",true);
                if($_delay > 0){
                    sleep($_delay);
                }
                $limit--;
                $res = call_user_func($funcName,$args);
                $this->taskLog("任务({$idx})响应",true, $res);
            }
        }catch(Exception $e){
            $this->taskLog("任务({$idx})异常",false, $e->getMessage()); 
        }
    }

    private function sock($event) {
        $url = $this->current_url();
        $host = parse_url($url,PHP_URL_HOST);
        $path = parse_url($url,PHP_URL_PATH);
        $query = parse_url($url,PHP_URL_QUERY);
        
        $port = parse_url($url,PHP_URL_PORT);
        $port = $port ? $port : 80;

        $scheme = parse_url($url,PHP_URL_SCHEME);
        if($scheme == 'https') $host = 'ssl://'.$host;

        $fp = fsockopen($host,$port,$errno,$errstr,1);
        if(!$fp) {
            return false;
        }

        if($query) $path .= '?'.$query;
        
        $data = $this->data;

        ksort($data);
        $data = http_build_query($data);

        //设置非阻塞模式
        stream_set_blocking($fp,false);
        stream_set_timeout($fp,1);

        $sign = $this->getDataSign($data);

        $header = "GET $path HTTP/1.1\r\n";
        $header .= "Host: $host\r\n";
        $header .= "Event: $event\r\n";
        $header .= "Data: $data\r\n";
        $header .= "Sign: $sign\r\n";
        $header .= "Auth: {$this->auth}\r\n";
        $header .= "taskid: {$this->taskId}\r\n";
        $header .= "tasktag: {$this->options['taskTag']}\r\n";
        $header .= "Connection: Close\r\n\r\n";
        fwrite($fp,$header);
        fclose($fp);
        $this->taskLog("发送任务",true);
        return true;
    }

    private function current_url() {
        $url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        if(!isset($_SERVER['HTTPS']))
            $url = 'http://'.$url;
        elseif($_SERVER['HTTPS'] === 1 || $_SERVER['HTTPS'] === 'on' || $_SERVER['SERVER_PORT'] == 443)
            $url = 'https://'.$url;
        else
            $url = 'http://'.$url;
        return $url;
    }

    private function setAuth(){
        $this->auth = substr(md5($this->options['auth']),8,22);
    }

    private function ft($field){
        $field = "async__ft_".$field;
        if(isset($this->data[$field])){
            return $this->data[$field];
        }
        return "";
    }
    private function getDataSign($data){
        if(is_array($data)){
            ksort($data);
            $data = http_build_query($data);
        }
        $data = urldecode($data)."&auth=".$this->auth;
        return md5(Crypt::encryptRC4($data));
    }

    private function dataFormat(&$data){
        if(is_string($data)){
            parse_str($data, $data);
        }
        $this->data = array_merge(array(
            'async__ft_cmd'     => "",
            'async__ft_delay'   => "",
            'async__ft_event'   => "",
            'async__ft_data'    => "",
            'async__ft_expired' => "",
            'async__ft_taskId'  => "",
            'async__ft_taskTag' => "",

        ), $data);
        unset($data);
    }

    private function listenTask(){
        $this->headers = Tools::getHttpHeaders();
        //tests($this->headers);
        if(isset($this->headers['auth']) && isset($this->headers['taskid']) && isset($this->headers['tasktag'])) {
            
            //标记任务已完成，防止“递归”
            $this->_completed = true;
            
            $data = isset($this->headers['data']) ? $this->headers['data'] : "";
            $sign = isset($this->headers['sign']) ? $this->headers['sign'] : "";
            
            if($sign != $this->getDataSign($data) ){
                return $this->taskLog("验签",false);
                //验签失败
                return -10;
            }
            $this->dataFormat($data);

            $this->taskId = $this->ft('taskId');
            $this->options['taskTag'] = $this->ft('taskTag');
            
            if($this->headers['auth'] != $this->auth){
                return $this->taskLog("鉴权",false);
            }


            if(!($cmd=$this->ft('cmd')) || !method_exists($this, $cmd) ){
                return $this->taskLog("来源认证",false);
                //任务发起入口无效
                return -20;
            }
            $call = "event_". $this->headers['event'];
            if(!method_exists($this, $call)){
                return $this->taskLog("事件认证",false);
                return -31;
            }

            //任务调用超时（是非阻塞request 过程超时，不是 exec(全局函数) 过程超时;
            //其实就是达到：增加篡改数据的成本咯；
            if( time() > intval($this->ft('expired')) ){
                return $this->taskLog("任务时效",false);
                return -40;
            }
            ignore_user_abort();
            set_time_limit(0);

            return $this->$call();
        }
    }

    private function taskLog(string $action, bool $status, $info = ""){
        $log = [
            "taskId"    => $this->taskId,
            "taskTag"   => $this->options['taskTag'],
            "time"      => date("Y-m-d H:i:s"),
            "action"    => $action,
            "status"    => $status ? "ok" : "failed",
            "info"      => $info
        ];
        file_put_contents($this->options['logFile'],"\r".json_encode($log,256), FILE_APPEND);
        unset($log);
    }
}
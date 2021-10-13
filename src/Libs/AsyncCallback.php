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

class AsyncCallback {
    private $auth;
    //标识异步调用已完成
    protected $_completed = false;

    public function __construct(string $auth = ""){
        $this->setAuth($auth);
        $this->listenTask();
    }

    //js setTimeout
    public function setTimeout(string $funcName, int $timeout, int $count = 1):void{
        if($this->_completed !== true && function_exists($funcName) && $timeout >= 0){
            $this->sock('setTimeout',$this->dataFormat([
                'async__ft_cmd'     => __FUNCTION__,
                'async__ft_delay'   => $timeout,
                'async__ft_event'   => $funcName,
                'async__ft_expired' => time() +3,
                "async__ft_limit"   => $count >= 1 ? $count : 1
            ]));
        }
    }

    private function event_setTimeout($data){
        $limits = $limit = $data['async__ft_limit'] >= 1 ? $data['async__ft_limit'] : 1;
        $_delay = intval($data['async__ft_delay']);
        $res = [];
        while($limit > 0){
            if($_delay > 0){
                sleep( $data['async__ft_delay'] * 1);
            }
            $limit--;
            $res[ "t".($limits - $limit) ] = call_user_func($data['async__ft_event'], __FUNCTION__.' : '. __LINE__ ." -- ". date('Y-m-d H:i:s'));
        }
        return $res;
    }

    private function sock($event,$data) {
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
        $header .= "Connection: Close\r\n\r\n";
        fwrite($fp,$header);
        fclose($fp);
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

    private function setAuth(string $auth = ""){
        $this->auth = substr(
            md5(
                $auth ?: '<ed495*&^ee4a3>d02<c8.#$%^&dc9b19!HGc2cdd~40^33fsdf."}*'
            )
        ,8,22);
    }

    private function getDataSign($data){
        if(is_array($data)){
            ksort($data);
            $data = http_build_query($data);
        }
        $data = urldecode($data)."&auth=".$this->auth;
        return md5(Tools::encrypt($data));
    }

    private function dataFormat($arr){
        if(is_string($arr)){
            parse_str($arr, $arr);
        }
        return array_merge(array(
            'async__ft_cmd'     => "",
            'async__ft_delay'   => "",
            'async__ft_event'    => "",
            'async__ft_expired' => ""
        ), $arr);
    }

    private function listenTask(){
        $this->headers = Tools::getHttpHeaders();

        if(isset($this->headers['auth']) && $this->headers['auth'] == $this->auth) {
            $data = isset($this->headers['data']) ? $this->headers['data'] : "";
            $sign = isset($this->headers['sign']) ? $this->headers['sign'] : "";

            if($sign != $this->getDataSign($data) ){
                //验签失败
                return -10;
            }
            $data = $this->dataFormat($data);
            if(!$data['async__ft_cmd'] || !method_exists($this, $data['async__ft_cmd'])){
                //任务发起入口无效
                return -20;
            }
            $call = "event_". $this->headers['event'];
            if(!method_exists($this, $call)){
                return -31;
            }

            //任务调用超时（是非阻塞request 过程超时，不是 exec(全局函数) 过程超时;
            //其实就是达到：增加篡改数据的成本咯；
            if( time() > intval($data['async__ft_expired']) ){
                return -40;
            }
            ignore_user_abort();
            set_time_limit(0);

            $this->_completed = true;
            //标记任务已完成，防止“递归”

            if(method_exists($this, $call)){
                return $this->$call($data);
            }
        }
    }
}
<?php

/**
 * @name websocket推送
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @link https://www.workerman.net/web-sender
 * @copyright The PHP-Tools
 */
namespace Vipkwd\Utils\SocketPush;

use Vipkwd\Utils\Http;

class PusherException extends \Exception
{
}

class WebPusher {

    private $salt = 'we_2.D28@82>0%';
    private $url = '/';
    private $content = '';
    private $contentPrefix = '';
    private $type = 'publish';
    private $toUser = '';

    private function __construct($url)
    {
       $this->url = $url; 
    }

    static function instance(string $url = 'http://ws.dev.superpal.cn/api/'){
        return new self($url);
    }

    /**
     * 设置消息体
     * @param string|array 
     * @param string $prefix 消息体前缀， 拼接在最后消息体前面，常用于业务体做消息场景界定
     * @param string $saltKey <> 数组消息默认自动签名。更改签名盐值 
     * @return this
     */
    public function data($data, string $prefix = '', string $saltKey=''){

        if(is_array($data)){
            if(!isset($data['time'])){
                $data['time'] = time();
            }

            if(isset($data['saltKey'])){
                if(!$saltKey){
                    $saltKey = $data['saltKey'];
                }
                unset($data['saltKey']);
            }
            ksort($data);
        }
        $this->content = $data;
        $this->contentPrefix = $prefix;
        if($saltKey) $this->salt = $saltKey;
        return $this;
    }

    /**
     * 消息类型: publish 发送消息
     * @param string $type <public>
     * @return this
     */
    public function type(string $type = 'publish'){
        $this->type = $type;
        return $this;
    }

    /**
     * 发给谁
     * @param string|int
     * 
     * @return this
     */
    public function to($toUser){
        $this->toUser = $toUser;
        return $this;
    }

    /**
     * 发送GET消息
     * 
     * @return mixed
     */
    public function get(){
        if(is_array($this->content)){
            if($this->salt){
                $this->content['sign'] = md5(http_build_query($this->content) . $this->salt);
            }
            $this->content = base64_encode(json_encode($this->content));
        }

        if($this->contentPrefix){
            $this->content = $this->contentPrefix . $this->content;
        }
        return Http::get($this->url, [
            'type' => $this->type,
            'content' => $this->content,
            'to' => $this->toUser
        ]);
    }

    /**
     * 发送post消息
     * 
     * @param string $dataType <form>
     * @param array $headers <[]>
     * 
     * @return mixed
     */
    public function post (string $dataType = 'form', array $headers = []){
        if(is_array($this->content)){
            if($this->salt){
                $this->content['sign'] = md5(http_build_query($this->content) . $this->salt);
            }
            $this->content = base64_encode(json_encode($this->content));
        }

        if(true !== ($message = $this->validateType())){
            return $message;
        }
        if(true !== ($message = $this->validateToUser())){
            return $message;
        }
        if(true !== ($message = $this->validateContent())){
            return $message;
        }
        if($this->contentPrefix){
            $this->content = $this->contentPrefix . $this->content;
        }
        return Http::post($this->url, http_build_query([
            'type' => $this->type,
            'content' => $this->content,
            'to' => $this->toUser
        ]), $dataType, $headers);
    }

    public function signVerify($data){

    }

    private function validateType(){
        if($this->type != 'publish'){
            return '消息类型无效';
        }
        return true;
    }

    private function validateToUser(){
        if($this->toUser == ''){
            return '目标用户无效';
        }
        return true;
    }

    private function validateContent(){
        if($this->type == ''){
            return '无效消息体';
        }
        return true;
    }
}
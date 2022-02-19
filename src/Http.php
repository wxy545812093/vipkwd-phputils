<?php

/**
 * @name http请求
 * @author devkeep <devkeep@skeep.cc>
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/aiqq363927173/Tools
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\Str as VipkwdStr;
use \Exception;
use \Closure;

class Http{

    /**
     * get请求
     *
     * -e.g: phpunit("Tools::get",["http://www.vipkwd.com/static/js/idcard.js"]);
     * 
     * @param string $url URL地址
     * @param array $data 请求数据 <[]>
     * @param array $header 请求头 <[]>
     *
     * @return mixed
     */
    static function get(string $url, array $data = [], array $header =[]){
        $ch = curl_init();

        if(!empty($header)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        if (!empty($data)) {
            if(strrpos($url, "?") > 0 ){
                $url = substr($url, 0, strrpos($url, "?")-1);
            }
            $url = $url . '?' . http_build_query($data);
        }

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * Post请求
     * 
     * -e.g: phpunit("Tools::post",["http://www.vipkwd.com/static/js/idcard.js"]);
     *
     * @param string $url URL地址
     * @param string $param <""> 发送参数
     * @param string $dataType <form> 设定发送的数据类型 [form|json]
     * @param array $header 请求头 <[]>
     *
     * @return mixed
     */
    static function post(string $url, string $param="", string $dataType = 'form', array $header = []){
        $ch = curl_init();
        $dataTypeArr = [
            'form' => ['content-type: application/x-www-form-urlencoded;charset=UTF-8'],
            'json' => ['Content-Type: application/json;charset=utf-8'],
        ];
        if(isset($dataTypeArr[$dataType])){
            $header[] = $dataTypeArr[$dataType][0];
        }

        if(!empty($header)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }  
}

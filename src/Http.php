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

use Vipkwd\Utils\Type\Str as VipkwdStr;
use Vipkwd\Utils\Libs\Net\{Request, Response};
use \Exception;
use \Closure;

class Http
{

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
    static function get(string $url, array $data = [], array $header = [])
    {
        $ch = curl_init();

        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        if (!empty($data)) {
            if (strrpos($url, "?") > 0) {
                $url = substr($url, 0, strrpos($url, "?") - 1);
            }
            $url = $url . '?' . http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
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
        if (isset($dataTypeArr[$dataType])) {
            $header[] = $dataTypeArr[$dataType][0];
        }

        if (!empty($header)) {
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

        // $data = array ('foo' => 'bar');
        // $data = http_build_query($data);

        // $opts = array (
        //     'http' => array (
        //         'method' => 'POST',
        //         'header'=> 'Content-type: application/x-www-form-urlencodedrn',
        //         'Content-Length: '. strlen($data) . "\r\n",
        //         'content' => $data
        //     )
        // );

        // $context = stream_context_create($opts);
        // $html = file_get_contents('http://localhost/e/admin/test.html', false, $context);
    }

    /**
     * Request 请求处理类
     *
     * -e.g: phpunit("Http::request");
     *
     * @param array $properties
     * @return Object
     */
    static function request(array $properties = [])
    {
        return new Request($properties);
    }

    /**
     * Response 请求响应类
     *
     * -e.g: phpunit("Http::response");
     *
     * @param array $properties
     * @return Object
     */
    static function response(array $properties = [])
    {
        return new Response($properties);
    }

    /**
     * 数据解码(xss)
     */
    static function encode($data, $rxss = true)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::encode($v, $rxss);
            }
            return $data;
        } else {
            $type = gettype($data);
            switch ($type) {
                case "boolean":
                case "integer":
                case "double":
                case "NULL":
                    break;
                case "string":
                    $data = trim($data);
                    if (substr($data, 0, 1) == '{' && substr($data, -1) == '}') {
                        return self::encode(json_decode($data, true), $rxss);
                    } else {
                        $data = urldecode($data);
                        $data = trim($rxss ? htmlspecialchars($data) : $data);
                    }
                    break;
                default:
                    $data = Null;
                    break;
            }
            return $data;
        }
    }


    /**
     * 发送请求状态
     *
     * @param integer $code
     * @return void
     */
    static function sendCode(int $code)
    {
        $response = self::response();
        isset($response::$codes[$code]) && header("HTTP/1.1 {$code} " . $response::$codes[$code]);
    }
}

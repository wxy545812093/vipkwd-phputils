<?php

/**
 * @name http请求
 * @author devkeep <devkeep@skeep.cc>
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/aiqq363927173/Tools
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\Type\{Str as VipkwdStr, Arr as VipkwdArray};
use Vipkwd\Utils\Libs\Net\{Request, Response};
use \Exception;
use \Closure;

class Http
{

    private static $timeout = 3;

    /**
     * get请求
     *
     * -e.g: phpunit("Http::get",["http://www.vipkwd.com/static/js/idcard.js"]);
     *
     * @param string $url URL地址
     * @param string|array $param <""> 发送参数
     * @param string $type <form> 设定发送的数据类型 [form|json]
     * @param array $header 请求头 <[]>
     * @param bool $rInfo 是否返回curlInfo
     *
     * @return mixed
     */
    static function get(string $url, array $param = [], array $header = [], string $type = 'form', bool $rInfo = false)
    {
        self::$timeout = 3;
        return self::__request('get', $url, $param, $type, $header, $rInfo);
    }

    /**
     * Post请求
     *
     * -e.g: phpunit("Http::post",["http://www.vipkwd.com/static/js/idcard.js"]);
     *
     * @param string $url URL地址
     * @param string|array $param <""> 发送参数
     * @param string $type <form> 设定发送的数据类型 [form|json]
     * @param array $header 请求头 <[]>
     * @param bool $rInfo 是否返回curlInfo
     *
     * @return mixed
     */
    static function post(string $url, $param = [], string $type = 'form', array $header = [], bool $rInfo = false)
    {
        self::$timeout = 3;
        return self::__request('post', $url, $param, $type, $header, $rInfo);
    }

    /**
     * Put请求
     *
     * @param string $url URL地址
     * @param string|array $param <""> 发送参数
     * @param string $type <form> 设定发送的数据类型 [form|json]
     * @param array $header 请求头 <[]>
     * @param bool $rInfo 是否返回curlInfo
     *
     * @return mixed
     */
    static function put(string $url, $param = [], string $type = 'form', array $header = [], bool $rInfo = false)
    {
        self::$timeout = 3;
        return self::__request('put', $url, $param, $type, $header, $rInfo);
    }
    /**
     * Delete 请求
     *
     * @param string $url URL地址
     * @param string|array $param <""> 发送参数
     * @param string $type <form> 设定发送的数据类型 [form|json]
     * @param array $header 请求头 <[]>
     * @param bool $rInfo 是否返回curlInfo
     *
     * @return mixed
     */
    static function delete(string $url, $param = [], string $type = 'form', array $header = [], bool $rInfo = false)
    {
        self::$timeout = 3;
        return self::__request('delete', $url, $param, $type, $header, $rInfo);
    }

    /**
     * Patch 请求
     *
     * @param string $url URL地址
     * @param string|array $param <""> 发送参数
     * @param string $type <form> 设定发送的数据类型 [form|json]
     * @param array $header 请求头 <[]>
     * @param bool $rInfo 是否返回curlInfo
     *
     * @return mixed
     */
    static function patch(string $url, $param = [], string $type = 'form', array $header = [], bool $rInfo = false)
    {
        self::$timeout = 3;
        return self::__request('patch', $url, $param, $type, $header, $rInfo);
    }

    /**
     * CURL测试链接连通性
     * 
     * @param string $url
     * @param array $header
     * @param integer $timeout <3>
     * @return array curl_getinfo
     */
    static function connectTest(string $url, array $header = [], int $timeout = 3)
    {
        self::$timeout = $timeout;
        return self::__request('get', $url, [], 'form', $header, true);
    }

    /**
     * Request 请求处理类
     *
     * -e.g: phpunit("Http::request");
     *
     * @param array $properties
     * @return Request
     */
    static function request(array $properties = []): Request
    {
        return new Request($properties);
    }

    /**
     * Response 请求响应类
     *
     * -e.g: phpunit("Http::response");
     *
     * @param array $properties
     * @return Response
     */
    static function response(array $properties = []): Response
    {
        return new Response($properties);
    }

    /**
     * 数据解码(xss)
     * 
     * @param mixed $data
     * @param bool $rxss <true> 是否转义为实体符
     * 
     * @return mixed
     * 
     */
    static function encode($data, $rxss = true)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::encode($v, $rxss);
            }
            return $data;
        } else {
            $type = strtolower(gettype($data));
            switch ($type) {
                case "boolean":
                case "object":
                case "integer":
                case "double":
                case "null":
                    break;
                case "string":
                    $data = trim($data);
                    $firstChar = substr($data, 0, 1);
                    $lastChar = substr($data, -1);
                    if (($firstChar == '{' &&  $lastChar == '}') || ($firstChar == '[' &&  $lastChar == ']')) {
                        return self::encode(json_decode($data, true), $rxss);
                    } else {
                        $data = urldecode($data);
                        $data = trim($rxss ? VipkwdStr::removeXss($data) : $data);
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
     * @param integer $httpStatusCode header(HTTP/1.1 206)
     * @return void
     */
    static function sendCode(int $httpStatusCode = 200): void
    {
        $response = self::response();
        isset($response::$codes[$httpStatusCode]) && header("HTTP/1.1 {$httpStatusCode} " . $response::$codes[$httpStatusCode]);
    }

    private static function __request(string $method, string $url, $param = [], string $dataType = 'form', array $header = [], bool $rInfo = false)
    {
        $ch = curl_init();
        if (!empty($header) && !VipkwdArray::isIndexList($header)) {
            $_header = [];
            foreach (array_keys($header) as $k) {
                if (preg_match("/[a-zA-Z_\-]/", $k)) {
                    if ($k == 'auth') {
                        $value = $header['auth'];
                        $type = isset($value[2]) ? \strtolower($value[2]) : 'basic';
                        switch ($type) {
                            case 'digest':
                                // @todo: Do not rely on curl
                                curl_setopt($ch, \CURLOPT_HTTPAUTH, \CURLAUTH_DIGEST);
                                curl_setopt($ch, \CURLOPT_USERPWD, "{$value[0]}:{$value[1]}");
                                break;
                            case 'ntlm':
                                curl_setopt($ch, \CURLOPT_HTTPAUTH, \CURLAUTH_NTLM);
                                curl_setopt($ch, \CURLOPT_USERPWD, "{$value[0]}:{$value[1]}");
                                break;
                            case 'basic':
                            default:
                                // Ensure that we don't have the header in different case and set the new value.
                                $_header[] = 'Authorization:Basic ' . base64_encode("{$value[0]}:{$value[1]}");
                                break;
                        }
                    } else {
                        $_header[] = $k . ":" . trim($header[$k]);
                    }
                } else {
                    $_header[] = $header[$k];
                }
                unset($header[$k]);
            }
            $header = $_header;
            unset($_header);
        }
        $header = array_values($header);
        $dataTypeArr = [
            'form' => ['content-type: application/x-www-form-urlencoded;charset=UTF-8'],
            'json' => ['Content-Type: application/json;charset=utf-8'],
        ];
        if (isset($dataTypeArr[$dataType])) {
            $header[] = $dataTypeArr[$dataType][0];
        }

        $header = array_unique($header);
        // devdump($header,1);
        $method = strtoupper($method);
        if ($method == 'GET') {
            if (!empty($param)) {

                $parse = parse_url($url);
                $_param = (is_array($param) ? http_build_query($param) : $param);
                $parse['query'] = isset($parse['query']) ? ($parse['query'] . '&' . $_param) : $_param;

                // 

                $url = $parse['scheme'] . '://' . $parse['host'];
                if (isset($parse['port']) && $parse['port'] != '80') {
                    $url .= ':' . $parse['port'];
                }
                if (isset($parse['path'])) {
                    $url .= $parse['path'];
                }
                $url .= '?' . $parse['query'];
                if (isset($parse['fragment'])) {
                    $url .= '#' . $parse['fragment'];
                }
            }
        } else {
        }
        $dataType == 'json' && is_array($param) && $param = json_encode($param);
        $dataType == 'form' && is_array($param) && $param = http_build_query($param);

        if (!empty($header)) {
            curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
        }
        // devdump($url);
        // devdump($param);
        // devdump($header, 1);

        curl_setopt($ch, \CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, \CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, \CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, \CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, self::$timeout);
        curl_setopt($ch, \CURLOPT_TIMEOUT, self::$timeout + 1);
        curl_setopt($ch, \CURLOPT_HEADER, FALSE);
        $result = curl_exec($ch);
        $data = $rInfo ? curl_getinfo($ch) : [];
        curl_close($ch);
        $data['result'] = self::encode($result, false);
        if (!$rInfo) $data = $data['result'];
        unset($result);
        return $data;
    }
}

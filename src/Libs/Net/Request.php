<?php
/**
 * Flight: An extensible micro-framework.
 *
 * @copyright   Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 * @license     MIT, http://flightphp.com/license
 */

namespace Vipkwd\Utils\Libs\Net;

use Vipkwd\Utils\Libs\Net\Util\Collection;

/**
 * The Request class represents an HTTP request. Data from
 * all the super globals $_GET, $_POST, $_COOKIE, and $_FILES
 * are stored and accessible via the Request object.
 *
 * The default request properties are:
 *   url - The URL being requested
 *   base - The parent subdirectory of the URL
 *   method - The request method (GET, POST, PUT, DELETE)
 *   referrer - The referrer URL
 *   ip - IP address of the client
 *   ajax - Whether the request is an AJAX request
 *   scheme - The server protocol (http, https)
 *   user_agent - Browser information
 *   type - The content type
 *   length - The content length
 *   query - Query string parameters
 *   data - Post parameters
 *   cookies - Cookie parameters
 *   files - Uploaded files
 *   secure - Connection is secure
 *   accept - HTTP accept parameters
 *   proxy_ip - Proxy IP address of the client
 */
class Request {
    /**
     * @var string URL being requested
     */
    public $url;

    /**
     * @var string Parent subdirectory of the URL
     */
    public $base;

    /**
     * @var string Request method (GET, POST, PUT, DELETE)
     */
    public $method;

    /**
     * @var string Referrer URL
     */
    public $referrer;

    /**
     * @var string IP address of the client
     */
    public $ip;

    /**
     * @var bool Whether the request is an AJAX request
     */
    public $ajax;

    /**
     * @var string Server protocol (http, https)
     */
    public $scheme;

    /**
     * @var string Browser information
     */
    public $user_agent;

    /**
     * @var string Content type
     */
    public $type;

    /**
     * @var int Content length
     */
    public $length;

    /**
     * @var \flight\util\Collection Query string parameters
     */
    public $query;

    /**
     * @var \flight\util\Collection Post parameters
     */
    public $data;

    /**
     * @var \flight\util\Collection Cookie parameters
     */
    public $cookies;

    /**
     * @var \flight\util\Collection Uploaded files
     */
    public $files;

    /**
     * @var bool Whether the connection is secure
     */
    public $secure;

    /**
     * @var string HTTP accept parameters
     */
    public $accept;

    /**
     * @var string Proxy IP address of the client
     */
    public $proxy_ip;

    /**
     * @var string HTTP host name
     */
    public $host;

    /**
     * Constructor.
     *
     * @param array $config Request configuration
     */
    public function __construct($config = array()) {
        $default = array(
            'url'   => str_replace('@', '%40', self::getVar('REQUEST_URI', '/')),
            'base'  => str_replace(array('\\',' '), array('/','%20'), dirname(self::getVar('SCRIPT_NAME'))),
            'ip'            => self::getVar('REMOTE_ADDR'),
            'host'          => self::getVar('HTTP_HOST'),
            'ajax'          => self::getVar('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest' || self::getHeaders('X-Requested-With') === 'XMLHttpRequest',
            'type'          => self::getVar('CONTENT_TYPE'),
            'method'        => self::getMethod(),
            'scheme'        => self::getScheme(),
            'length'        => self::getVar('CONTENT_LENGTH', 0),
            'secure'        => self::getScheme() == 'https',
            'accept'        => self::getVar('HTTP_ACCEPT'),
            'proxy_ip'      => self::getProxyIpAddress(),
            'referrer'      => self::getVar('HTTP_REFERER'),
            'user_agent'    => self::getVar('HTTP_USER_AGENT'),
            'server_name'   => self::getVar('SERVER_NAME'),
            'server_port'   => self::getVar('SERVER_PORT'),
            'server_addr'   => self::getVar('SERVER_ADDR'),
            'remote_port'   => self::getVar('REMOTE_PORT'),
            'remote_addr'   => self::getVar('REMOTE_ADDR'),
            'query_string'  => self::getVar('QUERY_STRING'),
            'query'     => new Collection(self::getQuerys()),
            'data'      => new Collection(self::getDatas()),
            'cookies'   => new Collection(self::getCookies()),
            'headers'   => new Collection(self::getHeaders()),
            'files'     => new Collection(self::getFiles()),
        );
        $config = array_merge( $default, $config);
        $this->init($config);
    }

    /**
     * Initialize request properties.
     *
     * @param array $properties Array of request properties
     */
    public function init($properties = array()) {
        // Set all the defined properties
        foreach ($properties as $name => $value) {
            $this->$name = $value;
        }

        // Get the requested URL without the base directory
        if ($this->base != '/' && strlen($this->base) > 0 && strpos($this->url, $this->base) === 0) {
            $this->url = substr($this->url, strlen($this->base));
        }

        // Default url
        if (empty($this->url)) {
            $this->url = '/';
        }
        // Merge URL query parameters with $_GET
        else {
            $_GET += self::parseQuery($this->url);

            $this->query->setData($_GET);
        }

        // Check for JSON input
        if (strpos($this->type, 'application/json') === 0) {
            $body = $this->getBody();
            if ($body != '') {
                $data = json_decode($body, true);
                if ($data != null) {
                    $this->data->setData($data);
                }
            }
        }
    }
	/**
	 * Returns the most preferred language by browser. Uses the `Accept-Language` header. If no match is reached, it returns `null`.
	 * @param  string[]  $langs  supported languages
	 */
	public static function detectLanguage(array $langs): ?string{
		$header = self::getHeaders('Accept-Language');
		if (!$header) {
			return null;
		}

		$s = strtolower($header);  // case insensitive
		$s = strtr($s, '_', '-');  // cs_CZ means cs-CZ
		rsort($langs);             // first more specific
		preg_match_all('#(' . implode('|', $langs) . ')(?:-[^\s,;=]+)?\s*(?:;\s*q=([0-9.]+))?#', $s, $matches);

		if (!$matches[0]) {
			return null;
		}

		$max = 0;
		$lang = null;
		foreach ($matches[1] as $key => $value) {
			$q = $matches[2][$key] === '' ? 1.0 : (float) $matches[2][$key];
			if ($q > $max) {
				$max = $q;
				$lang = $value;
			}
		}
		return $lang;
	}

    /**
     * Gets the body of the request.
     *
     * @return string Raw HTTP request body
     */
    public static function getBody() {
        static $body;
        if (!is_null($body)) {
            return $body;
        }
        $method = self::getMethod();
        if ($method == 'POST' || $method == 'PUT' || $method == 'DELETE' || $method == 'PATCH') {
            $body = file_get_contents('php://input');
        }
        return $body;
    }

    /**
     * Gets the request method.
     *
     * @return string
     */
    public static function getMethod() {
        $method = self::getVar('REQUEST_METHOD', 'GET');

        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        }
        elseif (isset($_REQUEST['_method'])) {
            $method = $_REQUEST['_method'];
        }

        return strtoupper($method);
    }

    /**
     * Gets the real remote IP address.
     *
     * @return string IP address
     */
    public static function getProxyIpAddress() {
        static $forwarded = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        $flags = \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE;

        foreach ($forwarded as $key) {
            if (array_key_exists($key, $_SERVER)) {
                sscanf($_SERVER[$key], '%[^,]', $ip);
                if (filter_var($ip, \FILTER_VALIDATE_IP, $flags) !== false) {
                    return $ip;
                }
            }
        }

        return '';
    }

    /**
     * Gets a variable from $_SERVER using $default if not provided.
     *
     * @param string $var Variable name
     * @param string $default Default value to substitute
     * @return string Server variable value
     */
    public static function getVar($var, $default = '') {
        return isset($_SERVER[$var]) ? $_SERVER[$var] : $default;
    }

    /**
     * Parse query parameters from a URL.
     *
     * @param string $url URL string
     * @return array Query parameters
     */
    public static function parseQuery($url) {
        $params = array();

        $args = parse_url($url);
        if (isset($args['query'])) {
            parse_str($args['query'], $params);
        }

        return $params;
    }

    public static function getScheme() {
        if (
            (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on')
            ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ||
            (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on')
            ||
            (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
        ) {
            return 'https';
        }
        return 'http';
    }

    public static function getHeaders(?string $key = null){
        $servers = $_SERVER;
        if (function_exists('apache_request_headers')) {
			return $servers = \apache_request_headers();
		}
        $headers = [];
        foreach($servers as $k => $v){
            if( substr($k, 0, 5) == "HTTP_" ){
                $headers[strtolower($k)]=$v;
            }
        }
        if($key){
            $key = strtolower($key);
            $key = strtr($key, '-', '_');
            if( strpos($key, "http_") !== 0){
                $key = 'http_'.$key;
            }
            return isset($headers[$key]) ? $headers[$key] : null;
        }
        return $headers;
    }


    public static function getCookies(?string $key = null){
        if($key){
            return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
        }
        return $_COOKIE;
    }

    public static function getDatas(?string $key = null){
        if($key){
            return isset($_POST[$key]) ? $_POST[$key] : null;
        }
        return $_POST;
    }
    public static function getQuerys(?string $key = null){
        if($key){
            return isset($_GET[$key]) ? $_GET[$key] : null;
        }
        return $_GET;
    }

    public static function getFiles(?string $key = null){
        if($key){
            return isset($_FILES[$key]) ? $_FILES[$key] : null;
        }
        return $_FILES;
    }

    public static function getUser(): ?string{
		return self::getVar('PHP_AUTH_USER', null);
	}

    public static function getPassword(): ?string{
		return self::getVar('PHP_AUTH_PW', null);
	}
}

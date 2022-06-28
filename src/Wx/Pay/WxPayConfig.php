<?php

/**
 * @name 配置账号信息
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay;

use Vipkwd\Utils\System\File;

class WxPayConfig
{
	private static $configFile = 'wx-pay.ini';

	private static $_instance = [];

	private $defaults = [

		//=======【基本信息设置】=====================================
		//
		/**
		 * TODO: 修改这里配置为您自己申请的商户信息
		 * 微信公众号信息配置
		 * 
		 * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
		 * 
		 * MCHID：商户号（必须配置，开户邮件中可查看）
		 * 
		 * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
		 * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
		 * 
		 * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
		 * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
		 * @var string
		 */
		"notify_url" => '',
		"app_id" => "",
		"mch_id" => "",
		"key" => "",
		"app_secret" => "",

		//=======【证书路径设置】=====================================
		/**
		 * TODO：设置商户证书路径
		 * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
		 * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
		 * @var path
		 */
		"sslcert_path" => '', //证书位置;绝对路径
		"sslkey_path" => '', //证书位置;绝对路径

		//=======【curl代理设置】===================================
		/**
		 * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
		 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
		 * 默认CURL_PROXY_HOST="0.0.0.0" 和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
		 * @var unknown_type
		 */
		"curl_proxy_host" => "0.0.0.0", //"10.152.18.220",
		"curl_proxy_port" => 0, //8080,

		//=======【上报信息配置】===================================
		/**
		 * TODO：接口调用上报等级，默认仅错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少开启错误上报。
		 * 上报等级，0.关闭上报, 1.仅错误出错上报, 2.全量上报
		 * @var int
		 */
		"report_level" => 1,

	];

	private function __construct()
	{
		$this->iniFile = __DIR__ . '/' . self::$configFile;
	}
	private function __clone()
	{
	}

	static function instance()
	{
		$key = md5(self::$configFile);
		if (!isset(self::$_instance[$key])) {
			self::$_instance[$key] = new self();
		}
		return self::$_instance[$key];
	}

	public function settings(array $options)
	{
		if (!empty($options)) {
			$options = array_merge($this->defaults, $options);
			$ini = file_exists($this->iniFile) ? File::readIniFile($this->iniFile) : [];
			$ini = array_merge($ini, $options);
			return File::writeIniFile($ini, $this->iniFile);
		}
	}

	public function data()
	{
		if (file_exists($this->iniFile)) {
			return File::readIniFile($this->iniFile);
		}
	}

	public function __get($key)
	{
		if (file_exists($this->iniFile)) {
			$ini = File::readIniFile($this->iniFile);
			if (is_array($ini) && isset($ini[$key])) {
				return $ini[$key];
			}
		}
		return null;
	}
}

<?php

/**
 * @name 接口访问类
 * 
 * 包含所有微信支付API列表的封装，类中方法为static方法，
 * 每个接口有默认超时时间（除提交被扫支付为10s，上报超时时间为1s外，其他均为6s）
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay;

use Vipkwd\Utils\Wx\Pay\Maps\WxPayOrderQuery;
use Vipkwd\Utils\Wx\Pay\Maps\WxPayUnifiedOrder;
use Vipkwd\Utils\Wx\Pay\Maps\WxPayException;
use Vipkwd\Utils\Wx\Pay\Maps\WxPayResults;
use Vipkwd\Utils\Wx\Pay\Maps\WxPayShortUrl;
use Vipkwd\Utils\Wx\Pay\Maps\WxPayReport;

class WxPayApi
{
	//关闭CSRF
	public $enableCsrfValidation = false;
	/**
	 * 
	 * 统一下单，WxPayUnifiedOrder中out_trade_no、body、total_fee、trade_type必填
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayUnifiedOrder $inputObj
	 * @param int $timeOut
	 * @throws WxPayException
	 * @return array 成功时返回，其他抛异常
	 */
	public static function unifiedOrder(WxPayUnifiedOrder $inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
		//检测必填参数
		if (!$inputObj->hasAttrOutTradeNo()) {
			throw new WxPayException("缺少统一支付接口必填参数out_trade_no！");
		} else if (!$inputObj->hasAttrBody()) {
			throw new WxPayException("缺少统一支付接口必填参数body！");
		} else if (!$inputObj->hasAttrTotalFee()) {
			throw new WxPayException("缺少统一支付接口必填参数total_fee！");
		} else if (!$inputObj->hasAttrTradeType()) {
			throw new WxPayException("缺少统一支付接口必填参数trade_type！");
		}

		//关联参数
		if ($inputObj->getAttrTradeType() == "JSAPI" && !$inputObj->hasAttrOpenId()) {
			throw new WxPayException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
		}
		if ($inputObj->getAttrTradeType() == "NATIVE" && !$inputObj->hasAttrProductId()) {
			throw new WxPayException("统一支付接口中，缺少必填参数product_id！trade_type为JSAPI时，product_id为必填参数！");
		}

		//异步通知url未设置，则使用配置文件中的url
		if (!$inputObj->hasAttrNotifyUrl()) {
			$inputObj->setAttrNotifyUrl(WxPayConfig::instance()->notify_url); //异步通知url
		}

		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrSpbillCreateIp($_SERVER['REMOTE_ADDR']); //终端ip	  
		//$inputObj->setAttrSpbillCreateIp("1.1.1.1");  	    
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		//签名
		$inputObj->setAttrSign();
		$xml = $inputObj->toXml();

		$startTimeStamp = self::getMillisecond(); //请求开始时间

		$response = self::postXmlCurl($xml, $url, false, $timeOut);
		//return $response;

		$result = WxPayResults::Init($response);

		self::reportCostTime($url, $startTimeStamp, $result); //上报请求花费时间

		return $result;
	}

	/**
	 * 
	 * 查询订单，WxPayOrderQuery中out_trade_no、transaction_id至少填一个
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayOrderQuery $inputObj
	 * @param int $timeOut
	 * @throws WxPayException
	 * @return array 成功时返回，其他抛异常
	 */
	public static function orderQuery($inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/pay/orderquery";
		//检测必填参数
		if (!$inputObj->hasAttrOutTradeNo() && !$inputObj->hasAttrTransactionId()) {
			throw new WxPayException("订单查询接口中，out_trade_no、transaction_id至少填一个！");
		}
		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		$inputObj->setAttrSign(); //签名
		$xml = $inputObj->toXml();

		$startTimeStamp = self::getMillisecond(); //请求开始时间
		$response = self::postXmlCurl($xml, $url, false, $timeOut);
		$result = WxPayResults::Init($response);
		self::reportCostTime($url, $startTimeStamp, $result); //上报请求花费时间

		return $result;
	}

	/**
	 * 
	 * 关闭订单，WxPayCloseOrder中out_trade_no必填
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayCloseOrder $inputObj
	 * @param int $timeOut
	 * @throws WxPayException
	 * @return array 成功时返回，其他抛异常
	 */
	public static function closeOrder($inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/pay/closeorder";
		//检测必填参数
		if (!$inputObj->hasAttrOutTradeNo()) {
			throw new WxPayException("订单查询接口中，out_trade_no必填！");
		}
		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		$inputObj->setAttrSign(); //签名
		$xml = $inputObj->toXml();

		$startTimeStamp = self::getMillisecond(); //请求开始时间
		$response = self::postXmlCurl($xml, $url, false, $timeOut);
		$result = WxPayResults::Init($response);
		self::reportCostTime($url, $startTimeStamp, $result); //上报请求花费时间

		return $result;
	}

	/**
	 * 
	 * 申请退款，WxPayRefund中out_trade_no、transaction_id至少填一个且
	 * out_refund_no、total_fee、refund_fee、op_user_id为必填参数
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayRefund $inputObj
	 * @param int $timeOut
	 * @throws WxPayException
	 * @return array 成功时返回，其他抛异常
	 */
	public static function refund($inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
		//检测必填参数
		if (!$inputObj->hasAttrOutTradeNo() && !$inputObj->hasAttrTransactionId()) {
			throw new WxPayException("退款申请接口中，out_trade_no、transaction_id至少填一个！");
		} else if (!$inputObj->hasAttrOutRefundNo()) {
			throw new WxPayException("退款申请接口中，缺少必填参数out_refund_no！");
		} else if (!$inputObj->hasAttrTotalFee()) {
			throw new WxPayException("退款申请接口中，缺少必填参数total_fee！");
		} else if (!$inputObj->hasAttrRefundFee()) {
			throw new WxPayException("退款申请接口中，缺少必填参数refund_fee！");
		}
		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrOpUserId(WxPayConfig::instance()->mch_id);
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		$inputObj->setAttrSign(); //签名
		$xml = $inputObj->toXml();
		$startTimeStamp = self::getMillisecond(); //请求开始时间
		$response = self::postXmlCurl($xml, $url, true, $timeOut);
		$result = WxPayResults::Init($response);
		self::reportCostTime($url, $startTimeStamp, $result); //上报请求花费时间

		return $result;
	}

	/**
	 * 
	 * 查询退款
	 * 提交退款申请后，通过调用该接口查询退款状态。退款有一定延时，
	 * 用零钱支付的退款20分钟内到账，银行卡支付的退款3个工作日后重新查询退款状态。
	 * WxPayRefundQuery中out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayRefundQuery $inputObj
	 * @param int $timeOut
	 * @throws WxPayException
	 * @return array 成功时返回，其他抛异常
	 */
	public static function refundQuery($inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/pay/refundquery";
		//检测必填参数
		if (
			!$inputObj->hasAttrOutRefundNo() &&
			!$inputObj->hasAttrOutTradeNo() &&
			!$inputObj->hasAttrTransactionId() &&
			!$inputObj->hasAttrRefundId()
		) {
			throw new WxPayException("退款查询接口中，out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个！");
		}
		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		$inputObj->setAttrSign(); //签名
		$xml = $inputObj->toXml();

		$startTimeStamp = self::getMillisecond(); //请求开始时间
		$response = self::postXmlCurl($xml, $url, false, $timeOut);
		$result = WxPayResults::Init($response);
		self::reportCostTime($url, $startTimeStamp, $result); //上报请求花费时间

		return $result;
	}

	/**
	 * 下载对账单，WxPayDownloadBill中bill_date为必填参数
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayDownloadBill $inputObj
	 * @param int $timeOut
	 * @throws WxPayException
	 * @return array 成功时返回，其他抛异常
	 */
	public static function downloadBill($inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/pay/downloadbill";
		//检测必填参数
		if (!$inputObj->hasAttrBillDate()) {
			throw new WxPayException("对账单接口中，缺少必填参数bill_date！");
		}
		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		$inputObj->setAttrSign(); //签名
		$xml = $inputObj->toXml();

		$response = self::postXmlCurl($xml, $url, false, $timeOut);
		if (substr($response, 0, 5) == "<xml>") {
			return "";
		}
		return $response;
	}

	/**
	 * 提交被扫支付API
	 * 收银员使用扫码设备读取微信用户刷卡授权码以后，二维码或条码信息传送至商户收银台，
	 * 由商户收银台或者商户后台调用该接口发起支付。
	 * WxPayWxPayMicroPay中body、out_trade_no、total_fee、auth_code参数必填
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayWxPayMicroPay $inputObj
	 * @param int $timeOut
	 * 
	 * @return array
	 */
	public static function micropay($inputObj, $timeOut = 10)
	{
		$url = "https://api.mch.weixin.qq.com/pay/micropay";
		//检测必填参数
		if (!$inputObj->hasAttrBody()) {
			throw new WxPayException("提交被扫支付API接口中，缺少必填参数body！");
		} else if (!$inputObj->hasAttrOutTradeNo()) {
			throw new WxPayException("提交被扫支付API接口中，缺少必填参数out_trade_no！");
		} else if (!$inputObj->hasAttrTotalFee()) {
			throw new WxPayException("提交被扫支付API接口中，缺少必填参数total_fee！");
		} else if (!$inputObj->hasAttrAuthCode()) {
			throw new WxPayException("提交被扫支付API接口中，缺少必填参数auth_code！");
		}

		$inputObj->setAttrSpbillCreateIp($_SERVER['REMOTE_ADDR']); //终端ip
		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		$inputObj->setAttrSign(); //签名
		$xml = $inputObj->toXml();

		$startTimeStamp = self::getMillisecond(); //请求开始时间
		$response = self::postXmlCurl($xml, $url, false, $timeOut);
		$result = WxPayResults::Init($response);
		self::reportCostTime($url, $startTimeStamp, $result); //上报请求花费时间

		return $result;
	}

	/**
	 * 
	 * 撤销订单API接口，WxPayReverse中参数out_trade_no和transaction_id必须填写一个
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayReverse $inputObj
	 * @param int $timeOut
	 * @throws WxPayException
	 * @return array
	 */
	public static function reverse($inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/secapi/pay/reverse";
		//检测必填参数
		if (!$inputObj->hasAttrOutTradeNo() && !$inputObj->hasAttrTransactionId()) {
			throw new WxPayException("撤销订单API接口中，参数out_trade_no和transaction_id必须填写一个！");
		}

		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		$inputObj->setAttrSign(); //签名
		$xml = $inputObj->toXml();

		$startTimeStamp = self::getMillisecond(); //请求开始时间
		$response = self::postXmlCurl($xml, $url, true, $timeOut);
		$result = WxPayResults::Init($response);
		self::reportCostTime($url, $startTimeStamp, $result); //上报请求花费时间

		return $result;
	}

	/**
	 * 
	 * 测速上报，该方法内部封装在report中，使用时请注意异常流程
	 * WxPayReport中interface_url、return_code、result_code、user_ip、execute_time_必填
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayReport $inputObj
	 * @param int $timeOut
	 * @throws WxPayException
	 * @return array 成功时返回，其他抛异常
	 */
	public static function report($inputObj, $timeOut = 1)
	{
		$url = "https://api.mch.weixin.qq.com/payitil/report";
		//检测必填参数
		if (!$inputObj->hasAttrInterfaceUrl()) {
			throw new WxPayException("接口URL，缺少必填参数interface_url！");
		}
		if (!$inputObj->hasAttrReturnCode()) {
			throw new WxPayException("返回状态码，缺少必填参数return_code！");
		}
		if (!$inputObj->hasAttrResultCode()) {
			throw new WxPayException("业务结果，缺少必填参数result_code！");
		}
		if (!$inputObj->hasAttrUserIp()) {
			throw new WxPayException("访问接口IP，缺少必填参数user_ip！");
		}
		if (!$inputObj->hasAttrExecuteTime()) {
			throw new WxPayException("接口耗时，缺少必填参数execute_time_！");
		}
		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrUserIp($_SERVER['REMOTE_ADDR']); //终端ip
		$inputObj->setAttrTime(date("YmdHis")); //商户上报时间	 
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		$inputObj->setAttrSign(); //签名
		$xml = $inputObj->toXml();

		// $startTimeStamp = self::getMillisecond(); //请求开始时间
		$response = self::postXmlCurl($xml, $url, false, $timeOut);
		return $response;
	}

	/**
	 * 
	 * 生成二维码规则,模式一生成支付二维码
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayBizPayUrl $inputObj
	 * @param int $timeOut
	 * @throws WxPayException
	 * @return array 成功时返回，其他抛异常
	 */
	public static function bizPayUrl($inputObj, $timeOut = 6)
	{
		if (!$inputObj->hasAttrProductId()) {
			throw new WxPayException("生成二维码，缺少必填参数product_id！");
		}
		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrTimeStamp(time()); //时间戳	 
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		$inputObj->setAttrSign(); //签名

		return $inputObj->getAttrValues();
	}

	/**
	 * 
	 * 转换短链接
	 * 该接口主要用于扫码原生支付模式一中的二维码链接转成短链接(weixin://wxpay/s/XXXXXX)，
	 * 减小二维码数据量，提升扫描速度和精确度。
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayShortUrl $inputObj
	 * @param int $timeOut
	 * @throws WxPayException
	 * @return array 成功时返回，其他抛异常
	 */
	public static function shorturl(WxPayShortUrl $inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/tools/shorturl";
		//检测必填参数
		if (!$inputObj->hasAttrLongUrl()) {
			throw new WxPayException("需要转换的URL，签名用原串，传输需URL encode！");
		}
		$inputObj->setAttrAppId(WxPayConfig::instance()->app_id); //公众账号ID
		$inputObj->setAttrMchId(WxPayConfig::instance()->mch_id); //商户号
		$inputObj->setAttrNonceStr(self::getNonceStr()); //随机字符串

		$inputObj->setAttrSign(); //签名
		$xml = $inputObj->toXml();

		$startTimeStamp = self::getMillisecond(); //请求开始时间
		$response = self::postXmlCurl($xml, $url, false, $timeOut);
		$result = WxPayResults::Init($response);
		self::reportCostTime($url, $startTimeStamp, $result); //上报请求花费时间

		return $result;
	}

	/**
	 * 
	 * 支付结果通用通知
	 * @param callable $callback
	 * 直接回调函数使用方法: notify(you_function);
	 * 回调类成员函数方法:notify(array($this, you_function));
	 * $callback 原型为：function function_name($data){}
	 */
	public static function notify(callable $callback, &$msg)
	{
		//获取通知的数据
		// $xml = Yii::$app->request->getRawBody('HTTP_RAW_POST_DATA');
		$xml = file_get_contents('php://input');
		//如果返回成功则验证签名
		try {
			$result = WxPayResults::Init($xml);
		} catch (WxPayException $e) {
			$msg = $e->errorMessage();
			return false;
		}

		return call_user_func($callback, $result);
	}

	/**
	 * 
	 * 产生随机字符串，不长于32位
	 * @param int $length
	 * @return 产生的随机字符串
	 */
	public static function getNonceStr($length = 32)
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}

	/**
	 * 直接输出xml
	 * @param string $xml
	 */
	public static function replyNotify($xml)
	{
		echo $xml;
	}

	/**
	 * 
	 * 上报数据， 上报的时候将屏蔽所有异常流程
	 * @param string $usrl
	 * @param int $startTimeStamp
	 * @param array $data
	 */
	private static function reportCostTime($url, $startTimeStamp, $data)
	{
		//如果不需要上报数据
		if (WxPayConfig::instance()->report_level == 0) {
			return;
		}
		//如果仅失败上报
		if (
			WxPayConfig::instance()->report_level == 1 &&
			array_key_exists("return_code", $data) &&
			$data["return_code"] == "SUCCESS" &&
			array_key_exists("result_code", $data) &&
			$data["result_code"] == "SUCCESS"
		) {
			return;
		}

		//上报逻辑
		$endTimeStamp = self::getMillisecond();
		$objInput = new WxPayReport();
		$objInput->setAttrInterfaceUrl($url);
		$objInput->setAttrExecuteTime($endTimeStamp - $startTimeStamp);
		//返回状态码
		if (array_key_exists("return_code", $data)) {
			$objInput->setAttrReturnCode($data["return_code"]);
		}
		//返回信息
		if (array_key_exists("return_msg", $data)) {
			$objInput->setAttrReturnMsg($data["return_msg"]);
		}
		//业务结果
		if (array_key_exists("result_code", $data)) {
			$objInput->setAttrResultCode($data["result_code"]);
		}
		//错误代码
		if (array_key_exists("err_code", $data)) {
			$objInput->setAttrErrCode($data["err_code"]);
		}
		//错误代码描述
		if (array_key_exists("err_code_des", $data)) {
			$objInput->setAttrErrCodeDes($data["err_code_des"]);
		}
		//商户订单号
		if (array_key_exists("out_trade_no", $data)) {
			$objInput->setAttrOutTradeNo($data["out_trade_no"]);
		}
		//设备号
		if (array_key_exists("device_info", $data)) {
			$objInput->setAttrDeviceInfo($data["device_info"]);
		}

		try {
			self::report($objInput);
		} catch (WxPayException $e) {
			//不做任何处理
		}
	}

	/**
	 * 以post方式提交xml到对应的接口url
	 * 
	 * @param string $xml  需要post的xml数据
	 * @param string $url  url
	 * @param bool $useCert 是否需要证书，默认不需要
	 * @param int $second   url执行超时时间，默认30s
	 * @throws WxPayException
	 */
	private static function postXmlCurl($xml, $url, $useCert = false, $second = 30)
	{
		// $isdir = $_SERVER['DOCUMENT_ROOT'] . "/xcert/"; //证书位置;绝对路径
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);

		//如果有配置代理这里就设置代理
		if (
			WxPayConfig::instance()->curl_proxy_host != "0.0.0.0"
			&& WxPayConfig::instance()->curl_proxy_port != 0
		) {
			curl_setopt($ch, CURLOPT_PROXY, WxPayConfig::instance()->curl_proxy_host);
			curl_setopt($ch, CURLOPT_PROXYPORT, WxPayConfig::instance()->curl_proxy_port);
		}

		curl_setopt($ch, CURLOPT_URL, $url);

		//原微信的有问题
		//curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
		//curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验

		//新加的
		if (stripos($url, "https://") !== FALSE) {
			curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //严格校验
		}

		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		if ($useCert == true) {
			//设置证书
			//使用证书：cert 与 key 分别属于两个.pem文件
			curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
			curl_setopt($ch, CURLOPT_SSLCERT, WxPayConfig::instance()->sslcert_path); //证书位置;绝对路径
			curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
			curl_setopt($ch, CURLOPT_SSLKEY, WxPayConfig::instance()->sslkey_path); //证书位置;绝对路径
		}
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

		//运行curl
		$data = curl_exec($ch);
		//返回结果
		if ($data) {
			curl_close($ch);
			return $data;
		} else {
			$error = curl_errno($ch);
			curl_close($ch);
			throw new WxPayException("curl出错，错误码:$error");
		}
	}

	/**
	 * 获取毫秒级别的时间戳
	 */
	private static function getMillisecond()
	{
		//获取毫秒的时间戳
		$time = explode(" ", microtime());
		$time = $time[1] . ($time[0] * 1000);
		$time2 = explode(".", $time);
		$time = $time2[0];
		return $time;
	}
}

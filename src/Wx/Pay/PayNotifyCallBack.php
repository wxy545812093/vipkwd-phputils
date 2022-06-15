<?php

/**
 * @name notify 处理类
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay;

use common\library\cashier\PayCenter;
use Yii;

use Vipkwd\Utils\Wx\Pay\Log\Log;
use Vipkwd\Utils\Wx\Pay\Maps\WxPayOrderQuery;
use Vipkwd\Utils\Wx\Pay\Maps\WxPayNotify;


class PayNotifyCallBack extends WxPayNotify
{
	/**
	 * 远程查询订单
	 */
	public function queryOrderByTid($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->setAttrTransactionId($transaction_id);
		$result = WxPayApi::orderQuery($input);
		Log::DEBUG("query:" . json_encode($result));
		if (
			array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS"
		) {

			//进行网站订单业务逻辑处理
			$out_trade_no = $result["out_trade_no"];
			//微信交易单号
			$transaction_id = $result["transaction_id"];
			$total_fee = $result["total_fee"] / 100;
			// $errcode = PayCenter::Process($out_trade_no, 4, $total_fee, $transaction_id)['errcode'];
			// if ($errcode == 0) {
			// 	$sskey = $out_trade_no . '_status';
			// 	// Yii::$app->cache->set($sskey, 1, 60);
			// 	return true;
			// } else {
			// 	return false;
			// }
		}
		return false;
	}

	//重写回调处理函数
	public function notifyProcess($data, &$msg)
	{
		Log::DEBUG("call back:" . json_encode($data));
		$notfiyOutput = array();

		if (!array_key_exists("transaction_id", $data)) {
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if (!$this->queryOrderByTid($data["transaction_id"])) {
			$msg = "订单查询失败";
			return false;
		}
		return true;
	}
}

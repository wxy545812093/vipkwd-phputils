<?php

/**
 * @name 支付实现类
 * 
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Pay;

use Vipkwd\Utils\Wx\Pay\Maps\WxPayBizPayUrl;
use Vipkwd\Utils\Wx\Pay\Maps\WxPayUnifiedOrder;
class NativePay
{
	/**
	 * 
	 * [模式一]生成扫描支付URL
	 * 
	 * @param string $productId 商品ID
	 */
	public function getPrePayUrl($productId)
	{
		$biz = new WxPayBizPayUrl();
		$biz->setAttrProductId($productId);
		$values = WxpayApi::bizPayUrl($biz);
		$url = "weixin://wxpay/bizpayurl?" . $this->toUrlParams($values);
		return $url;
	}

	/**
	 * 
	 * [模式二]生成直接支付url，支付url有效期为2小时
	 * @param WxPayUnifiedOrder $input
	 */
	public function getPayUrl(WxPayUnifiedOrder $input)
	{
		if ($input->getAttrTradeType() == "NATIVE") {
			$result = WxPayApi::unifiedOrder($input);
			return $result;
		}
	}

	/**
	 * 
	 * 参数数组转换为url参数
	 * @param array $urlObj
	 */
	private function toUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v) {
			$buff .= $k . "=" . $v . "&";
		}

		$buff = trim($buff, "&");
		return $buff;
	}
}

<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
namespace Amulet\Wechat\Pay;

class WxPayException extends \Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}

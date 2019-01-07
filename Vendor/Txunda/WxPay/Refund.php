<?php
include_once 'lib/WxPay.Api.php';
include_once 'lib/WxPay.Data.php';

class Refund
{
	private $trade_no = '';
	private $out_trade_no = '';
	private $receipt_amount = '';
	function __construct($transaction_id,$order_sn,$receipt_amount)
	{
		$this->trade_no = $transaction_id; // 退款订单号
		$this->out_trade_no = $order_sn; // 订单号
		$this->receipt_amount = $receipt_amount * 100;// 实际支付价格(微信返回)
	}

    /**
     * 微信支付退款
     * @return bool
     */
	function appRefund()
	{
		$input = new \WxPayRefund();
		$input->SetAppid(WxPayConfig::APPID);
		$input->SetOp_user_id(WxPayConfig::MCHID);
		$input->SetMch_id(WxPayConfig::MCHID);
		$input->SetNonce_str(WxPayApi::getNonceStr());
		$input->SetOut_trade_no($this->out_trade_no); // 订单号
		$input->SetTransaction_id($this->trade_no); // 商户订单号
		$input->SetOut_refund_no($this->out_trade_no); // 商户退款订单号
		$input->SetTotal_fee($this->receipt_amount);
		$input->SetRefund_fee($this->receipt_amount);
		$result = WxPayApi::refund($input); // 执行退款
//		$result_new = new \WxPayDataBase();
//		$res_refund = $result_new->FromXml($result);
		if($result['return_code'] =='SUCCESS' && $result['return_msg'] == 'OK'){
			if(!empty($result['transaction_id'])){
				return true;
			}else{
				return $result['err_code_des'];
			}
		}else{
			return $result['return_msg'];
		}
	}
}


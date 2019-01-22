<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/01/03
 * Time: 02:24
 */

namespace Api\Controller;

use Think\Controller;

/**
 * 加盟商支付控制器
 * Class PayController
 * @package Api\Controller
 */
class PayController extends BaseController
{

    /**
     *支付构造方法
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/03 02:26
     */
    public function _initialize ()
    {
        parent::_initialize ();
    }

    /**
     *提现页面
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/03 02:27
     */
    public function withdraw ()
    {
        $post = checkAppData ('token' , 'token');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $member = $this->getAgentInfo ($post['token']);
//        $alipay = M('Withdraw')->where(array('member_id'=>$member['id']))->find();

        if ( !empty($member) ) {
            $this->apiResponse ('1' , '成功' , $member['balance']);
        }
    }

    /**
     *提现类型
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/03 14:26
     */
    public function bankType ()
    {
        $bank = M ('BankType')->where (array ('status' => 1))->field ('bank_name,bank_pic')->select ();
        foreach ( $bank as $k => $v ) {
            $pic['bank_pic'] = $v['bank_pic'];
            $pic_path = getPicPath ($pic['bank_pic']);
            $bank[$k]['bank_pic_path'] = $pic_path;
        }
        if ( !empty($bank) ) {
            $this->apiResponse ('1' , '成功' , $bank);
        } else {
            $this->apiResponse ('0' , '暂无提现类型');
        }
    }

    /**
     *添加银行卡
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/03 15:52
     */
    public function addBankCard ()
    {
        $post = checkAppData ('token,card_name,card_code,ID_card,phone,card_id' , 'token-持卡人姓名-持卡人卡号-身份证号-手机号-卡类型');
        /*$post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $post['card_name'] = '王子';
        $post['card_code'] = '621679087718187784585';
        $post['ID_card'] = '587456988541235187';
        $post['phone'] = '18635359874';
        $post['card_id'] = 1;*/         //1建行  2中行  3农行

        $agent = $this->getAgentInfo ($post['token']);
        $data = array (
            'agent_id' => $agent['id'] ,
            'card_name' => $post['card_name'] ,
            'card_code' => $post['card_code'] ,
            'ID_card' => $post['ID_card'] ,
            'phone' => $post['phone'] ,
            'card_id' => $post['card_id'] ,
        );
        $agent_id = M ('BankCard')->where (array ('agent_id' => $data['agent_id'] , 'card_code' => $post['card_code']))->find ();
        if ( !empty($agent_id) ) {
            $save = M ('BankCard')->where (array ('agent_id' => $data['agent_id']))->save ($data);
            $this->apiResponse ('1' , '此卡号已绑定');
        }
        $card = M ('BankCard')->where (array ('agent_id' => $agent['id']))->find ();
        $car_way = substr ($post['card_code'] , -4);
        $car_ways = M ('BankType')->where (array ('id' => $post['card_id']))->field ('bank_name')->find ();
        $car = substr ($car_ways['bank_name'] , 0) . '储蓄卡   尾号' . $car_way;
        /* if($post['card_id'] = 1){
             $car = '建设储蓄卡  尾号'.$car_way;
         }else if($post['card_id'] = 2){
             $car = '中行储蓄卡  尾号'.$car_way;
         }else if($post['card_id'] = 3){
             $car = '农行储蓄卡  尾号'.$car_way;
         }*/
        if ( empty($card) ) {
            $add = M ('BankCard')->add ($data);
            $this->apiResponse ('1' , '绑定成功' , $car);
        } else {
            $save = M ('BankCard')->where (array ('agent_id' => $data['agent_id']))->save ($data);
            $this->apiResponse ('1' , '已修改' , $car);
        }
    }

    /**
     *提现提交
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/04 02:01
     */
    public function getWithdraw ()
    {
        $post = checkAppData ('token,price' , 'token-提现金额');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['price'] = 500;

        $agent = $this->getAgentInfo ($post['token']);
        $data = array (
            'balance' => $agent['balance'] - $post['price'] ,
        );
        if ( $data['balance'] < 0 ) {
            $this->apiResponse ('0' , '对不起,您余额不足');
        }
        $balance = M ('Agent')->where (array ('id' => $agent['id']))->save ($data);
        if ( !empty($balance) ) {
            $this->apiResponse ('1' , '已提交申请');
        } else {
            $this->apiResponse ('0' , '申请失败');
        }
    }

    /**
     *提现记录
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/04 17:26
     */
    public function withdrawInfo ()
    {
        $post = checkAppData ('token' , 'token');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $agent = $this->getAgentInfo ($post['token']);
        $withdraw = M ('Withdraw')->where (array ('agent_id' => $agent['id']))->field ('money,status,create_time')->select ();
        //var_dump($withdraw);exit;
        if ( !empty($withdraw) ) {
            $this->apiResponse ('1' , '成功' , $withdraw);
        } else {
            $this->apiResponse ('0' , '暂无提现记录');
        }
    }

    /*****————————————————————用户端支付————————————————————*****/
    /**
     * 获取支付宝支付参数
     * 传递参数的方式：post
     * 需要传递的参数：
     * 订单编号：orderid
     * 类型：o_type 1洗车订单 2小鲸卡购买 3余额充值
     **/
    public function Alipay ()
    {
        Vendor ('Txunda.Alipay.Alipay');
        $request = I ('post.');
        $rule = array (
            array ('orderid' , 'string' , '订单编号不能为空') ,
            array ('o_type' , 'string' , '订单类型不能为空') ,
        );
        $this->checkParam ($rule);
        $order_info = D ("Order")->where (array ('orderid' => $request['orderid'] , 'o_type' => $request['o_type']))->find ();
        if ( !$order_info ) {
            $this->apiResponse (0 , '订单信息查询失败');
        }
        $url_data = [
            "orderid" => $order_info['orderid'] ,
            "o_type" => $order_info['o_type'] ,
            "m_id" => $order_info['m_id'] ,
        ];
        $notify_url = C ('API_URL') . '/index.php/Api/Pay/AlipayNotify?' . http_build_query ($url_data);
        // 生成支付字符串
        $out_trade_no = $order_info['orderid'];
        $total_amount = $order_info['pay_money'];
        $signType = 'RSA2';
        $payObject = new \Alipay($notify_url , $out_trade_no , $total_amount , $signType);
        $pay_string = $payObject->appPay ();
        $result['pay_string'] = $pay_string;

        $this->apiResponse (1 , '请求成功' , $result);

    }

    /**
     * 支付宝回调
     */
    public function AlipayNotify ()
    {
        Vendor ('Txunda.Alipay.Notify');
        $notify = new \Notify();
        if ( $notify->rsaCheck () ) {
            $out_trade_no = $_REQUEST['out_trade_no'];
            $trade_status = $_REQUEST['trade_status'];
            $trade_no = $_REQUEST['trade_no'];
            if ( $trade_status == 'TRADE_SUCCESS' ) {
                $order = D ('Order')->where (array ('orderid' => $out_trade_no))->find ();
                $Member = D ('Member')->where (array ('id' => $order['m_id']))->find ();
                $date['pay_type'] = 2;
                $date['status'] = 2;
                $date['pay_time'] = time ();
                $date['trade_no'] = $trade_no;
                if ( $_REQUEST['o_type'] == 1 ) {//1洗车订单
                    $date['detail'] = 0;
                    $save = D ("Order")->where (array ('orderid' => $out_trade_no))->save ($date);
                    if ( $save ) {
                        echo "success";
                    }
                } elseif ( $_REQUEST['o_type'] == 2 ) {//2小鲸卡购买
                    $have = D ("CardUser")->where (array ('m_id' => $order['m_id']))->find ();
                    if ( $have ) {
                        $where['end_time'] = $have['end_time'] + 30 * 24 * 3600;
                        $where['update_time'] = time ();
                        D ("CardUser")->where (array ('m_id' => $order['m_id']))->save ($where);
                    } else {
                        $where['end_time'] = time () + 30 * 24 * 3600;
                        $where['create_time'] = time ();
                        $where['stare_time'] = time ();
                        $where['l_id'] = $order['card_id'];
                        $where['m_id'] = $order['m_id'];
                        D ("CardUser")->add ($where);
                    }
                    $date['detail'] = 0;
                    $save = D ("Order")->where (array ('orderid' => $out_trade_no))->save ($date);
                    if ( $save ) {
                        echo "success";
                    }
                } elseif ( $_REQUEST['o_type'] == 3 ) {//3余额充值
                    $date['detail'] = 1;
                    $save = D ("Order")->where (array ('orderid' => $out_trade_no))->save ($date);
                    $buy = D ('Member')->where (array ('id' => $order['m_id']))->Save (array ('balance' => $Member['balance'] + $order['pay_money'] + $order['give_money']));
                    if ( $save && $buy ) {
                        echo "success";
                    }
                }
            }
        }
    }

    public function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    /**
     * 获取微信支付参数
     * APP | JSAPI
     * $APPID = 'wx01411f76038d52cc' | 'wx80091a390250d36a';
     * $MCHID = '1520316711' | '1518626231';
     * $KEY = 'Zo6YUkc2RGSBpagPBkya4yH9AFL7oR10';
     * $APPSECRET = 'fbd773e0cfb5e2ea14304f042fa917aa' | '9ab5724077031f646f388f1bba29e70b';
     */
    public function Wechat()
    {
        $request = I("");
        // 查询订单信息
        $order_info = M("Order")->where(array('orderid' => $request['orderid']))->find();
        if (!$order_info) {
            $this->apiResponse(0, '订单信息查询失败', $request);
        }
        /* 统一下单 start */
        $url_unifiedorder = "https://api.mch.weixin.qq.com/pay/unifiedorder"; // 统一下单 URL
        $xml_data = [];
        $xml_data['body'] = "小鲸洗车-订单号-" . $order_info['orderid']; // 商品描述
        $xml_data['out_trade_no'] = $order_info['orderid']; // 订单流水
        $xml_data['notify_url'] = "http://www.xiaojingxiche.com/index.php/Api/Pay/wXNotify"; // 回调 URL
        $xml_data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR']; // 终端 IP
//        $xml_data['total_fee'] = 1; // 支付金额 单位[分]
        $xml_data['total_fee'] = $order_info['pay_money'] * 100; // 支付金额 单位[分]
        $xml_data['nonce_str'] = $this->getNonceStr(32);
        $key = "b2836e3bb4d1c04f567eab868fb99aee"; // 设置的KEY值相同
        // 附加数据
        $attach_data = [
            'orderid' => $request['orderid'],
            'pay_money' => $order_info['pay_money'],
        ];
        $xml_data['attach'] = json_encode($attach_data); // 附加数据 JSON
        if ($request['trade_type']) {
            // 小程序
            $xml_data['appid'] = 'wxf348bbbcc28d7e10'; // APP ID
            $xml_data['mch_id'] = '1518626231'; // 商户号
            $xml_data['trade_type'] = 'JSAPI'; // 支付类型
            $xml_data['openid'] = $request['openid']; // JSAPI支付必须参数 openid
        } else {
            // APP
            $xml_data['appid'] = 'wx9723d2638af03b5b';
            $xml_data['mch_id'] = '1521437101';
            $xml_data['trade_type'] = 'APP';
        }
        ksort($xml_data); // 整理数组顺序
        $xml_data['sign'] = $this->MakeSign($xml_data, $key); // 生成签名
        $xml_string = $this->data_to_xml($xml_data); // 生成 XML 格式
        $unifiedorder_result_xml = $this->postXmlCurl($xml_string, $url_unifiedorder);
        $unifiedorder_result_array = $this->xml_to_data($unifiedorder_result_xml);
        /* 统一下单 end */

        // 捕获统计下单结果
        if ($unifiedorder_result_array['return_code'] == "FAIL") {
            $this->apiResponse(0, $unifiedorder_result_array['return_msg']);
        } elseif ($unifiedorder_result_array['result_code'] == "FAIL") {
            $this->apiResponse(0, $unifiedorder_result_array['err_code_des']);
        }

        /* 二次签名 start */
        $time = '' . time();
        if ($request['trade_type']) {
            // 小程序 - 签名数据
            $sign_data['appId'] = $unifiedorder_result_array['appid'];
            $sign_data['nonceStr'] = $unifiedorder_result_array['nonce_str'];
            $sign_data['package'] = "prepay_id=" . $unifiedorder_result_array['prepay_id'];
            $sign_data['signType'] = "MD5";
            $sign_data['timeStamp'] = $time;
            $sign_data['sign'] = $this->MakeSign($sign_data, $key); // 进行签名
        } else {
            // APP - 签名数据
            $sign_data['appid'] = $unifiedorder_result_array['appid'];
            $sign_data['partnerid'] = $unifiedorder_result_array['mch_id'];
            $sign_data['prepayid'] = $unifiedorder_result_array['prepay_id'];
            $sign_data['package'] = 'Sign=WXPay';
            $sign_data['noncestr'] = $unifiedorder_result_array['nonce_str'];
            $sign_data['timestamp'] = $time;
            $sign_data['sign'] = $this->MakeSign($sign_data, $key);
        }
        /* 二次签名 end */

        // 返回支付调起数据
        $this->apiResponse(1, "微信支付", $sign_data);
    }

    /**
     * 生成签名
     * @param array $params
     * @param string $key
     * @return string 签名
     */
    public function MakeSign($params, $key)
    {
        //按字典序排序数组参数
        ksort($params);
        $string = $this->ToUrlParams($params);
        //在string后加入KEY
        $string = $string . "&key=" . $key;
        //MD5加密
        $string = md5($string);
        //所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 将参数拼接为url: key=value&key=value
     * @param $params
     * @return string
     */
    public function ToUrlParams($params)
    {
        $string = '';
        if (!empty($params)) {
            $array = array();
            foreach ($params as $key => $value) {
                $array[] = $key . '=' . $value;
            }
            $string = implode("&", $array);
        }
        return $string;
    }

    public function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml 需要post的xml数据
     * @param string $url url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second url执行超时时间，默认30s
     * @return bool|mixed
     */
    public function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if ($useCert == true) {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            //curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            //curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
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
            return false;
        }
    }

    /**
     * 输出xml字符
     * @param array $params 参数名称
     * @return string 返回组装的xml
     **/
    public function data_to_xml($params)
    {
        if (!is_array($params) || count($params) <= 0) {
            return false;
        }
        $xml = "<xml>";
        foreach ($params as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @return bool|array
     */
    public function xml_to_data($xml)
    {
        if (!$xml) {
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    /**
     * 错误代码
     * @param string $code 服务器输出的错误代码
     * @return string
     */
    public function error_code($code)
    {
        $errList = array(
            'NOAUTH' => '商户未开通此接口权限',
            'NOTENOUGH' => '用户帐号余额不足',
            'ORDERNOTEXIST' => '订单号不存在',
            'ORDERPAID' => '商户订单已支付，无需重复操作',
            'ORDERCLOSED' => '当前订单已关闭，无法支付',
            'SYSTEMERROR' => '系统错误!系统超时',
            'APPID_NOT_EXIST' => '参数中缺少APPID',
            'MCHID_NOT_EXIST' => '参数中缺少MCHID',
            'APPID_MCHID_NOT_MATCH' => 'appid和mch_id不匹配',
            'LACK_PARAMS' => '缺少必要的请求参数',
            'OUT_TRADE_NO_USED' => '同一笔交易不能多次提交',
            'SIGNERROR' => '参数签名结果不正确',
            'XML_FORMAT_ERROR' => 'XML格式错误',
            'REQUIRE_POST_METHOD' => '未使用post传递参数 ',
            'POST_DATA_EMPTY' => 'post数据不能为空',
            'NOT_UTF8' => '未使用指定编码格式',
        );
        if (array_key_exists($code, $errList)) {
            return $errList[$code];
        }
    }

    /**
     * 微信支付回调
     */
    public function WeChatNotify ()
    {
        // 捕获返回值
        $xml = file_get_contents("php://input");
        // 读取返回值
        $log = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        // 获取订单流水号
        $order_no = $log['out_trade_no'];
        // 获取其他订单信息
        $order_info = json_decode($log['attach'], true);


//        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
//        $xml_res = $this->xmlToArray ($xml);
//        $out_trade_no = $xml_res["out_trade_no"];
//        $trade_no = $xml_res['transaction_id'];
//        $where['orderid'] = $xml_res["out_trade_no"];
        $order = D ('Order')->where (array ('orderid' => $order_info))->find ();
        $Member = D ('Member')->where (array ('id' => $order['m_id']))->find ();
        $date['pay_time'] = time ();
        $date['status'] = 2;
        $date['pay_type'] = 1;
        $date['trade_no'] = $order_info;
        if ( $_REQUEST['o_type'] == 1 ) {//1洗车订单
            $date['detail'] = 0;
            $save = D ("Order")->where (array ('orderid' => $order_info))->save ($date);
            if ( $save ) {
                echo "success";
            }
        } elseif ( $_REQUEST['o_type'] == 2 ) {//2小鲸卡购买
            $have = D ("CardUser")->where (array ('m_id' => $order['m_id']))->find ();
            if ( $have ) {
                $where['end_time'] = $have['end_time'] + 30 * 24 * 3600;
                $where['update_time'] = time ();
                D ("CardUser")->where (array ('m_id' => $order['m_id']))->save ($where);
            } else {
                $where['end_time'] = time () + 30 * 24 * 3600;
                $where['create_time'] = time ();
                $where['stare_time'] = time ();
                $where['l_id'] = $order['card_id'];
                $where['m_id'] = $order['m_id'];
                D ("CardUser")->add ($where);
            }
            $date['detail'] = 0;
            $save = D ("Order")->where (array ('orderid' => $order_info))->save ($date);
            if ( $save ) {
                echo "success";
            }
        } elseif ( $_REQUEST['o_type'] == 3 ) {//3余额充值
            $date['detail'] = 1;
            $save = D ("Order")->where (array ('orderid' => $order_info))->save ($date);
            $buy = D ('Member')->where (array ('id' => $order['m_id']))->Save (array ('balance' => $Member['balance'] + $order['pay_money'] + $order['give_money']));
            if ( $save && $buy ) {
                echo "success";
            }
        }
    }

    /**
     * 订单结算
     */
    public function settlement ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $details = D ("Details")->where (array ('m_id' => $m_id , 'status' => 1))->find ();
        $request = I ('post.');
        $rule = array (
            array ('orderid' , 'string' , '订单编号不能为空') ,
            array ('o_type' , 'string' , '订单类型不能为空') ,
        );
        $this->checkParam ($rule);
        if ( $request['o_type'] == 3 ) {
            $this->apiResponse (0 , '订单信息查询失败');
        }
        $order_info = D ("Order")->where (array ('orderid' => $request['orderid'] , 'o_type' => $request['o_type'] , 'status' => 1))->find ();
        if ( !$order_info ) {
            $this->apiResponse (0 , '订单信息查询失败');
        }
        if ( $request['c_id'] ) {
            $rule = array ('c_type' , 'string' , '卡券类型不能为空');
            $this->checkParam ($rule);
            $card = D ("VipCard")->where (array ('id' => $request['c_id'] , 'c_type' => $request['c_type'] , 'status' => 1 , 'k_status' => 1))->find ();
//            var_dump ($card);die;
            if ( !$card ) {
                $this->apiResponse (0 , '卡券信息查询失败');
            }
        }

        $data['update_time'] = time ();
        $res = M ('Order')->data ($data)->save ();
        if ( $res ) {
            $this->apiResponse (1 , '查询成功' , $order_info);
        }
    }

    /**
     * 余额支付
     */
    public function localPay ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = I ('post.');
        $rule = array (
            array ('orderid' , 'string' , '订单编号不能为空') ,
            array ('o_type' , 'string' , '订单类型不能为空') ,
        );
        $this->checkParam ($rule);
        $order = D ("Order")->where (array ('m_id' => $m_id , 'orderid' => $request['orderid'] , 'o_type' => $request['o_type'] , 'status' => 1))->find ();
        $Member = D ('Member')->where (array ('id' => $order['m_id']))->find ();
        if ( !$order ) {
            $this->apiResponse (0 , '订单信息查询失败');
        }
        $date['detail'] = 2;
        $date['pay_time'] = time ();
        $date['status'] = 2;
        $date['pay_type'] = 3;
        $date['trade_no'] = 'YZF4200000' . date ('YmdHis') . rand (1000 , 9999);
        if ( $Member['balance'] < $order['pay_money'] ) {
            $this->apiResponse (0 , '您的余额不足，请充值');
        }
        if ( $request['o_type'] == 3 ) {
            $this->apiResponse (0 , '订单信息查询失败');
        }
        if ( $_REQUEST['o_type'] ) {
            if ( $_REQUEST['o_type'] == 1 ) {//1洗车订单
                $pay = D ('Member')->where (array ('id' => $m_id))->field ('balance')->Save (array ('balance' => $Member['balance'] - $order['pay_money']));
                $save = D ("Order")->where (array ('orderid' => $request['orderid']))->save ($date);

                //添加收益表
//                if($save){
//                    $find_order = M('Order')->where()->field()->find();
//                }


                if ( $save && $pay ) {
                    $this->apiResponse (1 , '支付成功');
                }
            } elseif ( $_REQUEST['o_type'] == 2 ) {//2小鲸卡购买
                $have = D ("CardUser")->where (array ('m_id' => $m_id))->select ();
                foreach ( $have as $k => $v ) {
                    $have['l_id'] = $have[$k]['l_id'];
                    $have['end_time'] = $have[$k]['end_time'];
                }
                $is_have = D ("CardUser")->where (array ('m_id' => $m_id))->find ();
                if ( $is_have ) {
                    if ( $have['l_id'] == $order['card_id'] ) {
                        $off['end_time'] = $have['end_time'] + (30 * 24 * 3600);
                        $off['update_time'] = time ();
                        $card = D ("CardUser")->where (array ('m_id' => $m_id , 'l_id' => $order['card_id']))->save ($off);
                    }
                    if ( $have['l_id'] !== $order['card_id'] ) {
                        $on['end_time'] = time () + (30 * 24 * 3600);
                        $on['create_time'] = time ();
                        $on['stare_time'] = time ();
                        $on['l_id'] = $order['card_id'];
                        $on['m_id'] = $m_id;
                        $card = D ("CardUser")->add ($on);
                    }
                }
                if ( !$is_have ) {
                    $on['end_time'] = time () + (30 * 24 * 3600);
                    $on['create_time'] = time ();
                    $on['stare_time'] = time ();
                    $on['l_id'] = $order['card_id'];
                    $on['m_id'] = $m_id;
                    $card = D ("CardUser")->add ($on);
                }
                $pay = D ('Member')->where (array ('id' => $m_id))->field ('balance')->Save (array ('degree' => $have['l_id'] , 'balance' => $Member['balance'] - $order['pay_money']));
                $save = D ("Order")->where (array ('orderid' => $request['orderid']))->save ($date);
            }
        }
        if ( $save && $pay && $card ) {
            $this->apiResponse (1 , '支付成功');
        }
    }

}
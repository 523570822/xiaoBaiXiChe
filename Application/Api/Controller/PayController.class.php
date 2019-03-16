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
class PayController extends BaseController {

    /**
     *支付构造方法
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/03 02:26
     */
    public function _initialize () {
        parent::_initialize ();
    }

    /**
     *提现页面
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/03 02:27
     */
    public function withdraw () {
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
    public function bankType () {
        $bank = M ('BankType')->where (array ('status' => 1))->field ('id,bank_name,bank_pic')->select ();
        foreach ( $bank as $k => $v ) {
            $pic['bank_pic'] = $v['bank_pic'];
            $pic_path = getPicPath ($pic['bank_pic']);
            $bank[$k]['bank_pic_path'] = $pic_path;
            $bank[$k]['id'] = $v['id'];
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
    public function addBankCard () {
        $post = checkAppData ('token,card_name,card_code,ID_card,phone,card_id' , 'token-持卡人姓名-持卡人卡号-身份证号-手机号-卡类型');
        //        $post['token'] = 'a8178ff7c6647e8e628971017ea4f55a';
        //        $post['card_name'] = '王子';
        //        $post['card_code'] = '621679087718187784568';
        //        $post['ID_card'] = '587456988541235187';
        //        $post['phone'] = '18635359874';
        //        $post['card_id'] = 1;         //1建行  2中行  3农行

        $agent = $this->getAgentInfo ($post['token']);
        $data = array (
            'agent_id' => $agent['id'] ,
            'card_name' => $post['card_name'] ,
            'card_code' => $post['card_code'] ,
            'ID_card' => $post['ID_card'] ,
            'phone' => $post['phone'] ,
            'card_id' => $post['card_id'] ,
        );

        $card = M ('BankCard')->where (array ('agent_id' => $agent['id']))->find ();
        $car_way = substr ($post['card_code'] , -4);
        $car_ways = M ('BankType')->where (array ('id' => $post['card_id']))->field ('bank_name')->find ();
        $car = substr ($car_ways['bank_name'] , 0) . '储蓄卡   尾号' . $car_way;
        $data['tail_number'] = $car;
        $agent_id = M ('BankCard')->where (array ('agent_id' => $data['agent_id'] , 'card_code' => $post['card_code']))->find ();
        if ( !empty($agent_id) ) {
            $save = M ('BankCard')->where (array ('agent_id' => $data['agent_id']))->save ($data);
            $this->apiResponse ('1' , '此卡号已绑定');
        }
        //         if($post['card_id'] = 1){
        //             $car = '建设储蓄卡  尾号'.$car_way;
        //         }else if($post['card_id'] = 2){
        //             $car = '中行储蓄卡  尾号'.$car_way;
        //         }else if($post['card_id'] = 3){
        //             $car = '农行储蓄卡  尾号'.$car_way;
        //         }
        if ( empty($card) ) {
            $add = M ('BankCard')->add ($data);
            $this->apiResponse ('1' , '绑定成功' , $car);
        } else {
            $save = M ('BankCard')->where (array ('agent_id' => $data['agent_id']))->save ($data);
            $this->apiResponse ('1' , '已修改' , $car);
        }
    }

    /**
     *提现方式
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/24 11:15
     */
    public function withdrawWay () {
        $post = checkAppData ('token,page,size' , 'token-页数-个数');
        //        $post['token'] = 'a8178ff7c6647e8e628971017ea4f55a';
        //        $post['page'] = 1;
        //        $post['size'] = 10;
        $agent = $this->getAgentInfo ($post['token']);

        $where['agent_id'] = $agent['id'];
        $where['status'] = array ('neq' , 9);
        $orders[] = 'sort DESC';
        $car = M ('BankCard')->where ($where)->field ('id,card_code,card_id,tail_number')->order ($orders)->limit (($post['page'] - 1) * $post['size'] , $post['size'])->select ();
        foreach ( $car as $k => $v ) {
            $where_type['id'] = $v['card_id'];
            $where_type['status'] = array ('neq' , 9);
            $car_type = M ('BankType')->where ($where_type)->field ('bank_name,bank_pic')->find ();
            $bank_pic = getPicPath ($car_type['bank_pic']);
            $cars[$k]['id'] = $v['id'];
            $cars[$k]['card_code'] = $v['card_code'];
            $cars[$k]['bank_name'] = $car_type['bank_name'];
            $cars[$k]['bank_pic'] = $bank_pic;
            $cars[$k]['tail_number'] = $v['tail_number'];
        };
        if ( $cars ) {
            $this->apiResponse ('1' , '成功' , $cars);
        } else {
            $this->apiResponse ('0' , '暂无提现方式');
        }
    }

    /**
     *提现提交
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/04 02:01
     */
    public function getWithdraw () {
        $post = checkAppData ('token,price,card_id' , 'token-提现金额-银行卡ID');
        //        $post['token'] = 'a8178ff7c6647e8e628971017ea4f55a';
        //        $post['price'] = 500;
        //        $post['card_id'] = 3;

        $agent = $this->getAgentInfo ($post['token']);
        $data = array (
            'balance' => $agent['balance'] - $post['price'] ,
        );
        if ( $data['balance'] < 0 ) {
            $this->apiResponse ('0' , '对不起,您余额不足');
        }
        $balance = M ('Agent')->where (array ('id' => $agent['id']))->save ($data);
        $add = array (
            'agent_id' => $agent['id'] ,
            'money' => $post['price'] ,
            'card_id' => $post['card_id'] ,
            'create_time' => time () ,
        );
        $withdraw = M ('Withdraw')->add ($add);
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
    public function withdrawInfo () {
        $post = checkAppData ('token，page,size' , 'token-页数-个数');
        //        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        //        $post['page'] = 2;
        //        $post['size'] = 1;
        $agent = $this->getAgentInfo ($post['token']);
        $order[] = 'sort DESC';
        $withdraw = M ('Withdraw')->where (array ('agent_id' => $agent['id']))->field ('money,status,create_time')->order ($order)->limit (($post['page'] - 1) * $post['size'] , $post['size'])->select ();
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
     * 订单编号：orderid
     **/
    public function Alipay () {
        Vendor ('Txunda.Alipay.Alipay');
        $request = I ('post.');
        $rule = array ('orderid' , 'string' , '订单编号不能为空');
        $this->checkParam ($rule);
        $order_info = D ("Order")->where (array ('orderid' => $request['orderid']))->find ();
        if ( !$order_info ) {
            $this->apiResponse (0 , '订单信息查询失败');
        }
        $notify_url = C ('API_URL') . '/index.php/Api/Pay/AlipayNotify';
        // 生成支付字符串
        $out_trade_no = $order_info['orderid'];
        $total_amount = 0.01;//$order_info['pay_money'];
        $signType = 'RSA2';
        $payObject = new \Alipay($notify_url , $out_trade_no , $total_amount , $signType);
        $pay_string = $payObject->appPay ();
        $result['pay_string'] = $pay_string;
        $this->apiResponse (1 , '请求成功' , $result);
    }

    /**
     * 支付宝回调
     */
    public function AlipayNotify () {
        Vendor ('Txunda.Alipay.Notify');
        Vendor ("Txunda.Alipay.aop.request.AlipayTradeAppPayRequest");
        Vendor ("Txunda.Alipay.aop.AopClient");
        $post_data = I ("request.");
        $index = new IndexController();
        $index->testCronTab (json_encode ($post_data));

//        $aop = new \AopClient;
//        $aop->alipayrsaPublicKey = \AlipayConfig::alipayrsaPublicKey;
        if ( $post_data['trade_status'] == 'TRADE_SUCCESS' ) {
            $order = D ('Order')->where (array ('orderid' => $post_data['out_trade_no']))->find ();
            $Member = D ('Member')->where (array ('id' => $order['m_id']))->find ();
            $date['pay_type'] = 2;
            $date['status'] = 2;
            $date['is_set'] = 1;
            $date['pay_time'] = time ();
            $date['trade_no'] = $post_data['trade_no'];
            if ( $order['o_type'] == 1 ) {//1洗车订单
//                if ( $post_data['methods'] == '1' ) {
//                    $cards = M ("CardUser")->where (['id' => $post_data['methods_id']])->field ('l_id')->find ();
//                    $card = M ("LittlewhaleCard")->where (['id' => $cards])->field ('rebate')->find ();
//                    $date['is_dis'] = '1';
//                    $date['card_id'] = $post_data['methods_id'];
//                    $date['allowance'] = $card['rebate'];
//                } elseif ( $post_data['methods'] == '2' ) {
//                    $coup = M ("CouponsBind")->where (['id' => $post_data['methods_id']])->field ('money')->find ();
//                    M ("CouponsBind")->where (['id' => $post_data['methods_id']])->save (['is_use' => '1']);
//                    $date['is_dis'] = '1';
//                    $date['coup_id'] = $post_data['methods_id'];
//                    $date['allowance'] = $coup['money'];
//                } elseif ( $post_data['methods'] == '3' ) {
//                    $date['is_dis'] = '0';
//                }
                $save = D ("Order")->where (array ('orderid' => $post_data['out_trade_no']))->save ($date);
                if ( $save ) {
                    echo "success";
                }
            } elseif ( $order['o_type'] == 2 ) {//2小鲸卡购买
                $is_have = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => $order['card_id']))->find ();
                $have = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => $order['card_id']))->find ();
                if ( $is_have ) {
                    if ( $have['l_id'] == $order['card_id'] ) {
                        if ( $have['end_time'] < time () ) {
                            $off['end_time'] = time () + (30 * 24 * 3600);
                        } else {
                            $off['end_time'] = $have['end_time'] + (30 * 24 * 3600);
                        }
                        $off['update_time'] = time ();
                        $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $order['m_id'] , 'l_id' => $order['card_id']))->save ($off);
                    } elseif ( $have['l_id'] !== $order['card_id'] ) {
                        $on['end_time'] = time () + (30 * 24 * 3600);
                        $on['create_time'] = time ();
                        $on['stare_time'] = time ();
                        $on['l_id'] = $order['card_id'];
                        $on['m_id'] = $order['m_id'];
                        $card = D ("CardUser")->add ($on);
                    }
                } elseif ( !$is_have ) {
                    $on['end_time'] = time () + (30 * 24 * 3600);
                    $on['create_time'] = time ();
                    $on['stare_time'] = time ();
                    $on['l_id'] = $order['card_id'];
                    $on['m_id'] = $order['m_id'];
                    $card = D ("CardUser")->add ($on);
                }
                $pay = D ('Member')->where (array ('id' => $order['m_id']))->save (array ('degree' => $have['l_id'] , 'balance' => $Member['balance'] - $order['pay_money']));
                $save = D ("Order")->where (array ('orderid' => $post_data['out_trade_no']))->save ($date);
                if ( $save && $pay && $card ) {
                    echo "success";
                }
            } elseif ( $order['o_type'] == 3 ) {//3余额充值
                $date['detail'] = 1;
                $save = D ("Order")->where (array ('orderid' => $post_data['out_trade_no']))->save ($date);
                $buy = D ('Member')->where (array ('id' => $order['m_id']))->Save (array ('balance' => $Member['balance'] + $order['pay_money'] + $order['give_money']));
                if ( $save && $buy ) {
                    echo "success";
                }
            }
        }
    }

    public function xmlToArray ($xml) {
        //将XML转为array
        $array_data = json_decode (json_encode (simplexml_load_string ($xml , 'SimpleXMLElement' , LIBXML_NOCDATA)) , true);
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
    public function Wechat () {
        $request = I ("");
        // 查询订单信息
        $order_info = M ("Order")->where (array ('orderid' => $request['orderid']))->find ();
        if ( !$order_info ) {
            $this->apiResponse (0 , '订单信息查询失败' , $request);
        }
        /* 统一下单 start */
        $url_unifiedorder = "https://api.mch.weixin.qq.com/pay/unifiedorder"; // 统一下单 URL
        $xml_data = [];
        $xml_data['body'] = "小鲸洗车-订单号-" . $order_info['orderid']; // 商品描述
        $xml_data['out_trade_no'] = $order_info['orderid']; // 订单流水
        $xml_data['notify_url'] = C ('API_URL') . "/index.php/Api/Pay/WeChatNotify"; // 回调 URL
        $xml_data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR']; // 终端 IP
        $xml_data['total_fee'] = 1; // 支付金额 单位[分]
        //        $xml_data['total_fee'] = $order_info['pay_money'] * 100; // 支付金额 单位[分]
        $xml_data['nonce_str'] = $this->getNonceStr (32);
        $key = "b2836e3bb4d1c04f567eab868fb99aee"; // 设置的KEY值相同
        // 附加数据
        $attach_data = [
            "methods" => $request['methods'] ,
            "methods_id" => $request['methods_id'] ,
        ];
        $xml_data['attach'] = json_encode ($attach_data); // 附加数据 JSON

        if ( $request['trade_type'] ) {// 小程序
            $xml_data['appid'] = 'wxf348bbbcc28d7e10'; // APP ID
            $xml_data['mch_id'] = '1524895951'; // 商户号
            $xml_data['trade_type'] = 'JSAPI'; // 支付类型
            $xml_data['openid'] = $request['openid']; // JSAPI支付必须参数 openid
        } else { // APP
            $xml_data['appid'] = 'wx9723d2638af03b5b';
            $xml_data['mch_id'] = '1521437101';
            $xml_data['trade_type'] = 'APP';
        }
        ksort ($xml_data); // 整理数组顺序
        $xml_data['sign'] = $this->MakeSign ($xml_data , $key); // 生成签名
        $xml_string = $this->data_to_xml ($xml_data); // 生成 XML 格式
        $unifiedorder_result_xml = $this->postXmlCurl ($xml_string , $url_unifiedorder);
        $unifiedorder_result_array = $this->xml_to_data ($unifiedorder_result_xml);
        /* 统一下单 end */
        // 捕获统计下单结果
        if ( $unifiedorder_result_array['return_code'] == "FAIL" ) {
            $this->apiResponse (0 , $unifiedorder_result_array['return_msg']);
        } elseif ( $unifiedorder_result_array['result_code'] == "FAIL" ) {
            $this->apiResponse (0 , $unifiedorder_result_array['err_code_des']);
        }
        /* 二次签名 start */
        $time = '' . time ();
        if ( $request['trade_type'] ) {
            // 小程序 - 签名数据
            $sign_data['appId'] = $unifiedorder_result_array['appid'];
            $sign_data['nonceStr'] = $unifiedorder_result_array['nonce_str'];
            $sign_data['package'] = "prepay_id=" . $unifiedorder_result_array['prepay_id'];
            $sign_data['signType'] = "MD5";
            $sign_data['timeStamp'] = $time;
            $sign_data['sign'] = $this->MakeSign ($sign_data , $key); // 进行签名
        } else {
            // APP - 签名数据
            $sign_data['appid'] = $unifiedorder_result_array['appid'];
            $sign_data['partnerid'] = $unifiedorder_result_array['mch_id'];
            $sign_data['prepayid'] = $unifiedorder_result_array['prepay_id'];
            $sign_data['package'] = 'Sign=WXPay';
            $sign_data['noncestr'] = $unifiedorder_result_array['nonce_str'];
            $sign_data['timestamp'] = $time;
            $sign_data['sign'] = $this->MakeSign ($sign_data , $key);
        }
        /* 二次签名 end */
        // 返回支付调起数据
        $this->apiResponse (1 , "微信支付" , $sign_data);
    }

    /**
     * 生成签名
     * @param array $params
     * @param string $key
     * @return string 签名
     */
    public function MakeSign ($params , $key) {
        //按字典序排序数组参数
        ksort ($params);
        $string = $this->ToUrlParams ($params);
        //在string后加入KEY
        $string = $string . "&key=" . $key;
        //MD5加密
        $string = md5 ($string);
        //所有字符转为大写
        $result = strtoupper ($string);
        return $result;
    }

    /**
     * 将参数拼接为url: key=value&key=value
     * @param $params
     * @return string
     */
    public function ToUrlParams ($params) {
        $string = '';
        if ( !empty($params) ) {
            $array = array ();
            foreach ( $params as $key => $value ) {
                $array[] = $key . '=' . $value;
            }
            $string = implode ("&" , $array);
        }
        return $string;
    }

    public function getNonceStr ($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ( $i = 0; $i < $length; $i++ ) {
            $str .= substr ($chars , mt_rand (0 , strlen ($chars) - 1) , 1);
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
    public function postXmlCurl ($xml , $url , $useCert = false , $second = 30) {
        $ch = curl_init ();
        //设置超时
        curl_setopt ($ch , CURLOPT_TIMEOUT , $second);
        curl_setopt ($ch , CURLOPT_URL , $url);
        curl_setopt ($ch , CURLOPT_SSL_VERIFYPEER , FALSE);
        curl_setopt ($ch , CURLOPT_SSL_VERIFYHOST , 2);
        //设置header
        curl_setopt ($ch , CURLOPT_HEADER , FALSE);
        curl_setopt ($ch , CURLOPT_SSLVERSION , CURL_SSLVERSION_TLSv1);
        //要求结果为字符串且输出到屏幕上
        curl_setopt ($ch , CURLOPT_RETURNTRANSFER , TRUE);
        if ( $useCert == true ) {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt ($ch , CURLOPT_SSLCERTTYPE , 'PEM');
            //curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
            curl_setopt ($ch , CURLOPT_SSLKEYTYPE , 'PEM');
            //curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
        }
        //post提交方式
        curl_setopt ($ch , CURLOPT_POST , TRUE);
        curl_setopt ($ch , CURLOPT_POSTFIELDS , $xml);
        //运行curl
        $data = curl_exec ($ch);
        //返回结果
        if ( $data ) {
            curl_close ($ch);
            return $data;
        } else {
            $error = curl_errno ($ch);
            curl_close ($ch);
            return false;
        }
    }

    /**
     * 输出xml字符
     * @param array $params 参数名称
     * @return string 返回组装的xml
     **/
    public function data_to_xml ($params) {
        if ( !is_array ($params) || count ($params) <= 0 ) {
            return false;
        }
        $xml = "<xml>";
        foreach ( $params as $key => $val ) {
            if ( is_numeric ($val) ) {
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
    public function xml_to_data ($xml) {
        if ( !$xml ) {
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader (true);
        $data = json_decode (json_encode (simplexml_load_string ($xml , 'SimpleXMLElement' , LIBXML_NOCDATA)) , true);
        return $data;
    }

    /**
     * 错误代码
     * @param string $code 服务器输出的错误代码
     * @return string
     */
    public function error_code ($code) {
        $errList = array (
            'NOAUTH' => '商户未开通此接口权限' ,
            'NOTENOUGH' => '用户帐号余额不足' ,
            'ORDERNOTEXIST' => '订单号不存在' ,
            'ORDERPAID' => '商户订单已支付，无需重复操作' ,
            'ORDERCLOSED' => '当前订单已关闭，无法支付' ,
            'SYSTEMERROR' => '系统错误!系统超时' ,
            'APPID_NOT_EXIST' => '参数中缺少APPID' ,
            'MCHID_NOT_EXIST' => '参数中缺少MCHID' ,
            'APPID_MCHID_NOT_MATCH' => 'appid和mch_id不匹配' ,
            'LACK_PARAMS' => '缺少必要的请求参数' ,
            'OUT_TRADE_NO_USED' => '同一笔交易不能多次提交' ,
            'SIGNERROR' => '参数签名结果不正确' ,
            'XML_FORMAT_ERROR' => 'XML格式错误' ,
            'REQUIRE_POST_METHOD' => '未使用post传递参数 ' ,
            'POST_DATA_EMPTY' => 'post数据不能为空' ,
            'NOT_UTF8' => '未使用指定编码格式' ,
        );
        if ( array_key_exists ($code , $errList) ) {
            return $errList[$code];
        }
    }

    /**
     * 微信支付回调
     */
    public function WeChatNotify () {
        // 捕获返回值
        $xml = file_get_contents ("php://input");
        // 读取返回值
        $log = json_decode (json_encode (simplexml_load_string ($xml , 'SimpleXMLElement' , LIBXML_NOCDATA)) , true);
        //PHP记录写入文件
        //        $myfile = fopen ("./WeChatNotify.txt" , "a") or die("Unable to open file!");
        //        $txt = json_encode ($log);
        //        fwrite ($myfile , $txt);
        //        fclose ($myfile);
        // 获取订单流水号
        $order_no = $log['out_trade_no'];
        //获取三方交易流水号
        $info = $log['transaction_id'];
        //获取其他订单信息
        $order_info = json_decode ($log['attach'] , true);
        $order = D ('Order')->where (array ('orderid' => $order_no))->find ();
        $Member = D ('Member')->where (array ('id' => $order['m_id']))->find ();
        $date['pay_time'] = time ();
        $date['status'] = 2;
        $date['is_set'] = 1;
        $date['pay_type'] = 1;
        $date['trade_no'] = $info;
        if ( $order['o_type'] == 1 ) {//1洗车订单
            if ( $order_info['methods'] == '1' ) {
                $cards = M ("CardUser")->where (['id' => $order_info['methods_id']])->field ('l_id')->find ();
                $card = M ("LittlewhaleCard")->where (['id' => $cards])->field ('rebate')->find ();
                $date['is_dis'] = '1';
                $date['card_id'] = $order_info['methods_id'];
                $date['allowance'] = $card['rebate'];
            } elseif ( $order_info['methods'] == '2' ) {
                M ("CouponsBind")->where (['id' => $order_info['methods_id']])->save (['is_use' => '1']);
                $coup = M ("CouponsBind")->where (['id' => $order_info['methods_id']])->field ('money')->find ();
                $date['is_dis'] = '1';
                $date['coup_id'] = $order_info['methods_id'];
                $date['allowance'] = $coup['money'];
            } elseif ( $order_info['methods'] == '3' ) {
                $date['is_dis'] = '0';
            }
            $save = D ("Order")->where (array ('orderid' => $order['orderid']))->save ($date);
            if ( $save ) {
                echo "success";
            }
        } elseif ( $order['o_type'] == 2 ) {//2小鲸卡购买
            $is_have = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => $order['card_id']))->find ();
            $have = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => $order['card_id']))->find ();
            if ( $is_have ) {
                if ( $have['l_id'] == $order['card_id'] ) {
                    if ( $have['end_time'] < time () ) {
                        $off['end_time'] = time () + (30 * 24 * 3600);
                    } else {
                        $off['end_time'] = $have['end_time'] + (30 * 24 * 3600);
                    }
                    $off['update_time'] = time ();
                    $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $order['m_id'] , 'l_id' => $order['card_id']))->save ($off);
                } elseif ( $have['l_id'] !== $order['card_id'] ) {
                    $on['end_time'] = time () + (30 * 24 * 3600);
                    $on['create_time'] = time ();
                    $on['stare_time'] = time ();
                    $on['l_id'] = $order['card_id'];
                    $on['m_id'] = $order['m_id'];
                    $card = D ("CardUser")->add ($on);
                }
            } elseif ( !$is_have ) {
                $on['end_time'] = time () + (30 * 24 * 3600);
                $on['create_time'] = time ();
                $on['stare_time'] = time ();
                $on['l_id'] = $order['card_id'];
                $on['m_id'] = $order['m_id'];
                $card = D ("CardUser")->add ($on);
            }
            $pay = D ('Member')->where (array ('id' => $order['m_id']))->save (array ('degree' => $have['l_id'] , 'balance' => $Member['balance'] - $order['pay_money']));
            $save = D ("Order")->where (array ('orderid' => $order_no))->save ($date);
            if ( $save && $pay && $card ) {
                echo "success";
            }
        } elseif ( $order['o_type'] == 3 ) {//3余额充值
            $date['detail'] = 1;
            $save = D ("Order")->where (array ('orderid' => $order_no))->save ($date);
            $buy = D ('Member')->where (array ('id' => $order['m_id']))->Save (array ('balance' => $Member['balance'] + $order['pay_money'] + $order['give_money']));
            if ( $save && $buy ) {
                echo "success";
            }
        }
    }

    /**
     * 数据实时请求
     */
    public function postLinux () {

    }

    /**
     *获取时间
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/21 18:37
     */
    public function weeks () {
        //        $timestamp = time();
        $timestamp = 1545674199;
        return [
            strtotime (date ('Y-m-d' , strtotime ("this week Monday" , $timestamp))) ,
            strtotime (date ('Y-m-d' , strtotime ("this week Sunday" , $timestamp))) + 24 * 3600 - 1 ,
            strtotime (date ('Y-m' , $timestamp)) ,    //月份
            strtotime (date ('Y' , $timestamp) . '-1-1') ,     //年份
        ];
    }

    /**
     * 余额支付
     */
    public function localPay () {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = I ('post.');
        $rule = array ('orderid' , 'string' , '订单编号不能为空');
        $this->checkParam ($rule);
        $where['o_type'] = array ('neq' , 3);
        $where['m_id'] = $m_id;
        $where['orderid'] = $request['orderid'];
        $where['is_set'] = 0;
        $where['status'] = 1;
        $order = D ("Order")->where ($where)->find ();
        $Member = D ('Member')->where (array ('id' => $order['m_id']))->find ();
        if ( !$order ) {
            $this->apiResponse (0 , '订单信息查询失败');
        }
        $date['detail'] = 2;
        $date['pay_time'] = time ();
        $date['is_set'] = 1;
        $date['status'] = 2;
        $date['pay_type'] = 3;
        $date['trade_no'] = 'YZF4200000' . date ('YmdHis') . rand (1000 , 9999);
        if ( $Member['balance'] < $order['pay_money'] ) {
            $this->apiResponse (0 , '您的余额不足，请充值');
        }
        if ( $order['pay_money'] == 0.00 ) {
            $this->apiResponse (0 , '数据异常' , '');
        }
        if ( $order['o_type'] ) {
            if ( $order['o_type'] == 1 ) {//1洗车订单
                if ( $request['methods'] == '1' ) {
                    $cards = M ("CardUser")->where (['id' => $request['methods_id']])->field ('l_id')->find ();
                    $card = M ("LittlewhaleCard")->where (['id' => $cards])->field ('rebate')->find ();
                    $date['is_dis'] = '1';
                    $date['card_id'] = $request['methods_id'];
                    $date['allowance'] = $card['rebate'];
                } elseif ( $request['methods'] == '2' ) {
                    M ("CouponsBind")->where (['id' => $request['methods_id']])->save (['is_use' => '1']);
                    $coup = M ("CouponsBind")->where (['id' => $request['methods_id']])->field ('money')->find ();
                    $date['is_dis'] = '1';
                    $date['coup_id'] = $request['methods_id'];
                    $date['allowance'] = $coup['money'];
                } elseif ( $request['methods'] == '3' ) {
                    $date['is_dis'] = '0';
                }
                $pay = D ('Member')->where (array ('id' => $m_id))->field ('balance')->save (array ('balance' => $Member['balance'] - $order['pay_money']));
                $save = D ("Order")->where (array ('orderid' => $request['orderid']))->save ($date);
                if ( $save && $pay ) {
                    //添加到收益表
                    $a_where['orderid'] = $request['orderid'];
                    $a_where['status'] = 2;
                    $a_where['o_type'] = 1;
                    $a_order = M ('Order')->where ($a_where)->field ('c_id,pay_money,pay_time')->find ();
                    $agent_where['id'] = $a_order['c_id'];
                    $car = M ('CarWasher')->where ($agent_where)->field ('agent_id')->find ();   //查找代理商id
                    $agent = M ('Agent')->where (array ('id' => $car['agent_id']))->field ('grade,balance')->find ();
                    $income_where['agent_id'] = $car['agent_id'];
                    $income_where['car_washer_id'] = $a_order['c_id'];
                    $income_where['day'] = strtotime (date ('Y-m-d' , $a_order['pay_time']));
                    $income = M ('Income')->where ($income_where)->field ('detail,net_income,car_wash,day,week_star,week_end,month,year,create_time')->find ();
                    if ( $agent['grade'] == 1 ) {
                        $net_income = $a_order['pay_money'] - $a_order['pay_money'] * 0.05;
                    } elseif ( $agent['grade'] == 2 ) {
                        $net_income = $a_order['pay_money'] - $a_order['pay_money'] * 0.1;
                    } elseif ( $agent['grade'] == 3 ) {
                        $net_income = $a_order['pay_money'] - $a_order['pay_money'] * 0.15;
                    }
                    if ( empty($income) ) {
                        //获取时间戳
                        $timestamp = $a_order['pay_time'];
                        $week_star = strtotime (date ('Y-m-d' , strtotime ("this week Monday" , $timestamp)));
                        $week_end = strtotime (date ('Y-m-d' , strtotime ("this week Sunday" , $timestamp))) + 24 * 3600 - 1;
                        $month = strtotime (date ('Y-m' , $timestamp));    //月份
                        $year = strtotime (date ('Y' , $timestamp) . '-1-1');     //年份
                        $income_add = array (
                            'agent_id' => $car['agent_id'] ,
                            'car_washer_id' => $a_order['c_id'] ,
                            'detail' => $a_order['pay_money'] ,
                            'net_income' => $net_income ,
                            'car_wash' => 1 ,
                            'day' => $income_where['day'] ,
                            'week_star' => $week_star ,
                            'week_end' => $week_end ,
                            'month' => $month ,
                            'year' => $year ,
                            'create_time' => $a_order['pay_time'] ,
                        );
                        M ('Income')->add ($income_add);
                        $agent_save['balance'] = $agent['balance'] + $net_income;
                        M ('Agent')->where (array ('id' => $car['agent_id']))->save ($agent_save);
                    } else {
                        $income_save = array (
                            'detail' => $income['detail'] + $a_order['pay_money'] ,
                            'net_income' => $income['net_income'] + $net_income ,
                            'car_wash' => $income['car_wash'] + 1 ,
                            'create_time' => $a_order['pay_time'] ,
                        );
                        if ( $income['create_time'] != $a_order['pay_time'] ) {
                            M ('Income')->where ($income_where)->save ($income_save);
                            $agent_save['balance'] = $agent['balance'] + $net_income;
                            M ('Agent')->where (array ('id' => $car['agent_id']))->save ($agent_save);
                        }
                    }
                    $this->apiResponse (1 , '支付成功');
                }
            } elseif ( $order['o_type'] == 2 ) {//2小鲸卡购买
                $is_have = D ("CardUser")->where (array ('m_id' => $m_id , 'l_id' => $order['card_id']))->find ();
                $have = D ("CardUser")->where (array ('m_id' => $m_id , 'l_id' => $order['card_id']))->find ();
                if ( $is_have ) {
                    if ( $have['l_id'] == $order['card_id'] ) {
                        if ( $have['end_time'] < time () ) {
                            $off['end_time'] = time () + (30 * 24 * 3600);
                        } else {
                            $off['end_time'] = $have['end_time'] + (30 * 24 * 3600);
                        }
                        $off['update_time'] = time ();
                        $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $m_id , 'l_id' => $order['card_id']))->save ($off);
                    } elseif ( $have['l_id'] !== $order['card_id'] ) {
                        $on['end_time'] = time () + (30 * 24 * 3600);
                        $on['create_time'] = time ();
                        $on['stare_time'] = time ();
                        $on['l_id'] = $order['card_id'];
                        $on['m_id'] = $m_id;
                        $card = D ("CardUser")->add ($on);
                    }
                } elseif ( !$is_have ) {
                    $on['end_time'] = time () + (30 * 24 * 3600);
                    $on['create_time'] = time ();
                    $on['stare_time'] = time ();
                    $on['l_id'] = $order['card_id'];
                    $on['m_id'] = $m_id;
                    $card = D ("CardUser")->add ($on);
                }
                $pay = D ('Member')->where (array ('id' => $m_id))->save (array ('degree' => $have['l_id'] , 'balance' => $Member['balance'] - $order['pay_money']));
                $save = D ("Order")->where (array ('orderid' => $request['orderid']))->save ($date);
            }
            if ( $save && $pay && $card ) {
                $this->apiResponse (1 , '支付成功');
            } else {
                $this->apiResponse (0 , '支付失败');
            }
        }
    }

}
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

    /**
     * 获取微信支付参数
     * 传递参数的方式：post
     * 需要传递的参数：
     * 订单id：orderid
     * 类型：o_type 1洗车订单 2小鲸卡购买 3余额充值
     */
    public function WeChat ()
    {
        Vendor ('Txunda.WxPay.lib.WxPay#Api');
        if ( empty($_REQUEST['orderid']) ) {
            apiResponse ('0' , '参数不完整');
        }
        if ( empty($_REQUEST['o_type']) ) {
            apiResponse ('0' , '类型错误');
        }
        $orderid = $_REQUEST['orderid'];
        if ( $_REQUEST['o_type'] ) {
            $order_info = D ("Order")->where (array ('orderid' => $_REQUEST['orderid'] , 'o_type' => $_REQUEST['o_type']))->find ();
            $pay_money = $order_info['pay_money'];
            if ( !$order_info ) {
                $this->apiResponse (0 , '订单信息查询失败');
            }
        }
        $url_data = [
            "orderid" => $order_info['orderid'] ,
            "o_type" => $order_info['o_type'] ,
            "m_id" => $order_info['m_id'] ,
        ];
        $url = C ('API_URL') . "/index.php/Api/Pay/WeChatNotify?" . http_build_query ($url_data);
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody ("小鲸洗车");
        $input->SetAttach ("小鲸洗车");
        $input->SetOut_trade_no ($orderid);
        $input->SetTotal_fee ($pay_money * 100);
        $input->SetTime_start (date ("YmdHis"));
        $input->SetTime_expire (date ("YmdHis" , time () + 600));
        $input->SetGoods_tag ("APP支付");
        $input->SetNotify_url ($url);
        $input->SetTrade_type ("APP");
        $order = \WxPayApi::unifiedOrder ($input);
        $time = time () . '';
        $sign_data['appid'] = $order['appid'];
        $sign_data['mch_id'] = $order['mch_id'];
        $sign_data['nonce_str'] = $order['nonce_str'];
        $sign_data['package'] = 'Sign=WXPay';
        $sign_data['prepay_id'] = $order['prepay_id'];
        $sign_data['time_stamp'] = $time;
        $sign_string = 'appid=' . $sign_data['appid'] . '&noncestr=' . $sign_data['nonce_str'] . '&package=' . $sign_data['package'] . '&partnerid=' . $order['mch_id'] . '&prepayid=' . $sign_data['prepay_id'] . '&timestamp=' . $sign_data['time_stamp'] . '&key=' . \WxPayConfig::KEY;
        $result_data['sign'] = strtoupper (md5 ($sign_string));
        $result_data['appid'] = $order['appid'];
        $result_data['nonce_str'] = $order['nonce_str'];
        $result_data['package'] = 'Sign=WXPay';
        $result_data['package_value'] = 'Sign=WXPay';
        $result_data['time_stamp'] = $time;
        $result_data['prepay_id'] = $order['prepay_id'];
        $result_data['mch_id'] = $order['mch_id'];
        apiResponse ('1' , '请求成功' , $result_data);
    }

    /**
     * 微信支付回调
     */
    public function WeChatNotify ()
    {
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $xml_res = $this->xmlToArray ($xml);
        $out_trade_no = $xml_res["out_trade_no"];
        $trade_no = $xml_res['transaction_id'];
        $where['orderid'] = $xml_res["out_trade_no"];
        $order = D ('Order')->where (array ('orderid' => $out_trade_no))->find ();
        $Member = D ('Member')->where (array ('id' => $order['m_id']))->find ();
        $date['pay_time'] = time ();
        $date['status'] = 2;
        $date['pay_type'] = 1;
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

//        if ( $request['c_id'] ) {
//            $rule = array ('c_type' , 'string' , '卡券类型不能为空');
//            $this->checkParam ($rule);
//            $card = D ("VipCard")->where (array ('id' => $request['c_id'] , 'c_type' => $request['c_type'] , 'status' => 1 , 'k_status' => 1))->find ();
//            if ( !$card ) {
//                $this->apiResponse (0 , '卡券信息查询失败');
//            }
//        }
    // 回调地址
//        var_dump ($payObject);die;
//                    unset($where);
//                    $where['orderid'] = $out_trade_no;
//                    $where['status'] = array ('eq' , 1);
//                    $order = M ('Order')->where ($where)->find ();
//                    if ( $order ) {//修改订单状态
//                        M ('Order')->where (array ('id' => $order['id']))->data (array ('pay_type' => 2 , 'trade_no' => $trade_no , 'pay_time' => time () , 'status' => 2))->save ();
//                    }
//                    echo "success";
//                    unset($where);
//                    $where['orderid'] = $out_trade_no;
//                    $where['status'] = array ('eq' , 1);
//                    $order = M ('Order')->where ($where)->find ();
//                    if ( $order ) {
//                        //修改订单状态
//                        M ('Order')->where (array ('orderid' => $order['orderid']))
//                            ->data (array ('pay_type' => 2 , 'trade_no' => $trade_no , 'pay_time' => time () , 'status' => 2))
//                            ->save ();
//                        //增加余额明细
//                        $where['orderid'] = $out_trade_no;
//                        $where['status'] = array ('eq' , 1);
//                        $order_card = M ('Order')->where ($where)->find ();
//                        $where1['id'] = $order_card['m_id'];
//                        $where1['status'] = array ('eq' , 1);
//                        $memberinfo = M ('Member')->where ($where1)->find ();
//                        $where2['id'] = $order_card['card_id'];
//                        $where2['status'] = array ('eq' , 1);
//                        $washcard = M ('WashCard')->where ($where2)->find ();
//                        $m_endtime = 2592000 * $washcard['end_time'];//var_dump($m_endtime);
//                        if ( time () <= $memberinfo['m_endtime'] ) {//1当前有会员卡且尚未过期
//                            if ( $order_card['card_id'] >= $memberinfo['card_id'] ) {//card_id越大卡的等级越大
//                            } else {
//                                $order_card['card_id'] = $memberinfo['card_id'];
//                                $washcard['car_type'] = $memberinfo['degree'];
//                            }
//                            $m_endtime = $m_endtime + $memberinfo['m_endtime'];
//                            M ('Member')->where ($where1)
//                                ->data (array ('pay_type' => 2 , 'm_endtime' => $m_endtime , 'card_type' => $washcard['car_type'] , 'update_time' => time () , 'card_id' => $order_card['card_id']))
//                                ->save ();
//                        } else {//1当前无会员卡或已过期
//                            $startime = time ();
//                            $m_endtime = $m_endtime + $startime;
//                            M ('Member')->where ($where1)
//                                ->data (array ('pay_type' => 2 , 'startime' => $startime , 'm_endtime' => $m_endtime , 'card_type' => $washcard['car_type'] , 'update_time' => time () , 'card_id' => $order_card['card_id']))
//                                ->save ();
//                        }
//                        echo "success";
//                }
//                    unset($where);
//                    $where['orderid'] = $out_trade_no;
//                    $where['status'] = array ('eq' , 0);
//                    $order = M ('Order')->where ($where)->find ();
//                    if ( $order ) {
//                        //修改订单状态
//                        M ('Order')->where (array ('orderid' => $order['orderid']))
//                            ->data (array ('pay_type' => 2 , 'trade_no' => $trade_no , 'update_time' => time () , 'detail' => "1" , 'status' => 2))
//                            ->save ();
//                        //增加余额明细
//                        $where['orderid'] = $out_trade_no;
//                        $where['status'] = array ('eq' , 1);
//                        $order_card = M ('Order')->where ($where)->find ();
//                        $where1['id'] = $order_card['m_id'];
//                        $where1['status'] = array ('eq' , 1);
//                        $memberinfo = M ('Member')->where ($where1)->find ();
//                        $washcard['balance'] = $memberinfo['balance'] + $order_card['pay_money'];//var_dump($memberinfo );var_dump($order_card['pay_money']);
//                        $wash = M ('Appsetting')->where (array ('id' => 1))
//                            ->find ();
//                        $give_price = $wash['give_price'];
//                        $washcard['give_balance'] = $memberinfo['give_balance'] + ($order_card['pay_money'] * $give_price);//var_dump($memberinfo );var_dump($order_card['pay_money']);
//                        if ( $washcard['balance'] >= $memberinfo['balance'] ) {
//                            M ('Member')->where ($where1)
//                                ->data (array ('balance' => $washcard['balance'] , 'give_balance' => $washcard['give_balance'] , 'update_time' => time () , 'is_pay' => 1))
//                                ->save ();
//                        }
//                        $this->addPayLog ($memberinfo['id'] , 1 , 1 , $order_card['pay_money'] , '钱包充值');
//                        $this->addPayLog ($memberinfo['id'] , 1 , 1 , $order_card['pay_money'] * $give_price , '钱包充值赠送');
//                        $wash = M ('Appsetting')->where (array ('id' => 1))->find ();
//$status =1;// $order['type'] == 1 ? 2 : 1;
// M('Order')->where(array('orderid' => $order['orderid']))
//     ->data(array('pay_type' =>2, 'order_trade_no' => $trade_no, 'update_time' => time(), 'title' => "钱包充值", 'sign'=>"1", 'status' => $status, 'is_pay' => 1))
//     ->save();
// //增加余额明细
// $where['orderid'] = $out_trade_no;
// $where['status'] = array('eq', 1);
// $order_card = M('Order')->where($where)->find();
// $where1['id'] = $order_card['m_id'];
// $where1['status'] = array('eq', 1);
// $memberinfo = M('Member')->where($where1)->find();
//                    $washcard['balance'] = $memberinfo['balance']+ $order_card['pay_money'];//var_dump($memberinfo );var_dump($order_card['pay_money']);
//                        if ($washcard['balance']>=$memberinfo['balance']){
//                            M('Member')->where($where1)
//                                ->data(array(  'balance' => $washcard['balance'], 'update_time' => time(),   'is_pay' => 1))
//                                ->save();
//                        }
//$wash = M ('Appsetting')->where (array ('id' => 1))
//    ->find ();
//$give_price = $wash['give_price'];
//$washcard['give_balance'] = $memberinfo['give_balance'] + ($order_card['pay_money'] * $give_price);//var_dump($memberinfo );var_dump($order_card['pay_money']);
//if ( $washcard['balance'] >= $memberinfo['balance'] ) {
//    M ('Member')->where ($where1)
//        ->data (array ('balance' => $washcard['balance'] , 'give_balance' => $washcard['give_balance'] , 'update_time' => time () , 'is_pay' => 1))
//        ->save ();
//}
//$this->addPayLog ($memberinfo['m_id'] , 1 , 1 , $order_card['pay_money']['order_price'] , '充值');
//$this->addPayLog ($memberinfo['m_id'] , 1 , 1 , $order_card['pay_money'] * $give_price , '充值赠送');
//$wash = M ('Appsetting')->where (array ('id' => 1))
//    ->find ();
//if ( $order_card['m_id'] ) {
//    if ( time () <= $memberinfo['m_endtime'] ) {
//        $wash['ratio'] = $order_card['pay_money'] / $wash['ratio'];
//        //增加积分
//        $wash['db_integral'] = $memberinfo['db_integral'] + $wash['ratio'];
//        if ( $wash['db_integral'] >= $memberinfo['db_integral'] ) {
//
//            M ('Member')->where (array ('id' => $order_card['m_id']))->save (array ('db_integral' => $wash['db_integral']));
//        }
//        //增加积分明细
//        $this->addIntegralLog ($order_card['m_id'] , 2 , 2 , 0 , $wash['ratio'] , '充值获取积分');
//    }
//}
//                        echo "success";

//    if ( $request['c_id'] ) {
//        $rule = array ('c_type' , 'string' , '卡券类型不能为空');
//        $this->checkParam ($rule);
//        $card = D ("VipCard")->where (array ('id' => $request['c_id'] , 'c_type' => $request['c_type'] , 'status' => 1 , 'k_status' => 1))->find ();
//        if ( !$card ) {
//            $this->apiResponse (0 , '卡券信息查询失败');
//        }
//    }
//    public function getAlipayParam ()
//    {
//        vendor ('Txunda.Alipay.Alipay');
//        if ( empty($_REQUEST['orderid']) ) {
//            $this->apiResponse ('0' , '请输入订单编号');
//        }
//        if ( empty($_REQUEST['o_type']) ) {
//            $this->apiResponse ('0' , '请输入订单类型');
//        }
//        if ($_REQUEST['card_id']) {
//            $card = M ('VipCard')->where (array ('id' => $_REQUEST['card_id'],'status'=>1))->find ();
//            if ( !$card ) {
//                $this->apiResponse ('0' , '您的卡券已失效');
//            }
//        }
//        $orderid = $_REQUEST['orderid'];
//        $pay_money = '';
//        if ( $_REQUEST['o_type'] == 1 ) {
//            $info = M ('Order')->where (array ('orderid' => $_REQUEST['orderid']))->find ();
//            if ( !$info ) {
//                $this->apiResponse ('0' , '信息查询失败');
//            }
//            $orderid = $info['orderid'];
//
//            $pay_money = $info['money']-$card[''];//0.01;
//        } elseif ( $_REQUEST['o_type'] == 2 ) {
//            $info = M ('Order')->where (array ('orderid' => $_REQUEST['orderid']))->find ();
//            if ( !$info ) {
//                $this->apiResponse ('0' , '信息查询失败');
//            }
//            $pay_money = 0.01;// $info['pay_money'];
//        } elseif ( $_REQUEST['o_type'] == 3 ) {
//            $info = M ('Order')->where (array ('orderid' => $_REQUEST['orderid']))->find ();
//            if ( !$info ) {
//                $this->apiResponse ('0' , '信息查询失败');
//            }
//            $pay_money = 0.01;// $info['pay_money'];
//        }
//        //生成支付字符串
//        $notify_url = C ('API_URL') . '/index.php/Api/Pay/alipayNotify/type/' . $_REQUEST['o_type'];
//        $out_trade_no = $orderid;
//        $total_amount = $pay_money;
//        $signo_type = 'RSA2';
//        $payObject = new \Alipay($notify_url , $out_trade_no , $total_amount , $signo_type);
//        $pay_string = $payObject->appPay ();
//        $this->apiResponse ('1' , '请求成功' , array ('pay_string' => $pay_string));
//    }
//    }

//        $_REQUEST['type'] = 3;
//if ( $_REQUEST['type'] == 1 ) {
//unset($where);
//$where['orderid'] = $out_trade_no;
//$where['status'] = array ('eq' , 0);
//$order = M ('order')->where ($where)->find ();
//if ( $order ) {
//    //修改订单状态
//M ('order')->where (array ('id' => $order['id']))
//->data (array ('pay_type' => 1 , 'trade_no' => $trade_no , 'update_time' => time () , 'status' => 1))
//->save ();
////                //扣除 积分
////                $order['integral'] = $order['integral'] * $order['num'];
////
////                M ('Member')->where (array ('id' => $order['m_id']))->setDec ('db_integral' , $order['integral']);
////                //增加积分明细
////                $this->addIntegralLog ($order['m_id'] , 1 , 1 , 1 , $order['integral'] , '兑换' . $order['goods_name']);
////                //增加余额明细
////                /*   $this->addPayLog($order['m_id'], 1, 1, $order['delivery_fee'], '积分加钱兑换商品支付金额');*/
//$integral_goods_info = M ('integral_goods')->where (array ('id' => $order['i_g_id']))->find ();
//if ( $integral_goods_info['is_wc'] == 1 ) {//如果是洗车券,立即发放
//for ( $i = 0; $i < $order['num']; $i++ ) {
//$this->addcoupons ($order['m_id'] , "积分兑换");
//}
//M ('order')->where (array ('id' => $order['id']))
//    ->data (array ('update_time' => time () , 'status' => 2))
//    ->save ();
//}
//echo "success";
//}
//} elseif ( $_REQUEST['type'] == 2 ) {
//
//            unset($where);
//            $where['orderid'] = $out_trade_no;
//            $where['status'] = array ('eq' , 0);
//            $groupOrder = M ('Order')->where ($where)->find ();
//            if ( $groupOrder ) {
//                //修改订单状态
//
//                $status = 1;// $groupOrder['type'] == 1 ? 2 : 1;
//                M ('Order')->where (array ('orderid' => $groupOrder['orderid']))
//                    ->data (array ('pay_type' => 1 , 'order_trade_no' => $trade_no , 'title' => "购买会员卡" , 'update_time' => time () , 'status' => $status , 'is_pay' => 1))
//                    ->save ();
////                //增加余额明细
////                $where['orderid'] = $out_trade_no;
////                $where['status'] = array ('eq' , 1);
////                $order_card = M ('Order')->where ($where)->find ();
////                $where1['id'] = $order_card['m_id'];
////                $where1['status'] = array ('eq' , 1);
////                $memberinfo = M ('Member')->where ($where1)->find ();
////                $where2['id'] = $order_card['card_id'];
////                $where2['status'] = array ('eq' , 1);
////                $washcard = M ('WashCard')->where ($where2)->find ();
////
////                $m_endtime = 2592000 * $washcard['end_time'];// var_dump($m_endtime);
////
////                if ( time () <= $memberinfo['m_endtime'] ) {//1当前有会员卡且尚未过期
////                    if ( $order_card['card_id'] >= $memberinfo['card_id'] ) {//card_id越大卡的等级越大
////
////                    } else {
////                        $order_card['card_id'] = $memberinfo['card_id'];
////                        $washcard['car_type'] = $memberinfo['card_type'];
////                    }
////                    $m_endtime = $m_endtime + $memberinfo['m_endtime'];
////                    M ('Member')->where ($where1)
////                        ->data (array ('m_endtime' => $m_endtime , 'card_type' => $washcard['car_type'] , 'update_time' => time () , 'card_id' => $order_card['card_id'] , 'is_pay' => 1))
////                        ->save ();
////                } else {//1当前无会员卡或已过期
////                    $startime = time ();
////                    $m_endtime = $m_endtime + $startime;
////                    M ('Member')->where ($where1)
////                        ->data (array ('startime' => $startime , 'm_endtime' => $m_endtime , 'card_type' => $washcard['car_type'] , 'update_time' => time () , 'card_id' => $order_card['card_id'] , 'is_pay' => 1))
////                        ->save ();
////                }
//
//                echo "success";
//            }
//        } elseif ( $_REQUEST['type'] == 3 ) {
//            unset($where);
//            $where['orderid'] = $out_trade_no;
//            $where['status'] = array ('eq' , 0);
//            $groupOrder = M ('Order')->where ($where)->find ();;
//            if ( empty($groupOrder) ) {
//                echo "null";
//            }
//            if ( $groupOrder ) {
//                //修改订单状态
//
//                $status = 1;// $groupOrder['type'] == 1 ? 2 : 1;
//                M ('Order')->where (array ('orderid' => $groupOrder['orderid']))
//                    ->data (array ('pay_type' => 1 , 'order_trade_no' => $trade_no , 'update_time' => time () , 'title' => "钱包充值" , 'sign' => "1" , 'status' => $status , 'is_pay' => 1))
//                    ->save ();
////                //增加余额明细
////                $where['orderid'] = $out_trade_no;
////                $where['status'] = array ('eq' , 1);
////                $order_card = M ('Order')->where ($where)->find ();
////                $where1['id'] = $order_card['m_id'];
////                $where1['status'] = array ('eq' , 1);
////                $memberinfo = M ('Member')->where ($where1)->find ();
////
////                $washcard['balance'] = $memberinfo['balance'] + $order_card['pay_money'];//var_dump($memberinfo );var_dump($order_card['pay_money']);
////                $wash = M ('Appsetting')->where (array ('id' => 1))
////                    ->find ();
////                $give_price = $wash['give_price'];
////
////                $washcard['give_balance'] = $memberinfo['give_balance'] + ($order_card['pay_money'] * $give_price);//var_dump($memberinfo );var_dump($order_card['pay_money']);
////                if ( $washcard['balance'] >= $memberinfo['balance'] ) {
////                    M ('Member')->where ($where1)
////                        ->data (array ('balance' => $washcard['balance'] , 'give_balance' => $washcard['give_balance'] , 'update_time' => time () , 'is_pay' => 1))
////                        ->save ();
////                }
////                $this->addPayLog ($memberinfo['id'] , 1 , 1 , $order_card['pay_money'] , '钱包充值');
////                $this->addPayLog ($memberinfo['id'] , 1 , 1 , $order_card['pay_money'] * $give_price , '钱包充值赠送');
//                $wash = M ('Appsetting')->where (array ('id' => 1))
//                    ->find ();
//
//                /*if ($order_card['m_id']) {
//                    $wash['ratio'] = $order_card['pay_money'] / $wash['ratio'];
//                    //增加积分
//                    $wash['db_integral']=$memberinfo['db_integral']+$wash['ratio'];
//                    if ($wash['db_integral']>=$memberinfo['db_integral']){
//                        M('Member')->where(array('id' => $order_card['m_id']))->save(array('db_integral'=> $wash['db_integral']));
//                    }
//                    //增加积分明细
//                    $this->addIntegralLog($order_card['m_id'], 2, 2, 0, $wash['ratio'], '充值获取积分');
//                }*/
//
//                echo "success";
//            }
//
//
//        } elseif ( $_REQUEST['type'] == 4 ) { //洗车
//
//            unset($where);
//            $where['orderid'] = $out_trade_no;
//            $where['status'] = array ('eq' , 0);
//            $groupOrder = M ('Order')->where ($where)->find ();
//            if ( $groupOrder ) {
//                //修改订单状态
//
//                $status = 1;// $groupOrder['type'] == 1 ? 2 : 1;
//                $info = M ('Order')->where (array ('orderid' => $groupOrder['orderid']))
//                    ->find ();
//                M ('Order')->where (array ('orderid' => $groupOrder['orderid']))
//                    ->data (array ('pay_type' => 1 , 'order_trade_no' => $trade_no , 'pay_money' => $info['order_price'] , 'update_time' => time () , 'title' => "洗车" , 'status' => $status , 'is_pay' => 1))
//                    ->save ();
//                $wash = M ('Appsetting')->where (array ('id' => 1))
//                    ->find ();
//                //增加积分明细
//                if ( $info['m_id'] ) {
//                    $where1['id'] = $info['m_id'];
//                    $where1['status'] = array ('eq' , 1);
//                    $memberinfo = M ('Member')->where ($where1)->find ();
//                    if ( time () <= $memberinfo['m_endtime'] ) {
//                        $this->addIntegralLog ($info['m_id'] , 2 , 1 , 0 , $wash['wash_jifen'] , '洗车赠送积分');
//                    }
//                }
//                $where['orderid'] = $out_trade_no;
//                $where['status'] = array ('eq' , 1);
//                $order_card = M ('Order')->where ($where)->find ();
//                $where1['id'] = $order_card['m_id'];
//                $where1['status'] = array ('eq' , 1);
//                $memberinfo = M ('Member')->where ($where1)->find ();
//                $wash = M ('Appsetting')->where (array ('id' => 1))
//                    ->find ();
//                if ( $order_card['m_id'] ) {
//                    if ( time () <= $memberinfo['m_endtime'] ) {
//                        //增加积分
//                        $wash['db_integral'] = $memberinfo['db_integral'] + $wash['wash_jifen'];
//                        if ( $wash['db_integral'] >= $memberinfo['db_integral'] ) {
//
//                            M ('Member')->where (array ('id' => $order_card['m_id']))->save (array ('db_integral' => $wash['db_integral']));
//                        }
//                        //增加积分明细
//                        $this->addIntegralLog ($order_card['m_id'] , 2 , 1 , 0 , $wash['wash_jifen'] , '洗车赠送积分');
//                    }
//                }
//                echo "success";
//            }
//
//
//        }
//
}
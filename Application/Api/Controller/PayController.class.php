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
//        $post['token'] = '5ecb3d16004f758c566a350346e0454b';
//        $post['card_name'] = '王子';
//        $post['card_code'] = '6216736088718187784568';
//        $post['ID_card'] = '587456988541235187';
//        $post['phone'] = '18635359874';
//        $post['card_id'] = 1;         //1建行  2中行  3农行

        $agent = $this->getAgentInfo ($post['token']);
        $data = array (
            'create_time' => time(),
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
        $add = M ('BankCard')->add ($data);
        if($add){
            $this->apiResponse ('1' , '绑定成功' , $car);
        }


    }

    /**
     *提现方式
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/24 11:15
     */
    public function withdrawWay () {
        $post = checkAppData ('token,page,size' , 'token-页数-个数');
//        $post['token'] = '5ecb3d16004f758c566a350346e0454b';
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
//        dump($cars);exit;
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
//                $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
//                $post['price'] = 2;
//                $post['card_id'] = 3;

        $agent = $this->getAgentInfo ($post['token']);
        $data = array (
            'balance' => $agent['balance'] - $post['price'] ,
        );
        if ( $data['balance'] < 0 ) {
            $this->apiResponse ('0' , '对不起,您余额不足');
        }
        if($post['price'] == 0){
            $this->apiResponse ('0' , '请输入正确金额');
        }
        $find_with = M('Withdraw')->where(array('agent_id'=>$agent['id'],'status'=>1))->find();
        if(!empty($find_with)){
            $this->apiResponse ('0' , '对不起,您有一笔提现金额待审核');
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
        $post = checkAppData ('token,page,size' , 'token-页数-个数');
//                $post['token'] = '5ecb3d16004f758c566a350346e0454b';
//                $post['page'] = 1;
//                $post['size'] = 10;
        $agent = $this->getAgentInfo ($post['token']);
        $order[] = 'create_time DESC';
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
//        $request = I ('post.');
        $request = $_REQUEST;// I('post.');

        $rule = array ('orderid' , 'string' , '订单编号不能为空');
        $this->checkParam ($rule);
        $order_info = D ("Order")->where (array ('orderid' => $request['orderid']))->find ();
        if ( !$order_info ) {
            $this->apiResponse (0 , '订单信息查询失败');
        }
        //日志
        $this->loggers('支付宝优惠方式:'.$request['methods'].'优惠ID:'.$request['methods_id']);

        $notify_url = C ('API_URL') . '/index.php/Api/Pay/AlipayNotify/methods/' . $request['methods'] . '/methods_id/' . $request['methods_id'];
        // 生成支付字符串
        $out_trade_no = $order_info['orderid'].rand(1000,9999);
        $total_amount = 0.01;//$order_info['pay_money'];
        if($total_amount == 0.00){
            $total_amount = 0.01;
        }
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
        $request = I ("request.");
        Vendor ('Txunda.Alipay.Notify');
        $notify = new \Notify();
        if ( $notify->rsaCheck () ) {
            $comma_separated = implode(",", $request);
            $this->loggers($comma_separated);
            //日志
            $this->loggers('huidiao订单号:'.$request['out_trade_no'].'金额:'.$request['total_fee']);
            $out_trade_no = substr($request['out_trade_no'], 0, -4); //本地订单号
            //日志
            $this->loggers('bendi订单号:'.$out_trade_no.'金额:'.$request['total_fee']);


            $trade_status = $request['trade_status'];
//            $pay_money = $request['total_amount']; //钱
            $order_no = $request['trade_no']; //支付宝流水号
            if ( $trade_status == 'TRADE_SUCCESS' ) {
//                $index = new IndexController();
//                $index->testCronTab (json_encode ($request));
                $order = M ('Order')->where (array ('orderid' => $out_trade_no))->find ();
                $Member = M ('Member')->where (array ('id' => $order['m_id']))->find ();
                $date['pay_type'] = 2;
                $date['status'] = 2;
                $date['is_set'] = 1;
                $date['pay_time'] = time ();
                $date['trade_no'] = $order_no;
                $date['detail'] = 2;
                if ( $order['o_type'] == 1 && $order['status'] == 1) {//1洗车订单
                    if ( $request['methods'] == '1' ) {
                        $cards = M ("CardUser")->where (['id' => $request['methods_id']])->field ('l_id')->find ();
                        $card = M ("LittlewhaleCard")->where (['id' => $cards])->field ('rebate')->find ();
                        $date['is_dis'] = '1';
                        $date['card_id'] = $request['methods_id'];
                        $date['allowance'] = $card['rebate'];
                    } elseif ( $request['methods'] == '2' ) {
                        $coup = M ("CouponBind")->where (['id' => $request['methods_id']])->field ('money')->find ();
                        M ("CouponBind")->where (['id' => $request['methods_id']])->save (['is_use' => '1']);
                        $date['is_dis'] = '1';
                        $date['coup_id'] = $request['methods_id'];
                        $date['allowance'] = $coup['money'];
                    } elseif ( $request['methods'] == '3' ) {
                        $date['is_dis'] = '0';
                    }
                    $save = D ("Order")->where (array ('orderid' => $out_trade_no))->save ($date);
                    if ( $save ) {
                        //添加到收益表
                        $a_where['orderid'] = $out_trade_no;
                        $a_where['status'] = 2;
                        $a_where['o_type'] = 1;
                        $a_order = M ('Order')->where ($a_where)->field ('c_id,pay_money,pay_time')->find ();
                        $agent_where['id'] = $a_order['c_id'];
                        $car = M ('CarWasher')->where ($agent_where)->find ();   //查找代理商id
                        $agent = M ('Agent')->where (array ('id' => $car['agent_id']))->field ('grade,balance,p_id')->find ();
                        //日志
                        $this->loggers('支付宝'.$car['h_rate']);
                        //新增代理商分润
                        if($agent['grade'] == 2){
                            if($order['pay_money'] < 1.5){
                                $platform = 0;          //平台运营服务费
                            }else{
                                $platform = $car['service_money'];
                            }
                            $operating = bcsub($order['pay_money'],$platform,2);         //营业收入
                            $plat_money = bcmul ($operating , $car['pt_rate'],2);         //平台分润
                            $partner_money = bcmul($operating , $car['h_rate'],2);           //合作方分润
                            //日志
                            $this->loggers('支付宝'.$partner_money.$car['h_rate']);
                            $p_money = 0;          //上级代理商分润
                            $net_incomes = bcsub($operating , $plat_money ,2);
                            $net_income = bcsub($net_incomes , $partner_money ,2);       //净收入
                        }elseif($agent['grade'] == 3){
                            if($order['pay_money'] < 1.5){
                                $platform = 0;
                            }else{
                                $platform = $car['service_money'];
                            }
                            $operating = bcsub($order['pay_money'],$platform,2);         //营业收入
                            $plat_money = bcmul ($operating , $car['pt_rate'],2);         //平台分润
                            $partner_money = bcmul($operating , $car['h_rate'],2);           //合作方分润
                            //日志
                            $this->loggers('支付宝'.$partner_money.$car['h_rate']);
                            $p_money = bcmul($operating , $car['p_rate'],2);          //上级代理商分润
                            $net_incomes = bcsub($operating , $plat_money ,2);            //营业收入减去平台分润
                            $net_incomess = bcsub($net_incomes , $partner_money ,2);       //再减去合作方分润
                            $net_income = bcsub($net_incomess , $p_money,2);              //再减去代理商收入 = 净收入
                            M('Agent')->where(array('id'=>$agent['p_id']))->setInc('balance',$p_money);    //上级代理商增加收入
                        }
//                    $income_where['agent_id'] = $car['agent_id'];
//                    $income_where['car_washer_id'] = $a_order['c_id'];
                        $income_where['day'] = strtotime (date ('Y-m-d' , $a_order['pay_time']));
//                    $income = M ('Income')->where ($income_where)->field ('detail,net_income,car_wash,day,week_star,week_end,month,year,create_time')->find ();
//                    if ( $agent['grade'] == 1 ) {
//                        $net_income = $a_order['pay_money'] - $a_order['pay_money'] * 0.05;
//                    } elseif ( $agent['grade'] == 2 ) {
//                        $net_income = $a_order['pay_money'] - $a_order['pay_money'] * 0.1;
//                    } elseif ( $agent['grade'] == 3 ) {
//                        $net_income = $a_order['pay_money'] - $a_order['pay_money'] * 0.15;
//                    }
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
                            'net_income' => $net_income ,     //净收入
                            'platform' => $platform,                //平台运营费
                            'plat_money' => $plat_money,             //平台分润
                            'partner_money' => $partner_money,           //合作方分润
                            'p_money' => $p_money ,                  //上级代理商分润
                            'car_wash' => 1 ,
                            'day' => $income_where['day'] ,
                            'week_star' => $week_star ,
                            'week_end' => $week_end ,
                            'month' => $month ,
                            'year' => $year ,
                            'create_time' => $a_order['pay_time'] ,
                            'orderid' => $a_where['orderid'],
                        );
                        M ('Income')->add ($income_add);
                        M ('Agent')->where (array ('id' => $car['agent_id']))->setInc('balance',$net_income);     //代理商增加收入
                        M('Agent')->where(array('id'=>$car['partner_id']))->setInc('balance',$partner_money);    //合作方增加收入

//                    } else {
//                        $income_save = array (
//                            'detail' => $income['detail'] + $a_order['pay_money'] ,
//                            'net_income' => $income['net_income'] + $net_income ,
//                            'car_wash' => $income['car_wash'] + 1 ,
//                            'create_time' => $a_order['pay_time'] ,
//                        );
//                        if ( $income['create_time'] != $a_order['pay_time'] ) {
//                            M ('Income')->where ($income_where)->save ($income_save);
//                            $agent_save['balance'] = $agent['balance'] + $net_income;
//                            M ('Agent')->where (array ('id' => $car['agent_id']))->save ($agent_save);
//                        }
//                    }
                        echo "success";
                    }
                } elseif ( $order['o_type'] == 2 && $order['status'] == 1) {//2小鲸卡购买
                    //判断是否存在小鲸卡
                    $have = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => $order['card_id']))->find ();
                    $have_h = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => 2))->find ();
                    $have_z = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => 1))->find ();
                    if(!empty($have)){
                        if($order['card_id'] == 1){      //购买钻石卡
                            if ( $have['end_time'] < time () ) {
                                $off['end_time'] = time () + (30 * 24 * 3600);
                            } else {
                                $off['end_time'] = $have_z['end_time'] + (30 * 24 * 3600);
                            }
                            $off['update_time'] = time ();
                            $off['status'] = 1;
                            $off['is_open'] = 1;
                            $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                            $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $order['m_id'] , 'l_id' => $order['card_id']))->save ($off);
                            $card_tsave = array(     //如果黄金卡还没过期还在使用中,直接覆盖
                                'status' => 2,
                                'is_open' => 2,
                                'end_time'=>1555147655,
                            );
                            $card_t = M('CardUser')->where(array ('m_id' => $order['m_id'] , 'l_id' => 2,'is_open'=>1))->save($card_tsave);
                            if($have_h['end_time'] > time()){        //购买钻石卡,黄金卡未过期,自动加时间
                                $h_time= $have_h['end_time']-$have_h['stare_time'];
                                $om['update_time'] = time ();
                                $om['end_time'] = $off['end_time'] + $h_time;
                                $om['stare_time'] = $off['end_time'] ;
                                $om['status'] = 1;
                                $om['is_open'] = 2;
                                $card_h = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => 2))->save ($om);
                            }
                        }elseif($order['card_id'] == 2){       //购买黄金卡
                            //判断是否存在尚未过期还在使用的钻石卡
                            $f_card = M('CardUser')->where(array('m_id' => $order['m_id'] , 'l_id' => 1,'status'=>1,'is_open'=>1))->find();
                            if(!empty($f_card)){    //如果存在尚未过期的,等钻石卡过期再使用
                                if ( $have_h['end_time'] < time () ) {
                                    $off['end_time'] = time () + (30 * 24 * 3600);
                                } else {
                                    $off['end_time'] = $have_h['end_time'] + (30 * 24 * 3600);
                                }
                                $off['update_time'] = time ();
                                $off['status'] = 1;
                                $off['is_open'] = 2;
                                $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $order['m_id'] , 'l_id' => $order['card_id']))->save ($off);
                            }else{
                                if ( $have_h['end_time'] < time () ) {
                                    $off['end_time'] = time () + (30 * 24 * 3600);
                                } else {
                                    $off['end_time'] = $have_h['end_time'] + (30 * 24 * 3600);
                                }
                                $off['update_time'] = time ();
                                $off['status'] = 1;
                                $off['is_open'] = 1;
                                $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                                $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $order['m_id'] , 'l_id' => $order['card_id']))->save ($off);
                            }
                        }
                    }else{
                        if($order['card_id'] == 1){      //购买钻石卡
                            $on['end_time'] = time() + (30 * 24 * 3600);
                            $on['create_time'] = time ();
                            $on['stare_time'] = time();
                            $on['l_id'] = $order['card_id'];
                            $on['m_id'] = $order['m_id'];
                            $on['status'] = 1;
                            $on['is_open'] = 1;
                            $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                            $card = D ("CardUser")->add($on);
                            $card_tsave = array(     //如果黄金卡还没过期还在使用中,直接覆盖
                                'status' => 2,
                                'is_open' => 2,
                                'end_time'=>1555147655,
                            );
                            $card_t = M('CardUser')->where(array ('m_id' => $order['m_id'] , 'l_id' => 2 , 'is_open'=>1))->save($card_tsave);
                        }elseif($order['card_id'] == 2){       //购买黄金卡
                            //判断是否存在尚未过期还在使用的钻石卡
                            $f_card = M('CardUser')->where(array('m_id' => $order['m_id'] , 'l_id' => 1,'status'=>1,'is_open'=>1))->find();
                            if(!empty($f_card)){    //如果存在尚未过期的,等钻石卡过期再使用
                                $on['end_time'] = $f_card['end_time'] + (30 * 24 * 3600);
                                $on['create_time'] = time ();
                                $on['stare_time'] = $f_card['end_time'];
                                $on['l_id'] = $order['card_id'];
                                $on['m_id'] = $order['m_id'];
                                $on['status'] = 1;
                                $on['is_open'] = 2;
                                $card = D ("CardUser")->add($on);
                            }else{
                                $on['end_time'] = time() + (30 * 24 * 3600);
                                $on['create_time'] = time ();
                                $on['stare_time'] = time();
                                $on['l_id'] = $order['card_id'];
                                $on['m_id'] = $order['m_id'];
                                $on['is_open'] = 1;
                                $on['status'] = 1;
                                $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                                $card = D ("CardUser")->add($on);
                            }
                        }
                    }
                    $save = D ("Order")->where (array ('orderid' => $order_no))->save ($date);
                    if ( $save && $card ) {
                        echo "success";
                    }
                } elseif ( $order['o_type'] == 3 && $order['status'] == 1) {//3余额充值
                    $date['detail'] = 1;
                    $save = M ("Order")->where (array ('orderid' => $out_trade_no))->save ($date);
                    $buy = M ('Member')->where (array ('id' => $order['m_id']))->Save (array ('balance' => $Member['balance'] + $order['pay_money'] + $order['give_money']));
                    if ( $save && $buy ) {
                        echo "success";
                    }
                }
            }
            echo 'success';
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
//        $request = I ("");
        $request = $_REQUEST;// I('post.');
//        $rule = array(
//            array('methods', 'string', '请输入手机号'),
//            array('methods_id', 'string', '请输入密码'),
//        );
//        $this->checkParam($rule);
        // 查询订单信息
        $order_info = M ("Order")->where (array ('orderid' => $request['orderid']))->find ();

        if ( !$order_info ) {
            $this->apiResponse (0 , '订单信息查询失败' , $request);
        }
        /* 统一下单 start */
        $url_unifiedorder = "https://api.mch.weixin.qq.com/pay/unifiedorder"; // 统一下单 URL
        $xml_data = [];
        $xml_data['body'] = "小鲸洗车-订单号-" . $order_info['orderid']; // 商品描述
//        if($request['methods'] == 1){           //1小鲸卡 2代金券 3无优惠方式
//
//        }
        $xml_data['out_trade_no'] = $order_info['orderid'].rand(1000,9999); // 订单流水
        $xml_data['notify_url'] = C ('API_URL') . "/index.php/Api/Pay/WeChatNotify"; // 回调 URL
        $xml_data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR']; // 终端 IP
//        $xml_data['total_fee'] = 1; // 支付金额 单位[分]
        $xml_data['total_fee'] = $order_info['pay_money'] * 100; // 支付金额 单位[分]
        //日志
        $this->loggers('订单号:'.$order_info['orderid'].'金额:'.$xml_data['total_fee']);
//日志
        $this->loggers('微信优惠方式:'.$request['methods'].'优惠ID:'.$request['methods_id']);

        if($xml_data['total_fee'] == 0){
            $xml_data['total_fee'] = 1;
        }
        $xml_data['nonce_str'] = $this->getNonceStr (32);
        $key = "b2836e3bb4d1c04f567eab868fb99aee"; // 设置的KEY值相同
        // 附加数据
        $attach_data = [
            "methods" => $request['methods'] ,
            "methods_id" => $request['methods_id'] ,
        ];
        $xml_data['attach'] = json_encode ($attach_data); // 附加数据 JSON

        if ( $request['trade_type'] == 1) {// 小程序
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
     *写入日志
     * @param $content
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/06/11 15:37
     */
    public function loggers($content){
        header("Content-type:text/html;charset=utf-8");
        $path = './Logs/';

        if(!empty($content)){
            $email = $content;
            $log = $path."settlements.txt";
            $input = $email."\r\n";
//            $max = 2*1024*1024;
//            if(strlen($input)>$max){
//
//            }
            file_put_contents($log,date('Y-m-d H:i:s') . " " .$input. "\n",FILE_APPEND);
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
        //日志
        $this->loggers('huidiao订单号:'.$log['out_trade_no'].'金额:'.$log['total_fee']);
        // 获取订单流水号
        $order_no = substr($log['out_trade_no'], 0, -4); //本地订单号
        $this->loggers('bendi订单号:'.$order_no.'金额:'.$log['total_fee']);

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
        $date['detail'] = 2;
        if ( $order['o_type'] == 1 && $order['status'] == 1) {//1洗车订单
            if ( $order_info['methods'] == '1' ) {
                $cards = M ("CardUser")->where (['id' => $order_info['methods_id']])->field ('l_id')->find ();
                $card = M ("LittlewhaleCard")->where (['id' => $cards])->field ('rebate')->find ();
                $date['is_dis'] = '1';
                $date['card_id'] = $order_info['methods_id'];
                $date['allowance'] = $card['rebate'];
            } elseif ( $order_info['methods'] == '2' ) {
                M ("CouponBind")->where (['id' => $order_info['methods_id']])->save (['is_use' => '1']);
                $coup = M ("CouponBind")->where (['id' => $order_info['methods_id']])->field ('money')->find ();
                $date['is_dis'] = '1';
                $date['coup_id'] = $order_info['methods_id'];
                $date['allowance'] = $coup['money'];
            } elseif ( $order_info['methods'] == '3' ) {
                $date['is_dis'] = '0';
            }
            $save = D ("Order")->where (array ('orderid' => $order['orderid']))->save ($date);
            if ( $save ) {
                //添加到收益表
                $a_where['orderid'] = $order_no;
                $a_where['status'] = 2;
                $a_where['o_type'] = 1;
                $a_order = M ('Order')->where ($a_where)->field ('c_id,pay_money,pay_time')->find ();
                $agent_where['id'] = $a_order['c_id'];
                $car = M ('CarWasher')->where ($agent_where)->find ();   //查找代理商id
                $agent = M ('Agent')->where (array ('id' => $car['agent_id']))->field ('grade,balance,p_id')->find ();

                //新增代理商分润
                if($agent['grade'] == 2){
                    if($order['pay_money'] < 1.5){
                        $platform = 0;          //平台运营服务费
                    }else{
                        $platform = $car['service_money'];
                    }
                    $operating = bcsub($order['pay_money'],$platform,2);         //营业收入
                    $plat_money = bcmul ($operating , $car['pt_rate'],2);         //平台分润
                    $partner_money = bcmul($operating , $car['h_rate'],2);           //合作方分润
                    //日志
                    $this->loggers('微信'.$partner_money,$car['h_rate']);
                    $p_money = 0;          //上级代理商分润
                    $net_incomes = bcsub($operating , $plat_money ,2);
                    $net_income = bcsub($net_incomes , $partner_money ,2);       //净收入
                }elseif($agent['grade'] == 3){
                    if($order['pay_money'] < 1.5){
                        $platform = 0;
                    }else{
                        $platform = $car['service_money'];
                    }
                    //日志
                    $this->loggers('洗车机id:'.$car['id'].'洗车机合作商分润:'.$car['h_rate'].'参数'.$order_info['methods']);
                    $operating = bcsub($order['pay_money'],$platform,2);         //营业收入
                    $plat_money = bcmul ($operating , $car['pt_rate'],2);         //平台分润
                    $partner_money = bcmul($operating , $car['h_rate'],2);           //合作方分润
                    //日志
                    $this->loggers('微信'.$partner_money.'洗车机合作商分润'.$car['h_rate']);
                    $p_money = bcmul($operating , $car['p_rate'],2);          //上级代理商分润
                    $net_incomes = bcsub($operating , $plat_money ,2);          //营业收入减去平台分润
                    $net_incomess = bcsub($net_incomes , $partner_money ,2);    //再减去合作方分润
                    $net_income = bcsub($net_incomess , $p_money,2);              // 再减去上级分润 = 净收入
                    M('Agent')->where(array('id'=>$agent['p_id']))->setInc('balance',$p_money);    //上级代理商增加收入
                }
//                    $income_where['agent_id'] = $car['agent_id'];
//                    $income_where['car_washer_id'] = $a_order['c_id'];
                $income_where['day'] = strtotime (date ('Y-m-d' , $a_order['pay_time']));
//                    $income = M ('Income')->where ($income_where)->field ('detail,net_income,car_wash,day,week_star,week_end,month,year,create_time')->find ();
//                    if ( $agent['grade'] == 1 ) {
//                        $net_income = $a_order['pay_money'] - $a_order['pay_money'] * 0.05;
//                    } elseif ( $agent['grade'] == 2 ) {
//                        $net_income = $a_order['pay_money'] - $a_order['pay_money'] * 0.1;
//                    } elseif ( $agent['grade'] == 3 ) {
//                        $net_income = $a_order['pay_money'] - $a_order['pay_money'] * 0.15;
//                    }
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
                    'net_income' => $net_income ,     //净收入
                    'platform' => $platform,                //平台运营费
                    'plat_money' => $plat_money,             //平台分润
                    'partner_money' => $partner_money,           //合作方分润
                    'p_money' => $p_money ,                  //上级代理商分润
                    'car_wash' => 1 ,
                    'day' => $income_where['day'] ,
                    'week_star' => $week_star ,
                    'week_end' => $week_end ,
                    'month' => $month ,
                    'year' => $year ,
                    'create_time' => $a_order['pay_time'] ,
                    'orderid' => $a_where['orderid'],
                );
                M ('Income')->add ($income_add);
                M ('Agent')->where (array ('id' => $car['agent_id']))->setInc('balance',$net_income);     //代理商增加收入
                M('Agent')->where(array('id'=>$car['partner_id']))->setInc('balance',$partner_money);    //合作方增加收入

//                    } else {
//                        $income_save = array (
//                            'detail' => $income['detail'] + $a_order['pay_money'] ,
//                            'net_income' => $income['net_income'] + $net_income ,
//                            'car_wash' => $income['car_wash'] + 1 ,
//                            'create_time' => $a_order['pay_time'] ,
//                        );
//                        if ( $income['create_time'] != $a_order['pay_time'] ) {
//                            M ('Income')->where ($income_where)->save ($income_save);
//                            $agent_save['balance'] = $agent['balance'] + $net_income;
//                            M ('Agent')->where (array ('id' => $car['agent_id']))->save ($agent_save);
//                        }
//                    }
                echo "success";
            }
        } elseif ( $order['o_type'] == 2 && $order['status'] == 1) {                        //2小鲸卡购买

            //判断是否存在小鲸卡
            $have = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => $order['card_id']))->find ();
            $have_h = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => 2))->find ();
            $have_z = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => 1))->find ();
            if(!empty($have)){
                if($order['card_id'] == 1){      //购买钻石卡
                    if ( $have['end_time'] < time () ) {
                        $off['end_time'] = time () + (30 * 24 * 3600);
                    } else {
                        $off['end_time'] = $have_z['end_time'] + (30 * 24 * 3600);
                    }
                    $off['update_time'] = time ();
                    $off['status'] = 1;
                    $off['is_open'] = 1;
                    $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                    $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $order['m_id'] , 'l_id' => $order['card_id']))->save ($off);
                    $card_tsave = array(     //如果黄金卡还没过期还在使用中,直接覆盖
                        'status' => 2,
                        'is_open' => 2,
                        'end_time'=>1555147655,
                    );
                    $card_t = M('CardUser')->where(array ('m_id' => $order['m_id'] , 'l_id' => 2,'is_open'=>1))->save($card_tsave);
                    if($have_h['end_time'] > time()){        //购买钻石卡,黄金卡未过期,自动加时间
                        $h_time= $have_h['end_time']-$have_h['stare_time'];
                        $om['update_time'] = time ();
                        $om['end_time'] = $off['end_time'] + $h_time;
                        $om['stare_time'] = $off['end_time'] ;
                        $om['status'] = 1;
                        $om['is_open'] = 2;
                        $card_h = D ("CardUser")->where (array ('m_id' => $order['m_id'] , 'l_id' => 2))->save ($om);
                    }
                }elseif($order['card_id'] == 2){       //购买黄金卡
                    //判断是否存在尚未过期还在使用的钻石卡
                    $f_card = M('CardUser')->where(array('m_id' => $order['m_id'] , 'l_id' => 1,'status'=>1,'is_open'=>1))->find();
                    if(!empty($f_card)){    //如果存在尚未过期的,等钻石卡过期再使用
                        if ( $have_h['end_time'] < time () ) {
                            $off['end_time'] = time () + (30 * 24 * 3600);
                        } else {
                            $off['end_time'] = $have_h['end_time'] + (30 * 24 * 3600);
                        }
                        $off['update_time'] = time ();
                        $off['status'] = 1;
                        $off['is_open'] = 2;
                        $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $order['m_id'] , 'l_id' => $order['card_id']))->save ($off);
                    }else{
                        if ( $have_h['end_time'] < time () ) {
                            $off['end_time'] = time () + (30 * 24 * 3600);
                        } else {
                            $off['end_time'] = $have_h['end_time'] + (30 * 24 * 3600);
                        }
                        $off['update_time'] = time ();
                        $off['status'] = 1;
                        $off['is_open'] = 1;
                        $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                        $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $order['m_id'] , 'l_id' => $order['card_id']))->save ($off);
                    }
                }
            }else{
                if($order['card_id'] == 1){      //购买钻石卡
                    $on['end_time'] = time() + (30 * 24 * 3600);
                    $on['create_time'] = time ();
                    $on['stare_time'] = time();
                    $on['l_id'] = $order['card_id'];
                    $on['m_id'] = $order['m_id'];
                    $on['status'] = 1;
                    $on['is_open'] = 1;
                    $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                    $card = D ("CardUser")->add($on);
                    $card_tsave = array(     //如果黄金卡还没过期还在使用中,直接覆盖
                        'status' => 2,
                        'is_open' => 2,
                        'end_time'=>1555147655,
                    );
                    $card_t = M('CardUser')->where(array ('m_id' => $order['m_id'] , 'l_id' => 2 , 'is_open'=>1))->save($card_tsave);
                }elseif($order['card_id'] == 2){       //购买黄金卡
                    //判断是否存在尚未过期还在使用的钻石卡
                    $f_card = M('CardUser')->where(array('m_id' => $order['m_id'] , 'l_id' => 1,'status'=>1,'is_open'=>1))->find();
                    if(!empty($f_card)){    //如果存在尚未过期的,等钻石卡过期再使用
                        $on['end_time'] = $f_card['end_time'] + (30 * 24 * 3600);
                        $on['create_time'] = time ();
                        $on['stare_time'] = $f_card['end_time'];
                        $on['l_id'] = $order['card_id'];
                        $on['m_id'] = $order['m_id'];
                        $on['status'] = 1;
                        $on['is_open'] = 2;
                        $card = D ("CardUser")->add($on);
                    }else{
                        $on['end_time'] = time() + (30 * 24 * 3600);
                        $on['create_time'] = time ();
                        $on['stare_time'] = time();
                        $on['l_id'] = $order['card_id'];
                        $on['m_id'] = $order['m_id'];
                        $on['is_open'] = 1;
                        $on['status'] = 1;
                        $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                        $card = D ("CardUser")->add($on);
                    }
                }
            }
            $save = D ("Order")->where (array ('orderid' => $order_no))->save ($date);
            if ( $save && $card ) {
                echo "success";
            }
        } elseif ( $order['o_type'] == 3 && $order['status'] == 1) {//3余额充值
            $date['detail'] = 1;
            $save = D ("Order")->where (array ('orderid' => $order_no))->save ($date);
            $buy = D ('Member')->where (array ('id' => $order['m_id']))->Save (array ('balance' => $Member['balance'] + $order['pay_money'] + $order['give_money']));
            if ( $save && $buy ) {
                echo "success";
            }
        }
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
//        $where['o_type'] = array ('neq' , 3);
//        $where['m_id'] = $m_id;
        $where['orderid'] = $request['orderid'];
//        $where['is_set'] = 0;
//        $where['status'] = 1;
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
        if($order['pay_money'] == 0){
            $order['pay_money'] = 0.01;
        }

        if ( $order['o_type'] ) {
            if ( $order['o_type'] == 1 && $order['status'] == 1) {//1洗车订单
                if ( $request['methods'] == 1 ) {
                    $cards = M ("CardUser")->where (['id' => $request['methods_id']])->field ('l_id')->find ();
                    $card = M ("LittlewhaleCard")->where (['id' => $cards])->field ('rebate')->find ();
                    $date['is_dis'] = 1;
                    $date['card_id'] = $request['methods_id'];
                    $date['allowance'] = $card['rebate'];
                } elseif ( $request['methods'] == 2 ) {
                    M ("CouponBind")->where (['id' => $request['methods_id']])->save (['is_use' => '1']);
                    $coup = M ("CouponBind")->where (['id' => $request['methods_id']])->field ('money')->find ();
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
//                    $a_where['status'] = 2;
                    $a_where['o_type'] = 1;
                    $a_order = M ('Order')->where ($a_where)->field ('c_id,pay_money,pay_time')->find ();
                    $agent_where['id'] = $a_order['c_id'];
                    $car = M ('CarWasher')->where ($agent_where)->find ();   //查找代理商id
                    $agent = M ('Agent')->where (array ('id' => $car['agent_id']))->field ('grade,balance,p_id')->find ();

                    //新增代理商分润
                    if($agent['grade'] == 2){
                        if($order['pay_money'] < 1.5){
                            $platform = 0;          //平台运营服务费
                        }else{
                            $platform = $car['service_money'];
                        }
                        $operating = bcsub($order['pay_money'],$platform,2);         //营业收入
                        $plat_money = bcmul ($operating , $car['pt_rate'],2);         //平台分润
                        $partner_money = bcmul($operating , $car['h_rate'],2);           //合作方分润
                        $p_money = 0;          //上级代理商分润
                        $net_incomes = bcsub($operating , $plat_money ,2);
                        $net_income = bcsub($net_incomes , $partner_money ,2);       //净收入
                    }elseif($agent['grade'] == 3){
                        if($order['pay_money'] < 1.5){
                            $platform = 0;
                        }else{
                            $platform = $car['service_money'];
                        }
                        $operating = bcsub($order['pay_money'],$platform,2);         //营业收入
                        $plat_money = bcmul ($operating , $car['pt_rate'],2);         //平台分润
                        $partner_money = bcmul($operating , $car['h_rate'],2);           //合作方分润
                        $p_money = bcmul($operating , $car['p_rate'],2);          //上级代理商分润
                        $net_incomes = bcsub($operating , $plat_money ,2);           //营业收入减去平台分润
                        $net_incomess = bcsub($net_incomes , $partner_money ,2);      //再减去合作方分润
                        $net_income = bcsub($net_incomess , $p_money,2);              // 再减去上级分润 = 净收入
                        M('Agent')->where(array('id'=>$agent['p_id']))->setInc('balance',$p_money);    //上级代理商增加收入
                    }
//                    $income_where['agent_id'] = $car['agent_id'];
//                    $income_where['car_washer_id'] = $a_order['c_id'];
                    $income_where['day'] = strtotime (date ('Y-m-d' , $a_order['pay_time']));
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
                        'net_income' => $net_income ,     //净收入
                        'platform' => $platform,                //平台运营费
                        'plat_money' => $plat_money,             //平台分润
                        'partner_money' => $partner_money,           //合作方分润
                        'p_money' => $p_money ,                  //上级代理商分润
                        'car_wash' => 1 ,
                        'day' => $income_where['day'] ,
                        'week_star' => $week_star ,
                        'week_end' => $week_end ,
                        'month' => $month ,
                        'year' => $year ,
                        'create_time' => $a_order['pay_time'] ,
                        'orderid' => $a_where['orderid'],
                    );
                    M ('Income')->add ($income_add);
                    M ('Agent')->where (array ('id' => $car['agent_id']))->setInc('balance',$net_income);   //代理商增加收入
                    M('Agent')->where(array('id'=>$car['partner_id']))->setInc('balance',$partner_money);    //合作方增加收入
                    $this->apiResponse (1 , '支付成功');
                }
            } elseif ( $order['o_type'] == 2 && $order['status'] == 1) {//2小鲸卡购买
                //判断是否存在小鲸卡
                $have = D ("CardUser")->where (array ('m_id' => $m_id , 'l_id' => $order['card_id']))->find ();
                $have_h = D ("CardUser")->where (array ('m_id' => $m_id , 'l_id' => 2))->find ();
                $have_z = D ("CardUser")->where (array ('m_id' => $m_id , 'l_id' => 1))->find ();
                if(!empty($have)){
                    if($order['card_id'] == 1){      //购买钻石卡
                        if ( $have['end_time'] < time () ) {
                            $off['end_time'] = time () + (30 * 24 * 3600);
                        } else {
                            $off['end_time'] = $have_z['end_time'] + (30 * 24 * 3600);
                        }
                        $off['update_time'] = time ();
                        $off['status'] = 1;
                        $off['is_open'] = 1;
                        $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                        $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $m_id , 'l_id' => $order['card_id']))->save ($off);
                        $card_tsave = array(     //如果黄金卡还没过期还在使用中,直接覆盖
                            'status' => 2,
                            'is_open' => 2,
                            'end_time'=>1555147655,
                        );
                        $card_t = M('CardUser')->where(array ('m_id' => $m_id , 'l_id' => 2,'is_open'=>1))->save($card_tsave);
                        if($have_h['end_time'] > time()){        //购买钻石卡,黄金卡未过期,自动加时间
                            $h_time= $have_h['end_time']-$have_h['stare_time'];
                            $om['update_time'] = time ();
                            $om['end_time'] = $off['end_time'] + $h_time;
                            $om['stare_time'] = $off['end_time'] ;
                            $om['status'] = 1;
                            $om['is_open'] = 2;
                            $card_h = D ("CardUser")->where (array ('m_id' => $m_id , 'l_id' => 2))->save ($om);
                        }
                    }elseif($order['card_id'] == 2){       //购买黄金卡
                        //判断是否存在尚未过期还在使用的钻石卡
                        $f_card = M('CardUser')->where(array('m_id' => $m_id , 'l_id' => 1,'status'=>1,'is_open'=>1))->find();
                        if(!empty($f_card)){    //如果存在尚未过期的,等钻石卡过期再使用
                            if ( $have_h['end_time'] < time () ) {
                                $off['end_time'] = time () + (30 * 24 * 3600);
                            } else {
                                $off['end_time'] = $have_h['end_time'] + (30 * 24 * 3600);
                            }
                            $off['update_time'] = time ();
                            $off['status'] = 1;
                            $off['is_open'] = 2;
                            $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $m_id , 'l_id' => $order['card_id']))->save ($off);
                        }else{
                            if ( $have_h['end_time'] < time () ) {
                                $off['end_time'] = time () + (30 * 24 * 3600);
                            } else {
                                $off['end_time'] = $have_h['end_time'] + (30 * 24 * 3600);
                            }
                            $off['update_time'] = time ();
                            $off['status'] = 1;
                            $off['is_open'] = 1;
                            $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                            $card = D ("CardUser")->where (array ('id' => $have['id'] , 'm_id' => $m_id , 'l_id' => $order['card_id']))->save ($off);
                        }
                    }
                }else{
                    if($order['card_id'] == 1){      //购买钻石卡
                        $on['end_time'] = time() + (30 * 24 * 3600);
                        $on['create_time'] = time ();
                        $on['stare_time'] = time();
                        $on['l_id'] = $order['card_id'];
                        $on['m_id'] = $m_id;
                        $on['status'] = 1;
                        $on['is_open'] = 1;
                        $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                        $card = D ("CardUser")->add($on);
                        $card_tsave = array(     //如果黄金卡还没过期还在使用中,直接覆盖
                            'status' => 2,
                            'is_open' => 2,
                            'end_time'=>1555147655,
                        );
                        $card_t = M('CardUser')->where(array ('m_id' => $m_id , 'l_id' => 2 , 'is_open'=>1))->save($card_tsave);
                    }elseif($order['card_id'] == 2){       //购买黄金卡
                        //判断是否存在尚未过期还在使用的钻石卡
                        $f_card = M('CardUser')->where(array('m_id' => $m_id , 'l_id' => 1,'status'=>1,'is_open'=>1))->find();
                        if(!empty($f_card)){    //如果存在尚未过期的,等钻石卡过期再使用
                            $on['end_time'] = $f_card['end_time'] + (30 * 24 * 3600);
                            $on['create_time'] = time ();
                            $on['stare_time'] = $f_card['end_time'];
                            $on['l_id'] = $order['card_id'];
                            $on['m_id'] = $m_id;
                            $on['status'] = 1;
                            $on['is_open'] = 2;
                            $card = D ("CardUser")->add($on);
                        }else{
                            $on['end_time'] = time() + (30 * 24 * 3600);
                            $on['create_time'] = time ();
                            $on['stare_time'] = time();
                            $on['l_id'] = $order['card_id'];
                            $on['m_id'] = $m_id;
                            $on['is_open'] = 1;
                            $on['status'] = 1;
                            $degree = D ('Member')->where (array ('id' => $order['m_id']))->save (array('degree'=>$order['card_id']));
                            $card = D ("CardUser")->add($on);
                        }
                    }
                }
                $pay = D ('Member')->where (array ('id' => $m_id))->save (array ( 'balance' => $Member['balance'] - $order['pay_money']));
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
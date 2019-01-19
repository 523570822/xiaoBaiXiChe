<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 10:02
 */

namespace Api\Controller;

use Common\Service\ControllerService;

class WalletController extends BaseController
{
    /**
     *余额
     **/
    public function balance ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $wallet = D ('Member')->field ('balance')->queryRow (array ('id' => $m_id));
        $this->apiResponse ('1' , '可用余额' , $wallet);
    }

    /**
     *充值表
     **/
    public function rechargeTable ()
    {
        $recharge_table = D ('Sum')
            ->where (array ('status' => 1))
            ->field ('id,name,money,pre_money')
            ->select ();
        $this->apiResponse ('1' , '金额' , $recharge_table);
    }


    /**
     *充值
     **/
    public function recharge ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $appsetting = D ('Appsetting')->field ('song_money')->find ();
        $request = $_REQUEST;
        $rule = array ('money' , 'string' , '选择、输入充值金额');
        $this->checkParam ($rule);
        if ( $request['money'] !== '' ) {
            $data['pay_money'] = $request['money'];
            $data['give_money'] = $request['money'] * $appsetting['song_money'];
            $member_info = M ('Member')->where (array ('id' => $m_id))->find ();
            $data['m_id'] = $m_id;
            $data['orderid'] = 'CZ' . date ('YmdHis') . rand (1000 , 9999);
            $data['title'] = "余额充值";
            $data['o_type'] = '3';
            $data['create_time'] = time ();
            $data['status'] = 2;
//            $data['mobile'] = $member_info['account'];
            $data['detail'] = 1;
            $res = M ('Order')->data ($data)->add ();
        }
        if ( $res ) {
            $this->apiResponse ('1' , '充值成功' , array ('orderid' => $data['orderid']));
        } else {
            $this->apiResponse ('0' , '充值失败');
        }
    }
//        $member = D ('Member')->where (array ('id' => $m_id))->field ('balance')->find ();
//        if ( $request['id'] ) {
//            $num = D ('Sum')
//                ->where (array ('id' => $request['id']))
//                ->field ('money,pre_money')
//                ->find ();
//            D ('Sum')
//                ->where (array ('id' => $request['id']))
//                ->Save (array ('pre_money' => $num['money'] * $appsetting['song_money']));
////            D ('Member')
////                ->where (array ('id' => $m_id))
////                ->field ('balance')
////                ->Save (array ('balance' => $member['balance'] + $num['money'] + $num['pre_money']));
//            $data['pay_money'] = $num['money'];
//            $data['give_money'] = $num['money'] * $appsetting['song_money'];
//        }
//            D ('Member')
//                ->where (array ('id' => $m_id))
//                ->field ('balance')
//                ->Save (array ('balance' => $member['balance'] + $request['money']));
//        $data['pay_time'] = time ();
//        $data['pay_type'] = $request['pay_type'];

    /**
     *账户明细
     **/
    public function detail ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('order_type' , 'string' , '请选择查看的状态');//0全部 1收入 2支出
        $this->checkParam ($rule);
        if ( $request['order_type'] == 1 ) {
            $where['db_order.detail'] = 1;
        }
        if ( $request['order_type'] == 2 ) {
            $where['db_order.detail'] = 2;
        }
        $order = D ('Order')
            ->where (array ('db_order.m_id' => $m_id))
            ->where (array ('pay_type' => 3))
            ->where (array ('db_order.status' => array ('neq' , 9)))
            ->where ($where)
            ->field ('title,pay_money,pay_time')
            ->select ();
        $this->apiResponse ('1' , '请求成功' , $order);
    }
}
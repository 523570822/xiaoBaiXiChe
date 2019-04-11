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
     *余额充值下单
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
            $data['m_id'] = $m_id;
            $data['orderid'] = 'CZ' . date ('YmdHis') . rand (1000 , 9999);
            $data['title'] = "余额充值";
            $data['o_type'] = '3';
            $data['create_time'] = time ();
            $data['detail'] = 1;
            $res = M ('Order')->add ($data);
        }
        if ( $res ) {
            $this->apiResponse ('1' , '下单成功' , array ('orderid' => $data['orderid']));
        } else {
            $this->apiResponse ('0' , '下单失败');
        }
    }

    /**
     *账户开支明细
     **/
    public function detail ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('order_type' , 'string' , '请选择查看的状态');//0全部 1收入 2支出
        $this->checkParam ($rule);
        if ( $request['order_type'] == 0 ) {
        $where['detail'] = array ('neq' , 0);
        }
        if ( $request['order_type'] == 1 ) {
            $where['detail'] = 1;
        }
        if ( $request['order_type'] == 2 ) {
            $where['detail'] = 2;
        }
        $order = D ('Order')
            ->where (array ('m_id' => $m_id,'status'=>2))
            ->where (array ('detail' => array ('neq' , 9)))
            ->where ($where)
            ->field ('title,pay_money,pay_time,detail')
            ->page($request['page'], '10')
            ->order('pay_time DESC')
            ->select ();
        if (!$order) {
            $message = $request['page'] == 1 ? '暂无消息' : '无更多消息';
            $this->apiResponse('1', $message);
        }
        $this->apiResponse ('1' , '请求成功' , $order);
    }
}
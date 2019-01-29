<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 10:02
 */

namespace Api\Controller;
/**
 * 订单模块
 * Class MsgController
 * @package Api\Controller
 */
class OrderController extends BaseController
{
    /**
     * 初始化方法
     */
    public function _initialize ()
    {
        parent::_initialize ();
    }

    /**
     *
     *
     *
     **/
    public function checkMsgs ($mc_id_request , $mc_id_db , $status , $type)
    {
        if ( $mc_id_request != $mc_id_db ) $this->apiResponse ('0' , '找不到该机器');
        $msg = '';
        switch ($status) {
            case "2":
                $msg = '机器故障';
                break;
            case "3":
                $msg = '机器报警';
                break;
            case "4":
                $msg = '机器不在线';
                break;
        }
        if ( !empty($msg) ) $this->apiResponse (0 , $msg);
        switch ($type) {
            case 2:
                $msg = '机器正在使用中';
                break;
            case 3:
                $msg = '机器已经被预订';
                break;
            case 4:
                $msg = '机器暂停使用';
                break;
        }
        if ( !empty($msg) ) $this->apiResponse (0 , $msg);
    }

    /**
     * 下订单
     * o_type 订单类型//1洗车订单 2小鲸卡购买
     * w_type 洗车类型//1普通洗车订单 2预约洗车订单
     * 机器运作状态//1正常 2故障 3报警 4不在线 9删除
     * 机器使用状态//1空闲中 2使用中 3预订中 4暂停中
     **/
    public function placingOrder ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('o_type' , 'string' , '请输入订单类型');
        $this->checkParam ($rule);
        if ( $request['o_type'] == 1 ) {
            $param['where']['status'] = 1;
            $param['where']['o_type'] = 1;
            $check_order = D ('Order')->where (array ('m_id' => $m_id , 'w_type' => 1 , 'o_type' => 1))->queryHave ($param['where']);
            $pay = D ('Order')->field ('orderid,m_id,id')->queryRow ($param['where']);
            $have = D ('Msg')->where (array ('m_id' => $pay['m_id'] , 'o_id' => $pay['id']))->find ();
            if ( $check_order == true ) {
                if ( !$have ) {
                    $param['type'] = 2;$param['o_id'] = $pay['id'];
                    $param['m_id'] = $pay['m_id'];$param['create_time'] = time ();
                    $param['send_type'] = 1;$param['msg_title'] = '您收到一条订单消息！';
                    $param['msg_content'] = '您有一个订单尚未支付，暂无法洗车';D ('Msg')->add ($param);
                }
                $this->apiResponse ('0' , '您有未支付订单' , $pay);
            } else {
                $rule = array ('w_type' , 'string' , '请输入洗车类型');
                $this->checkParam ($rule);
                if ( $request['w_type'] == 1 ) {
                    $rule = array ('mc_id' , 'string' , '请输入洗车机编号');
                    $this->checkParam ($rule);
                    $check_is=M ('Order')->where (array ('mc_id'=>$request['mc_id'],'m_id'=>$m_id,'w_type'=>2))->find ();
                    if ($check_is){
                       $this->send_post ('device_manage' , $request['mc_id'] , '1');
                    }
                    $car_washer_info = M ('CarWasher')->where (array ('mc_id' => $request['mc_id']))->find ();
                    $this->checkMsgs ($car_washer_info['m_id'],$request['mc_id'] , $car_washer_info['mc_id'] , $car_washer_info['status'] , $car_washer_info['type']);
                    $data['m_id'] = $m_id;
                    $data['orderid'] = 'XC' . date ('YmdHis') . rand (1000 , 9999);
                    $data['title'] = "扫码洗车";
                    $data['o_type'] = '1';
                    $data['w_type'] = '1';
                    $data['create_time'] = time ();
                    $data['mc_id'] = $car_washer_info['mc_id'];
                    $data['c_id'] = $car_washer_info['id'];
                    $res = M ('Order')->data ($data)->add ();
                    $type['type'] = '2';
                    $XG['mc_id'] = $request['mc_id'];
                    $yes = M ('CarWasher')->where ($XG)->save ($type);
//                    $jqcx = $this->send_post ('runtime_query' , $request['mc_id']);
//                    $this->ajaxReturn ($jqcx);
//                    $jqgg = $this->send_post ('device_manage' , $request['mc_id'] , '1');
//                    $this->ajaxReturn ($jqgg);
                    if ( $res && $yes ) {
                        $this->apiResponse ('1' , '下单成功' , array ('orderid' => $data['orderid']));
                    } else {
                        $this->apiResponse ('0' , '下单失败');
                    }
                } elseif ( $request['w_type'] == 2 ) {
                    $rule = array ('mc_id' , 'string' , '请输入洗车机编号');
                    $this->checkParam ($rule);
                    $car_washer_info = M ('CarWasher')->where (array ('mc_id' => $request['mc_id']))->find ();
                    $this->checkMsgs ($request['mc_id'] , $car_washer_info['mc_id'] , $car_washer_info['status'] , $car_washer_info['type']);
                    $data['m_id'] = $m_id;
                    $data['orderid'] = 'YC' . date ('YmdHis') . rand (1000 , 9999);
                    $data['title'] = "预约洗车";
                    $data['o_type'] = '1';
                    $data['w_type'] = '2';
                    $data['create_time'] = time ();
                    $data['subs_time'] = time () + (15 * 60);
                    $data['mc_id'] = $car_washer_info['mc_id'];
                    $data['c_id'] = $car_washer_info['id'];
                    $res = M ('Order')->data ($data)->add ();
                    $type['type'] = '3';
                    $XG['mc_id'] = $request['mc_id'];
                    $yes = M ('CarWasher')->where ($XG)->save ($type);
//                    $this->send_post ('runtime_query' , $request['mc_id'] , '');
                    $this->send_post ('device_manage' , $request['mc_id'] , '2');
                    if ( $res && $yes ) {
                        $this->apiResponse ('1' , '预约成功' , array ('orderid' => $data['orderid']));
                    } else {
                        $this->apiResponse ('0' , '预约失败');
                    }
                }
            }
        } elseif ( $request['o_type'] == 2 ) {
            $rule = array ('id' , 'string' , '请输入小鲸卡ID');
            $this->checkParam ($rule);
            $request = $_REQUEST;
            $card = M ('LittlewhaleCard')->where (array ('id' => $request['id']))->find ();
            $data['pay_money'] = $card['card_price'];
            $data['m_id'] = $m_id;
            $data['allowance'] = $card['rebate'];
            $data['card_id'] = $card['id'];
            $data['orderid'] = 'MK' . date ('YmdHis') . rand (1000 , 9999);
            $data['title'] = "小鲸卡购买";
            $data['o_type'] = '2';
            $data['create_time'] = time ();
            $res = M ('Order')->data ($data)->add ();
            if ( $res ) {
                $this->apiResponse ('1' , '购买成功' , array ('orderid' => $data['orderid']));
            } else {
                $this->apiResponse ('0' , '购买失败');
            }
        }
    }

    /**
     * 我的订单
     */
    public function myOrder ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array (
            array ('order_type' , 'string' , '请选择查看的订单状态') ,//0全部 1待支付 2已完成
            array ('page' , 'string' , '请输入参数:page') ,
        );
        $this->checkParam ($rule);
        $where['db_order.o_type'] = 1;
        $where['db_order.status'] = array ('neq' , 9);
        if ( $request['order_type'] == 1 ) {
            $where['db_order.status'] = 1;
        }
        if ( $request['order_type'] == 2 ) {
            $where['db_order.status'] = 2;
        }
        $where['db_order.m_id'] = $m_id;
        $list_info = D ('Order')
            ->where ($where)
            ->join ("LEFT JOIN db_car_washer ON db_order.c_id = db_car_washer.id")
            ->field ('db_order.id,db_order.orderid,db_order.status,db_order.money,db_order.pay_money,db_order.is_no,db_car_washer.mc_id,db_car_washer.p_id')
            ->page ($request['page'] , '10')
            ->select ();
//        var_dump ($list_info);die;
        foreach ( $list_info as $k => $v ) {
            $m = $list_info[$k]['p_id'];
            $shop = D ('Washshop')->where (array ('id' => $m))->field ('shop_name')->find ();
            $list_info[$k]['shop_name'] = $shop['shop_name'];
        }
        if ( !$list_info ) {
            $message = $request['page'] == 1 ? '暂无消息' : '无更多消息';
            $this->apiResponse ('1' , $message);
        }
        $this->apiResponse ('1' , '请求成功' , $list_info);
    }

    /**
     * 订单详情
     **/
    public function orderDetails ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('id' , 'string' , '请选择查看的订单详情');
        $this->checkParam ($rule);
        $order = D ('Order')->where (array ('id' => $request['id'] , 'o_type' => 1))->field ('id,status,money,pay_money,orderid,pay_type,c_id,is_dis,card_id,coup_id,update_time,create_time,pay_time,is_no')->find ();
        if ( !$order ) {
            $this->apiResponse ('0' , '请输入正确订单ID');
        }
        $car = D ('CarWasher')->where (array ('id' => $order['c_id']))->field ('*')->find ();
        $shop = D ('Washshop')->where (array ('id' => $car['p_id']))->field ('shop_name')->find ();
        $details = D ('Details')->where (array ('o_id' => $order['id']))->field ('*')->find ();
        $order['shop_name'] = $shop['shop_name'];
        $order['lon'] = $car['lon'];
        $order['lat'] = $car['lat'];
        $order['mc_id'] = $car['mc_id'];
        $order['address'] = $car['address'];
        $order['washing'] = $details['washing'] . '(min)';
        $order['foam'] = $details['foam'] . '(min)';
        $order['cleaner'] = $details['cleaner'] . '(min)';
        $appsetting = D ('Appsetting')->find ();
        $order['washing_money'] = $details['washing'] * $appsetting['washing_money'];
        $order['foam_money'] = $details['foam'] * $appsetting['foam_money'];
        $order['cleaner_money'] = $details['cleaner'] * $appsetting['cleaner_money'];
        if ( $order['is_dis'] == 0 ) {//无优惠
            $this->apiResponse ('1' , '查询成功' , $order);
        }
        if ( $order['is_dis'] == 1 ) {//有优惠
            if ( $order['card_id'] ) {//小鲸卡
                $m_id = $this->checkToken ();
                $this->errorTokenMsg ($m_id);
                $list = D ('CardUser')->where (array ('db_card_user.id' => $order['card_id'] , 'db_card_user.m_id' => $m_id , 'db_card_user.status' => array ('neq' , 9)))->join ("db_littlewhale_card ON db_card_user.l_id = db_littlewhale_card.id")->field ('db_littlewhale_card.name,db_littlewhale_card.rebate')->select ();
                foreach ( $list as $key => $value ) {
                    $card = $list[$key]['name'] . '会员' . ($list[$key]['rebate'] * 10) . '折';
                    $order['discount'] = $card;
                    $this->apiResponse ('1' , '查询成功' , $order);
                }
            }
            if ( $order['coup_id'] ) {//现金抵用券
                $m_id = $this->checkToken ();
                $this->errorTokenMsg ($m_id);
                $list = D ('CouponBind')->where (array ('db_coupon_bind.id' => $order['coup_id'] , 'db_coupon_bind.m_id' => $m_id , 'is_bind' => 1))->join ("db_batch ON db_coupon_bind.b_id = db_batch.id")->field ('db_batch.title,db_batch.price')->select ();
                foreach ( $list as $key => $value ) {
                    $card = $list[$key]['title'] . $list[$key]['price'] . '元';
                    $order['discount'] = $card;
                    $this->apiResponse ('1' , '查询成功' , $order);
                }
            }
        }
    }

    /**
     * 取消订单
     **/
    function orderCancel ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('orderid' , 'string' , '订单编号不能为空');
        $this->checkParam ($rule);
        $where['status'] =  array ('neq' , 9);
        $where['orderid'] = $request['orderid'];
        $orderid = D ('Order')->where ($where)->find ();
        if ( $orderid ) {
            $datal['status'] = "9";
            $datal['update_time'] = time ();
            D ('Order')->where ($where)->save ($datal);
            $this->apiResponse (1 , '取消订单成功');
        } else {
            $this->apiResponse (0 , '订单不存在');
        }

    }

    /**
     * 计时器
     */
    public function timer ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $order = D ('Order')->where (array ('$m_id' => $m_id , 'orderid' => $request['orderid'],'o_type'=>1,'w_type'=>2))->find ();
        if ( $order['subs_time'] ) {
            $time1 = time();
            $time2 = $order['subs_time'];
            $sub = ($time2-$time1);
            $order['is_time']= $order['subs_time'] < time () ?1:0;//1超时 0未超时
           if ($order['is_time']==1){
               $pmae['is_no']=1;
               D ('Order')->where (array ('$m_id' => $m_id , 'orderid' => $request['orderid'],'o_type'=>1,'w_type'=>2))->save ($pmae);
           }
        }
        $this->apiResponse (1,'查询成功',array ('is_time'=>$order['is_time'],'end_time'=>$sub));
    }
}
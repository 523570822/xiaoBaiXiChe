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
     *洗车机列表
     **/
    public function carwashList ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('id' , 'string' , '请选择洗车门店');
        $this->checkParam ($rule);
        $car_washer = M ('CarWasher')->where (array ('p_id' => $request['id']))->select ();
        $this->apiResponse ('1' , '洗车机列表' , $car_washer);
    }

    /**
     * 下订单
     * o_type 订单类型//1洗车订单 2小鲸卡购买
     * w_type 洗车类型//1普通洗车订单 2预约洗车订单
     * 机器运作状态//1正常 2故障 3报警 4不在线 9删除
     * 机器使用状态//1空闲中 2使用中 3预订中 4暂停中
     * 暂时这样写
     */
    public function placingOrder ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('o_type' , 'string' , '请输入订单类型');
        $this->checkParam ($rule);
        if ( $request['o_type'] == '1' ) {
            $param['where']['status'] = 1;
            $param['where']['o_type'] = 1;
            $check_order = D ('Order')->where (array ('m_id' => $m_id , 'w_type' => 1 , 'o_type' => 1))->queryCount ($param['where']);
            $pay = D ('Order')->field ('orderid')->queryRow ($param['where']);
            if ( $check_order ) {
                $this->apiResponse ('0' , '您有' . $check_order . '个订单待支付,请支付后再进行下订单' , $pay);
            }
            $rule = array ('w_type' , 'string' , '请输入洗车类型');
            $this->checkParam ($rule);
            if ( $request['w_type'] == '1' ) {
                $rule = array ('mc_id' , 'string' , '请输入洗车机编号');
                $this->checkParam ($rule);
                $car_washer_info = M ('CarWasher')->where (array ('mc_id' => $request['mc_id']))->find ();
                if ( !$request['mc_id'] = $car_washer_info['mc_id'] ) {
                    $this->apiResponse ('0' , '找不到该机器');
                }
                if ( $car_washer_info['status'] == 2 ) {
                    $this->apiResponse ('0' , '机器故障');
                }
                if ( $car_washer_info['status'] == 3 ) {
                    $this->apiResponse ('0' , '机器报警');
                }
                if ( $car_washer_info['status'] == 4 ) {
                    $this->apiResponse ('0' , '机器不在线');
                }
                if ( $car_washer_info['type'] == 2 ) {
                    $this->apiResponse ('0' , '机器正在使用中');
                }
                if ( $car_washer_info['type'] == 3 ) {
                    $this->apiResponse ('0' , '机器已经被预订');
                }
                if ( $car_washer_info['type'] == 4 ) {
                    $this->apiResponse ('0' , '机器暂停使用');
                }
                $member_info = M ('Member')->where (array ('id' => $m_id))->find ();
                $data['m_id'] = $m_id;
                $data['w_id'] = $car_washer_info['p_id'];
                $data['orderid'] = 'XC' . date ('YmdHi') . rand (100 , 999);
                $data['title'] = "扫码洗车";
                $data['o_type'] = '1';
                $data['w_type'] = '1';
                $data['create_time'] = time ();
                $data['mc_id'] = $car_washer_info['mc_id'];
                $data['mobile'] = $member_info['account'];
                $res = M ('Order')->data ($data)->add ();
                $type['type'] = '2';
                $XG['mc_id'] = $request['mc_id'];
                $yes = M ('CarWasher')->where ($XG)->save ($type);
                if ( $res && $yes ) {
                    $this->apiResponse ('1' , '下单成功' , array ('orderid' => $data['orderid']));
                } else {
                    $this->apiResponse ('0' , '下单失败');
                }
            }
            if ( $request['w_type'] == 2 ) {
//                $rule = array (
//                    array ('' , 'string' , '预约洗车') ,
//                    array ('' , 'string' , '预约洗车') ,
//                );
//                $this->checkParam ($rule);
                $car_washer_info = M ('CarWasher')->where (array ('mc_id' => $request['mc_id']))->find ();
                if ( !$request['mc_id'] = $car_washer_info['mc_id'] ) {
                    $this->apiResponse ('0' , '找不到该机器');
                }
                if ( $car_washer_info['status'] == 2 ) {
                    $this->apiResponse ('0' , '机器故障');
                }
                if ( $car_washer_info['status'] == 3 ) {
                    $this->apiResponse ('0' , '机器报警');
                }
                if ( $car_washer_info['status'] == 4 ) {
                    $this->apiResponse ('0' , '机器不在线');
                }
                if ( $car_washer_info['type'] == 2 ) {
                    $this->apiResponse ('0' , '机器正在使用中');
                }
                if ( $car_washer_info['type'] == 3 ) {
                    $this->apiResponse ('0' , '机器已经被预订');
                }
                if ( $car_washer_info['type'] == 4 ) {
                    $this->apiResponse ('0' , '机器暂停使用');
                }
                $member_info = M ('Member')->where (array ('id' => $m_id))->find ();
                $data['m_id'] = $m_id;
                $data['w_id'] = $car_washer_info['p_id'];
                $data['orderid'] = 'YC' . date ('YmdHi') . rand (100 , 999);
                $data['title'] = "预约洗车";
                $data['o_type'] = '1';
                $data['w_type'] = '2';
                $data['create_time'] = time ();
                $data['mc_id'] = $car_washer_info['mc_id'];
                $data['mobile'] = $member_info['account'];
                $res = M ('Order')->data ($data)->add ();
                $type['type'] = '3';
                $XG['mc_id'] = $request['mc_id'];
                $yes = M ('CarWasher')->where ($XG)->save ($type);
                if ( $res && $yes ) {
                    $this->apiResponse ('1' , '预约成功' , array ('orderid' => $data['orderid']));
                } else {
                    $this->apiResponse ('0' , '预约失败');
                }
            }
        }
        if ( $request['o_type'] == 2 ) {
//            $rule = array (
//                array ('' , 'string' , '小鲸卡') ,
//                array ('' , 'string' , '小鲸卡') ,
//            );
//            $this->checkParam ($rule);
            $member_info = M ('Member')->where (array ('id' => $m_id))->find ();
            $data['m_id'] = $m_id;
            $data['w_id'] = $car_washer_info['p_id'];
            $data['orderid'] = 'MK' . date ('YmdHi') . rand (100 , 999);
            $data['title'] = "小鲸卡购买";
            $data['o_type'] = '2';
            $data['create_time'] = time ();
            $data['mc_id'] = $car_washer_info['mc_id'];
            $data['mobile'] = $member_info['account'];
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
        if ( $request['order_type'] == 0 ) {
            $where['db_order.status'] = array ('neq' , 9);
        }
        if ( $request['order_type'] == 1 ) {
            $where['db_order.status'] = 1;
        }
        if ( $request['order_type'] == 2 ) {
            $where['db_order.status'] = 2;
        }
        $list_info = D ('Order')
            ->where (array ('db_order.m_id' => $m_id))
            ->where ($where)
            ->join ("db_car_washer ON db_order.c_id = db_car_washer.id")
            ->field ('db_order.id,db_order.orderid,db_order.status,db_order.money,db_order.pay_money,db_car_washer.mc_id,db_car_washer.p_id')
            ->page ($request['page'] , '10')
            ->select ();
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
        $order = D ('Order')->where (array ('id' => $request['id'] , 'o_type' => 1))->field ('id,status,money,pay_money,orderid,pay_type,c_id,is_dis,card_id,coup_id,update_time,create_time,pay_time')->find ();
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
        $rule = array ('orderid' , 'string' , '参数不能为空'
//            array(),
//            array('type','string','参数不能为空'),
        );
        $this->checkParam ($rule);
        $where['orderid'] = $request['orderid'];
        D ('Order')->where ($where)->find ();
//        if($_REQUEST['type']==1){
        $datal['pay_type'] = "9";
        $where['orderid'] = $request['orderid'];
        //  $where['pay_type']=array("neq"=>"9");
        $orderid = D ('Order')->where ($where)->find ();
        if ( $orderid ) {
        } else {
            $this->apiResponse (0 , '订单不存在');
        }
        $datal['update_time'] = time ();
        D ('Order')->where ($where)->save ($datal);
        $this->apiResponse (1 , '取消订单成功');
//        }elseif($_REQUEST['type']==1){
//            $where['orderid']=$request['orderid'];
//            $where['status']=array("neq","9");
//            $orderid=D('Carwash')->where($where)->find();//var_dump($orderid);die;
//            if($orderid){}else{
//                $this->apiResponse(0, '订单不存在');
//            }
//            $datal['status']="9";
//            D('Order')->where($where)->save($datal);
//            $this->apiResponse(1, '取消订单成功');
//        }
    }
}
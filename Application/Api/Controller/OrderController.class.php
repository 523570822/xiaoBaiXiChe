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
class OrderController extends BaseController {
    /**
     * 初始化方法
     */
    public function _initialize () {
        parent::_initialize ();
    }

    /**
     *查询接收
     * @param $mc_id
     * @param $o_id
     * @param $m_id
     * @param $c_id
     **/
    public function cs ($mc_id , $o_id , $m_id , $c_id) {
        $o = $this->send_post ('runtime_query' , $mc_id , '');
        foreach ( $o['devices'] as $k => $v ) {
            $one['status'] = $v['queryitem']['service_status'];
            $one['water_L'] = $v['queryitem']['clean_water_usage'];
            $one['foam_L'] = $v['queryitem']['foam_usage'];
            $one['water_S'] = $v['queryitem']['clean_water_duration'];
            $one['foam_S'] = $v['queryitem']['foam_duration'];
            $one['vacuum_S'] = $v['queryitem']['vacuum_info']['accumulated_usage'];
            $one['water_status'] = $v['queryitem']['pump1_status'];
            $one['foam_status'] = $v['queryitem']['pump2_status'];
            $one['come_water_status'] = $v['queryitem']['valve1_status'];
            $one['vacuum_status'] = $v['queryitem']['vacuum_info']['status'];
            $one['water_level_status'] = $v['queryitem']['level1_status'];
            $one['foam_level_status'] = $v['queryitem']['level3_status'];
            $one['lon'] = $v['queryitem']['location']['longitude'];
            $one['lat'] = $v['queryitem']['location']['latitude'];
        }
        $param['washing_start_time'] = $one['water_S'];
        $param['foam_start_time'] = $one['foam_S'];
        $param['cleaner_start_time'] = $one['vacuum_S'];
        $param['m_id'] = $m_id;
        $param['o_id'] = $o_id;
        $param['c_id'] = $c_id;
        if ( $one['status'] = 5 ) {
            $param['tips'] = 'start';
            $param['status'] = '1';
        } elseif ( $one['status'] = 4 ) {
            $param['tips'] = 'end';
            $param['status'] = '0';
        }
        D ('Details')->add ($param);
    }

    /**
     *二维码编号转换洗车机编号封装
     * @param $mc_code //二维码编号
     **/
    public function check_mc_code ($mc_code) {
        if ( $mc_code ) {
            $is = M ('CarWasher')->where (array ('mc_code' => $mc_code))->field ('mc_id')->find ();
            $request['mc_id'] = $is['mc_id'];
            return $request['mc_id'];
        } else {
            $this->apiResponse ('0' , '请扫描正确二维码');
        }
    }

    /**
     *消息提示封装
     * @param $mc_id_request
     * @param $mc_id_db
     * @param $status
     * @param $type
     * @param $where
     * @param $m_id
     * @param $w_type
     **/
    public function checkMsgs ($mc_id_request , $mc_id_db , $status , $type , $where , $m_id , $w_type) {
        if ( $mc_id_request != $mc_id_db ) $this->apiResponse ('0' , '找不到该机器');
        $check_use = M ('CarWasher')->where (array ('mc_code' => $where , 'm_id' => $m_id))->find ();
        $check_is_use = M ('Order')->where (array ('c_id' => $check_use['id'] , 'm_id' => $m_id , 'w_type' => $w_type , 'is_set' => 0 , 'status' => 1))->find ();
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
        if ( !empty($msg) ) $this->apiResponse ('0' , $msg);
        switch ($type) {
            case 2:
                $msg = '机器正在使用中';
                $data = $check_is_use['orderid'];
                break;
            case 3:
                $msg = '机器已经被预订';
                $data = $check_is_use['orderid'];
                break;
            case 4:
                $msg = '机器暂停使用';
                break;
        }
        if ( !empty($msg) ) $this->apiResponse ('0' , $msg , $data);
    }

    /**
     *条件封装
     * @param $m_id
     * @param $status
     * @param $where
     **/
    public function status ($m_id , $status , $where) {
        if ( $status == 1 ) {
            $param['type'] = 2;
            $param['o_id'] = $where['id'];
            $param['m_id'] = $where['m_id'];
            $param['create_time'] = time ();
            $param['send_type'] = 1;
            $param['msg_title'] = '您收到一条订单消息！';
            $param['msg_content'] = '您有订单未处理，暂无法洗车';
            return $param;
        } elseif ( $status == 2 ) {
            $data['m_id'] = $m_id;
            $data['mc_id'] = $where['mc_id'];
            $data['c_id'] = $where['id'];
            $data['orderid'] = 'XC' . date ('YmdHis') . rand (1000 , 9999);
            $data['title'] = "扫码洗车";
            $data['o_type'] = '1';
            $data['w_type'] = '1';
            $data['status'] = '1';
            $data['create_time'] = time ();
            return $data;
        } elseif ( $status == 3 ) {
            $data['m_id'] = $m_id;
            $data['orderid'] = 'YC' . date ('YmdHis') . rand (1000 , 9999);
            $data['title'] = "预约洗车";
            $data['o_type'] = '1';
            $data['w_type'] = '2';
            $data['create_time'] = time ();
            $data['subs_time'] = time () + (15 * 60);
            $data['mc_id'] = $where['mc_id'];
            $data['c_id'] = $where['id'];
            return $data;
        } elseif ( $status == 4 ) {
            $data['pay_money'] = $where['card_price'];
            $data['m_id'] = $m_id;
            $data['allowance'] = $where['rebate'];
            $data['card_id'] = $where['id'];
            $data['orderid'] = 'MK' . date ('YmdHis') . rand (1000 , 9999);
            $data['title'] = "小鲸卡购买";
            $data['o_type'] = '2';
            $data['create_time'] = time ();
            return $data;
        }
    }

    /**
     *订单检查封装
     * @param $m_id
     * @param $w_type
     * @param $type
     * @param $mc_code
     **/
    public function checkhave ($m_id , $w_type , $type , $mc_code) {
        $car_wash = D ('CarWasher')->where (array ('mc_code' => $mc_code , 'type' => $type , 'status' => 1))->find ();
        $check_order = D ('Order')->where (array ('m_id' => $m_id , 'w_type' => $w_type , 'o_type' => 1 , 'status' => 1 , 'is_set' => 0))->find ();
        $have = D ('Msg')->where (array ('m_id' => $check_order['m_id'] , 'o_id' => $check_order['id'] , 'status' => 1))->find ();
        //        var_dump ($m_id , $w_type , $type , $mc_code,$car_wash,$check_order,$have);die;
        if ( $check_order ) {
            if ( !$have ) {
                $param = $this->status ($m_id , 1 , $check_order);
                D ('Msg')->add ($param);
            }
        }
        if ( $car_wash ) {
            if ( $w_type == 1 ) {
                $this->apiResponse ('0' , '您有未支付订单' , $check_order);
            } elseif ( $w_type == 2 ) {
                if ( $check_order['subs_time'] < time () ) {
                    $appsetting = D ('Appsetting')->field ('overtime_money')->find ();
                    if ( !empty($check_order['pay_money']) ) {
                        $param['is_no'] = 1;
                        $param['pay_money'] = $appsetting['overtime_money'];
                        D ('Order')->where (array ('id' => $check_order['id']))->save ($param);
//                        var_dump ($car_wash['type']);
                        if($car_wash['type']=3){
                            $where['type'] = 1;
                            D ('CarWasher')->where (array ('mc_code' => $mc_code))->save ($where);
                            $this->apiResponse ('0' , '您有预约订单已超时' , $check_order);
                        }
                    }
                } else {
                    $this->apiResponse ('0' , '您有预约订单未处理' , $check_order);
                }
            }
        }
    }

    /**
     * 下订单
     * o_type 订单类型//1洗车订单 2小鲸卡购买
     * w_type 洗车类型//1普通洗车订单 2预约洗车订单
     * is_set 是否结算//0未结算 1已结算
     * is_no  是否违约//0未违约 1违约
     * detail 是否收入//1收入  2支出
     * invoice 是否开票//0未开票 1已开票
     * status 订单状态//1待支付 2已完成 9取消订单
     * pay_type 支付类型//1微信支付 2支付宝支付 3余额支付
     **/
    public function placingOrder () {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('o_type' , 'string' , '请输入订单类型');
        $this->checkParam ($rule);
        if ( $request['o_type'] == 1 ) {
            $rule = array (
                array ('w_type' , 'string' , '请输入洗车类型') ,
                array ('mc_code' , 'string' , '请输入洗车机编号') ,
            );
            $this->checkParam ($rule);
            $request['mc_id'] = $this->check_mc_code ($request['mc_code']);
            $all = M ('CarWasher')->where (array ('mc_id' => $request['mc_id']))->find ();
            if ( $request['w_type'] == 1 ) {
                $this->checkhave ($m_id , 1 , 2 , $request['mc_code']);
                $car_washer_info = M ('CarWasher')->where (array ('mc_id' => $request['mc_id'] , 'type' => 3))->find ();
                if ( $car_washer_info ) {
                    $check_is = M ('Order')->where (array ('c_id' => $car_washer_info['id'] , 'm_id' => $m_id , 'w_type' => 2))->find ();
                }
                if ( $check_is ) {
                    $this->send_post ('device_manage' , $request['mc_id'] , '1');
                    $check_details = D ('Details')->where (array ('m_id' => $check_is['m_id'] , 'c_id' => $check_is['c_id'] , 'o_id' => $check_is['id'] , 'status' => 1))->find ();
                    if ( !$check_details ) {
                        $this->cs ($request['mc_id'] , $check_is['id'] , $m_id , $check_is['c_id']);
                    }
                    $param['type'] = 2;
                    $param['update_time'] = time ();
                    $where['mc_id'] = $request['mc_id'];
                    $where['type'] = 3;
                    $save = M ('CarWasher')->where ($where)->save ($param);
                    if ( $save ) {
                        $this->apiResponse ('1' , '洗车机已开启');
                    }
                }

                $this->checkMsgs ($request['mc_id'] , $all['mc_id'] , $all['status'] , $all['type'] , $request['mc_code'] , $m_id , 1);
                $open = $this->send_post ('device_manage' , $request['mc_id'] , '1');
                if ( $open ) {
                    $data = $this->status ($m_id , 2 , $all);
                    $res = M ('Order')->data ($data)->add ();
                    $XG['mc_id'] = $request['mc_id'];
                    $type['type'] = '2';
                    $yes = M ('CarWasher')->where ($XG)->save ($type);
                    if ( $res && $yes ) {
                        $order = M ('Order')->where (array ('orderid' => $data['orderid']))->find ();
                        $this->cs ($request['mc_id'] , $order['id'] , $m_id , $order['c_id']);
                        $this->apiResponse ('1' , '洗车机已开启' , array ('orderid' => $data['orderid']));
                    } else {
                        $this->apiResponse ('0' , '下单失败');
                    }
                } else {
                    $this->apiResponse ('0' , '开启失败');
                }
            } elseif ( $request['w_type'] == 2 ) {
                $this->checkhave ($m_id , 2 , 3 , $request['mc_code']);
                //                var_dump ($request['mc_id'] , $all['mc_id'] , $all['status'] , $all['type'] , $request['mc_code'] , $m_id);die;
                $this->checkMsgs ($request['mc_id'] , $all['mc_id'] , $all['status'] , $all['type'] , $request['mc_code'] , $m_id , 2);
                $bespoke = $this->send_post ('device_manage' , $request['mc_id'] , '2');
                if ( $bespoke ) {
                    $data = $this->status ($m_id , 3 , $all);
                    $res = M ('Order')->data ($data)->add ();
                    $type['type'] = '3';
                    $XG['mc_id'] = $request['mc_id'];
                    $yes = M ('CarWasher')->where ($XG)->save ($type);
                    $this->send_post ('device_manage' , $request['mc_id'] , '2');
                    if ( $res && $yes ) {
                        $this->apiResponse ('1' , '预约成功' , array ('orderid' => $data['orderid']));
                    } else {
                        $this->apiResponse ('0' , '下单失败');
                    }
                } else {
                    $this->apiResponse ('0' , '预约失败');
                }
            }
        } elseif ( $request['o_type'] == 2 ) {
            $rule = array ('id' , 'string' , '请输入小鲸卡ID');
            $this->checkParam ($rule);
            $request = $_REQUEST;
            $card = M ('LittlewhaleCard')->where (array ('id' => $request['id']))->find ();
            $data = $this->status ($m_id , 4 , $card);
            $res = M ('Order')->data ($data)->add ();
            if ( $res ) {
                $this->apiResponse ('1' , '购买成功' , array ('orderid' => $data['orderid']));
            } else {
                $this->apiResponse ('0' , '购买失败');
            }
        }
    }

    /**
     * 订单列表
     */
    public function myOrder () {
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
            ->field ('db_order.id,db_order.orderid,db_order.status,db_order.money,db_order.pay_money,db_order.is_no,db_car_washer.mc_code as mc_id ,db_car_washer.p_id,db_car_washer.type')
            ->page ($request['page'] , '10')
            ->select ();
        foreach ( $list_info as $k => $v ) {
            $m = $list_info[$k]['p_id'];
            $shop = D ('Washshop')->where (array ('id' => $m))->field ('shop_name')->find ();
            $list_info[$k]['shop_name'] = $shop['shop_name'];
            $list_info[$k]['is_use'] = ($list_info[$k]['type'] == 2) ? 0 : 1;//0订单正在进行中
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
    public function orderDetails () {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('id' , 'string' , '请选择查看的订单详情');
        $this->checkParam ($rule);
        $order = D ('Order')->where (array ('id' => $request['id'] , 'o_type' => 1))->field ('id,status,money,pay_money,orderid,pay_type,c_id,is_dis,card_id,coup_id,update_time,create_time,pay_time,is_no,is_set')->find ();
        if ( !$order ) {
            $this->apiResponse ('0' , '请输入正确订单ID');
        }
        $car = D ('CarWasher')->where (array ('id' => $order['c_id']))->field ('*')->find ();
        $shop = D ('Washshop')->where (array ('id' => $car['p_id']))->field ('shop_name')->find ();
        $details = D ('Details')->where (array ('o_id' => $order['id']))->field ('*')->find ();
        $order['shop_name'] = $shop['shop_name'];
        $order['lon'] = $car['lon'];
        $order['lat'] = $car['lat'];
        $order['mc_id'] = $car['mc_code'];
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
    public function orderCancel () {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('orderid' , 'string' , '订单编号不能为空');
        $this->checkParam ($rule);
        $where['status'] = array ('neq' , 9);
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
    public function timer () {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $order = D ('Order')->where (array ('$m_id' => $m_id , 'orderid' => $request['orderid'] , 'o_type' => 1 , 'w_type' => 2))->find ();
        if ( $order['subs_time'] ) {
            $time1 = time ();
            $time2 = $order['subs_time'];
            $sub = ($time2 - $time1);
            $order['is_time'] = $order['subs_time'] < time () ? 1 : 0;//1超时 0未超时
            if ( $order['is_time'] == 1 ) {
                $pmae['is_no'] = 1;
                D ('Order')->where (array ('$m_id' => $m_id , 'orderid' => $request['orderid'] , 'o_type' => 1 , 'w_type' => 2))->save ($pmae);
            }
        }
        $this->apiResponse (1 , '查询成功' , array ('is_time' => $order['is_time'] , 'end_time' => $sub));
    }

}
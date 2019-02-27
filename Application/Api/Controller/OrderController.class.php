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
     *查询接收、储存
     * @param $mc_id
     * @param $o_id
     * @param $m_id
     * @param $c_id
     **/
    public function receive ($mc_id , $o_id , $m_id , $c_id , $model) {
        $o = $this->send_post ('runtime_query' , $mc_id , '');
        foreach ( $o['devices'] as $k => $v ) {
            $one['status'] = $v['queryitem']['service_status'];//服务状态
            $one['water_L'] = $v['queryitem']['clean_water_usage'];//清水使用状态
            $one['foam_L'] = $v['queryitem']['foam_usage'];//泡沫使用状态
            $one['water_S'] = $v['queryitem']['clean_water_duration'];//清水累计时间
            $one['foam_S'] = $v['queryitem']['foam_duration'];//泡沫累计时间
            $one['vacuum_S'] = $v['queryitem']['vacuum_info']['accumulated_usage'];//吸尘器使用累计时间
            $one['water_status'] = $v['queryitem']['pump1_status'];//清水泵状态
            $one['foam_status'] = $v['queryitem']['pump2_status'];//泡沫泵状态
            $one['vacuum_status'] = $v['queryitem']['vacuum_info']['status'];//吸尘器状态
            $one['come_water_status'] = $v['queryitem']['valve1_status'];//进水阀状态
            $one['water_level_status'] = $v['queryitem']['level1_status'];//清水水位状态
            $one['foam_level_status'] = $v['queryitem']['level3_status'];//泡沫液位状态
            $one['lon'] = $v['queryitem']['location']['longitude'];//经度
            $one['lat'] = $v['queryitem']['location']['latitude'];//纬度
        }
        if ( $model == '6' ) {
            $bespeak = $this->send_post ('device_manage' , $mc_id , '2');
            if ( !$bespeak ) {
                $this->apiResponse ('0' , '预约失败');
            }
        } elseif ( $model == '5' ) {
            $param['m_id'] = $m_id;
            $param['o_id'] = $o_id;
            $param['c_id'] = $c_id;
            $param['tips'] = 'start';
            $param['status'] = '1';
            $param['washing_start_time'] = $one['water_S'];
            $param['foam_start_time'] = $one['foam_S'];
            $param['cleaner_start_time'] = $one['vacuum_S'];
            $open = $this->send_post ('device_manage' , $mc_id , '1');
            $add = D ('Details')->add ($param);
            if ( !$add && !$open ) {
                $this->apiResponse ('0' , '开启失败');
            }
        } elseif ( $model == '4' ) {
            $date['tips'] = 'end';
            $date['status'] = '0';
            $date['washing_end_time'] = $one['water_S'];
            $date['foam_end_time'] = $one['foam_S'];
            $date['cleaner_end_time'] = $one['vacuum_S'];
            D ('Details')->where (array ('m_id' => $m_id , 'o_id' => $o_id , 'c_id' => $c_id))->save ($date);
            $find = D ('Details')->where (array ('m_id' => $m_id , 'o_id' => $o_id , 'c_id' => $c_id))->find ();
            $where['washing'] = $find['washing_end_time'] - $find['washing_start_time'];
            $where['foam'] = $find['foam_end_time'] - $find['foam_start_time'];
            $where['cleaner'] = $find['cleaner_end_time'] - $find['cleaner_start_time'];
            $save = D ('Details')->where (array ('id' => $find['id']))->save ($where);
            $close = $this->send_post ('device_manage' , $mc_id , '3');
            if ( !$save && !$close ) {
                $this->apiResponse ('0' , '关闭失败');
            }
        }
    }

    /**
     *二维码编号查询数据封装
     * @param $mc_code //二维码编号
     **/
    public function check_mc_code ($mc_code , $mode) {
        if ( $mc_code ) {
            $is = M ('CarWasher')->where (array ('mc_code' => $mc_code))->field ('*')->find ();
            $check['mc_id'] = $is['mc_id'];
            $check['id'] = $is['id'];
            if ( $mode == '0' ) {
                return $is;
            }
            if ( $mode == '1' ) {
                return $check['mc_id'];
            }
            if ( $mode == '2' ) {
                return $check['id'];
            }
            if ( $mode == '3' ) {
                return $check['type'];
            }
            if ( $mode == '4' ) {
                return $check['status'];
            }
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
    public function checkMsgs ($mc_id , $mc_code , $m_id , $w_type) {
        $check = M ('CarWasher')->where (array ('mc_code' => $mc_code))->find ();
        $status = $check['status'];
        $type = $check['type'];
        $mc_id_ = $check['mc_id'];
        $c_id = $check['id'];
        if ( empty($mc_id) || $mc_id_ != $mc_id ) $this->apiResponse ('0' , '找不到该机器');
        $use = M ('Order')->where (array ('c_id' => $c_id , 'm_id' => $m_id , 'w_type' => $w_type , 'is_set' => '0' , 'status' => '1'))->find ();
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
                if ( $m_id !== $use['m_id'] ) {
                    D ('CarWasher')->where (array ('id' => $c_id))->save (array ('type' => '1'));
                } else {
                    if ( !empty($use['orderid']) ) {
                        $msg = '机器正在使用中，请勿重复扫码';
                        $data = $use['orderid'];
                    } else {
                        $msg = '机器正在使用中，请勿重复扫码';
                        $data = 'The machine is in use. Please do not repeat code scanning！';
                    }
                }
                break;
            case 3:
                if ( !empty($use['orderid']) ) {
                    $msg = '机器已经被预订，请尝试其他机器';
                    $data = $use['orderid'];
                } else {
                    $msg = '机器已经被预订，请尝试其他机器';
                    $data = 'The machine has been reserved, please try another machine！';
                }
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
    public function status ($status , $m_id = '' , $mc_id = '' , $c_id = '' , $o_id = '' , $where = '') {
        if ( $status == '1' ) {//您收到一条订单消息
            $date['type'] = '2';
            $date['o_id'] = $o_id;
            $date['m_id'] = $m_id;
            $date['create_time'] = time ();
            $date['send_type'] = '1';
            $date['msg_title'] = '您收到一条订单消息！';
            $date['msg_content'] = '您有订单未处理，暂无法洗车';
            return $date;
        } elseif ( $status == '2' ) {//扫码洗车
            $data['m_id'] = $m_id;
            $data['mc_id'] = $mc_id;
            $data['c_id'] = $c_id;
            $data['orderid'] = 'XC' . date ('YmdHis') . rand (1000 , 9999);
            $data['title'] = "扫码洗车";
            $data['o_type'] = '1';
            $data['w_type'] = '1';
            $data['status'] = '1';
            $data['create_time'] = time ();
            return $data;
        } elseif ( $status == '3' ) {//预约洗车
            $data['m_id'] = $m_id;
            $data['mc_id'] = $mc_id;
            $data['c_id'] = $c_id;
            $data['orderid'] = 'YC' . date ('YmdHis') . rand (1000 , 9999);
            $data['title'] = "预约洗车";
            $data['o_type'] = '1';
            $data['w_type'] = '2';
            $data['status'] = '1';
            $data['create_time'] = time ();
            $data['subs_time'] = time () + (15 * 60);
            return $data;
        } elseif ( $status == '4' ) {//小鲸卡购买
            $data['m_id'] = $m_id;
            $data['pay_money'] = $where['card_price'];
            $data['allowance'] = $where['rebate'];
            $data['card_id'] = $where['id'];
            $data['orderid'] = 'MK' . date ('YmdHis') . rand (1000 , 9999);
            $data['title'] = "小鲸卡购买";
            $data['o_type'] = '2';
            $data['status'] = '1';
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
    public function checkhave ($m_id , $w_type , $mc_code) {
        $order = D ('Order')->where (array ('m_id' => $m_id , 'w_type' => $w_type , 'o_type' => '1' , 'status' => '1' , 'is_set' => '0'))->find ();
        $have = D ('Msg')->where (array ('m_id' => $m_id , 'o_id' => $order['id'] , 'status' => '1'))->find ();
        if ( $order ) {
            if ( !$have ) {
                $o_id = $order['id'];
                $param = $this->status ('1' , $m_id , '' , '' , $o_id);
                D ('Msg')->add ($param);
            }
            if ( $w_type == '1' ) {
                $this->apiResponse ('0' , '您有付订单待处理' , array ('ID' => $order['id'] , 'Orderid' => $order['orderid']));
            } elseif ( $w_type == '2' ) {
                if ( $order['subs_time'] < time () ) {
                    $appsetting = D ('Appsetting')->field ('overtime_money')->find ();
                    if ( !empty($order['pay_money']) ) {
                        D ('Order')->where (array ('id' => $order['id']))->save (array ('pay_money' => $appsetting['overtime_money'] , 'is_no' => '1'));
                        D ('CarWasher')->where (array ('mc_code' => $mc_code))->save (array ('type' => '1'));
                        $this->apiResponse ('0' , '您有预约订单已超时' , array ('ID' => $order['id'] , 'Orderid' => $order['orderid']));
                    }
                } else {
                    $this->apiResponse ('0' , '您有预约订单未处理' , array ('ID' => $order['id'] , 'Orderid' => $order['orderid']));
                }
            }
        }
    }

    /**
     *预订检查封装
     * @param $m_id
     * @param $mc_code
     **/
    public function checkisToken ($m_id , $mc_code) {
        $CarWasher = D ('CarWasher')->where (array ('mc_code' => $mc_code))->find ();
        if ( $CarWasher['type'] == '3' ) {
            $Order = D ('Order')->where (array ('c_id' => $CarWasher['id'] , 'o_type' => '1' , 'w_type' => '2' , 'status' => '1' , 'is_no' => '0' , 'is_set' => '0'))->find();
            if ( $Order['m_id'] == $m_id ) {
                $this->checkhave($m_id ,'2' , $mc_code);
                //变更机器 预订中->使用中
                $yes = M ('CarWasher')->where (array ('mc_code' => $mc_code))->save (array ('type' => '2'));
                $res = M ('Order')->where (array ('id'=>$Order['id']))->save (array ('create_time' => time ()));
                //控制机器
                $this->receive ($CarWasher['mc_id'] , $Order['id'] , $m_id , $CarWasher['id'] , '5');
                //返回数据
                if ( $res && $yes ) {
                    $this->apiResponse ('1' , '已开启洗车机' , array ('ID' => $Order['id'] , 'Orderid' => $Order['orderid']));
                } else {
                    $this->apiResponse ('0' , '开启失败,请重试' , 'The order failed, please try again');
                }
            }
        }
    }

    /**
     * （扫码/普通）洗车
     **/
    public function CarWashOrder () {
        //检查Token
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        //接收数据
        $request = I ('post.');
        $rules = array ('mc_code' , 'string' , '请输入洗车机编号');
        $this->checkParam ($rules);
        $this->checkisToken ($m_id,$request['mc_code']);
        $rule = array (
            array ('o_type' , 'string' , '请输入订单类型') ,
            array ('w_type' , 'string' , '请输入洗车类型') ,
            //            array ('mc_code' , 'string' , '请输入洗车机编号') ,
        );
        $this->checkParam ($rule);
        //重定义名称
        $o_type = $request['o_type'];
        $w_type = $request['w_type'];
        $mc_code = $request['mc_code'];
        //转换数据
        $mc_id = $this->check_mc_code ($mc_code , '1');
        $c_id = $this->check_mc_code ($mc_code , '2');
        //检查数据
        if ( $o_type !== '1' ) {
            $this->apiResponse ('0' , '订单类型错误');
        } elseif ( $w_type !== '1' ) {
            $this->apiResponse ('0' , '洗车类型错误');
        }

        //检查订单
        $this->checkhave ($m_id , $w_type , $mc_code);
        //检查机器
        $this->checkMsgs ($mc_id , $mc_code , $m_id , $w_type);
        //添加订单
        $data = $this->status ('2' , $m_id , $mc_id , $c_id , '' , '');
        $res = M ('Order')->add ($data);
        //查询订单ID
        $find = M ('Order')->where (array ('orderid' => $data['orderid']))->find ();
        $o_id = $find['id'];
        //变更机器 空闲中->使用中
        $yes = M ('CarWasher')->where (array ('mc_id' => $mc_id))->save (array ('type' => '2'));
        //控制机器
        $this->receive ($mc_id , $o_id , $m_id , $c_id , '5');
        //返回数据
        if ( $res && $yes ) {
            $this->apiResponse ('1' , '下单成功,洗车机已开启' , array ('ID' => $o_id , 'Orderid' => $data['orderid']));
        } else {
            $this->apiResponse ('0' , '下单失败,请重试' , 'The order failed, please try again');
        }
    }

    /**
     * 预约洗车
     */
    public function BookCarWashOrder () {
        //检查Token
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        //接收数据
        $request = I ('post.');
        $rule = array (
            array ('o_type' , 'string' , '请输入订单类型') ,
            array ('w_type' , 'string' , '请输入洗车类型') ,
            array ('mc_code' , 'string' , '请输入洗车机编号') ,
        );
        $this->checkParam ($rule);
        //重定义名称
        $o_type = $request['o_type'];
        $w_type = $request['w_type'];
        $mc_code = $request['mc_code'];
        //转换数据
        $mc_id = $this->check_mc_code ($mc_code , '1');
        $c_id = $this->check_mc_code ($mc_code , '2');
        //检查数据
        if ( $o_type !== '1' ) {
            $this->apiResponse ('0' , '订单类型错误');
        } elseif ( $w_type !== '2' ) {
            $this->apiResponse ('0' , '洗车类型错误');
        }
        //检查订单
        $this->checkhave ($m_id , $w_type , $mc_code);
        //检查机器
        $this->checkMsgs ($mc_id , $mc_code , $m_id , $w_type);
        //添加订单
        $data = $this->status ('3' , $m_id , $mc_id , $c_id , '' , '');
        $res = M ('Order')->add ($data);
        //查询订单ID
        $find = M ('Order')->where (array ('orderid' => $data['orderid']))->find ();
        $o_id = $find['id'];
        //变更机器 空闲中->预约中
        $yes = M ('CarWasher')->where (array ('mc_id' => $mc_id))->save (array ('type' => '3'));
        //控制机器
        $this->receive ($mc_id , $o_id , $m_id , $c_id , '6');
        //返回数据
        if ( $res && $yes ) {
            $this->apiResponse ('1' , '下单成功,洗车机已预订' , array ('ID' => $o_id , 'Orderid' => $data['orderid']));
        } else {
            $this->apiResponse ('0' , '下单失败,请重试' , 'The order failed, please try again');
        }
    }

    /**
     * 小鲸卡购买
     **/
    public function MembershipCardOrder () {
        //检查Token
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        //接收数据
        $request = I ('post.');
        $rule = array (
            array ('o_type' , 'string' , '请输入订单类型') ,
            array ('id' , 'string' , '请选择小鲸卡') ,
        );
        $this->checkParam ($rule);
        //重定义名称
        $o_type = $request['o_type'];
        $id = $request['id'];
        //检查数据
        if ( $o_type !== '2' ) {
            $this->apiResponse ('0' , '订单类型错误');
        }elseif (empty($id)){
            $this->apiResponse ('0' , '小鲸卡为空');
        }
        //查询卡表
        $card = M ('LittlewhaleCard')->where (array ('id' => $id))->find ();
        //添加订单
        $data = $this->status ('4' , $m_id , '' , '' , '' , $card);
        $res = M ('Order')->add ($data);
        //查询订单ID
        $find = M ('Order')->where (array ('orderid' => $data['orderid']))->find ();
        $o_id = $find['id'];
        if ( $res ) {
            $this->apiResponse ('1' , '下单成功' , array ('ID' => $o_id , 'Orderid' => $data['orderid']));
        } else {
            $this->apiResponse ('0' , '下单失败,请重试','The order failed, please try again');
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

    /**
     *结算
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/18 15:52
     */
    public function settlement(){
        $post = checkAppData('token,order_id,off_on','token-订单ID-开关');
//        $post['token'] = '2cd9559683f90bc9816dd83b024cf9bd';
//        $post['order_id'] = 59;
//        $post['off_on'] = 0;

        $where['token'] = $post['token'];
        $member = M('Member')->where($where)->find();
        $d_where = array(
            'o_id'=>$post['order_id'],
            'm_id'=>$member['id'],
            'status'=>0
        );
        $details = M('Details')->where($d_where)->find();
        $car = M('CarWasher')->where(array('id'=>$details['c_id']))->find();
        $send_post = $this->send_post('runtime_query',$car['mc_id']);

//        var_dump($send_post['devices'][0]);
        //判断机器使用状态
        if($send_post['devices'][0]['queryitem']['service_status'] < 13){     //当机器service_status =13的时候,洗车机开启
            if(round($send_post['devices'][0]['queryitem']['clean_water_duration']) != $details['washing_end_time']){
                $indication = 1;
            }elseif(round($send_post['devices'][0]['queryitem']['foam_duration']) != $details['foam_end_time']){
                $indication = 2;
            }elseif(round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']) != $details['cleaner_end_time']){
                $indication = 3;
            }else{
                $indication = 0;
            }


            //正式    洗车机使用前设备秒数读取
            if(($send_post['devices'][0]['queryitem']['service_status'] == 5) && ($send_post['devices'][0]['queryitem']['pump1_status'] == 2) && ($send_post['devices'][0]['queryitem']['pump2_statu'] == 2) && ($send_post['devices'][0]['queryitem']['vacuum_info']['status'] == 2)){
                $start_data['washing_start_time'] = $send_post['devices'][0]['queryitem']['clean_water_duration'];
                $start_data['foam_start_time'] = $send_post['devices'][0]['queryitem']['foam_duration'];
                $start_data['cleaner_start_time'] = $send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage'];
                $d_where['status'] = 0;
                $d_where['id'] = $details['id'];
                $start = M('Details')->where($d_where)->save($start_data);
            }
            //水枪时间
//        if(($send_post['devices'][0]['queryitem']['service_status'] == 5) && ($send_post['devices'][0]['queryitem']['pump1_status'] == 3) ){    //水枪使用时间
//            $w_end_data['washing_end_time'] = round($send_post['devices'][0]['queryitem']['clean_water_duration']);
//            $w_end_data['washing'] = $w_end_data['washing_end_time'] - $details['washing_start_time'] + $details['washing'];
//
//            $d_where['status'] = 0;
//            $d_where['id'] = $details['id'];
//
//            $w_start = M('Details')->where($d_where)->save($w_end_data);
//        }
            //泡沫枪时间
//        if (($send_post['devices'][0]['queryitem']['service_status'] == 5) && ($send_post['devices'][0]['queryitem']['pump2_statu'] == 3) ){    //泡沫枪使用时间
//            $f_end_data['foam_end_time'] = round($send_post['devices'][0]['queryitem']['foam_duration']);
//            $f_end_data['foam'] = $f_end_data['foam_end_time'] - $details['foam_start_time'] + $details['foam'];
//
//            $d_where['status'] = 0;
//            $d_where['id'] = $details['id'];
//
//            $f_start = M('Details')->where($d_where)->save($f_end_data);
//        }
            //吸尘器时间
//        if (($send_post['devices'][0]['queryitem']['service_status'] == 5) && ($send_post['devices'][0]['queryitem']['vacuum_info']['status'] == 3)){     //吸尘器使用时间
//            $c_end_data['cleaner_end_time'] = round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']);
//            $c_end_data['cleaner'] = $c_end_data['cleaner_end_time'] - $details['cleaner_start_time'] + $details['cleaner'];
//
//            $d_where['status'] = 0;
//            $d_where['id'] = $details['id'];
//            $c_start = M('Details')->where($d_where)->save($c_end_data);
//        }

            //测试
            if($send_post['devices'][0]['queryitem']['service_status'] == 8){

                $start_data['washing_start_time'] = round($send_post['devices'][0]['queryitem']['clean_water_duration']);
                $start_data['foam_start_time'] = round($send_post['devices'][0]['queryitem']['foam_duration']);
                $start_data['cleaner_start_time'] = round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']);
                $d_where['status'] = 0;
                $d_where['id'] = $details['id'];
                $start = M('Details')->where($d_where)->save($start_data);
            }
            //水枪使用时间
            if(($send_post['devices'][0]['queryitem']['service_status'] == 8) && ($send_post['devices'][0]['queryitem']['pump1_status'] == 0) ){

                $w_end_data['washing_end_time'] = round($send_post['devices'][0]['queryitem']['clean_water_duration']);
                $w_end_data['washing'] = $w_end_data['washing_end_time'] - $details['washing_start_time'] + $details['washing'];

                $d_where['status'] = 0;
                $d_where['id'] = $details['id'];

                $w_start = M('Details')->where($d_where)->save($w_end_data);
            }
            //泡沫枪使用时间
            if (($send_post['devices'][0]['queryitem']['service_status'] == 12) && ($send_post['devices'][0]['queryitem']['pump2_status'] == 4) ){
                $f_end_data['foam_end_time'] = round($send_post['devices'][0]['queryitem']['foam_duration']);
                $f_end_data['foam'] = $f_end_data['foam_end_time'] - $details['foam_start_time'] + $details['foam'];

                $d_where['status'] = 0;
                $d_where['id'] = $details['id'];

                $f_start = M('Details')->where($d_where)->save($f_end_data);
            }
            //吸尘器使用时间
            if (($send_post['devices'][0]['queryitem']['service_status'] == 12) && ($send_post['devices'][0]['queryitem']['vacuum_info']['status'] == 2)){
                $c_end_data['cleaner_end_time'] = round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']);
                $c_end_data['cleaner'] = $c_end_data['cleaner_end_time'] - $details['cleaner_start_time'] + $details['cleaner'];

                $d_where['status'] = 0;
                $d_where['id'] = $details['id'];
                $c_start = M('Details')->where($d_where)->save($c_end_data);
            }

            $price = M('Appsetting')->where(array('id'=>1))->find();
            $wash_fen = round($details['washing']/60,2);
//        if($wash_fen > 0 && $wash_fen<0.5){
//            $wash_fen = 0.50;
//        }
            $foam_fen = round($details['foam']/60,2);
//        if($foam_fen > 0 && $foam_fen<0.5){
//            $foam_fen = 0.50;
//        }
            $cleaner_fen = round($details['cleaner']/60,2);
//        if($cleaner_fen > 0 && $cleaner_fen<0.5){
//            $cleaner_fen = 0.50;
//        }

            //价格
            $wash_money =  round($details['washing'] * $price['washing_money'],2);

            $foam_money = round($details['foam'] * $price['foam_money'],2);

            $cleaner_money = round($details['cleaner'] * $price['cleaner_money'],2);
            $data_money = array(
                'indication' => $indication,    //1  代表水枪    2代表泡沫枪   3代表吸尘器
                'washing' =>floor($wash_fen),
                'foam'=>floor($foam_fen),
                'cleaner'=>floor($cleaner_fen),
                'all_money' =>$wash_money+$foam_money+$cleaner_money
            );

            if(!empty($data_money)){
                if($post['off_on'] == 0){
                    $this->apiResponse('1','查询成功',$data_money);
                }elseif($post['off_on'] == 1){
                    $send_post = $this->send_post('device_manage',$car['mc_id'],3);
                    $this->apiResponse('1','结算成功',$data_money);
                }
            }
        }else{
            $this->apiResponse('0','请先开启设备');
        }

    }

    /**
     *立即支付
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/21 13:19
     */
    public function  Pay(){
        $post = checkAppData('token,order_id,washing,foam,cleaner,method,methodID','token-订单ID,水枪清洗时间,泡沫清洗时间,吸尘器使用时间,优惠方式,优惠卡ID');
//        $post['token'] = '2cd9559683f90bc9816dd83b024cf9bd';
//        $post['order_id'] = 59;
//        $post['washing'] = 14;
//        $post['foam'] = 1;
//        $post['cleaner'] = 357;
//        $post['method'] = 2;     //1代表折扣卡    2代表抵用券   3无优惠方式
//        $post['methodID'] = 29;    //折扣卡ID
        $where['token'] = $post['token'];
        $member = M('Member')->where($where)->find();
        $d_where = array(
            'o_id'=>$post['order_id'],
            'm_id'=>$member['id'],
            'status'=>0
        );
        $details = M('Details')->where($d_where)->find();
        $car = M('CarWasher')->where(array('id'=>$details['c_id']))->find();
        $send_post = $this->send_post('runtime_query',$car['mc_id']);
//        var_dump($send_post['devices'][0]['queryitem']);exit;
        if($send_post['devices'][0]['queryitem']['service_status'] == 12){
            $price = M('Appsetting')->where(array('id'=>1))->find();
//        $car = M('CarWasher')->where(array('id'=>$details['c_id']))->find();
//        $send_post = $this->send_post('device_manage',$car['mc_id'],3);
            $wash_money =  round($details['washing'] * $price['washing_money'],2);    //水枪金额

            $foam_money = round($details['foam'] * $price['foam_money'],2); //泡沫枪金额

            $cleaner_money = round($details['cleaner'] * $price['cleaner_money'],2); //吸尘器金额

            $all_money = $wash_money + $foam_money + $cleaner_money;  //总金额

            if($post['method'] == 1){
                $card_list = M ('CardUser')->where (array ('db_card_user.id' => $post['methodID'], 'db_card_user.m_id' => $member['id'] , 'db_card_user.status' => array ('neq' , 9)))->join ("db_littlewhale_card ON db_card_user.l_id = db_littlewhale_card.id")->field ('db_littlewhale_card.name,db_littlewhale_card.rebate,db_card_user.id')->find ();
                $price = round($all_money * $card_list['rebate'],2);
                $method = $card_list['name'] . '会员' . ($card_list['rebate'] * 10) . '折';
            }elseif ($post['method'] == 2){
                $coupon_list = M ('CouponBind')->where (array ('db_coupon_bind.id' =>$post['methodID'],'db_coupon_bind.m_id' => $member['id'] , 'is_bind' => 1))->join ("db_batch ON db_coupon_bind.code_id = db_batch.id")->field ('db_batch.title,db_batch.price,db_coupon_bind.id')->find ();
                $price = round($all_money - $coupon_list['price'],2);
                $method = $coupon_list['title'] . $coupon_list['price'] . '元';
            }elseif ($post['method'] == 3){
                $price = $all_money;
                $method = '暂无使用优惠方式';
            }


            $data = array(
                'time' =>array(
                    'wash' =>$post['washing'],
                    'foam' =>$post['foam'],
                    'cleaner' =>$post['cleaner'],
                ),
                'now_price' =>array(
                    'wash_price' =>$wash_money,
                    'foam_price' =>$foam_money,
                    'cleaner_price' =>$cleaner_money
                ),
                'all_price' =>$all_money,
                'method' => $method,
                'real_price' =>$price,
            );
            if(!empty($data)){
                $this->apiResponse('1','成功',$data);
            }
        }else{
            $this->apiResponse('0','该订单未结算');
        }

    }

    /**
     *优惠方式
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/22 18:21
     */
    public function proMethod(){
        $post = checkAppData('token','token');
//        $post['token'] = 'ba185544043e439f861943a7416102f3';
        $where['token'] = $post['token'];
        $member = M('Member')->where($where)->find();
        $card_list = M ('CardUser')->where (array ( 'db_card_user.m_id' => $member['id'] , 'db_card_user.status' => array ('neq' , 9)))->join ("db_littlewhale_card ON db_card_user.l_id = db_littlewhale_card.id")->field ('db_littlewhale_card.name,db_littlewhale_card.rebate,db_card_user.id')->select ();

        foreach ( $card_list as $key => $value ) {
            $c_card = $card_list[$key]['name'] . '会员' . ($card_list[$key]['rebate'] * 10) . '折';
            $card_lists[$key]['discount'] = $c_card;
            $card_lists[$key]['id'] = $value['id'];
        }
        $coupon_list = M ('CouponBind')->where (array ('db_coupon_bind.m_id' => $member['id'] , 'is_bind' => 1))->join ("db_batch ON db_coupon_bind.code_id = db_batch.id")->field ('db_batch.title,db_batch.price,db_coupon_bind.id')->select ();
        foreach ( $coupon_list as $k1 => $v1 ) {
            $o_card = $coupon_list[$k1]['title'] . $coupon_list[$k1]['price'] . '元';
            $coupon_lists[$k1]['discount'] = $o_card;
            $coupon_lists[$k1]['id'] = $v1['id'];
        }

        if(!empty($member)){
            if(empty($card_lists) && !empty($coupon_lists)){
                $data = array(
                    'card' => 0,
                    'coupon' => $coupon_lists,
                );
                $this->apiResponse('1','暂无会员卡',$data);
            }
            if(empty($coupon_lists) && !empty($card_lists)){
                $data = array(
                    'card' => $card_lists,
                    'coupon' => 0,
                );
                $this->apiResponse('1','暂无折扣卡',$data);
            }
            if(empty($coupon_lists) && empty($card_lists)){
                $data = array(
                    'card' => 0,
                    'coupon' => 0,
                );
                $this->apiResponse('1','没有优惠方式',$data);
            }
            $data = array(
                'card' => $card_lists,
                'coupon' => $coupon_lists,
            );
            $this->apiResponse('1','成功',$data);
        }

    }

}
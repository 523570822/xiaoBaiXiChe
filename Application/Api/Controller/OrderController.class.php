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
     * @param $model
     **/
    public function receive ($mc_id , $o_id , $m_id , $c_id , $model) {
        $o = $this->send_post ('runtime_query' , $mc_id );
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
            $o = $this->send_post ('runtime_query' , $mc_id);
            foreach ( $o['devices'] as $k => $v ) {
                $one['status'] = $v['queryitem']['service_status'];//服务状态
            }
            if ( !$bespeak && $one['status'] !='14') {
                $this->apiResponse ('0' , '预约失败');
            }
        } elseif ( $model == '5' ) {
            $param['m_id'] = $m_id;
            $param['o_id'] = $o_id;
            $param['c_id'] = $c_id;
            $param['tips'] = 'start';
//            $param['status'] = '1';
            $param['washing_start_time'] = $one['water_S'];
            $param['foam_start_time'] = $one['foam_S'];
            $param['cleaner_start_time'] = $one['vacuum_S'];
            $open = $this->send_post ('device_manage' , $mc_id , '1');
            $add = D ('Details')->add ($param);
            $o = $this->send_post ('runtime_query' , $mc_id );
            foreach ( $o['devices'] as $k => $v ) {
                $one['status'] = $v['queryitem']['service_status'];//服务状态
            }
            if ( !$add && !$open && $one['status'] !='13' ) {
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
     * @param $mode
     **/
    public function check_mc_code ($mc_code , $mode) {
        if ( $mc_code ) {
            $is = M ('CarWasher')->where (array ('mc_code' => $mc_code))->field ('*')->find ();
            $check['mc_id'] = $is['mc_id'];
            $check['id'] = $is['id'];
            $check['type'] = $is['type'];
            $check['status'] = $is['status'];
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
     * @param $mc_id
     * @param $mc_code
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
     * @param $status
     * @param $m_id
     * @param $mc_id
     * @param $c_id
     * @param $o_id
     * @param $where
     **/
    public function status ($status , $m_id = '' , $mc_id = '' , $c_id = '' , $o_id = '' , $card = '') {
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
            $data['start_time'] = time ();
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
            $data['pay_money'] = $card['card_price'];
            $data['allowance'] = $card['rebate'];
            $data['card_id'] = $card['id'];
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
                $this->apiResponse ('0' , '您有付订单待处理' , array ('id' => $order['id'] , 'orderid' => $order['orderid']));
            } elseif ( $w_type == '2' ) {
                if ( $order['subs_time'] < time () ) {
                    $appsetting = D ('Appsetting')->field ('overtime_money')->find ();
//                    var_dump ($order['id'],$order['pay_money']);die;
                    if ( $order['pay_money']=='0.00' ) {
                        D ('Order')->where (array ('id' => $order['id']))->save (array ('pay_money' => $appsetting['overtime_money'] , 'is_no' => '1'));
                        D ('CarWasher')->where (array ('mc_code' => $mc_code))->save (array ('type' => '1'));
                        $this->apiResponse ('0' , '您有预约订单已超时' , array ('id' => $order['id'] , 'orderid' => $order['orderid']));
                    }
                } else {
                    $this->apiResponse ('0' , '您有预约订单未处理' , array ('id' => $order['id'] , 'orderid' => $order['orderid']));
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
                    $this->apiResponse ('1' , '已开启洗车机' , array ('id' => $Order['id'] , 'orderid' => $Order['orderid']));
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
        //var_dump($request['mc_code']);die;
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
        $data = $this->status ('2' , $m_id , $mc_id , $c_id );
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
            //语音播放
            $voice = M('Voice')->where(array('voice_type'=>1,'status'=>1))->find();
            $this->send_post('device_manage',$mc_id,5,1,$voice['content']);
            $this->apiResponse ('1' , '下单成功,洗车机已开启' , array ('id' => $o_id , 'orderid' => $data['orderid']));
        } else {
            $car = M('CarWasher')->where(array('mc_id'=>$mc_id))->find();
            if($car['status'] == 2 || $car['status'] == 3  || $car['status'] == 4){    //机器停用中扫码使用播放语音
                $voice = M('Voice')->where(array('voice_type'=>3,'status'=>1))->find();
                $this->send_post('device_manage',$mc_id,5,1,$voice['content']);
            }
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
        $data = $this->status ('3' , $m_id , $mc_id , $c_id );
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
            $this->apiResponse ('1' , '下单成功,洗车机已预订' , array ('id' => $o_id , 'orderid' => $data['orderid']));
        } else {
            $this->apiResponse ('0' , '下单失败,请重试' , 'The order failed, please try again');
        }
    }

    /**/
//    public function b(){
//        $voice = M('Voice')->where(array('voice_type'=>1,'status'=>1))->find();
//        var_dump($voice);exit;
//    }

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
            $this->apiResponse ('1' , '下单成功' , array ('id' => $o_id , 'orderid' => $data['orderid']));
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
            ->field ('db_order.id,db_order.orderid,db_order.status,db_order.w_type,db_order.money,db_order.pay_money,db_order.is_no,is_set,db_car_washer.mc_code as mc_id ,db_car_washer.p_id,db_car_washer.type')
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
        $order = D ('Order')->where (array ('id' => $request['id'] , 'o_type' => 1))->find ();
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
        $order['washing_money'] = $details['washing'] * $car['washing_money'];
        $order['foam_money'] = $details['foam'] * $car['foam_money'];
        $order['cleaner_money'] = $details['cleaner'] * $car['cleaner_money'];
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
        $order = D ('Order')->where (array ('m_id' => $m_id , 'orderid' => $request['orderid'] , 'o_type' => 1 , 'w_type' => 2))->find ();
        if ( $order['subs_time'] ) {
            $time1 = time ();
            $time2 = $order['subs_time'];
            $sub = ($time2 - $time1);
            $order['is_time'] = $order['subs_time'] < time () ? 1 : 0;//1超时 0未超时
            if ( $order['is_time'] == 1 ) {
                $pmae['is_no'] = 1;
                $pmae['button'] = 1;
                D ('Order')->where (array ('m_id' => $m_id , 'orderid' => $request['orderid'] , 'o_type' => 1 , 'w_type' => 2))->save ($pmae);
            }
        }
        $this->apiResponse (1 , '查询成功' , array ('is_time' => $order['is_time'] , 'end_time' => $sub));
    }

    /**
     *定时超时查询
     */
    public function overtime(){
        $order=D ('Order')->where (array ('status'=>1,'is_no'=>0,'is_set'=>0,'button'=>0,'o_type' => 1 , 'w_type' => 2))->select();
        foreach ($order as $k=>$v){
            $notime=time();
            if($notime > $v['subs_time']){
                $pmae['is_no'] = 1;
                $pmae['button'] = 1;
                D ('Order')->where (array ('id'=>$v['id']))->save ($pmae);
            }
        }
    }


    /**
     *结算
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/18 15:52
     */
    public function settlement(){
        $post = checkAppData('token,orderid,off_on','token-订单ID-开关');
//        $post['token'] = '7497b54142301841be3d55d1ed5ae975';
//        $post['orderid'] = 'XC201903071431598116';
//        $post['off_on'] = 1;

        $where['token'] = $post['token'];
        $member = M('Member')->where($where)->find();
        $o_where = array(
            'm_id' =>$member['id'],
            'orderid' =>$post['orderid'],
            //'button' =>0,   //还未结算
        );
        $order = M('Order')->where($o_where)->find();
        $d_where = array(
            'o_id'=>$order['id'],
            'm_id'=>$member['id'],
            'status'=> 0,     //0代表未完成   订单还没结束
        );
        $details = M('Details')->where($d_where)->find();
        $car = M('CarWasher')->where(array('id'=>$details['c_id']))->find();
        $send_post = $this->send_post('runtime_query',$car['mc_id']);       //查询洗车机状态


        if(!empty($details)){
            if($details['status'] == 1){
                $this->apiResponse('0','此订单已结算,无法进行洗车操作');
            }
//        var_dump($send_post['devices'][0]);
            //判断机器使用状态
            if($send_post['devices'][0]['queryitem']['service_status'] == 13){     //当机器service_status =13的时候,洗车机开启
                $car_start_save = array(
                    'type' => 2,
                );
                $car_start = M('CarWasher')->where(array('id'=>$details['c_id']))->save($car_start_save);
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
                if(($send_post['devices'][0]['queryitem']['service_status'] == 13) && ($send_post['devices'][0]['queryitem']['pump1_status'] == 3) && ($send_post['devices'][0]['queryitem']['pump2_statu'] == 3) && ($send_post['devices'][0]['queryitem']['vacuum_info']['status'] == 2)){
                    $start_data['washing_start_time'] = $send_post['devices'][0]['queryitem']['clean_water_duration'];
                    $start_data['foam_start_time'] = $send_post['devices'][0]['queryitem']['foam_duration'];
                    $start_data['cleaner_start_time'] = $send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage'];
                    $d_where['status'] = 0;
                    $d_where['id'] = $details['id'];
                    $start = M('Details')->where($d_where)->save($start_data);
                }
                //测试        //
                if($send_post['devices'][0]['queryitem']['service_status'] == 8){  //将之前洗车机使用时间读取存到数据库

                    $start_data['washing_start_time'] = round($send_post['devices'][0]['queryitem']['clean_water_duration']);
                    $start_data['foam_start_time'] = round($send_post['devices'][0]['queryitem']['foam_duration']);
                    $start_data['cleaner_start_time'] = round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']);
                    $d_where['status'] = 0;
                    $d_where['id'] = $details['id'];
                    $start = M('Details')->where($d_where)->save($start_data);
                }
                $f_where['id'] = $details['id'];
                $f_where['status'] = 0;

                $f_details = M('Details')->where($f_where)->find();
//            var_dump($f_details);exit;
                //水枪使用时间
                if(($send_post['devices'][0]['queryitem']['service_status'] == 13) && ($send_post['devices'][0]['queryitem']['pump1_status'] == 3) ){
                    if($send_post['devices'][0]['queryitem']['clean_water_duration'] != $f_details['washing_end_time']){
                        $w_end_data['washing_end_time'] = round($send_post['devices'][0]['queryitem']['clean_water_duration']);
                        $w_end_data['washing'] = $w_end_data['washing_end_time'] - $details['washing_start_time'];
                        $d_where['status'] = 0;
                        $d_where['id'] = $details['id'];
                        $w_start = M('Details')->where($d_where)->save($w_end_data);
                    }
                }
                //泡沫枪使用时间
                if (($send_post['devices'][0]['queryitem']['service_status'] == 13) && ($send_post['devices'][0]['queryitem']['pump2_status'] == 3) ){
                    if($send_post['devices'][0]['queryitem']['foam_duration'] != $f_details['foam_end_time']){
                        $f_end_data['foam_end_time'] = round($send_post['devices'][0]['queryitem']['foam_duration']);
                        $f_end_data['foam'] = $f_end_data['foam_end_time'] - $details['foam_start_time'];
                        $d_where['status'] = 0;
                        $d_where['id'] = $details['id'];
                        $f_start = M('Details')->where($d_where)->save($f_end_data);
                    }
                }
                //吸尘器使用时间
                if (($send_post['devices'][0]['queryitem']['service_status'] == 13) && ($send_post['devices'][0]['queryitem']['vacuum_info']['status'] == 2)){
                    if($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage'] != $f_details['cleaner_end_time']){
                        $c_end_data['cleaner_end_time'] = round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']);
                        $c_end_data['cleaner'] = $c_end_data['cleaner_end_time'] - $details['cleaner_start_time'];
                        $d_where['status'] = 0;
                        $d_where['id'] = $details['id'];
                        $c_start = M('Details')->where($d_where)->save($c_end_data);
                    }
                }
                //各设备使用时间
                $wash_fen = round($details['washing']/60,2);
                $foam_fen = round($details['foam']/60,2);
                $cleaner_fen = round($details['cleaner']/60,2);
                //价格
                $wash_money =  round($details['washing'] * $car['washing_money'],2);
                $foam_money = round($details['foam'] * $car['foam_money'],2);
                $cleaner_money = round($details['cleaner'] * $car['cleaner_money'],2);
                $data_money = array(
                    'indication' => $indication,    //1  代表水枪    2代表泡沫枪   3代表吸尘器
                    'washing' =>floor($wash_fen),
                    'foam'=>floor($foam_fen),
                    'cleaner'=>floor($cleaner_fen),
                    'all_money' =>$wash_money+$foam_money+$cleaner_money,
                    'off_on' => $post['off_on'],
                );


                if(!empty($data_money)){
                    if($post['off_on'] == 0){
                        $this->apiResponse('1','查询成功',$data_money);
                    }elseif($post['off_on'] == 1){
                        $send_post = $this->send_post('device_manage',$car['mc_id'],3);   //结算
                        $d_save = array(
                            'status'  => 1,
                        );
                        $detailss = M('Details')->where($d_where)->save($d_save);
                        $o_save = array(
                            'button' => 1,
                        );
                        $o_order = M('Order')->where($o_where)->save($o_save);
                        //语音播报
                        $voice = M('Voice')->where(array('voice_type'=>2,'status'=>1))->find();
                        $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
                        $this->apiResponse('1','结算成功',$data_money);
                    }
                }
            }else if($send_post['devices'][0]['queryitem']['service_status'] == 12){   //12代表机器结算   结算跳转到立即支付页
                //各设备使用时间
                $wash_fen = round($details['washing']/60,2);
                $foam_fen = round($details['foam']/60,2);
                $cleaner_fen = round($details['cleaner']/60,2);
                //价格
                $wash_money =  round($details['washing'] * $car['washing_money'],2);
                $foam_money = round($details['foam'] * $car['foam_money'],2);
                $cleaner_money = round($details['cleaner'] * $car['cleaner_money'],2);
                $data_money = array(
                    'washing' =>floor($wash_fen),             //水枪使用时间
                    'foam'=>floor($foam_fen),                   //泡沫使用时间
                    'cleaner'=>floor($cleaner_fen),             //吸尘器使用时间
                    'all_money' =>$wash_money+$foam_money+$cleaner_money,       //总金额
                    'off_on' => $post['off_on'],
                );
                $post['off_on'] = 1;
                $d_save = array(
                    'status'  => 1,
                );
                $detailsss = M('Details')->where($d_where)->save($d_save);    //洗车数据详情表状态改为1,订单结束
                $o_save = array(
                    'button' => 1,
                );
                $o_order = M('Order')->where($o_where)->save($o_save);
                //语音播报
                $voice = M('Voice')->where(array('voice_type'=>2,'status'=>1))->find();
                $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
                $this->apiResponse('1','结算成功',$data_money);
            } else if($send_post['devices'][0]['queryitem']['service_status'] <= 8){
                $this->apiResponse('0','请先开启设备');
            }
        }else{
            $this->apiResponse('0','暂无查询此订单');
        }
    }

    /**
     *立即支付
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/21 13:19
     */
    public function  Pay(){
        $post = checkAppData('token,orderid,method,methodID','token-订单ID-优惠方式-优惠卡ID');
//        $post['token'] = 'cbbd2563ea8e79dab27a8115dd8bf08f';
//        $post['orderid'] = 'XC201903041455215144';

//        $post['method'] = 2;     //1代表折扣卡    2代表抵用券   3无优惠方式
//        $post['methodID'] = 29;    //折扣卡ID

        $where['token'] = $post['token'];
        $member = M('Member')->where($where)->find();
        $order_w = array(
            'orderid' =>$post['orderid'],
            'm_id' => $member['id'],
            'button' =>1,
        );
        //查询订单信息
        $order_f = M('Order')->where($order_w)->find();
        if(empty($order_f)){
            $this->apiResponse('0','暂无该订单信息或订单未结算');
        }
        $d_where = array(
            'o_id'=>$order_f['id'],
            'm_id'=>$member['id'],
            'status'=> 1
        );
        //查询洗车数据详情
        $details = M('Details')->where($d_where)->find();
        //查询洗车机列表
        $car = M('CarWasher')->where(array('id'=>$details['c_id']))->find();
        //请求物联网接口,获取数据
        $send_post = $this->send_post('runtime_query',$car['mc_id']);
        //判断洗车机状态
        $wash_fen = round($details['washing']/60,2);
        $foam_fen = round($details['foam']/60,2);
        $cleaner_fen = round($details['cleaner']/60,2);
//        var_dump($send_post['devices'][0]['queryitem']['service_status']);exit;
        if($send_post['devices'][0]['queryitem']['service_status'] == 12){    //正确填12
            $price = M('Appsetting')->where(array('id'=>1))->find();
//        $car = M('CarWasher')->where(array('id'=>$details['c_id']))->find();
//        $send_post = $this->send_post('device_manage',$car['mc_id'],3);

            $wash_money =  round($details['washing'] * $car['washing_money'],2);    //水枪金额
            $foam_money = round($details['foam'] * $car['foam_money'],2); //泡沫枪金额
            $cleaner_money = round($details['cleaner'] * $car['cleaner_money'],2); //吸尘器金额
            $all_money = $wash_money + $foam_money + $cleaner_money;  //总金额

            //判断是否有优惠方式
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
            //返回的数据
            $data = array(
                'time' =>array(
                    'wash' =>$wash_fen,
                    'foam' =>$foam_fen,
                    'cleaner' =>$cleaner_fen,
                ),
                'now_price' =>array(
                    'wash_price' =>$wash_money,
                    'foam_price' =>$foam_money,
                    'cleaner_price' =>$cleaner_money
                ),
                'all_price' =>$all_money,
                'method' => $method,
                'real_price' =>$price,
                'methods' =>$post['method'],
                'methods_id' =>$post['methodID'],
            );
            if(!empty($data)){
                //查找条件
                //echo M('Order')->_sql();
                $stop = $this->send_post('device_manage',$car['mc_id'],4);

                $sa_where = array(
                    'orderid' =>$post['orderid'],
                    'm_id' => $member['id'],
                    'button' =>1,
                );
                $sa_data = array(
                    'money' => $all_money,
                    'pay_money' => $price
                );
                $sa_order = M('Order')->where($sa_where)->save($sa_data);
                $this->apiResponse('1','查询成功',$data);
            }
        }else{
            $this->apiResponse('0','暂无信息');
        }

    }

    /**
     *优惠方式
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/22 18:21
     */
    public function proMethod(){
        $post = checkAppData('token','token');
//        $post['token'] = 'cce9c86617d3d51ce98a7e018978f3f8';
        $where['token'] = $post['token'];
        $member = M('Member')->where($where)->find();
        $card_list = M ('CardUser')->where (array ( 'db_card_user.m_id' => $member['id'] , 'db_card_user.status' => array ('neq' , 9)))->join ("db_littlewhale_card ON db_card_user.l_id = db_littlewhale_card.id")->field ('db_littlewhale_card.name,db_littlewhale_card.rebate,db_card_user.id')->select ();

        foreach ( $card_list as $key => $value ) {
            $c_card = $card_list[$key]['name'] . '会员' . ($card_list[$key]['rebate'] * 10) . '折';
            $card_lists[$key]['discount'] = $c_card;
            $card_lists[$key]['id'] = $value['id'];
        }

        $coupon_list = M ('CouponBind')->where (array ('db_coupon_bind.m_id' => $member['id'] , 'is_bind' => 1))->join ("db_batch ON db_coupon_bind.code_id = db_batch.id")->field ('db_batch.title,db_batch.price,db_coupon_bind.id,db_coupon_bind.end_time')->select ();
        foreach ( $coupon_list as $k1 => $v1 ) {
            $o_card = $coupon_list[$k1]['title'] . $coupon_list[$k1]['price'] . '元';
            $coupon_lists[$k1]['discount'] = $o_card;
            $coupon_lists[$k1]['end_time'] = $v1['end_time'];
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

    /**
     *设备按钮结算
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/03/02 11:14
     */
    public function Button(){
        $post = checkAppData('deviceid,event,clean_usage,clean_duration,foam_usage,foam_duration,vacuum_usage','洗车机编号-事件-清水用量-清水使用时间-泡沫用量-泡沫使用时间-吸尘器使用时间');
//        $post['deviceid'] = '510042001451373435363337';
//        $post['event'] = 1;
//        $post['clean_usage'] = 0;
//        $post['clean_duration'] =0 ;
//        $post['foam_usage'] =0 ;
//        $post['foam_duration'] =0 ;
//        $post['vacuum_usage'] = 0;
        $car = M('CarWasher')->where(array('mc_id'=>$post['deviceid']))->find();
        $order = M('Order')->where(array('c_id'=>$car['id'],'button'=>0))->find();
        if($post['event'] == 1){
            $send_post = $this->send_post('device_manage',$post['deviceid'],3);
            $d_save = array(
                'washing' => $post['clean_duration'],
                'foam' => $post['foam_duration'],
                'cleaner' => $post['vacuum_usage'],
                'status' => 1,
            );
            $detail = M('Details')->where(array('c_id'=>$car['id'],'o_id'=>$order['id'],'status'=>0))->save($d_save);
            $o_save = array(
                'button' => 1,
            );
            $orders = M('Order')->where(array('c_id'=>$car['id'],'button'=>0))->save($o_save);
            if($send_post){
                $this->apiResponse(1,'result','OK');
            }else{
                $this->apiResponse(0,'result','FAILED');
            }
        }
    }

    /**
     *设备结算
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/27 17:41
     */
//    public function account(){
//        $post = checkAppData('deviceid,event,clean_usage,clean_duration,foam_usage,foam_duration,vacuum_usage','洗车机编号-事件-清水用量-清水使用时间-泡沫用量-泡沫使用时间-吸尘器使用时间');
////        $post['deviceid'] = 510042001451373435363337;
////        $post['event'] = 1;
////        $post['clean_usage'] = 0;
////        $post['clean_duration'] =0 ;
////        $post['foam_usage'] =0 ;
////        $post['foam_duration'] =0 ;
////        $post['vacuum_usage'] = 0;
//        $data = array(
//            'devices' => array(
//                'deviceid' =>$post['deviceid'],
//                'event' => $post['event'],
//                'settlement_info' => array(
//                    'clean_usage' => $post['clean_usage'],                     //清水用量
//                    'clean_duration' => $post['clean_duration'],                  //清水使用时间
//                    'foam_usage' => $post['foam_usage'],                       //泡沫用量
//                    'foam_duration' => $post['foam_duration'],                    //泡沫使用时间
//                    'vacuum_usage' => $post['vacuum_usage'],                     //吸尘器使用时间
//                ),
//            ),
//        );
//        if($data){
//            $this->apiResponse('1','成功',$data);
//        }
    //  }

    /**
     *语音播放
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/03/05 14:32
     */
//    public function a(){
//
//        $voice = M('Voice')->where(array('voice_type'=>2,'status'=>1))->find();
//        var_dump($voice);exit;
//    }

}
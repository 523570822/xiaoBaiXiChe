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
                $this->apiResponse ('0' , '您有未付订单待处理' , array ('id' => $order['id'] , 'orderid' => $order['orderid']));
            } elseif ( $w_type == '2' ) {
                if ( $order['subs_time'] < time () ) {
                    $appsetting = D ('Appsetting')->field ('overtime_money')->find ();
//                    var_dump ($order['id'],$order['pay_money']);die;
                    if ( $order['pay_money']=='0.00' ) {
                        D ('Order')->where (array ('id' => $order['id']))->save (array ('pay_money' => $appsetting['overtime_money'] , 'is_no' => '1'));
                        D ('CarWasher')->where (array ('mc_code' => $mc_code))->save (array ('type' => '1'));
                        $this->apiResponse ('0' , '您有预约订单已超时' , array ('id' => $order['id'] , 'orderid' => $order['orderid']));
                    }
                }
//                else {
//                    $this->apiResponse ('0' , '您有预约订单未处理' , array ('id' => $order['id'] , 'orderid' => $order['orderid']));
//                }
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
            }else if($Order['m_id'] != $m_id ){
                $this->apiResponse('0','该洗车机已被人预约');
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
        //查找未支付的订单
//        $find_order = M('Order')->where(array('m_id'=>$m_id,'status'=>1))->find();
//        if(!empty($find_order)){
//            echo 222;
//            $this->apiResponse('0','您还有未支付的订单');
//        }
//        echo 111;exit;


        $this->checkParam ($rule);
        //重定义名称
        $o_type = $request['o_type'];
        $w_type = $request['w_type'];
        $mc_code = $request['mc_code'];
        $find_car = M('CarWasher')->where(array('mc_code'=>$mc_code))->find();
        //type 2使用中   4故障中
        if($find_car['type'] == 2){
            $this->apiResponse('0','洗车机正在使用中');
        }
        if($find_car['type'] == 3){
            $this->apiResponse('0','洗车机正在预定中');
        }
        if($find_car['type'] == 4){
            //语音播报
            $voice = M('Voice')->where(array('voice_type'=>3,'status'=>1))->find();
            $this->send_post('device_manage',$find_car['mc_id'],5,1,$voice['content']);
            $this->apiResponse('0','洗车机故障中');
        }
        //转换数据
        $mc_id = $this->check_mc_code ($mc_code , '1');
        $c_id = $this->check_mc_code ($mc_code , '2');
        //检查数据
        $x_car = M('CarWasher')->where(array('mc_code'=>$mc_code))->find();
        if($x_car['type'] == 3){
            $x_order = M('Order')->where(array('c_id'=>$x_car['id'],'button'=>0,'is_no'=>0))->find();
            if($x_order['m_id'] != $m_id){
                $this->apiResponse('0','机器已经被预订，请尝试其他机器');
            }
        }
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
        //查找为完成的预约订单
        $f_order = M('Order')->where(array('m_id'=>$m_id,'w_type'=>2,'status'=>1))->find();
        if($f_order){
            $this->apiResponse('0','您还有预约订单未结算');
        }
        //查找未支付的订单
        $find_order = M('Order')->where(array('m_id'=>$m_id))->select();
        foreach ($find_order as $fk=>$fv){
            if($fv['status'] == 1){
                $this->apiResponse('0','您还有未支付的订单');
            }
        }
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
        $order [] = 'create_time DESC';
        $list_info = D ('Order')
            ->where ($where)
            ->join ("LEFT JOIN db_car_washer ON db_order.c_id = db_car_washer.id")
            ->field ('db_order.id,db_order.orderid,db_order.status,db_order.create_time,db_order.w_type,db_order.money,db_order.pay_money,db_order.is_no,is_set,db_order.button,db_car_washer.mc_code as mc_id ,db_car_washer.p_id,db_car_washer.type')
            ->order($order)
            ->page ($request['page'] , '10')
            ->select ( ['id'=>'db_order.id asc','order' =>'db_order.create_time desc']);
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
        if($details['washing'] >= 60 || $details['foam'] >= 60 || $details['cleaner']>=60){
            $wash_fen = intval($details['washing']/60).'分';
            $wash_miao = $details['washing'] % 60 . '秒';
            $wash_time = $wash_fen . $wash_miao;                //水枪时间
            $foam_fen = intval($details['foam']/60).'分';
            $foam_miao = $details['foam'] % 60 . '秒';
            $foam_time = $foam_fen . $foam_miao;                  //泡沫枪时间
            $cleaner_fen = intval($details['cleaner']/60).'分';
            $cleaner_miao = $details['cleaner'] % 60 . '秒';
            $cleaner_time = $cleaner_fen . $cleaner_miao;          //吸尘器时间
        }else if($details['washing'] < 60 || $details['foam'] < 60 || $details['cleaner'] < 60){
            $wash_time = 0 . '分' . $details['washing'] . '秒';    //水枪时间
            $foam_time = 0 . '分' . $details['foam'] . '秒';     //泡沫枪时间
            $cleaner_time = 0 . '分' . $details['cleaner'] . '秒';   //吸尘器时间
        }
        $order['shop_name'] = $shop['shop_name'];
        $order['lon'] = $car['lon'];
        $order['lat'] = $car['lat'];
        $order['mc_id'] = $car['mc_code'];
        $order['address'] = $car['address'];
        $order['washing'] = $wash_time;
        $order['foam'] = $foam_time;
        $order['cleaner'] = $cleaner_time;
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
        $request = I ("");
        $rule = array ('openid' , 'string' , '请输入openid');
        $this->checkParam ($rule);
        $open_id = M ('MemberBind')->where (array ('openid' => $request['openid']))->find ();
        $order = D ('Order')->where (array ('m_id' => $open_id['m_id'] , 'orderid' => $request['orderid'] , 'o_type' => 1 , 'w_type' => 2))->find ();
        $details = M('Details')->where(array('o_id'=>$order['id']))->find();   //查询是否下单

        if(empty($details)){
            if ( $order['subs_time'] ) {
                $time1 = time ();
                $time2 = $order['subs_time'];
                $sub = ($time2 - $time1);
                $order['is_time'] = $order['subs_time'] < time () ? 1 : 0;//1超时 0未超时
                if ( $order['is_time'] == 1 ) {
                    $pmae['is_no'] = 1;
                    $pmae['button'] = 1;
                    D ('Order')->where (array ('m_id' =>  $open_id['m_id'] , 'orderid' => $request['orderid'] , 'o_type' => 1 , 'w_type' => 2))->save ($pmae);
                }
            }
            $this->apiResponse (1 , '查询成功' , array ('is_time' => $order['is_time'] , 'end_time' => $sub));
        }
        else{
            $this->apiResponse (1 , '查询成功' ,0);
        }

    }

    /**
     *定时超时查询
     */
    public function overtime(){
        $order = D ('Order')->where (array ('is_no'=>0,'is_set'=>0,'button'=>0,'o_type' => 1 , 'w_type' => 2))->select();
        $money = M('Appsetting')->where(array('id'=>1))->find();
        $card_where['status'] = array('neq',9);
        $card_user = M('CardUser')->where($card_where)->select();
        foreach($card_user as $ck=>$cv){
            $time = time();
            if($cv['end_time'] > $time){
                $save = array(
                    'status' => 1,
                );
                $s_where = array(
                    'id' => $cv['id']
                );
                $s_card = M('CardUser')->where($s_where)->save($save);
            }else if($cv['end_time'] <= $time){
                $save = array(
                    'status' => 2,
                );
                $d_where = array(
                    'id' => $cv['id']
                );
                $s_card = M('CardUser')->where($d_where)->save($save);
            }
        }
        foreach ($order as $k=>$v){
            $details = M('Details')->where(array('o_id'=>$v['id']))->find();   //查询是否下单
            $notime=time();
            $order[$k]['c_id']=$v['c_id'];
            if(empty($details)){
                if($notime > $v['subs_time']){
                    if($v['status'] == 1) {
                        $pmae['is_no'] = 1;
                        $pmae['button'] = 1;
                        $pmae['pay_money'] = $money['overtime_money'];
                        D('Order')->where(array('id' => $v['id']))->save($pmae);
                        $car = M('CarWasher')->where(array('id' => $order[$k]['c_id']))->find();
                        //结算
                        $send_post = $this->send_post('device_manage', $car['mc_id'], 3);
                        $where['type'] = 1;
                        D('CarWasher')->where(array('id' => $order[$k]['c_id']))->save($where);

                        //语音播报
                        $voice = M('Voice')->where(array('voice_type'=>2,'status'=>1))->find();
                        $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
                    }
                }
            }else{
                $this->apiResponse('1','您已开始洗车,预约已完成');
            }
        }
    }

    /**
     *结算
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/
     * 02/18 15:52
     */
    public function settlement(){
        $post = checkAppData('token,orderid,off_on','token-订单ID-开关');
//        $post['token'] = '0d16de65e1665010d9c69fcfc5732d58';
//        $post['orderid'] = 'XC201903281908215760';
//        $post['off_on'] = 0;

        $where['token'] = $post['token'];
        $member = M('Member')->where($where)->find();
        $o_where = array(
            'm_id' =>$member['id'],
            'orderid' =>$post['orderid'],
//            'button' =>0,   //还未结算
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

        //各设备使用时间
        if($details['washing'] >= 60 || $details['foam'] >= 60 || $details['cleaner']>=60){
            $wash_fen = intval($details['washing']/60).'分';
            $wash_miao = $details['washing'] % 60 . '秒';
            $wash_time = $wash_fen . $wash_miao;                //水枪时间
            $foam_fen = intval($details['foam']/60).'分';
            $foam_miao = $details['foam'] % 60 . '秒';
            $foam_time = $foam_fen . $foam_miao;                  //泡沫枪时间
            $cleaner_fen = intval($details['cleaner']/60).'分';
            $cleaner_miao = $details['cleaner'] % 60 . '秒';
            $cleaner_time = $cleaner_fen . $cleaner_miao;          //吸尘器时间
        }else if($details['washing'] < 60 || $details['foam'] < 60 || $details['cleaner'] < 60){
            $wash_time = 0 . '分' . $details['washing'] . '秒';    //水枪时间
            $foam_time = 0 . '分' . $details['foam'] . '秒';     //泡沫枪时间
            $cleaner_time = 0 . '分' . $details['cleaner'] . '秒';   //吸尘器时间
        }
        if($details['washing'] < 2){
            $details['washing'] = 0;
            $wash_time = 0 . '分' . 0 . '秒';    //水枪时间
        }
        if($details['foam'] < 2){
            $details['foam'] = 0;
            $foam_time = 0 . '分' . 0 . '秒';    //水枪时间
        }
        if($details['cleaner'] < 1){
            $details['cleaner'] = 0;
            $cleaner_time = 0 . '分' . 0 . '秒';    //水枪时间
        }

        //订单结算自动跳转
        $data_moneys = $this->details($member['id'],$order['id'],0,$post['orderid']);
        //结算存储时间
        $this->carWasherTime($car['mc_id'],$order['id'],$member['id']);

        if($order['button'] ==1){
//            echo 123;
            //检查订单费用是否为0
            $zero = $this->payZero($member['id'],$order['id']);
            if($zero == 1){
                $this->apiResponse('1','未产生洗车费用,已为您自动结算');
            }
            $this->apiResponse('1','结算成功',$data_moneys);
        }

        //判断订单
        if(!empty($details)){
            if($details['status'] == 1){
                $this->apiResponse('0','此订单已结算,无法进行洗车操作');
            }
            //判断使用哪个设备
            if(round($send_post['devices'][0]['queryitem']['clean_water_duration']) != $details['washing_end_time']){
                $indication = 1;
            }elseif(round($send_post['devices'][0]['queryitem']['foam_duration']) != $details['foam_end_time']){
                $indication = 2;
            }elseif(round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']) != $details['cleaner_end_time']){
                $indication = 3;
            }else{
                $indication = 0;
            }
            //价格
            $wash_money =  round($details['washing'] * $car['washing_money'],2);
            $foam_money = round($details['foam'] * $car['foam_money'],2);
            $cleaner_money = round($details['cleaner'] * $car['cleaner_money'],2);
            $data_money = array(
                'indication' => $indication,    //1  代表水枪    2代表泡沫枪   3代表吸尘器
                'washing' =>$wash_time,
                'foam'=>$foam_time,
                'cleaner'=>$cleaner_time,
                'all_money' =>$wash_money+$foam_money+$cleaner_money,
                'off_on' => $post['off_on'],
            );

            //判断机器使用状态
            if($car['type'] == 2){     //当机器service_status =13的时候,洗车机开启
                $f_where['id'] = $details['id'];
                $f_where['status'] = 0;
                $f_details = M('Details')->where($f_where)->find();
                //水枪使用时间
                if(($send_post['devices'][0]['queryitem']['service_status'] == 13) && ($send_post['devices'][0]['queryitem']['pump1_status'] == 3) ){
                    if($send_post['devices'][0]['queryitem']['clean_water_duration'] != $f_details['washing_end_time']){
                        $w_end_data['washing_end_time'] = round($send_post['devices'][0]['queryitem']['clean_water_duration']);
                        $w_end_data['washing'] = $w_end_data['washing_end_time'] - $details['washing_start_time'];
                        if($w_end_data['washing'] < 0 ){    //为-1就等于0
                            $w_end_data['washing'] = 0;
                        }
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
                        if($f_end_data['foam'] < 0 ){    //为-1就等于0
                            $f_end_data['foam'] = 0;
                        }
                        $d_where['status'] = 0;
                        $d_where['id'] = $details['id'];
                        $f_start = M('Details')->where($d_where)->save($f_end_data);
                    }
                }
                //吸尘器使用时间
                if (($send_post['devices'][0]['queryitem']['service_status'] == 13) && ($send_post['devices'][0]['queryitem']['vacuum_info']['status'] == 3)){
                    if($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage'] != $f_details['cleaner_end_time']){
                        $c_end_data['cleaner_end_time'] = round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']);
                        $c_end_data['cleaner'] = $c_end_data['cleaner_end_time'] - $details['cleaner_start_time'];
                        if($c_end_data['cleaner'] < 0 ){    //为-1就等于0
                            $c_end_data['cleaner'] = 0;
                        }
                        $d_where['status'] = 0;
                        $d_where['id'] = $details['id'];
                        $c_start = M('Details')->where($d_where)->save($c_end_data);
                    }
                }

                $data_moneyss = $this->onDetails($member['id'],$order['id'],$indication,$post['orderid']);
                $c_save = array(
                    'money' => $data_moneyss['all_money'],
                    'pay_money' => $data_moneyss['all_money'],
                );
//                var_dump($data_moneyss);exit;
                $c_order = M('Order')->where(array('orderid'=>$post['orderid']))->save($c_save);
                //检查洗车机继续使用还是结算
                if(!empty($data_money)){
//                    echo 85545;
                    if($post['off_on'] == 0){
                        //结算存储时间
                        $this->carWasherTime($car['mc_id'],$order['id'],$member['id']);
                        if($send_post['devices'][0]['queryitem']['pump1_status'] >= 4 || $send_post['devices'][0]['queryitem']['pump2_status'] >= 4 || $send_post['devices'][0]['queryitem']['valve1_status'] >= 4 || $send_post['devices'][0]['queryitem']['level2_status'] == 0){   //12代表机器结算   结算跳转到立即支付页
//                            echo 8784582;
                            $d_save = array(
                                'status'  => 1,
                            );
                            $detailsss = M('Details')->where($d_where)->save($d_save);    //洗车数据详情表状态改为1,订单结束
                            $o_save = array(
                                'button' => 1,
                                'money' =>$data_money['all_money'],
                                'pay_money' =>$data_money['all_money'],
                            );
                            $o_order = M('Order')->where($o_where)->save($o_save);
                            //语音播报
                            $voice = M('Voice')->where(array('voice_type'=>2,'status'=>1))->find();
                            $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
                            $data_moneys = $this->details($member['id'],$order['id'],$indication,$car['mc_id']);
                            //结算存储时间
                            $this->carWasherTime($car['mc_id'],$order['id'],$member['id']);


                            //结算洗车机状态为1空闲
                            $this->typeOne($details['c_id']);
                            //检查订单费用是否为0
                            $zero = $this->payZero($member['id'],$order['id']);
                            if($zero == 1){
                                $this->apiResponse('1','未产生洗车费用,已为您自动结算');
                            }
                            $this->apiResponse('1','结算成功',$data_moneys);
                        } else if($send_post['devices'][0]['queryitem']['service_status'] < 8) {
//                            echo 122;
                            $send_post = $this->send_post('device_manage', $car['mc_id'], 3);   //结算
                            $d_save = array(
                                'status' => 1,
                            );
                            $detailsss = M('Details')->where($d_where)->save($d_save);    //洗车数据详情表状态改为1,订单结束
                            $o_save = array(
                                'button' => 1,
                                'money' => $data_money['all_money'],
                                'pay_money' =>$data_money['all_money'],
                            );
                            $o_order = M('Order')->where($o_where)->save($o_save);
                            $data_moneys = $this->details($member['id'], $order['id'], $indication, $car['mc_id']);
                            //结算存储时间
                            $this->carWasherTime($car['mc_id'], $order['id'], $member['id']);
                            //结算洗车机状态为1空闲
                            $this->typeOne($details['c_id']);
                            //检查订单费用是否为0
                            $zero = $this->payZero($member['id'],$order['id']);
                            if($zero == 1){
                                $this->apiResponse('1','未产生洗车费用,已为您自动结算');
                            }
                            $this->apiResponse('1', '该设备已掉线,已为您自动结算', $data_moneys);
                        } else if($send_post['devices'][0]['queryitem']['service_status'] == 8){
                            $this->apiResponse('0','当前洗车机尚未开启');
                        }
//                        var_dump($data_moneyss);exit;
                        $this->apiResponse('1','查询成功',$data_moneyss);
                    }elseif($post['off_on'] == 1){
                        $send_post = $this->send_post('device_manage',$car['mc_id'],3);   //结算
                        $d_save = array(
                            'status'  => 1,
                        );
                        $detailss = M('Details')->where($d_where)->save($d_save);
                        $o_save = array(
                            'button' => 1,
                            'money' => $data_money['all_money'],
                            'pay_money' =>$data_money['all_money'],
                        );
                        $o_order = M('Order')->where($o_where)->save($o_save);
                        //语音播报
                        $voice = M('Voice')->where(array('voice_type'=>2,'status'=>1))->find();
                        $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
                        $data_moneys = $this->details($member['id'],$order['id'],$indication,$car['mc_id']);
                        //结算存储时间
                        $this->carWasherTime($car['mc_id'],$order['id'],$member['id']);
                        //检查订单费用是否为0
                        $zero = $this->payZero($member['id'],$order['id']);
                        //结算洗车机状态为1空闲
                        $this->typeOne($details['c_id']);
                        if($zero == 1){
                            $this->apiResponse('1','未产生洗车费用,已为您自动结算');
                        }
                        $this->apiResponse('1','结算成功',$data_moneys);
                    }
                }
            }else if($car['type'] == 4){
//                echo 852369;
                if($send_post['devices'][0]['queryitem']['service_status'] <= 8) {
//                    echo 123;exit;
                    $send_post = $this->send_post('device_manage', $car['mc_id'], 3);   //结算
                    $d_save = array(
                        'status' => 1,
                    );
                    $detailsss = M('Details')->where($d_where)->save($d_save);    //洗车数据详情表状态改为1,订单结束
                    $o_save = array(
                        'button' => 1,
                        'money' => $data_money['all_money'],
                        'pay_money' =>$data_money['all_money'],
                    );
                    $o_order = M('Order')->where($o_where)->save($o_save);
                    $data_moneys = $this->details($member['id'], $order['id'], $indication, $car['mc_id']);
                    //结算存储时间
                    $this->carWasherTime($car['mc_id'], $order['id'], $member['id']);
                    //结算洗车机状态为1空闲
                    $this->typeOne($details['c_id']);
                    //检查订单费用是否为0
                    $zero = $this->payZero($member['id'],$order['id']);
                    if($zero == 1){
                        $this->apiResponse('1','未产生洗车费用,已为您自动结算');
                    }
                    $this->apiResponse('1', '该设备已掉线,已为您自动结算', $data_moneys);
                }else if($send_post['devices'][0]['queryitem']['pump1_status'] >= 4 || $send_post['devices'][0]['queryitem']['pump2_status'] >= 4 || $send_post['devices'][0]['queryitem']['valve1_status'] >= 4 || $send_post['devices'][0]['queryitem']['level2_status'] == 0){   //12代表机器结算   结算跳转到立即支付页
//                    echo 118541;
                    $d_save = array(
                        'status'  => 1,
                    );
                    $detailsss = M('Details')->where($d_where)->save($d_save);    //洗车数据详情表状态改为1,订单结束
                    $o_save = array(
                        'button' => 1,
                        'money' =>$data_money['all_money'],
                        'pay_money' =>$data_money['all_money'],
                    );
                    $o_order = M('Order')->where($o_where)->save($o_save);
                    //语音播报
                    $voice = M('Voice')->where(array('voice_type'=>2,'status'=>1))->find();
                    $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
                    $data_moneys = $this->details($member['id'],$order['id'],$indication,$car['mc_id']);
                    //结算存储时间
                    $this->carWasherTime($car['mc_id'],$order['id'],$member['id']);
                    //结算洗车机状态为1空闲
                    $this->typeOne($details['c_id']);
                    //检查订单费用是否为0
                    $zero = $this->payZero($member['id'],$order['id']);
                    if($zero == 1){
                        $this->apiResponse('1','未产生洗车费用,已为您自动结算');
                    }
                    $this->apiResponse('1','结算成功',$data_moneys);
                }
            }
        }else{
            $this->apiResponse('0','请先扫码或手动输入下单');
        }
    }

    /**
     *立即支付
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/21 13:19
     */
    public function  Pay(){
        $post = checkAppData('token,orderid,method,methodID','token-订单ID-优惠方式-优惠卡ID');
//        $post['token'] = 'edd4fa2b6b8e6d2da35d3b4ac20c5d98';
//        $post['orderid'] = 'XC201903171839349455';
//        $post['method'] = 1;     //1代表折扣卡    2代表抵用券   3无优惠方式
//        $post['methodID'] = 17;    //折扣卡ID

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

        $price = M('Appsetting')->where(array('id'=>1))->find();
//        $car = M('CarWasher')->where(array('id'=>$details['c_id']))->find();
//        $send_post = $this->send_post('device_manage',$car['mc_id'],3);

        $wash_money =  round($details['washing'] * $car['washing_money'],2);    //水枪金额
        $foam_money = round($details['foam'] * $car['foam_money'],2); //泡沫枪金额
        $cleaner_money = round($details['cleaner'] * $car['cleaner_money'],2); //吸尘器金额
        $all_money = $wash_money + $foam_money + $cleaner_money;  //总金额

        //各设备使用时间
        if($details['washing'] >= 60 || $details['foam'] >= 60 || $details['cleaner']>=60){
            $wash_fen = intval($details['washing']/60).'分';
            $wash_miao = $details['washing'] % 60 . '秒';
            $wash_time = $wash_fen . $wash_miao;                //水枪时间
            $foam_fen = intval($details['foam']/60).'分';
            $foam_miao = $details['foam'] % 60 . '秒';
            $foam_time = $foam_fen . $foam_miao;                  //泡沫枪时间
            $cleaner_fen = intval($details['cleaner']/60).'分';
            $cleaner_miao = $details['cleaner'] % 60 . '秒';
            $cleaner_time = $cleaner_fen . $cleaner_miao;          //吸尘器时间
        }else if($details['washing'] < 60 || $details['foam'] < 60 || $details['cleaner'] < 60){
            $wash_time = 0 . '分' . $details['washing'] . '秒';    //水枪时间
//            var_dump($details);
            $foam_time = 0 . '分' . $details['foam'] . '秒';     //泡沫枪时间
            $cleaner_time = 0 . '分' . $details['cleaner'] . '秒';   //吸尘器时间
        }

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
                'wash' =>$wash_time,
                'foam' =>$foam_time,
                'cleaner' =>$cleaner_time,
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
//        }
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
                    'card' => [],
                    'coupon' => $coupon_lists,
                );
                $this->apiResponse('1','暂无会员卡',$data);
            }
            if(empty($coupon_lists) && !empty($card_lists)){
                $data = array(
                    'card' => $card_lists,
                    'coupon' => [],
                );
                $this->apiResponse('1','暂无折扣卡',$data);
            }
            if(empty($coupon_lists) && empty($card_lists)){
                $data = array(
                    'card' => [],
                    'coupon' => [],
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
    public function button(){
//        $post = checkAppData('deviceid,event','洗车机编号-事件');
        $json = file_get_contents("php://input");
        $array = (array)json_decode(strip_tags($json,true));
        $post = $array['devices'][0];
        $array['devices'][0] = [
            "deviceid" => $post->deviceid,
			"event" => $post->event,
	    ];
        $car = M('CarWasher')->where(array('mc_id'=>$post->deviceid))->find();
        $order = M('Order')->where(array('c_id'=>$car['id'],'button'=>0,'o_type'=>1))->order('id DESC')->find();
//        echo M('Order')->_sql();
//        var_dump($order);exit;
        $details = M('Details')->where(array('o_id'=>$order['id']))->find();

        if($post->event == 1){
//            echo 74178;
            $send_post = $this->send_post('device_manage',$post->event,3);
            $d_save = array(
                'status' => 1,
            );
            $o_save = array(
                'button' =>1,
            );
            $detail = M('Details')->where(array('c_id'=>$car['id'],'o_id'=>$order['id'],'status'=>0))->save($d_save);
            //这台洗车机的全部订单都结算
            $f_order = M('Order')->where(array('button'=>0,'c_id'=>$car['id'],'o_type'=>1))->save($o_save);
            if($send_post){
                //语音播报
                $voice = M('Voice')->where(array('voice_type'=>2,'status'=>1))->find();
                $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
                //存储金额
                $data_moneys = $this->details($order['m_id'],$order['id'],0,$car['mc_id']);
                //结算存储时间
                $this->carWasherTime($car['mc_id'],$order['id'],$order['m_id']);
                //结算洗车机状态为1空闲
                $this->typeOne($details['c_id']);
                //检查订单费用是否为0
                $zero = $this->payZero($order['m_id'],$order['id']);
                $this->apiResponse(1,'result','OK');
            }else{
                $this->apiResponse(0,'result','FAILED');
            }
        }
        //缺水停泵
        if($post->event == 2){
//            echo 753;exit;
            //结算
            $send_post = $this->send_post('device_manage',$post->event,3);
            $d_save = array(
                'status' => 1,
            );
            $detail = M('Details')->where(array('c_id'=>$car['id'],'o_id'=>$order['id'],'status'=>0))->save($d_save);
            $q_save = array(
                'button' =>  1
            );
            $f_order = M('Order')->where(array('button'=>0,'c_id'=>$car['id'],'o_type'=>1))->save($q_save);
            if($send_post){
                //语音播报
                $voice = M('Voice')->where(array('voice_type'=>7 ,'status'=>1))->find();
                $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
                //存储金额
//                echo 123;exit;
                $data_moneys = $this->details($order['m_id'],$order['id'],0,$car['mc_id']);
                //结算存储时间
                $this->carWasherTime($car['mc_id'],$order['id'],$order['m_id']);
                //结算洗车机状态为1空闲
                $this->typeOne($details['c_id']);
                //检查订单费用是否为0
                $zero = $this->payZero($order['m_id'],$order['id']);
                $this->apiResponse(1,'result','OK');
            }else{
                $this->apiResponse(0,'result','FAILED');
            }
        }
        //8分钟超时
        if($post->event == 3){
//            echo 589;exit;
            //语音播报
            $voice = M('Voice')->where(array('voice_type'=>4,'status'=>1))->find();
            $seccess = $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
            if($seccess){
                //存储金额
                $data_moneys = $this->onDetails($order['m_id'],$order['id'],0,$car['mc_id']);
                //结算存储时间
                $this->carWasherTime($car['mc_id'],$order['id'],$order['m_id']);
                $this->apiResponse(1,'result','OK');
            }else{
                $this->apiResponse(0,'result','FAILED');
            }
        }
        //10分钟超时
        if($post->event == 4){
//            echo 8525;exit;
            //结算
            $send_post = $this->send_post('device_manage',$post->event,3);
            $d_save = array(
                'status' => 1,
            );
            $detail = M('Details')->where(array('c_id'=>$car['id'],'o_id'=>$order['id'],'status'=>0))->save($d_save);
            //这台洗车机的全部订单都结算
            $t_save = array(
                'button' =>  1
            );
            $f_order = M('Order')->where(array('button'=>0,'c_id'=>$car['id'],'o_type'=>1))->save($t_save);
            if($send_post){
                //语音播报
                $voice = M('Voice')->where(array('voice_type'=>5,'status'=>1))->find();
                $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
                //存储金额
                $data_moneys = $this->details($order['m_id'],$order['id'],0,$car['mc_id']);
                //结算存储时间
                $this->carWasherTime($car['mc_id'],$order['id'],$order['m_id']);



                //结算洗车机状态为1空闲
                $this->typeOne($details['c_id']);
                //检查订单费用是否为0
                $zero = $this->payZero($order['m_id'],$order['id']);
                $this->apiResponse(1,'result','OK');
            }else{
                $this->apiResponse(0,'result','FAILED');
            }
        }
        //20分钟超时
        if($post->event == 5){
//            echo 785155;exit;
            //结算
            $send_post = $this->send_post('device_manage',$post->event,3);
            $d_save = array(
                'status' => 1,
            );
            $detail = M('Details')->where(array('c_id'=>$car['id'],'o_id'=>$order['id'],'status'=>0))->save($d_save);
            //这台洗车机的全部订单都统一结算
            $a_save = array(
                'button' =>  1
            );
            $f_order = M('Order')->where(array('button'=>0,'c_id'=>$car['id'],'o_type'=>1))->save($a_save);
            if($send_post){
                //语音播报
                $voice = M('Voice')->where(array('voice_type'=>6,'status'=>1))->find();
                $this->send_post('device_manage',$car['mc_id'],5,1,$voice['content']);
                //存储金额
                $data_moneys = $this->details($order['m_id'],$order['id'],0,$car['mc_id']);
                //结算存储时间
                $this->carWasherTime($car['mc_id'],$order['id'],$order['m_id']);
                //结算洗车机状态为1空闲
                $this->typeOne($details['c_id']);
                //检查订单费用是否为0
                $zero = $this->payZero($order['m_id'],$order['id']);
                $this->apiResponse(1,'result','OK');
            }else{
                $this->apiResponse(0,'result','FAILED');
            }
        }
    }

}
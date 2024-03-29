<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/20
 * Time: 11:22
 */

namespace Api\Controller;
use Common\Service\ControllerService;

/**
 * 洗车机模块
 * Class CarWasherController
 * @package Api\Controller
 */
class CarWasherController extends BaseController
{
    /**
     *构造方法
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/18 14:34
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
    *洗车机列表
    *user:jiaming.wang  459681469@qq.com
    *Date:2018/12/20 10:38
    */
    public function carWasher()
    {
        $post = checkAppData('token,page,size','token-页数-个数');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['page'] = 2;
//        $post['size'] = 2;
        $agent = $this->getAgentInfo($post['token']);
        $orders[] = 'id ASC';
        $car_washer = D('CarWasher')->field('id,mc_code as mc_id')->order($orders)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        foreach($car_washer as $k=>$v){
            $income = D('Income')->where(array('car_washer_id'=>$v['id'],'agent_id'=>$agent['id']))->field('SUM(net_income) as net_income,SUM(car_wash) as car_wash')->group('car_washer_id')->find();
            if(!empty($income)){
                $incomes[] = $income;
                $incomes[$k]['mc_id']= $v['mc_id'];
            }
        }
        if(!empty($incomes)){
            $this->apiResponse('1','成功',$incomes);
        }else{
            $this->apiResponse('0','暂无洗车机信息');
        }
    }

    /**
     *洗车机收入
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/20 11:53
     */
    public function carWasherIncome(){
        $post = checkAppData('token,car_washer_id,in_month,page,size','token-洗车机ID-月份时间戳-页数-个数');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['car_washer_id'] = 1;
//        $post['in_month'] = 'all';
//        $post['page'] = 1;
//        $post['size'] = 10;
        if($post['in_month'] == 'all'){
            $post['in_month'] = strtotime(date('Y-m'));
        }
        $agent = $this->getAgentInfo($post['token']);
        $order[] = 'sort DESC';
        $income = M('Income')->where(array('car_washer_id'=>$post['car_washer_id'],'agrnt_id'=>$agent['id'],'month'=>$post['in_month']))->field('net_income,car_wash,day,car_washer_id')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        $month = M('Income')->where(array('car_washer_id'=>$post['car_washer_id'],'agrnt_id'=>$agent['id'],'month'=>$post['in_month']))->field('SUM(net_income) as net_income,month as ag_month')->select();
        $data = array(
            'now_month' => $month,
            'income' => $income,
        );
        if(!empty($income)){
            $this->apiResponse('1','成功',$data);
        }else{
            $this->apiResponse('0','暂无加盟商信息');
        }
    }

    /**
     *我的洗车机
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/19 11:45
     */
    public function myCarWasher(){
        $post = checkAppData('token,address,car_num,status,page,size','token-洗车机地址-洗车机编号-洗车机状态-页数-个数');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['address'] = 'all';
//        $post['car_num'] = 'all';
//        $post['status'] = 'all';
//        $post['page'] = 1;
//        $post['size'] = 10;
        if($post['address'] == 'all'){
            $post['address'] = '';
        }
        if($post['car_num'] == 'all'){
            $post['car_num'] = '';
        }
        if($post['status'] == 'all'){
            $post['status'] = '';
        }
        $order[] = 'sort DESC';
        if($post['address']){            //筛选地址

            if(!empty($post['address'])){
                $where['address'] = array('LIKE', "%" . $post['address'] . "%");
            }else{
                $where['address'] = '';
            }
        }

        if($post['car_num']){        //搜索洗车机编号
            if(!empty($post['car_num'])){
                $where['mc_code'] = array('Like', "%" . $post['car_num'] . "%");
            }else{
                $where['mc_code'] = '';
            }
        }

        if($post['status']){                  //筛选状态  1正常  2故障 3报警  4不在线
            if(!empty($post['status'])){
                $where['status'] = $post['status'];
            }else{
                $where['status'] = '';
            }
        }

        $agent = $this->getAgentInfo($post['token']);

        $where['agent_id'] = $agent['id'];
        if ($post['page'] != '') {
            $result = M('CarWasher')->field('address,mc_code as mc_id,status')->where($where)->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        } else {
            $result = M('CarWasher')->field('address,mc_code as mc_id,status')->where($where)->order($order)->select();
        }
        if($result){
            $this->apiResponse('1','成功',$result);
        }else{
            $this->apiResponse('0','暂无数据');
        }
    }

    /**
     *收入详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/02 10:22
     */
    public function carIncomeInfo(){
        $post = checkAppData('token,day,page,size','token-日期时间戳-页数-个数');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['day'] = 1550132967;
//        $post['page'] = 1;
//        $post['size'] = 1;
//        if($post['day'] == 'all'){
//            $post['day'] = strtotime(date('Y-m-d'));
//        }
        $day = strtotime(date('Y-m-d',$post['day']));
        $agent = $this->getAgentInfo($post['token']);
//        $income = M('Income')->where(array('car_washer_id'=>$post['car_washer_id'],'agent_id'=>$agent['id'],'day'=>$post['day']))->field('net_income,create_time')->select();
//        foreach ($income as $k=>$v){
        $order = M('CarWasher')->where(array('agent_id'=>$agent['id']))->field('mc_code as mc_id,id')->select();
        foreach ($order as $kk=>$vv){
            $orders[] = 'pay_time DESC';
            $order_num = M('Order')->where(array('c_id'=>$vv['id'],'o_type'=>1,'status'=>2))->field('orderid as mc_id,pay_money as net_income,pay_time as create_time')->order($orders)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
            foreach($order_num as $k=>$v){
                $time[$k] = strtotime(date('Y-m-d',$v['create_time']));
                if($time[$k] == $day){
                    $order_nums[$k]['car_washer'] = $vv['mc_id'];
                    $order_nums[$k]['mc_id'] = $v['mc_id'];
                    $order_nums[$k]['net_income'] = $v['net_income'];
                    $order_nums[$k]['car_washer'] = $v['mc_id'];
                }
            }
//        }
        }

//        var_dump($v);
        if(!empty($order_nums)){
            $this->apiResponse('1','成功',$order_nums);
        }else{
            $this->apiResponse('0','暂无收入详情');
        }
    }

    /**
     *设备详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/24 14:49
     */
    public function carInfo(){
        $post = checkAppData('token,car_id','token-洗车机ID');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['car_id'] = 'A00001';

        $agent = $this->getAgentInfo($post['token']);
        $where['agent_id'] = $agent['id'];
        $where['status'] = array('neq',9);
        $where['mc_code'] = $post['car_id'];
        $car_washer = M('CarWasher')->where($where)->field('mc_code as mc_id,address,status,electricity,water_volume,foam')->find();
        if($car_washer){
            $this->apiResponse('1','成功',$car_washer);
        }else{
            $this->apiResponse('0','暂无此设备信息');
        }
    }

    /**
     *异常订单结算
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/10 10:01
     */
    public function malfunction(){
        $where['status'] = array('neq',9);
        $car = M('CarWasher')->where($where)->field('mc_id,id')->select();
    }


    /**
     *实时状态查询
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/30 14:36
     */
    public function realTime(){
        $where['status'] = array('neq',9);
        $car = M('CarWasher')->where($where)->field('mc_id,id,type')->select();
        foreach ($car as $k=>$v){
            $cars[$k]['car_num'] = $v['mc_id'];
            $cars[$k]['id'] = $v['id'];
            $query = 'runtime_query';
            $queryitem[$k] = $this->send_post($query,$cars[$k]['car_num']);
            if(!empty($queryitem[$k])){
                foreach ($queryitem[$k] as $kk=>$vv){
                    if( $vv[0]['queryitem']['service_status'] == 8 || $vv[0]['queryitem']['service_status'] == 12){
//                        dump($vv[0]['deviceid']);
                        $using_where = array(
                            'mc_id' => $vv[0]['deviceid'],
                        );
                        $using_data = array(
                            'type' => 1,
                        );
                        $using = M('CarWasher')->where($using_where)->save($using_data);
                    } elseif($vv[0]['queryitem']['pump1_status'] >= 4 || $vv[0]['queryitem']['pump2_status'] >= 4 || $vv[0]['queryitem']['valve1_status'] >= 4 || $vv[0]['queryitem']['level2_status'] == 0 || $vv[0]['queryitem']['level1_status'] == 0 || $vv[0]['queryitem']['service_status'] < 8){
                        $using_where = array(
                            'mc_id' => $vv[0]['deviceid'],
                        );
                        $using_data = array(
                            'type' => 4,
                        );
                        $using = M('CarWasher')->where($using_where)->save($using_data);
//                        echo M('CarWasher')->_sql();
                    } elseif ($vv[0]['queryitem']['service_status'] == 13){
                        $using_where = array(
                            'mc_id' => $vv[0]['deviceid'],
                        );
                        $using_data = array(
                            'type' => 2,
                        );
                        $using = M('CarWasher')->where($using_where)->save($using_data);
                    }
                    $find_order = M('Details')->where(array('c_id'=>$v['id']))->order(array('id DESC'))->find();
                    if(!empty($find_order)){
                        if($find_order['status'] == 1){
                            if($vv[0]['queryitem']['service_status'] == 8 || $vv[0]['queryitem']['service_status'] == 12){
//                                dump($vv[0]['deviceid']);
                                $using_where = array(
                                    'mc_id' => $vv[0]['deviceid'],
                                );
                                $using_data = array(
                                    'type' => 1,
                                );
                                $using = M('CarWasher')->where($using_where)->save($using_data);
                            }elseif($vv[0]['queryitem']['pump1_status'] >= 4 || $vv[0]['queryitem']['pump2_status'] >= 4 || $vv[0]['queryitem']['valve1_status'] >= 4 || $vv[0]['queryitem']['level2_status'] == 0 || $vv[0]['queryitem']['level1_status'] == 0 || $vv[0]['queryitem']['service_status'] < 8){
                                $using_where = array(
                                    'mc_id' => $vv[0]['deviceid'],
                                );
                                $using_data = array(
                                    'type' => 4,
                                );

                                $using = M('CarWasher')->where($using_where)->save($using_data);
//                        echo M('CarWasher')->_sql();
                            }
                        }elseif ($find_order['status'] == 0){
//                            dump($vv[0]['deviceid']);
                                  //泡沫液位不足
                            if(/*$vv[0]['queryitem']['level3_status'] == 1 &&*/  $vv[0]['queryitem']['service_status'] == 13){
//                                dump($vv[0]['deviceid']);
                                $using_wheres = array(
                                    'mc_id' => $vv[0]['deviceid'],
                                );
                                $using_datas = array(
                                    'type' => 2,
                                );
                                $usings = M('CarWasher')->where($using_wheres)->save($using_datas);
//                                echo M('CarWasher')->_sql();
                            }
                        }
                    }elseif($vv[0]['queryitem']['pump1_status'] >= 4 || $vv[0]['queryitem']['pump2_status'] >= 4 || $vv[0]['queryitem']['valve1_status'] >= 4 || $vv[0]['queryitem']['level2_status'] == 0 || $vv[0]['queryitem']['level1_status'] == 0 || $vv[0]['queryitem']['service_status'] < 8){
                        $using_where = array(
                            'mc_id' => $vv[0]['deviceid'],
                        );
                        $using_data = array(
                            'type' => 4,
                        );
                        $using = M('CarWasher')->where($using_where)->save($using_data);
//                        echo M('CarWasher')->_sql();
                    }
                }
                //判断洗车机状态   1在线   2故障   3报警   4不在线
                if(($vv[0]['queryitem']['pump1_status'] >= 4) || ($vv[0]['queryitem']['pump2_status'] >= 4 ) || ($vv[0]['queryitem']['valve1_status'] >= 4) ){   //三个值有一个值>=4就代表故障
                    $malf_where = array(
                        'mc_id' => $vv[0]['deviceid'],
                    );
                    $malf_data = array(
                        'status' => 2,
                    );
                    $malfunction = M('CarWasher')->where($malf_where)->save($malf_data);
                }else if ($vv[0]['queryitem']['level2_status']  == 0 || $vv[0]['queryitem']['level1_status']  == 0){                  //三个状态判断液位不足
                    $alarm_where = array(
                        'mc_id' => $vv[0]['deviceid'],
                    );
                    $alarm_data = array(
                        'status' => 3,
                    );
                    $alarm = M('CarWasher')->where($alarm_where)->save($alarm_data);
                }else if ($vv[0]['queryitem']['service_status'] < 8){   //<8掉线
                    $off_where = array(
                        'mc_id' => $vv[0]['deviceid'],
                    );
                    $off_data['status'] = 4;
                    $offline = M('CarWasher')->where($off_where)->save($off_data);
                } else if ($vv[0]['queryitem']['level3_status'] == 0){   //=0泡沫不足
                    $off_where = array(
                        'mc_id' => $vv[0]['deviceid'],
                    );
                    $off_data['status'] = 5;
                    $offline = M('CarWasher')->where($off_where)->save($off_data);
                }else if($vv[0]['queryitem']['service_status'] >= 8){                                                //判断正常
                    $where = array(
                        'mc_id' => $vv[0]['deviceid'],
                    );
                    $data = array(
                        'status' => 1,
                    );
                    $online = M('CarWasher')->where($where)->save($data);
                }
                foreach ($vv as $kk1=>$vv1){
                    $car_data['electricity'] = $vv1['queryitem']['device_energy'];
                    $car_data['water_volume'] = $vv1['queryitem']['clean_water_usage'];
                    $car_data['foam'] = $vv1['queryitem']['foam_usage'];
                    $car_data['update_time'] = time();
                }
                $car_save = M('CarWasher')->where(array('mc_id'=>$cars[$k]['car_num']))->save($car_data);
            }
            $electricity = round($vv[0]['queryitem']['vacuum_info']['accumulated_usage']);
            $water_volume = round($vv[0]['queryitem']['clean_water_usage']);
            $foam = round($vv[0]['queryitem']['foam_usage']);
            $d_save = array(
                'electricity'=>$electricity,
                'water_volume'=>$water_volume,
                'foam'=>$foam,
            );
            $dosage = M('CarWasher')->where(array('mc_id'=>$vv[0]['deviceid']))->save($d_save);

            //所有空闲中的机器和故障的机器订单都结算
//            if($v['type'] =in_array( 'in','2,3')){
//
//                echo 785849;exit;
//            }else{
//                echo 741852963;exit;
//
//            }
        }
    }

}
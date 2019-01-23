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
        $post = checkAppData('token','token');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $agent = $this->getAgentInfo($post['token']);
        $car_washer = D('CarWasher')->field('id,mc_id')->select();
        foreach($car_washer as $k=>$v){
            $income = D('Income')->where(array('car_washer_id'=>$v['id'],'agent_id'=>$agent['id']))->field('SUM(net_income) as net_income,SUM(car_wash) as car_wash,car_washer_id')->group('car_washer_id')->find();
            if(!empty($income)){
                $incomes[] = $income;
                $incomes[$k]['mc_id']= $v['mc_id'];
            }
        }
        if(!empty($incomes)){
            $this->apiResponse('1','成功',$incomes);
        }else{
            $this->apiResponse('0','暂无加盟商信息');
        }
    }

    /**
     *洗车机收入
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/20 11:53
     */
    public function carWasherIncome(){
        $post = checkAppData('token,car_washer_id,in_month','token-洗车机ID-月份时间戳');
        /*$post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $post['car_washer_id'] = 1;
        $post['in_month'] = 'all';*/
        if($post['in_month'] == 'all'){
            $post['in_month'] = strtotime(date('Y-m'));
        }
        $agent = $this->getAgentInfo($post['token']);
        $income = M('Income')->where(array('car_washer_id'=>$post['car_washer_id'],'agrnt_id'=>$agent['id'],'month'=>$post['in_month']))->field('net_income,car_wash,day,car_washer_id')->select();
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
                $where['mc_id'] = array('Like', "%" . $post['car_num'] . "%");
            }else{
                $where['mc_id'] = '';
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
            $result = M('CarWasher')->field('address,mc_id,status')->where($where)->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        } else {
            $result = M('CarWasher')->field('address,mc_id,status')->where($where)->order($order)->select();
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
//        $post = checkAppData('token,car_washer_id,day','token-洗车机ID-日期时间戳');
        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $post['car_washer_id'] = 1;
        $post['day'] = 'all';
        if($post['day'] == 'all'){
            $post['day'] = strtotime(date('Y-m-d'));
        }
        $agent = $this->getAgentInfo($post['token']);
//        $income = M('Income')->where(array('car_washer_id'=>$post['car_washer_id'],'agent_id'=>$agent['id'],'day'=>$post['day']))->field('net_income,create_time')->select();
//        foreach ($income as $k=>$v){
            $order = M('CarWasher')->where(array('id'=>$post['car_washer_id'],'agent_id'=>$agent['id']))->field('mc_id,id')->find();
            $order_num = M('Order')->where(array('c_id'=>$order['id'],'o_type'=>1,'status'=>2))->field('orderid as mc_id,pay_money as net_income,pay_time as create_time')->select();
            foreach($order_num as $k=>$v){
                $time[$k] = $v['create_time'];
                $order_num[$k]['car_washer'] = $order['mc_id'];
            }

//        }
        var_dump($time);exit;
        if(!empty($income)){
            $this->apiResponse('1','成功',$income);
        }else{
            $this->apiResponse('0','暂无收入详情');
        }
    }

}
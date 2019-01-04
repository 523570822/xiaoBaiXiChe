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
            $income = D('Income')->where(array('car_washer_id'=>$v['id'],'agent_id'=>$agent['id']))->field('SUM(net_income),SUM(car_wash),car_washer_id')->group('car_washer_id')->find();
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
        $post = checkAppData('token,car_washer_id,month','token-洗车机ID-月份时间戳');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['car_washer_id'] = 1;
//        $post['month'] = '';
        if(empty($post['month'])){
            $post['month'] = strtotime(date('Y-m'));
        }
        $agent = $this->getAgentInfo($post['token']);
        $income = M('Income')->where(array('car_washer_id'=>$post['car_washer_id'],'agrnt_id'=>$agent['id'],'month'=>$post['month']))->field('net_income,car_wash,day,car_washer_id')->select();

        $month = M('Income')->where(array('car_washer_id'=>$post['car_washer_id'],'agrnt_id'=>$agent['id'],'month'=>$post['month']))->field('SUM(net_income),month')->select();
        $data = array(
            'month' => $month,
            'income' => $income,
        );
        if(!empty($income)){
            $this->apiResponse('1','成功',$data);
        }else{
            $this->apiResponse('0','暂无加盟商信息');
        }
    }

    /**
     *收入详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/02 10:22
     */
    public function carIncomeInfo(){
        $post = checkAppData('token,car_washer_id,day','token-洗车机ID-日期时间戳');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['car_washer_id'] = 1;
//        $post['day'] = 1545148800;
        /*if(empty($post['day'])){
            $post['day'] = strtotime(date('Y-m-d'));
        }*/
        $agent = $this->getAgentInfo($post['token']);
        $income = M('Income')->where(array('car_washer_id'=>$post['car_washer_id'],'agrnt_id'=>$agent['id'],'day'=>$post['day']))->field('net_income,create_time')->select();
        foreach ($income as $k=>$v){
            $order = M('CarWasher')->where(array('id'=>$post['car_washer_id']))->field('mc_id')->find();
            $order_num = M('Order')->where(array('mc_id'=>$order['mc_id']))->field('orderid')->find();
            $income[$k]['mc_id'] = $order_num['orderid'];
            $income[$k]['car_washer'] = $order['mc_id'];
        }
        if(!empty($income)){
            $this->apiResponse('1','成功',$income);
        }else{
            $this->apiResponse('0','暂无加盟商信息');
        }
    }
}
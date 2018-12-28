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
        //$post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $agent = $this->getAgentInfo($post['token']);
        $car_washer = D('CarWasher')->field('id,mc_id')->select();
        foreach($car_washer as $k=>$v){
            $income = D('Income')->where(array('car_washer_id'=>$v['id'],'agent_id'=>$agent['id']))->field('SUM(net_income),SUM(car_wash),day,car_washer_id')->group('car_washer_id')->find();
            if(!empty($income)){
                $incomes[] = $income;
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

    }
}
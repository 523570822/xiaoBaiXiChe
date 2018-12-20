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
        $post = checkAppData();
        $car_washer = D('CarWasher')->field('mc_id,car_num,net_income')->select();
        if($car_washer){
            $this->apiResponse('1','成功',$car_washer);
        }else{
            $this->apiResponse('0','暂无数据');
        }
    }

    /**
     *洗车机收入
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/20 11:53
     */
    public function carWasherIncome(){
        $post = checkAppData('agent_id,mc_id,month','代理商ID-洗车机编号-月份');
        /*$post['agent_id'] = 1;
        $post['mc_id'] = 'A00001';
        $post['month'] = '2018-12';*/
        /*$month = date('Y/m',$post['month']);
        var_dump($month);exit;*/

        $where = array(
            'agent_id' =>$post['agent_id'],
            'mc_id' =>$post['mc_id'],
            'month' => strtotime($post['month']),
        );
        $data=D('Income')->where($where)->field("id,SUM(net_income),car_num,day")->group("day")->select();
        if($data){
            $this->apiResponse('1','成功',$data);
        }else{
            $this->apiResponse('2','暂无数据');
        }
    }
}
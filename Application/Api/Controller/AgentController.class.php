<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/20
 * Time: 10:11
 */

namespace Api\Controller;
use Common\Service\ControllerService;


class AgentController extends BaseController
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
     *登录方法
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/18 16:22
     */
    public function login(){
        $post = checkAppData('phone，password','手机号-密码');
        /*$post['phone'] = 17622818248;
        $post['password'] = 123456;*/
        if (!isMobile($post['phone'])) {
            $this->apiResponse('0','手机号格式有误');
        }
        $member = D('Agent')->where(array('account'=>$post['phone']))->find();
        $check_password = checkPassword($post['password'], $member['salt'], $member['password']);
        if ($member) {
            if ($check_password != 1) {
                $this->apiResponse('1','登陆成功',array('token'=>$member['token']));
            }else{
                $this->apiResponse('0','密码错误');
            }
        } else {
            $this->apiResponse('0','用户不存在',array('token'=>''));
        }
    }

    /**
     *收益
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/19 02:01
     */
    public function income(){
        $post = checkAppData();
        $post['time'] = date('Y-m-d');
        $incomel = D('Income')->where(array())->field('id')->select();
        echo D('Income')->_sql();
        if($incomel){
//            foreach($incomel as $k=>$v){
//                $create[] = date('Y-m-d',$v['update_time']);
//            }
            var_dump($incomel);exit;
        }
    }

    /**
     *洗车机
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

}
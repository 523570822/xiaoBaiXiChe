<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/20
 * Time: 10:11
 */

namespace Api\Controller;
use Common\Service\ControllerService;

/**
 * 加盟商模块
 * Class AgentController
 * @package Api\Controller
 */
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
        $post = checkAppData('phone,password','账号-密码');
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
        $post = checkAppData('agent_id,month','代理商ID-月份');
        /*$post['agent_id'] = 1;
        $post['month'] = '2018-12';*/
        /*$month = date('Y/m',$post['month']);
        var_dump($month);exit;*/

        $where = array(
            'agent_id' =>$post['agent_id'],
            'month' => strtotime($post['month']),
        );
        $income = D('Income')->where($where)->field("id,SUM(net_income),car_wash,day")->group("day")->select();
        $agent = D('Agent')->where(array('id'=>$post['agent_id']))->field('car_washer_num')->find();
        $data = array(
            'income' =>$income,
            'agent' =>$agent
        );
        if($data){
            $this->apiResponse('1','成功',$data);
        }else{
            $this->apiResponse('2','暂无数据');
        }
    }



}
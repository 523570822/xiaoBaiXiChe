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
     *日期格式筛选
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/21 11:20
     */
    public function timeType(){
        $post = checkAppData('token','token');
        /*$post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';*/
        $agent = $this->getAgentInfo($post['token']);
        //日筛选
        $day = D('Income')->where(array('agent_id'=>$agent['id']))->field('day,week_star,month,year')->select();
        foreach ($day as $k=>$v){
            $vs[] = $day[$k]['day'];
            $week[] = $day[$k]['week_star'];
            $mon[] = $day[$k]['month'];
            $year[] = $day[$k]['year'];
        }
        $days = array_unique($vs);
        //周筛选
        $weeks = array_unique($week);
        foreach ($weeks as $key=>$value){
            $weekend[] = D('Income')->where(array('agent_id'=>$agent['id'],'week_star'=>$value))->field('week_star,week_end')->find();
        }
        $weeks = $weekend;
        //月筛选
        $month = array_unique($mon);
        //年筛选
        $years = array_unique($year);
        $data = array(
            'day' =>$days,
            'week' =>$weeks,
            'month' =>$month,
            'year' =>$years
        );
        if($data){
            $this->apiResponse('1','成功',$data);
        }else{
            $this->apiResponse('0','错误');

        }
    }

    /**
     *收益
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/19 02:01
     */
    public function income(){
        $a = $this->weeks();
        foreach ($a as $k=>$v){
            $c = $v;
            $b[] = date('Y-m-d',$c);
        }
        var_dump($b);exit;
        $post = checkAppData('token','token');
        /*$post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $post['month'] = '2018-12';*/
        /*$month = date('Y/m',$post['month']);
        var_dump($month);exit;*/
        $agent = $this->getAgentInfo($post['token']);
        $where = array(
            'agent_id' =>$agent['id'],
            'month' => strtotime($post['month']),
        );
        $income = D('Income')->where($where)->field("id,SUM(net_income),car_wash,day")->group("day")->select();
        $data = array(
            'agent' =>$agent['car_washer_num'],
            'income' =>$income,
        );

        if($data){
            $this->apiResponse('1','成功',$data);
        }else{
            $this->apiResponse('2','暂无数据');
        }
    }

    /**/
    public function weeks()
    {
        $timestamp = time();
        return [
            strtotime(date('Y-m-d', strtotime("this week Monday", $timestamp))),
            strtotime(date('Y-m-d', strtotime("this week Sunday", $timestamp))) + 24 * 3600 - 1
        ];
    }




}
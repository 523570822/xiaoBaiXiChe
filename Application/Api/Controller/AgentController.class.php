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
     *修改密码
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/14 01:36
     */
    public function changePassword(){
        $post = checkAppData('token,old_password,password,apassword','token-旧密码-新密码-再一次输新密码');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['old_password'] = 123456;
//        $post['password'] = 123456;
//        $post['apassword'] = 123456;
        if($post['old_password'] == $post['password']){
            $this->apiResponse('1','新旧密码不能一致');
        }
        if($post['password'] != $post['apassword']){
            $this->apiResponse('1','新密码不一致');
        }
        $member = $this->getAgentInfo($post['token']);
//        var_dump($post['old_password']);
//        var_dump($post['password']);
//        var_dump($member);exit;
//        $a = checkPassword($post['old_password'],$member['salt'],$member['password']);
//        var_dump($a);exit;
        $check_password = checkPassword($post['old_password'],$member['salt'],$member['password']);
        if ($check_password != 1) {
//            $password = getPassword($post['password']);
            $data['salt'] = NoticeStr(6);
            $data['password'] = CreatePassword($post['password'], $data['salt']);
            $mem['password'] = $data['password'];
            $mem['salt'] = $data['salt'];
            $mem['update_time'] = time();
            $r = M('Agent')->where(array('id'=>$member['id']))->save($mem);

            if ($r) {
                $this->apiResponse('1','成功');
            } else {
                $this->apiResponse('0','修改失败');
            }
        } else {
            $this->apiResponse('0','原密码错误');
        }
    }

    /**
     *判断周时间
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/21 11:20
     */
    /*public function timeType(){
        $a = $this->weeks();
        foreach ($a as $k=>$v){
            $c = $v;
            $b[] = date('Y-m-d',$c);
        }
//        var_dump($a);exit;
        //$post = checkAppData('token','token');
        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $agent = $this->getAgentInfo($post['token']);
        //日筛选
        $day = D('Income')->where(array('agent_id'=>$agent['id']))->field('id,day,week_star,month,year')->select();
        foreach ($day as $k=>$v){
            $vs[] = date("Y-m-d",$day[$k]['day']);
            $week[] = $day[$k]['week_star'];
            $mon[] = date("Y-m",$day[$k]['month']);
            $year[] = date("Y", $day[$k]['year']);
        }
        $days = array_unique($vs);
        //周筛选
        $weeks = array_unique($week);
        foreach ($weeks as $key=>$value){
            $weekend = D('Income')->where(array('agent_id'=>$agent['id'],'week_star'=>$value))->field('week_star,week_end')->find();
            $weekends[$key] = date('Y-m-d',$weekend['week_star']).'~'.date('Y-m-d',$weekend['week_end']);
        }
        $weeks = $weekends;
        //月筛选
        $month = array_unique($mon);
        //年筛选
        $years = array_unique($year);
        $data = array(
            'day' =>$days,
            'week' =>$weeks,
            'month' =>$month,
            'year' =>$years,
        );
        if($data){
            $this->apiResponse('1','成功',$data);
        }else{
            $this->apiResponse('0','错误');

        }
    }*/

    /**
     *收益
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/19 02:01
     */
    public function income(){
        $post = checkAppData('token,timeType','token-时间筛选');
        /*$post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $post['timeType'] = 4; */                 //查询方式  1日  2周  3月   4年
        /*$month = date('Y/m',$post['month']);
        var_dump($month);exit;*/
        $agent = $this->getAgentInfo($post['token']);
        $car_num = D('CarWasher')->where(array("agent_id"=>$agent['id']))->select();
        //日筛选
        $day = D('Income')->where(array('agent_id'=>$agent['id'],'status'=>1))->field('day,week_star,month,year')->select();
        foreach ($day as $k=>$v){
            $vs[] = date("Y-m-d",$day[$k]['day']);
            $week[] = $day[$k]['week_star'];
            $mon[] = date("Y-m",$day[$k]['month']);
            $year[] = date("Y", $day[$k]['year']);
        }
        $days = array_unique($vs);
        //周筛选
        $weeks = array_unique($week);
        foreach ($weeks as $key=>$value){
            $weekend = D('Income')->where(array('agent_id'=>$agent['id'],'week_star'=>$value))->field('week_star,week_end')->find();
            $weekends[$key] = date('Y-m-d',$weekend['week_star']);
        }
        $weeks = $weekends;
        //月筛选
        $month = array_unique($mon);
        //年筛选
        $years = array_unique($year);
        $where['agent_id'] =  $agent['id'];
        if($post['timeType'] == 1) {
            $where['day'] = strtotime(date('Y-m-d'));
            $where['status'] = 1;
//            var_dump($where['day']);exit;
            $income = D('Income')->where($where)->field("SUM(net_income) as net_income,SUM(car_wash) as car_wash,day as now_day")->group("day")->find();
            if(empty($income['now_day'])) {
                $income['net_income'] = 0;
                $income['car_wash'] = 0;
//                $income['weeks'] = date('Y-m-d',$income['day']);
            }
            $income['now_day'] = date("Y-m-d",$where['day']);
            if($days){
                foreach($days as $dk=>$dv){
                    $dayss[] = strtotime($dv);
                }
                foreach ($dayss as $dkk=>$dvv){
                    $dayIncome = D('Income')->where(array('day'=>$dvv))->field("SUM(net_income) as net_income,day as ago_day")->group("day")->find();
                    $dayIncome['ago_day'] = date("Y-m-d",$dayIncome['ago_day']);
                    $agoDay[] = $dayIncome;
                }
            }
            $data = array(
                'agent' => count($car_num),
                'income' => $income,
                'ag_day' =>$agoDay,
            );
            if ($data) {
                $this->apiResponse('1', '成功', $data);
            }
        } elseif($post['timeType'] == 2) {
            $array = $this->weeks();
            $where['week_star'] = $array['0'];
            $where['status'] = 1;
//            var_dump($where);exit;
            $week_income = D('Income')->where($where)->field("SUM(net_income) as net_income,SUM(car_wash) as car_wash,week_star,week_end")->group("week_star")->find();
//            var_dump($week_income);exit;
            $income['net_income'] = $week_income['net_income'];
            $income['car_wash'] = $week_income['car_wash'];
            if(empty($week_income['week_star'])){
                $income['net_income'] = 0;
                $income['car_wash'] = 0;
            }
            $income['now_day'] = date('Y-m-d',$array['0']).'~'.date('Y-m-d',$array['1']);

            if($weeks){
                foreach($weeks as $wk=>$wv){
                    $weekss[] = strtotime($wv);
                }
                foreach ($weekss as $wkk=>$wvv){
                    $weekIncome = D('Income')->where(array('week_star'=>$wvv))->field("SUM(net_income) as n_net_income,week_star,week_end")->group("week_star")->find();
                    $weekIncomes['net_income'] = $weekIncome['n_net_income'];
                    $weekIncomes['ago_day'] = date("Y-m-d",$weekIncome['week_star']).'~'.date("Y-m-d",$weekIncome['week_end']);
                    $agoWeek[] = $weekIncomes;
                }
            }
            $data = array(
                'agent' => count($car_num),
                'income' => $income,
                'ag_day' =>$agoWeek,
            );
            if ($data) {
                $this->apiResponse('1', '成功', $data);
            }
        } elseif($post['timeType'] == 3) {
            $where['month'] = strtotime(date('Y-m'));
            $where['status'] = 1;
            $income = D('Income')->where($where)->field("SUM(net_income) as net_income,SUM(car_wash) as car_wash,month as now_day")->group("month")->find();
//            var_dump($where['month']);exit;
            if(empty($income['now_day'])){
                $income['net_income'] = 0;
                $income['car_wash'] = 0;
            }
            $income['now_day'] = date('Y-m',$where['month']);
            if($month){
                foreach($month as $mk=>$mv){
                    $months[] = strtotime($mv);
                }
                foreach ($months as $mkk=>$mvv){
                    $monIncome = D('Income')->where(array('month'=>$mvv))->field("SUM(net_income) as net_income,month as ago_day")->group("month")->find();
                    $monIncome['ago_day'] = date("Y-m",$monIncome['ago_day']);
                    $agoMonth[] = $monIncome;
                }
            }
            /*if(empty($agoMonth)){
                $this->apiResponse('暂无收益');
            }*/

            $data = array(
                'agent' => count($car_num),
                'income' => $income,
                'ag_day' =>$agoMonth,
            );
            if ($data) {
                $this->apiResponse('1', '成功', $data);
            }
        }elseif($post['timeType'] == 4) {
            $y = date("Y",1545205151);
            $where['year'] = strtotime($y.'-1-1');
            $where['status'] = 1;
            $income = D('Income')->where($where)->field("SUM(net_income) as net_income,SUM(car_wash) as car_wash,year as now_day")->group("year")->find();
//            echo D('Income')->_sql();
            if(empty($income['now_day'])){
                $income['net_income'] = 0;
                $income['car_wash'] = 0;
            }
            $income['now_day'] = date('Y',$where['year']);
            if($years){
                foreach($years as $yk=>$yv){
                    $yearss[] = strtotime($yv.'-1-1');
                }
                foreach ($yearss as $ykk=>$yvv){
                    $yearIncome = D('Income')->where(array('year'=>$yvv))->field("SUM(net_income) as net_income,year as ago_day")->group("year")->find();
                    $yearIncome['ago_day'] = date("Y",$yearIncome['ago_day']);
                    $agoYear[] = $yearIncome;
                }
            }
            $data = array(
                'agent' => count($car_num),
                'income' => $income,
                'ag_day' =>$agoYear,
            );
            if ($data) {
                $this->apiResponse('1', '成功', $data);
            }
        }

    }

    /**
     *获取时间
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/21 18:37
     */
    public function weeks()
    {
        $timestamp = time();
//        $timestamp = 1545674199;
        return [
            strtotime(date('Y-m-d', strtotime("this week Monday", $timestamp))),
            strtotime(date('Y-m-d', strtotime("this week Sunday", $timestamp))) + 24 * 3600 - 1,
            strtotime(date('Y-m', $timestamp)),
            strtotime(date('Y', $timestamp).'-1-1'),
        ];
    }

    /**
     *加盟商列表
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/25 01:46
     */
    public function agent(){
        $post = checkAppData();
        $agent = D('Agent')->where(array('status'=>1))->field('id,nickname,account')->select();
        foreach($agent as $k=>$v){
            $car[] = D('CarWasher')->where(array('agent_id'=>$v['id']))->field('id')->select();
            foreach($car as $kk=>$vv){
                $car_num = count($vv);
                $agent[$k]['car_num'] = $car_num;
            }
        }
        if(!empty($agent)){
            $this->apiResponse('1','成功',$agent);
        }else{
            $this->apiResponse('1','暂无可查询加盟商');
        }
    }

    /**
     *管理列表
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/14 00:41
     */
    public function management(){

    }

    /**
     *账户明细
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/05 16:07
     */
    /*public function detail(){
        //$post = checkAppData('token','token');
        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $agent = $this->getAgentInfo($post['token']);
        var_dump($agent);exit;
    }*/


}
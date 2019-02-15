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
     *添加
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/18 17:49
     */
//    public function add(){
//        $post['account'] = 18635356098;
//        $post['password'] = 123456;
//        $post['nickname'] = '东南角';
//        $token = $this->createToken();
//        $data['salt'] = NoticeStr(6);;
//        $data['password'] = CreatePassword($post['password'], $data['salt']);
//        $data['account'] = $post['account'];
//        $data['token'] = $token['token'];
//        $data['token'] = $token['token'];
//        $data['nickname'] = $post['nickname'];
//        $data['grade'] = 1;
//
//        $add = M('Agent')->add($data);
//    }

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
     *收益
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/19 02:01
     */
    public function income(){
        $post = checkAppData('token,timeType,page,size','token-时间筛选-页数-个数');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['timeType'] = 1;                 //查询方式  1日  2周  3月   4年
//        $post['page'] = 2;
//        $post['size'] = 10;

        /*$month = date('Y/m',$post['month']);
        var_dump($month);exit;*/
        $agent = $this->getAgentInfo($post['token']);
        $car_where['agent_id'] = $agent['id'];
        $car_where['status'] = array('neq',9);
        $car_num = D('CarWasher')->where($car_where)->select();
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
            $where['day'] = strtotime(date('Y-m-d',1545291552));
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
        $post = checkAppData('token,grade,page,size','token-加盟商等级-页数-个数');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['grade'] = 2;
//        $post['page'] = 3;
//        $post['size'] = 2;

        $agent = $this->getAgentInfo($post['token']);
//        var_dump($agent);exit;
        $where['status'] = 1;
        $where['p_id'] = $agent['id'];
        $where['grade'] = $post['grade'];
        $order[] = 'sort DESC';
        $agent = D('Agent')->where($where)->field('id,nickname,account')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        foreach($agent as $k=>$v){
            $car[] = D('CarWasher')->where(array('agent_id'=>$v['id']))->field('id')->select();
            foreach($car as $kk=>$vv){
                $car_num = count($vv);
                $agent[$k]['car_num'] = $car_num;
            }
        }
//        var_dump($agent);exit;
        if(!empty($agent)){
            $this->apiResponse('1','成功',$agent);
        }else{
            $this->apiResponse('0','暂无可查询加盟商');
        }
    }

    /**
     *加盟商详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/18 16:46
     */
    public function agentInfo(){
        $post = checkAppData('token','token');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $agent = $this->getAgentInfo($post['token']);
        $car_washer = M('CarWasher')->where(array('agent_id'=>$agent['id']))->select();
        $data['car_washer'] = count($car_washer);
        if($agent['grade'] == 1){
            $t_agent = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>2))->field('id')->select();
            foreach ($t_agent as $k=>$v){
                $where['status'] = array('neq',9);
                $where['agent_id'] = $v['id'];
                $t_num[$k] = M('CarWasher')->where($where)->field('agent_id')->select();
                foreach ($t_num[$k] as $k1=>$v1){
                    foreach ($v1 as $k2=>$v2){
                        $tn[$k2] = $v2;
                        $tnumber[] = $tn[$k2];
                    }
                }
            }
//            var_dump($a);exit;
//            var_dump(count($a));exit;
            $s_agent = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>3))->field('id')->select();
            foreach ($s_agent as $kk=>$vv){
                $where['status'] = array('neq',9);
                $where['agent_id'] = $vv['id'];
                $s_num[$kk] = M('CarWasher')->where($where)->field('agent_id')->select();
                foreach ($s_num[$kk] as $kk1=>$vv1){
                    foreach($vv1 as $kk2=>$vv2){
                        $sn[$kk2] = $vv2;
                        $snumber[] = $sn[$kk2];
                    }
                }
            }
            $data['two'] = count($t_agent);
            $data['t_num'] = count($tnumber);
            $data['three'] = count($s_agent);
            $data['s_num'] = count($snumber);
            $data['car_washer'] = count($car_washer)+$data['t_num']+$data['s_num'];

        }
        if($data){
            $this->apiResponse('1','成功',$data);
        }
    }

    /**
     *管理列表
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/14 00:41
     */
    public function management(){
        $post = checkAppData('page,size','页数-个数');

//        $post['page'] = 2;
//        $post['size'] = 2;

        $order[] = 'sort DESC';
        $agent = M('Agent')->where(array('grade'=>1,'status'=>1))->field('id,nickname')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        foreach($agent as $k=>$v){
            $wheres['status'][$k] = array('neq',9);
            $wheres['agent_id'] = $v['id'];
            $car= M('CarWasher')->where($wheres)->field('id,agent_id')->select();
            if(empty($car)){
               $car[0]['agent_id'] = $v['id'];
            }
            $car[0]['nickname'] = $v['nickname'];
            $cars[] = $car;
        }
        $f_where['status'] = array('neq',9);
        $f_where['grade'] = array('neq',1);
        $f_agent = M('Agent')->where($f_where)->field('id,p_id')->select();
        foreach ($cars as $k1=>$v1){
            $agents[] = $v1;
        }
        foreach ($f_agent as $kk=>$vv){
            $f_car['status'] = array('neq',9);
            $f_car['agent_id'] = $vv['id'];
            $f_car_num = M('CarWasher')->where($f_car)->field('id,agent_id')->select();
            $f_car_numss[] = $f_car_num;
            foreach ($f_car_numss as $kk1=>$vv1){
                foreach ($vv1 as $kk5=>$vv5){
                    $where_pid['id'] =  $vv5['agent_id'];
                    $where_pid['status'] =  array('neq',9);
                    $where_pid['grade'] =  array('neq',1);
                    $p_id = M('Agent')->where($where_pid)->field('p_id')->find();
                    $f_car_numss[$kk1][$kk5]['p_id'] = $p_id['p_id'];
                }
                $fcar_nus = $f_car_numss;
            }
        }
        foreach ($agents as $k2=>$v2){
            if(!empty($v2)){
                foreach ($fcar_nus as $kk4=>$vv4){
                    foreach ($vv4 as $key=>$value){
                        if($value['p_id'] == $v2[0]['agent_id']){
                            $a['two_agent'] = $value;
                            $agentNum[$k2]['car_nums'][] = $a['two_agent'];
                        }
                    }
                }
//                var_dump($agentNum);exit;

                foreach ($agentNum as $key1=>$value1){
                    foreach ($value1 as $key2=>$value2){
                        foreach ($value2 as $key3=>$vale3){
                            $anu = $vale3;
                        }

                        $agent_num[$k2]['name'] = $v2[0]['nickname'];
                        $age[$k2]['name'] = $agent_num[$k2]['name'];
                        $agent_num[$k2]['agent_id'] = $v2[0]['agent_id'];
                        $agent_num[$k2]['car_num'] = count($v2);
                        $agent_num[$k2]['car_nums'] = count($value2);
                        if(empty($v2[0]['id'])){
                            $agent_num[$k2]['car_num'] = $agent_num[$k2]['car_num']-1;
                        }
                        if($v2[0]['agent_id'] != $anu['p_id']){
                            $agent_num[$k2]['car_nums'] = 0;
                        }
                        $age[$k2]['car_num'] = $agent_num[$k2]['car_num']+$agent_num[$k2]['car_nums'];

                        $w_total['status'] = array('neq',9);
                        $total = M('CarWasher')->where($w_total)->select();
                        $agent_total = count($total);
                        $age[$k2]['percent'] = sprintf("%.2f",round($age[$k2]['car_num']/$agent_total,2))*100..'%';
                    }
                }
            }
        }
        if($age){
            $this->apiResponse('1','成功',$age);
        }else{
            $this->apiResponse('0','暂无更多数据');
        }
    }

    /**
     *账户明细
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/05 16:07
     */
    public function detail(){
        $post = checkAppData('token','token');
//        $post['token'] = '64a516f028e6fb9cdc7f9dc497f5653a';
        $agent = $this->getAgentInfo($post['token']);
        $car_where['status'] = array('neq',9);
        $car_where['agent_id'] = array('eq',$agent['id']);
        $car = M('CarWasher')->where($car_where)->field('id,agent_id')->select();
        foreach ($car as $k=>$v){
            $in_where['status'] = array('eq',2);
            $in_where['c_id'] = array('eq',$v['id']);
            $in_where['o_type'] = array('eq',1);
            $income = M('Order')->where($in_where)->field('SUM(pay_money) income,id')->find();
            $incomes[] = $income;
        }
        $sum = 0;
        for($i = 0; $i < count($incomes); $i++) {
            $sum += $incomes[$i]['income'];
        }
        if($agent['grade'] == 1){
            $comm = $sum*0.05;
        }elseif($agent['grade'] == 2){
            $comm = $sum*0.10;
        }elseif($agent['grade'] == 3) {
            $comm = $sum * 0.15;
        }
        $income = $sum-$comm;
        $data = array(
            'total' => $sum,     //总收入
            'income' => $income,     //总收入
            'come' => $comm,     //总收入
        );
        if($data){
            $this->apiResponse('1','成功',$data);
        }
    }

    /**
     *收入明细
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/22 14:01
     */
    public function incomeDetail(){
        $post = checkAppData('token,in_month,page,size','token-月份时间戳-页数-个数');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['in_month'] = 'all';
//        $post['page'] = 1;
//        $post['size'] = 10;
        if($post['in_month'] == 'all'){
            $post['in_month'] = strtotime(date('Y-m'));
        }
        $agent = $this->getAgentInfo($post['token']);
        $car_where['status'] = array('neq',9);
        $car_where['agent_id'] = array('eq',$agent['id']);
        $car_where['month'] = $post['in_month'];
        //总净收入
        $income = M('Income')->where($car_where)->field('SUM(net_income) as de_income')->find();

        //每天净收入

        $order[] = 'sort DESC';
        $day_income = M('Income')->where($car_where)->field('day,SUM(net_income) as net_income,SUM(detail) as detail')->group('day')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        foreach ($day_income as $k=>$v){
            $time = strtotime(date('Y-m',$v['day']));
            if($time == $post['in_month']){
                $now_day['day'] = $v['day'];
                $now_day['net_income'] = $v['net_income'];
                $now_day['detail'] = $v['detail'];
                $now_days[] = $now_day;
            }
        }
        $data = array(
            'now_month' => $post['in_month'],
            'income' => $income,
            'day_income' => $now_days,
        );

        if(!empty($income)){
            $this->apiResponse('1','成功',$data);
        }else{
            $this->apiResponse('0','暂无数据信息');
        }
    }

    /**
     *净收入详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/02 10:22
     */
    public function carIncomeInfo(){
        $post = checkAppData('token,day,page,size','token-日期时间戳-页数-个数');
//        $post['token'] = '64a516f028e6fb9cdc7f9dc497f5653a';
//        $post['day'] = 1548000000;
//        $post['page'] = 2;
//        $post['size'] = 10;
        /*if(empty($post['day'])){
            $post['day'] = strtotime(date('Y-m-d'));
        }*/
        $agent = $this->getAgentInfo($post['token']);

        $order[] = 'id ASC';
        $car_washer = M('CarWasher')->where(array('agent_id'=>$agent['id']))->field('id,mc_id')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();

        foreach ($car_washer as $k=>$v){
            $order_num = M('Order')->where(array('c_id'=>$v['id']))->field('c_id,orderid,pay_money as net_income,pay_time')->find();
            $time = strtotime(date('Y-m-d',$order_num['pay_time']));

//            var_dump($time);exit;
            if($time == $post['day']){
                if(!empty($order_num['orderid'])){
                    if($agent['grade'] == 1){
                        $cars[$k]['net_income'] = $order_num['net_income']-$order_num['net_income']*0.05;
                    }elseif($agent['grade'] == 2){
                        $cars[$k]['net_income'] = $order_num['net_income']-$order_num['net_income']*0.10;
                    }elseif($agent['grade'] == 3){
                        $cars[$k]['net_income'] = $order_num['net_income']-$order_num['net_income']*0.15;
                    }

                    $cars[$k]['create_time'] = $order_num['pay_time'];
                    $cars[$k]['mc_id'] = $order_num['orderid'];
                    $cars[$k]['car_washer'] = $v['mc_id'];
                }
            }
        }
//        var_dump($cars);exit;
        if(!empty($cars)){
            $this->apiResponse('1','成功',$cars);
        }else{
            $this->apiResponse('0','暂无收入详情');
        }
    }

    /**
     *提成明细
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/22 14:01
     */
    public function CommDetail(){
        $post = checkAppData('token,in_month,page,size','token-月份时间戳-页数-个数');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['in_month'] = 1543593788;
//        $post['page'] = 1;
//        $post['size'] = 10;
//        if($post['in_month'] == 'all'){
//            $post['in_month'] = strtotime(date('Y-m'));
//        }
        $agent = $this->getAgentInfo($post['token']);
        $car_where['status'] = array('neq',9);
        $car_where['agent_id'] = array('eq',$agent['id']);
        $car_where['month'] = strtotime(date('Y-m',$post['in_month']));
        //总提成
        $income = M('Income')->where($car_where)->field('SUM(net_income) as de_income,SUM(detail) as de_detail')->find();
        $commission = $income['de_detail']-$income['de_income'];

        //每天提成
        $order[] = 'sort DESC';
        $day_income = M('Income')->where($car_where)->field('day,SUM(net_income) as net_income,SUM(detail) as detail')->group('day')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();

        foreach ($day_income as $k=>$v){
            $time = strtotime(date('Y-m',$v['day']));
            if($time == $car_where['month']){
                $now_day['day'] = $v['day'];
                $now_day['net_commission'] = $v['detail']-$v['net_income'];
                $now_days[] = $now_day;
            }
        }
        $data = array(
            'now_month' => $car_where['month'],
            'commission' => $commission,
            'day_commission' => $now_days,
        );

        if(!empty($income)){
            $this->apiResponse('1','成功',$data);
        }else{
            $this->apiResponse('0','暂无数据信息');
        }
    }

    /**
     *提成详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/22 17:16
     */
    public function carCommInfo(){
        $post = checkAppData('token,day,page,size','token-日期时间戳-页数-个数');
//        $post['token'] = '64a516f028e6fb9cdc7f9dc497f5653a';
//        $post['day'] = 1548000000;
//        $post['page'] = 1;
//        $post['size'] = 10;
        /*if(empty($post['day'])){
            $post['day'] = strtotime(date('Y-m-d'));
        }*/
        $agent = $this->getAgentInfo($post['token']);

        $order[] = 'id ASC';
        $car_washer = M('CarWasher')->where(array('agent_id'=>$agent['id']))->field('id,mc_id')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();

        foreach ($car_washer as $k=>$v){
            $order_num = M('Order')->where(array('c_id'=>$v['id']))->field('c_id,orderid,pay_money as net_income,pay_time')->find();
            $time = strtotime(date('Y-m-d',$order_num['pay_time']));

//            var_dump($time);exit;
            if($time == $post['day']){
                if(!empty($order_num['orderid'])){
                    if($agent['grade'] == 1){
                        $cars[$k]['net_income'] = $order_num['net_income']*0.05;
                    }elseif($agent['grade'] == 2){
                        $cars[$k]['net_income'] = $order_num['net_income']*0.10;
                    }elseif($agent['grade'] == 3){
                        $cars[$k]['net_income'] = $order_num['net_income']*0.15;
                    }

                    $cars[$k]['create_time'] = $order_num['pay_time'];
                    $cars[$k]['mc_id'] = $order_num['orderid'];
                    $cars[$k]['car_washer'] = $v['mc_id'];
                }
            }
        }
        if(!empty($cars)){
            $this->apiResponse('1','成功',$cars);
        }else{
            $this->apiResponse('0','暂无详情');
        }
    }

    /**
     *我的详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/19 17:51
     */
    public function myInfo(){
        $post = checkAppData('token','token');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';

        $agent = $this->getAgentInfo($post['token']);

        $my = M('Agent')->where(array('id'=>$agent['id']))->field('account,nickname,balance,grade')->find();
        if($my){
            $this->apiResponse('1','成功',$my);
        }
    }


}
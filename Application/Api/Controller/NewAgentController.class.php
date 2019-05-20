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
 * 新加盟商模块
 * Class NewAgentController
 * @package Api\Controller
 */
class NewAgentController extends BaseController
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
     *首页
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/11 16:03
     */
    public function income(){
        $post = checkAppData('token,timeType,grade,page,size','token-时间筛选-身份-页数-个数');
//        $post['token'] = 'd7b8e3afec48f4b75d1ea8ebb3182845';
//        $post['timeType'] = 1;                 //查询方式  1日  2周  3月   4年
//        $post['grade'] = 4;
//        $post['page'] = 1;
//        $post['size'] = 10000000;

        /*$month = date('Y/m',$post['month']);
        var_dump($month);exit;*/
        $agent = $this->getAgentInfo($post['token']);
//        dump($agent);exit;
        $car_where['agent_id'] = $agent['id'];
        $car_where['status'] = array('neq',9);
        $car = D('CarWasher')->where($car_where)->select();
        //日期筛选
        $array = $this->filter($post['timeType'],$agent['id'],$post['page'],$post['size']);
        //身份筛选
        if($post['grade'] == 1){
            $this->apiResponse('1','区域合伙人无数据');
        }elseif ($post['grade'] == 2){
            $f_agent = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>3))->select();
            foreach($f_agent as $key=>$val){
                $arr = $this->filter($post['timeType'],$val['id'],$post['page'],$post['size']);
                if(!empty($arr)){             //获取下级分润
                    foreach ($arr['data'] as $k1=>$v1){
                        $s_data[] =array(
                            'time' => $v1['time'],
                            'p_money' => $v1['p_money'],
                        );
                    }
                }
            }
            foreach ($array['data'] as $k2=>$v2) {
                if($array['todaytime'] == $array['data'][0]['time']){
                    if($array['todaytime'] == $s_data[0]['time']){
                        $s_money = $s_data[0]['p_money'];
                    }else{
                        $s_money = 0;
                    }
                    $new_income = bcadd($array['data'][0]['net_income'],$s_money,2);
                    $wash_num = $array['data'][0]['car_wash'];
                }else{
                    $new_income = 0;
                    $wash_num = 0;
                }
                $date_time = date("Y-m-d",$v2['time']);
                foreach ($s_data as &$v3){
                    if($v2['time'] == $v3['time']){
                        $s_income = $v3['p_money'];
                    }else{
                        $s_income = 0;
                    }
                }
                $income = bcadd($v2['net_income'],$s_income,2);
                $record = array(
                    'date_time' => $date_time,
                    'income' => $income,
                );
                $records[] = $record;
            }
            $car_num = count($car);
            $result = array(
                'new_income' => $new_income,    //今日收益
                'record' =>$records,             //记录
                'wash_num' => $wash_num,      //洗车数量
                'car_num' => $car_num,      //设备数量
            );
        }elseif ($post['grade'] == 3){
            foreach ($array['data'] as $k=>$v){
                if($array['todaytime'] == $array['data'][0]['time']){
                    $new_income =$array['data'][0]['net_income'];
                    $wash_num = $array['data'][0]['car_wash'];
                }else{
                    $new_income = 0;
                    $wash_num = 0;
                }
                $date_time = date("Y-m-d",$v['time']);
                $income = $v['net_income'];
                $record = array(
                    'date_time' => $date_time,
                    'income' => $income,
                );
                $records[] = $record;
            }
            $car_num = count($car);
            $result = array(
                'new_income' => $new_income,    //今日收益
                'record' =>$records,             //记录
                'wash_num' => $wash_num,      //洗车数量
                'car_num' => $car_num,      //设备数量
            );
        }elseif ($post['grade'] == 4){
            $car_Washer = M('CarWasher')->where(array('partner_id'=>$agent['id'],'status'=>1))->field('id')->select();
            foreach ($car_Washer as $ck=>$cv){

                $order[] = 'create_time DESC';
                if($post['timeType'] == 1){
                    $month = strtotime(date ('Y-m' , time()));      //当前月份  查找本月份数据
                    $datas[] = M('Income')->where(array('car_washer_id'=>$cv['id'],'status'=>1,'month'=>$month))->field('id,agent_id,day as time,SUM(partner_money) as partner_money,create_time')->group("day")->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
                    $todaytime=strtotime("today");
                }elseif($post['timeType'] == 2){
                    $month = strtotime(date ('Y-m' , time()));
                    $datas[] = M('Income')->where(array('car_washer_id'=>$cv['id'],'status'=>1,'month'=>$month))->field('id,agent_id,week_star as time,SUM(partner_money) as partner_money,create_time')->group("week_star")->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
                    $todaytime = strtotime (date ('Y-m-d' , strtotime ("this week Monday" , time())));
                }elseif($post['timeType'] == 3){
                    $year = strtotime (date ('Y' , time()) . '-1-1');
                    $datas[] = M('Income')->where(array('car_washer_id'=>$cv['id'],'status'=>1,'year'=>$year))->field('id,agent_id,month as time,SUM(partner_money) as partner_money,create_time')->group("month")->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
                    $todaytime = strtotime (date ('Y-m' , time()));    //月份
                }elseif($post['timeType'] == 4){
                    $datas[] = M('Income')->where(array('car_washer_id'=>$cv['id'],'status'=>1))->field('id,agent_id,year as time,SUM(partner_money) as partner_money,create_time')->group("year")->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
                    $todaytime = strtotime (date ('Y' , time()) . '-1-1');     //年份
                }
            }
            foreach ($datas as &$dv){
                foreach($dv as $dv1){
                    $taskData[] = $dv1;
                }
            }
            $item=[];
            foreach($taskData as $k4=>$v4) {
                if (!isset($item[$v4['time']])) {
                    $item[$v4['time']] = $v4;
                } else {
                    $item[$v4['time']]['partner_money'] += $v4['partner_money'];
                }
            }
            foreach ($item as $k=>$v){
                if($todaytime == $item[$todaytime]['time']){
                    $new_income =$item[$todaytime]['partner_money'];
                }else{
                    $new_income = 0;
                }
                $date_time = date("Y-m-d",$v['time']);
                $income = $v['partner_money'];
                $record = array(
                    'date_time' => $date_time,
                    'income' => $income,
                );
                $records[] = $record;
            }
            $result = array(
                'new_income' => $new_income,    //今日收益
                'record' =>$records,             //记录
            );
        }
        $this->apiResponse('1','查询成功',$result);

    }

    /**
     *净收入详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/15 14:39
     */
    public function incomeInfo(){
        $post = checkAppData('token,day,page,size','token-日期时间戳-页数-个数');
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
//        $post['day'] = 1555862400;
//        $post['page'] = 2;
//        $post['size'] = 10;
        if(empty($post['day'])){
            $post['day'] = strtotime(date('Y-m-d'));
        }else{
            $post['day'] = strtotime(date('Y-m-d',$post['day']));
        }
        $agent = $this->getAgentInfo($post['token']);
        $order[] = 'create_time ASC';
        $income = M('Income')->where(array('agent_id'=>$agent['id'],'day'=>$post['day']))->field('orderid,net_income,detail,car_washer_id,create_time')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        foreach($income as $k=>$v){
            $mc_code = M('CarWasher')->where(array('id'=>$v['car_washer_id']))->field('mc_code')->find();
            $income[$k]['car_washer_id'] = $mc_code['mc_code'];
        }
        if(!empty($income)){
            $this->apiResponse('1','成功',$income);
        }else{
            $this->apiResponse('0','暂无收入详情');
        }
    }

    /**
     *筛选日期
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/14 10:38
     */
    public function filter($time = 1,$agent_id,$page=1,$size=15){
        if(empty($agent_id)){
            $this->apiResponse('0','代理商不能为空');
        }
        $order[] = 'create_time DESC';
        if($time == 1){
            $month = strtotime(date ('Y-m' , time()));      //当前月份  查找本月份数据
            $data = M('Income')->where(array('agent_id'=>$agent_id,'status'=>1,'month'=>$month))->field('id,agent_id,day as time,SUM(net_income) as net_income,SUM(p_money) as p_money,SUM(car_wash) as car_wash,create_time')->group("day")->order($order)->limit(($page - 1) * $size, $size)->select();
            $todaytime=strtotime("today");
        }elseif($time == 2){
            $month = strtotime(date ('Y-m' , time()));
            $data = M('Income')->where(array('agent_id'=>$agent_id,'status'=>1,'month'=>$month))->field('id,agent_id,week_star as time,SUM(net_income) as net_income,SUM(p_money) as p_money,SUM(car_wash) as car_wash,create_time')->group("week_star")->order($order)->limit(($page - 1) * $size, $size)->select();
            $todaytime = strtotime (date ('Y-m-d' , strtotime ("this week Monday" , time())));
        }elseif($time == 3){
            $year = strtotime (date ('Y' , time()) . '-1-1');
            $data = M('Income')->where(array('agent_id'=>$agent_id,'status'=>1,'year'=>$year))->field('id,agent_id,month as time,SUM(net_income) as net_income,SUM(p_money) as p_money,SUM(car_wash) as car_wash,create_time')->group("month")->order($order)->limit(($page - 1) * $size, $size)->select();
            $todaytime = strtotime (date ('Y-m' , time()));    //月份
        }elseif($time == 4){
            $data = M('Income')->where(array('agent_id'=>$agent_id,'status'=>1))->field('id,agent_id,year as time,SUM(net_income) as net_income,SUM(p_money) as p_money,SUM(car_wash) as car_wash,create_time')->group("year")->order($order)->limit(($page - 1) * $size, $size)->select();
            $todaytime = strtotime (date ('Y' , time()) . '-1-1');     //年份
        }
        if(!empty($data)){
            $array = array(
                'data' => $data,
                'todaytime' => $todaytime,
            );
        }
        return $array;
    }

    /**
     *代理商洗车机数量
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/15 16:52
     */
    public function caNum(){
        $post = checkAppData('token,grade','token-等级');
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
//        $post['grade'] = 2;

        $agent = $this->getAgentInfo($post['token']);
        $car = M('CarWasher')->where(array('agent_id'=>$agent['id']))->select();
        if($post['grade'] == 1){
            $t_adent = M('Agent')->where(array('p_id'=>$agent['id']))->select();
            foreach($t_adent as &$v){
                $t_car = M('CarWasher')->where(array('agent_id'=>$v['id']))->field('id')->select();
                if(!empty($t_car)){
                    $t_cars[] = $t_car;
                }
            }
//            $a = count($t_cars);
            foreach ($t_cars as &$vv){
                foreach ($vv as &$vvs){
                    $car_num[] = $vvs;
                }
            }
        }
        $data = array(
            'car_num' => count($car),
            't_agent' => count($t_adent),
            't_car' => count($car_num),
        );
        if(!empty($car) || !empty($t_adent) || !empty($car_num)){
            $this->apiResponse(1,'查询成功',$data);
        }else{
            $this->apiResponse(0,'暂无数据');
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
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
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
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
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
        $post = checkAppData('token,page,size,grade','token-页数-个数-身份');
//        $post['token'] = 'd7b8e3afec48f4b75d1ea8ebb3182845';
//        $post['page'] = 24;
//        $post['size'] = 10;
//        $post['grade'] = 3;    //1 一级代理商    2  二级代理商   3合作方

        $agent = $this->getAgentInfo($post['token']);
        $car_where['status'] = array('neq',9);
        $car_where['agent_id'] = array('eq',$agent['id']);
        $car_where['grade'] = array('neq',4);
        //寻找一级代理商下的二级代理商
        $p_money = array();
        $partner = array();
        if($post['grade'] == 1){
            $f_agent = M('Agent')->where(array('p_id'=>$agent['id'],'status'=>array('neq',9),'grade'=>3))->field('id')->select();
            foreach($f_agent as $kk=>$vv){
                $f_income = M('Income')->where(array('agent_id'=>$vv['id'],'status'=>array('neq',9)))->field('p_money as money,create_time')->select();
                if(!empty($f_income)){
                    //给分润增加类别
                    foreach ($f_income as $f=>$m){
                        $f_income[$f]['type'] = 3;     //上级代理商分润
                        $f_income[$f]['money'] = '+'.$f_income[$f]['money'];     //上级代理商分润
                    }
                    //分润为0去除
                    if($m['money'] != 0){
                        $ft_income[] = $f_income;
                    }
                }
            }
            foreach ($ft_income as $kk1=>$vv1){
                foreach ($vv1 as $kk2=>$vv2){
                    $p_money[] = $vv2;
                }
            }
        }elseif ($post['grade'] == 3) {
            $f_car = M('CarWasher')->where(array('partner_id' => $agent['id'], 'status' => array('neq', 9)))->field('id')->select();
            foreach ($f_car as $kkk => $vvv) {
                $h_income[] = M('Income')->where(array('car_washer_id' => $vvv['id']))->field('partner_money as money,create_time')->select();
            }
            foreach ($h_income as $hk=>$hv){
                foreach ($hv as $hks=>$hvs){
                    $hvs['money'] = '+'.$hvs['money'];
                    $hvs['type'] = 4;
                    if($hvs['money'] != 0){
                        $partner[] = $hvs;
                    }
                }
            }
        }
        $income = M('Income')->where($car_where)->field('net_income as money,create_time')->select();
        $withdraw = M('Withdraw')->where($car_where)->field('money,create_time')->select();

        foreach($income as $k=>$v){
            $income[$k]['money'] = '+'.$income[$k]['money'];
            $income[$k]['type'] = 1;    //代表账单收入
        }
        foreach($withdraw as $k1=>$v1){
            $withdraw[$k1]['money'] = '-'.$withdraw[$k1]['money'];
            $withdraw[$k1]['type'] = 2;    //代表提现

        }

        $list=array_merge($income,$withdraw,$p_money,$partner);
        $lists = list_sort_by($list, 'create_time', 'desc');
        for($i = ($post['page'] - 1) * $post['size']; $i < $post['page'] * $post['size']; $i++){
            $datas[] = $lists[$i];
        }
        foreach ($datas as $k3=>$v3) {
            if(!empty($datas[$k3])){
                $data = $datas[$k3];
                $datass[] = $data;
            }
        }

        if($data){
            $this->apiResponse('1','成功',$datass);
        }else{
            $this->apiResponse('1','暂无数据');
        }
    }

    /**
     *明细汇总
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/17 09:59
     */
    public function summary(){
        $post = checkAppData('token,grade','token-等级');
//        $post['token'] = 'd7b8e3afec48f4b75d1ea8ebb3182845';
//        $post['grade'] = 3;           //1一级代理商   2二级代理商   3合作方
        $agent = $this->getAgentInfo($post['token']);

        if($post['grade'] == 1){
            $n_income = M('Income')->where(array('agent_id'=>$agent['id']))->field('SUM(net_income) as net_income,SUM(detail) as detail,SUM(platform) as platform,SUM(partner_money) as partner_money,SUM(plat_money) as plat_money')->find();
            $trade = bcsub($n_income['detail'],$n_income['platform'],2);
            $f_tagent = M('Agent')->where(array('p_id'=>$agent['id']))->field('id')->select();
            foreach($f_tagent as $k=>$v){
                $f_tincome = M('Income')->where(array('agent_id'=>$v['id']))->field('SUM(p_money) as p_money')->find();
                if($f_tincome['p_money'] != 0){
                    $p_money[] = $f_tincome['p_money'];
                }
            }
            $p_money = (string)array_sum($p_money);
            $all_money = bcadd($n_income['net_income'],$p_money,2);
            $n_income['p_money'] = '';
            $all_partner = '';
        }elseif ($post['grade'] == 2){
            $n_income = M('Income')->where(array('agent_id'=>$agent['id']))->field('SUM(net_income) as net_income,SUM(detail) as detail,SUM(platform) as platform,SUM(partner_money) as partner_money,SUM(plat_money) as plat_money,SUM(p_money) as p_money')->find();
            $trade = bcsub($n_income['detail'],$n_income['platform'],2);
            $p_money = '';
            $all_money = '';
            $all_partner = '';
        }elseif ($post['grade'] == 3){
            $car = M('CarWasher')->where(array('partner_id'=>$agent['id']))->field('id')->select();
            foreach ($car as $ck=>$cv){
                $h_income = M('Income')->where(array('car_washer_id'=>$cv['id']))->field('SUM(partner_money) as partner_money')->find();
                $a[] = $h_income['partner_money'];
            }
            $all_partner = (string)array_sum($a);
            $n_income['p_money'] = '';
            $n_income['net_income'] = '';
            $trade = '';
            $p_money = '';
            $all_money = '';
            $n_income['partner_money'] = '';
            $n_income['plat_money'] = '';
        }
        $data = array(
            'net_income' => $n_income['net_income'],    //净收入
            'trade' => $trade,         //营业收入
            'p_money' => $p_money,          //下级分润收入
            'all_money' => $all_money,       //总收入
            'partner_money' => $n_income['partner_money'],                //合作方分润
            'plat_money' => $n_income['plat_money'],                    //平台分润
            'below' => $n_income['p_money'],                    //上级分润支出
            'all_partner' => $all_partner,                 //总分润收入
        );
        if($data){
            $this->apiResponse('1','查询成功',$data);
        }else{
            $this->apiResponse('1','暂无数据');
        }
    }



    /**
     *一级代理商明细详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/17 16:13
     */
    public function oneDetail(){
        $post = checkAppData('token,in_month,page,size','token-月份时间戳-页数-个数');
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
//        $post['in_month'] = 1558082005;
//        $post['page'] = 1;
//        $post['size'] = 10;
        if($post['in_month'] == ''){
            $post['in_month'] = strtotime(date('Y-m'));
        }
        $agent = $this->getAgentInfo($post['token']);
        if($agent['grade'] != 2){
            $this->apiResponse('0','您不是一级代理商');
        }
        $car_where['status'] = array('neq',9);
        $car_where['agent_id'] = array('eq',$agent['id']);
        $car_where['month'] =strtotime(date('Y-m',$post['in_month'])) ;
        //总净收入
        $month_income = M('Income')->where($car_where)->field('SUM(net_income) as net_income,month')->group("month")->find();
        $month_income['p_money'] = '';
        $day_income = M('Income')->where($car_where)->field('SUM(detail) as detail,SUM(net_income) as net_income,SUM(plat_money) as plat_money,SUM(partner_money) as partner_money,SUM(platform) as platform,day')->group("day")->select();
        foreach ($day_income as &$dv){
            $dv['p_money'] = 0;
            $dv['open'] = bcsub ($dv['detail'],$dv['platform'],2);   //营业收入
        }
        $under = M('Agent')->where(array('p_id'=>$agent['id']))->field('id')->select();
        foreach($under as $uk=>$uv){
            $under_income = M('Income')->where(array('agent_id'=>$uv['id'],'month'=>$car_where['month']))->field('SUM(p_money) as p_money,day')->group("day")->order('day DESC')->select();
            if(!empty($under_income)){
                $under_incomes[] = $under_income;
            }
        }
//            $a = array_merge_rec($under_income);
        foreach($under_incomes as $uk1=>$uv1){
            foreach($uv1 as &$uv2){
                $uv2['detail'] = 0;
                $uv2['net_income'] = 0;
                $uv2['plat_money'] = 0;
                $uv2['partner_money'] = 0;
                $uv2['platform'] = 0;
                $uv2['open'] = 0;
                $unders[] = $uv2;
            }
        }
        $new_under = array_merge($unders,$day_income);
        //相同键值相加形成新数组
        $result = array();
        foreach($new_under as $key=>$value){
            $month_income['p_money'] += $value['p_money'];
            if(!isset($result[$value['day']])){
                $result[$value['day']]=$value;
            }else{
                $result[$value['day']]['p_money']+=$value['p_money'];
                $result[$value['day']]['detail']+=$value['detail'];
                $result[$value['day']]['net_income']+=$value['net_income'];
                $result[$value['day']]['plat_money']+=$value['plat_money'];
                $result[$value['day']]['partner_money']+=$value['partner_money'];
                $result[$value['day']]['platform']+=$value['platform'];
                $result[$value['day']]['open']+=$value['open'];
            }
        }
        $month_income['p_money'] = (string)$month_income['p_money'];
        //数组按时间排序
        array_multisort(i_array_column($result,'day'),SORT_DESC,$result);
        $data = array(
            'all_income' => $month_income,
            'now_income' => $result,
        );
        if($result){
            $this->apiResponse(1,'查询成功',$data);
        }
    }

    /**
     *二级代理商明细详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/20 15:12
     */
    public function twoDetail(){
        $post = checkAppData('token,in_month,page,size','token-月份时间戳-页数-个数');
//        $post['token'] = '5ecb3d16004f758c566a350346e0454b';
//        $post['in_month'] = 1558082005;
//        $post['page'] = 1;
//        $post['size'] = 10;
        if($post['in_month'] == ''){
            $post['in_month'] = strtotime(date('Y-m'));
        }
        $agent = $this->getAgentInfo($post['token']);
        if($agent['grade'] != 3){
            $this->apiResponse('0','您不是二级代理商');
        }
        $car_where['status'] = array('neq',9);
        $car_where['agent_id'] = array('eq',$agent['id']);
        $car_where['month'] =strtotime(date('Y-m',$post['in_month'])) ;
        //总净收入
        $month_income = M('Income')->where($car_where)->field('SUM(net_income) as net_income,SUM(p_money) as p_money,month')->group("month")->find();
        //日净收入
        $day_income = M('Income')->where($car_where)->field('SUM(net_income) as net_income,SUM(plat_money) as plat_money,SUM(partner_money) as partner_money,SUM(p_money) as p_money,SUM(platform) as platform,day')->group("day")->order('day DESC')->select();
        $data = array(
            'all_income' => $month_income,
            'now_income' => $day_income,
        );
        if($data){
            $this->apiResponse(1,'查询成功',$data);
        }

    }


        /**
     *净收入详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/02 10:22
     */
    public function carIncomeInfosss(){
        $post = checkAppData('token,day,page,size','token-日期时间戳-页数-个数');
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
//        $post['day'] = 1548000000;
//        $post['page'] = 2;
//        $post['size'] = 10;
        /*if(empty($post['day'])){
            $post['day'] = strtotime(date('Y-m-d'));
        }*/
        $agent = $this->getAgentInfo($post['token']);

        $order[] = 'id ASC';
        $car_washer = M('CarWasher')->where(array('agent_id'=>$agent['id']))->field('id,mc_code as mc_id')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();

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
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
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

        if(!empty($now_days)){
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
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
//        $post['day'] = 1550073600;
//        $post['page'] = 1;
//        $post['size'] = 1;
        /*if(empty($post['day'])){
            $post['day'] = strtotime(date('Y-m-d'));
        }*/
        $agent = $this->getAgentInfo($post['token']);

        $order[] = 'id ASC';
        $car_washer = M('CarWasher')->where(array('agent_id'=>$agent['id']))->field('id,mc_code as mc_id')->select();

        foreach ($car_washer as $k=>$v){
            $order_num = M('Order')->where(array('c_id'=>$v['id']))->field('c_id,orderid,pay_money as net_income,pay_time')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
            foreach ($order_num as $kkk=>$vvv) {
                $time = strtotime(date('Y-m-d', $vvv['pay_time']));

                if ($time == $post['day']) {
                    if (!empty($vvv['orderid'])) {
                        if ($agent['grade'] == 1) {
                            $cars[$kkk]['net_income'] = $vvv['net_income'] * 0.05;
                        } elseif ($agent['grade'] == 2) {
                            $cars[$kkk]['net_income'] = $vvv['net_income'] * 0.10;
                        } elseif ($agent['grade'] == 3) {
                            $cars[$kkk]['net_income'] = $vvv['net_income'] * 0.15;
                        }
                        $cars[$kkk]['create_time'] = $vvv['pay_time'];
                        $cars[$kkk]['mc_id'] = $vvv['orderid'];
                        $cars[$kkk]['car_washer'] = $v['mc_id'];

//                        $net_income = $cars[$kkk]['net_income'];
//                        $create_time = $cars[$kkk]['create_time'];
//                        $car_washerss = $cars[$kkk]['car_washer'];
                    }
                }
            }

//            $v['net_income'] = $net_income;
//            $v['create_time'] = $create_time;
//            $v['car_washer'] = $car_washerss;
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
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';

        $agent = $this->getAgentInfo($post['token']);

        $my = M('Agent')->where(array('id'=>$agent['id']))->field('account,nickname,balance,grade')->find();
        if($my){
            $this->apiResponse('1','成功',$my);
        }
    }


}
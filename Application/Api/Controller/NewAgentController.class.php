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
//        $post = checkAppData('token,timeType,grade,page,size','token-时间筛选-身份-页数-个数');
        $post['token'] = '5ecb3d16004f758c566a350346e0454b';
        $post['timeType'] = 2;                   //查询方式  1日  2周  3月   4年
        $post['grade'] = 3;                      //1区域合作人 2一级代理商 3二级代理商 4合作方
        $post['page'] = 1;
        $post['size'] = 10000000;

        /*$month = date('Y/m',$post['month']);
        var_dump($month);exit;*/
        $agent = $this->getAgentInfo($post['token']);
        if($agent['grade'] != $post['grade']){
            $this->apiResponse(0,'您的身份信息有误');
        }

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
                if($post['timeType'] == 4){
                    $year_time = $array['data'][0]['time'] . '-1-1';
                    $array_time = strtotime($year_time );
                }else{
                    $array_time = strtotime($array['data'][0]['time']);
                }
                if($array['todaytime'] == $array_time){      //当前时间戳相等
                    if($array['todaytime'] == $s_data[0]['time']){          //时间戳与一级代理商时间戳相等
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
                $date_time = $v2['time'];
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
                'news_income' => $new_income,    //今日收益
                'record' =>$records,             //记录
                'wash_num' => $wash_num,      //洗车数量
                'car_num' => $car_num,      //设备数量
            );
        }elseif ($post['grade'] == 3){
            foreach ($array['data'] as $k=>$v){
                if($post['timeType'] == 4){
                    $year_time = $array['data'][0]['time'] . '-1-1';
                    $array_time = strtotime($year_time );
                }else{
                    $array_time = strtotime($array['data'][0]['time']);
                }
                if($array['todaytime'] == $array_time){
                    $new_income =$array['data'][0]['net_income'];
                    $wash_num = $array['data'][0]['car_wash'];
                }else{
                    $new_income = 0;
                    $wash_num = 0;
                }
                $date_time = $v['time'];
                $income = $v['net_income'];
                if(!empty($income)){
                    $record = array(
                        'date_time' => $date_time,
                        'income' => $income,
                    );
                }else{
                    $record = '';
                }
                $records[] = $record;
            }
            $car_num = count($car);
            $result = array(
                'news_income' => $new_income,    //今日收益
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
                    $datas[] = M('Income')->where(array('car_washer_id'=>$cv['id'],'status'=>1,'month'=>$month))->field('id,agent_id,week_star as time,week_end,SUM(partner_money) as partner_money,create_time')->group("week_star")->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
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
                if($post['timeType'] == 1){
                    $date_time = date('Y-m-d',$v['time']);
                }elseif($post['timeType'] == 2){
                    $date_time = date('Y-m-d',$v['time']).'~'.date('Y-m-d',$v['week_end']);
                }elseif($post['timeType'] == 3){
                    $date_time = date('Y-m',$v['time']);
                }elseif($post['timeType'] == 4){
                    $date_time = date('Y',$v['time']);
                }
                $income = $v['partner_money'];
                $record = array(
                    'date_time' => $date_time,
                    'income' => $income,
                );
                $records[] = $record;
            }
            $result = array(
                'news_income' => $new_income,    //今日收益
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
//        $post['token'] = '5ecb3d16004f758c566a350346e0454b';
//        $post['day'] = 1558972800;
//        $post['page'] = 1;
//        $post['size'] = 10;
        if(empty($post['day'])){
            $post['day'] = strtotime(date('Y-m-d'));
        }else{
            $post['day'] = strtotime(date('Y-m-d',$post['day']));
        }
        $agent = $this->getAgentInfo($post['token']);
        $order[] = 'create_time ASC';
        $income = M('Income')->where(array('agent_id'=>$agent['id'],'day'=>$post['day']))->field('orderid,net_income,detail,car_washer_id,create_time')->order($order)->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
//        echo M('Income')->_sql();
//        dump($income);exit;
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
            if(empty($data)){
                $data[0] = array(
                    'net_income' => 0,
                    'p_money' => 0,
                    'car_wash' => 0,
                );
            }
            $todaytime=strtotime("today");
            foreach ($data as &$v){
                $v['time'] = date ('Y-m-d' , $v['time']);
            }
        }elseif($time == 2){
            $month = strtotime(date ('Y-m' , time()));
//            $month = 1556640000;
            $data = M('Income')->where(array('agent_id'=>$agent_id,'status'=>1,'month'=>$month))->field('month,id,agent_id,week_star as time,week_end,SUM(net_income) as net_income,SUM(p_money) as p_money,SUM(car_wash) as car_wash,create_time')->group("week_star")->order($order)->limit(($page - 1) * $size, $size)->select();
            if(empty($data)){
                $data[0] = array(
                    'net_income' => 0,
                    'p_money' => 0,
                    'car_wash' => 0,
                );
            }
            //            echo M('Income')->_sql();
//            dump($data);exit;
            $todaytime = strtotime (date ('Y-m-d' , strtotime ("this week Monday" , time())));
            foreach ($data as &$v){
                $v['time'] = date ('Y-m-d' , $v['time']).'~'.date ('Y-m-d' , $v['week_end']);
            }
        }elseif($time == 3){
            $year = strtotime (date ('Y' , time()) . '-1-1');
            $data = M('Income')->where(array('agent_id'=>$agent_id,'status'=>1,'year'=>$year))->field('id,agent_id,month as time,SUM(net_income) as net_income,SUM(p_money) as p_money,SUM(car_wash) as car_wash,create_time')->group("month")->order($order)->limit(($page - 1) * $size, $size)->select();
            $todaytime = strtotime (date ('Y-m' , time()));    //月份
            foreach ($data as &$v){
                $v['time'] = date ('Y-m' , $v['time']);
            }
        }elseif($time == 4){
            $data = M('Income')->where(array('agent_id'=>$agent_id,'status'=>1))->field('id,agent_id,year as time,SUM(net_income) as net_income,SUM(p_money) as p_money,SUM(car_wash) as car_wash,create_time')->group("year")->order($order)->limit(($page - 1) * $size, $size)->select();
            foreach ($data as &$v){
                $v['time'] = date ('Y' , $v['time']);
            }
            $todaytime = strtotime (date ('Y' , time()) . '-1-1');     //年份
        }
        if(!empty($data)) {
//            echo 231;exit;
            $array = array(
                'data' => $data,
                'todaytime' => $todaytime,
            );
        }else{
            $array = array(
                'data' => 0,
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
        $post = checkAppData('token,page,size,grade,status','token-页数-个数-身份-状态');
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
//        $post['page'] = 1;
//        $post['size'] = 400;
//        $post['grade'] = 1;    //1 一级代理商    2  二级代理商   3合作方
//        $post['status'] = 2;    //1 全部    2  收入   3支出

//        $request = $_REQUEST;
//        $post['status'] = $request['status'];

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

        if($post['status'] == 1){
            $list=array_merge($income,$withdraw,$p_money,$partner);
        }elseif($post['status'] == 2){
            $list=array_merge($income,$p_money,$partner);
        }elseif($post['status'] == 3){
            $list=array_merge($withdraw);
        }

        $lists = list_sort_by($list, 'create_time', 'desc');
        for($i = ($post['page'] - 1) * $post['size']; $i < $post['page'] * $post['size']; $i++){
            if(!empty($lists[$i])){
                $datas[] = $lists[$i];
            }
        }

        if($datas){
            $this->apiResponse('1','成功',$datas);
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
//        $post['token'] = '5ecb3d16004f758c566a350346e0454b';
//        $post['grade'] = 1;            //1一级代理商   2二级代理商   3合作方
        $agent = $this->getAgentInfo($post['token']);

        if($post['grade'] == 1){
            if($agent['grade'] != 2){
                $this->apiResponse(0,'您的身份不是一级代理商');
            }
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
            if($agent['grade'] != 3){
                $this->apiResponse(0,'您的身份不是二级代理商');
            }
            $n_income = M('Income')->where(array('agent_id'=>$agent['id']))->field('SUM(net_income) as net_income,SUM(detail) as detail,SUM(platform) as platform,SUM(partner_money) as partner_money,SUM(plat_money) as plat_money,SUM(p_money) as p_money')->find();
            $trade = bcsub($n_income['detail'],$n_income['platform'],2);
            $p_money = '';
            $all_money = '';
            $all_partner = '';
        }elseif ($post['grade'] == 3){
            if($agent['grade'] != 4){
                $this->apiResponse(0,'您的身份不是合作方');
            }
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
        $post = checkAppData('token,page,size','token-页数-个数');
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
//        $post['page'] = 1;
//        $post['size'] = 10;

        $request = $_REQUEST;
        $post['in_month'] = $request['in_month'];

//        $post['in_month'] = 1553448192;


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
        if(empty($month_income)){
            $month_income['net_income'] = 0;
        }
        $month_income['p_money'] = '';
        $day_income = M('Income')->where($car_where)->field('SUM(detail) as detail,SUM(net_income) as net_income,SUM(plat_money) as plat_money,SUM(partner_money) as partner_money,SUM(platform) as platform,day')->group("day")->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
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
        }else{
            $data = array(
                'all_income' => array(),
                'now_income' => array(),
            );
            $this->apiResponse(1,'暂无数据',$data);

        }
    }

    /**
     *二级代理商明细详情
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/20 15:12
     */
    public function twoDetail(){
        $post = checkAppData('token,page,size','token-页数-个数');
//        $post['token'] = '5ecb3d16004f758c566a350346e0454b';
//        $post['page'] = 1;
//        $post['size'] = 10;
        $request = $_REQUEST;
        $post['in_month'] = $request['in_month'];
//        $post['in_month'] = 1558082005;

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
        $day_income = M('Income')->where($car_where)->field('SUM(detail) as detail,SUM(net_income) as net_income,SUM(plat_money) as plat_money,SUM(partner_money) as partner_money,SUM(p_money) as p_money,SUM(platform) as platform,day')->group("day")->order('day DESC')->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        foreach($day_income as &$dv){
            $dv['open'] = bcsub ($dv['detail'],$dv['platform'],2);   //营业收入
        }
        $data = array(
            'all_income' => $month_income,
            'now_income' => $day_income,
        );
        if(!empty($day_income)){
            $this->apiResponse(1,'查询成功',$data);
        }else{
            $this->apiResponse(0,'暂无数据');
        }
    }

    /**
     *合作方分润明细
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/20 17:24
     */
    public function partnerDetail(){
        $post = checkAppData('token,page,size','token-页数-个数');
//        $post['token'] = 'd7b8e3afec48f4b75d1ea8ebb3182845';
//        $post['page'] = 1;
//        $post['size'] = 10;

        $request = $_REQUEST;
        $post['in_month'] = $request['in_month'];
//        $post['in_month'] = 1535082005;

        if($post['in_month'] == ''){
            $post['in_month'] = strtotime(date('Y-m'));
        }
        $agent = $this->getAgentInfo($post['token']);
        if($agent['grade'] != 4){
            $this->apiResponse('0','您不是合作方');
        }
        $car_where['status'] = array('neq',9);
        $car_where['partner_id'] = array('eq',$agent['id']);
        $month =strtotime(date('Y-m',$post['in_month'])) ;
        $car = M('CarWasher')->where($car_where)->field('id')->select();
        foreach ($car as &$v){
            //总分润
            $income = M('Income')->where(array('car_washer_id'=>$v['id'],'month'=>$month))->field('SUM(partner_money) as partner_money,month')->group('month')->find();
            if($income['partner_money'] != 0){
                $incomes[] = $income['partner_money'];
            }
            //日分润
            $day_income[] = M('Income')->where(array('car_washer_id'=>$v['id'],'month'=>$month))->field('SUM(partner_money) as partner_money,day')->group('day')->order('day DESC')->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();

        }
        foreach($day_income as &$dv){
            foreach ($dv as $dk2 => $dv2){
                $new_under[] = $dv2;
            }
        }
        //相同键值相加形成新数组
        $result = array();
        foreach($new_under as $key=>$value){
            if(!isset($result[$value['day']])){
                $result[$value['day']]=$value;
            }else{
                $result[$value['day']]['partner_money']+=$value['partner_money'];
            }
        }
        $now_income = array_values($result);
        $all_income['month'] = $month;
        $all_income['partner_money'] = (string)array_sum($incomes);

        $data = array(
            'all_income' => $all_income,
            'now_income' => $now_income,
        );
        if(!empty($now_income)){
            $this->apiResponse(1,'查询成功',$data);
        }else{
            $this->apiResponse(1,'暂无数据');
        }
    }

    /**
     *区域合伙人-代理商
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/21 14:56
     */
    public function franchisee(){
        $post = checkAppData('token','token');
//        $post['token'] = 'c00c797967b0d8480a1c8f9645bde388';

        $agent = $this->getAgentInfo($post['token']);
        $one_agent = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>2))->field('id')->select();
        foreach($one_agent as &$v){
            $one_car = M('CarWasher')->where(array('agent_id'=>$v['id']))->field('id')->select();
            if(!empty($one_car)){
                $one_cars[] = $one_car;
            }
            $two_agent = M('Agent')->where(array('p_id'=>$v['id'],'grade'=>3))->field('id')->select();
            if(!empty($two_agent)){
                $two_agents[] = $two_agent;
            }
        }
        //一级代理商洗车机
        foreach ($one_cars as &$cv){
            foreach ($cv as &$cv2){
                $one_car_num[] =  $cv2;
            }
        }
        //二级代理商
        foreach($two_agents as &$tv){
            foreach($tv as &$tv2){
                $two_agent_num[] = $tv2;
                $two_car = M('CarWasher')->where(array('agent_id'=>$tv2['id']))->field('id,mc_code')->select();
                if(!empty($two_car)){
                    $two_cars[] = $two_car;
                }
            }
        }
        //二级代理商洗车机
        foreach ($two_cars as &$cv3){
            foreach ($cv3 as &$cv4){
                $two_car_num[] =  $cv4;
            }
        }
        //一级代理商洗车机数量
        $one_car_num = count($one_car_num);
        $one_agent_num = count($one_agent);
        $two_agent_num = count($two_agent_num);
        $two_car_num = count($two_car_num);
        $data = array(
            'one_car_num' => $one_car_num,
            'one_agent_num' => $one_agent_num,
            'two_agent_num' => $two_agent_num,
            'two_car_num' => $two_car_num,
        );
        if($data){
            $this->apiResponse(1,'查询成功',$data);
        }
    }

    /**
     *区域合伙人-代理商列表
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/21 16:22
     */
    public function franchiseeAgent(){
        $post = checkAppData('token,grade,page,size','token-等级-页数-个数');
//        $post['token'] = 'c00c797967b0d8480a1c8f9645bde388';
//        $post['page'] = 1;
//        $post['size'] = 10;
//        $post['grade'] = 2;

        $agent = $this->getAgentInfo($post['token']);
        if($post['grade'] == 1){
            $agents = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>2))->field('id,nickname,account,token')->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
            foreach($agents as &$v){
                $car = M('CarWasher')->where(array('agent_id'=>$v['id']))->field('id')->select();
                $v['car_num'] = count($car);
            }
            if($agents){
                $this->apiResponse(1,'查询成功',$agents);
            }
        }elseif ($post['grade'] == 2){
            $one_agents = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>2))->field('id')->select();
            foreach ($one_agents as &$sv){
                $two_agent = M('Agent')->where(array('p_id'=>$sv['id'],'grade'=>3))->field('id,nickname,account,token')->select();
                foreach($two_agent as &$tv){
                    if(!empty($tv)){
                        $agents[] = $tv;
                    }
                }

            }
            foreach($agents as &$agv){
                $car = M('CarWasher')->where(array('agent_id'=>$agv['id']))->field('id')->select();
                $agv['car_num'] = count($car);

            }
            if($agents){
                $this->apiResponse(1,'查询成功',$agents);
            }

        }


    }

    /**
     *一级代理商下二级代理商列表
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/22 11:39
     */
    public function oneAgentList(){
        $post = checkAppData('token,page,size','token-页数-个数');
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';

        $agent = $this->getAgentInfo($post['token']);

        $agents = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>3))->field('id,nickname,account,token')->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();

        foreach($agents as &$v){
            $car = M('CarWasher')->where(array('agent_id'=>$v['id']))->field('id')->select();
            $v['car_num'] = count($car);
        }
        if($agents){
            $this->apiResponse(1,'查询成功',$agents);
        }

    }

    /**
     *一级代理商-代理商收入
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/22 13:41
     */
    public function oneAgentIncome(){
        $post = checkAppData('token','token');

//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
        $agent = $this->getAgentInfo($post['token']);
        $two_agent = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>3))->field('id')->select();
        foreach($two_agent as &$v){
            $n_income = M('Income')->where(array('agent_id'=>$v['id']))->field('status,SUM(net_income) as net_income,SUM(detail) as detail,SUM(platform) as platform,SUM(partner_money) as partner_money,SUM(p_money) as p_money')->find();
            if($n_income['detail'] != 0){
                $n_incomes[] = $n_income;
            }
        }
//        dump($n_incomes);exit;
        //相同键值day相加形成新数组
        $result = array();
        foreach($n_incomes as $key=>$value){
            if(!isset($result[$value['status']])){
                $result[$value['status']]=$value;
            }else{
                $result[$value['status']]['net_income']+=$value['net_income'];
                $result[$value['status']]['detail']+=$value['detail'];
                $result[$value['status']]['platform']+=$value['platform'];
                $result[$value['status']]['partner_money']+=$value['partner_money'];
                $result[$value['status']]['p_money']+=$value['p_money'];
            }
        }
        //营业收入
        $trade = bcsub($result[$value['status']]['detail'],$result[$value['status']]['platform'],2);
        $data = array(
            'net_income' => $result[$value['status']]['net_income'],
            'trade' => $trade,
            'p_money' => $result[$value['status']]['p_money'],
        );
        if($data){
            $this->apiResponse(1,'查询成功',$data);
        }
    }

    /**
     *一级代理商-代理商收入-代理商
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/22 14:52
     */
    public function twoAgentList(){
        $post = checkAppData('token,page,size','token-页数-个数');

//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
//        $post['page'] = 1;
//        $post['size'] = 10;

        $agent = $this->getAgentInfo($post['token']);
        $two_agent = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>3))->field('id,nickname,account,token')->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        foreach($two_agent as $k=>$v){
            $n_income = M('Income')->where(array('agent_id'=>$v['id']))->field('SUM(net_income) as net_income,SUM(detail) as detail,SUM(platform) as platform,SUM(p_money) as p_money')->find();
            $two_agent[$k]['net_income'] = $n_income['net_income'];
            $two_agent[$k]['p_money'] = $n_income['p_money'];
            $trade = bcsub($n_income['detail'],$n_income['platform'],2);
            $two_agent[$k]['trade'] = $trade;
        }
        if(!empty($two_agent)){
            $this->apiResponse(1,'查询成功',$two_agent);
        }else{
            $this->apiResponse(0,'暂无数据');

        }
    }

    /**
     *一级代理商-一级代理商订单明细
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/22 15:16
     */
    public function oneAgentDetail(){
        $post = checkAppData('token,page,size','token-页数-个数');
//        $post['token'] = '60abe1fe939803dd1e4ea29fb1d0fd58';
//        $post['page'] = 1;
//        $post['size'] = 10;

        $request = $_REQUEST;
        $post['data_time'] = $request['data_time'];
        if($post['data_time'] == ''){
            $post['data_time'] = strtotime(date('Y-m'));
        }
//        $post['data_time'] = 1558686980;
        $day =strtotime(date('Y-m-d',$post['data_time'])) ;

        $agent = $this->getAgentInfo($post['token']);
        if($agent['grade'] != 2){
            $this->apiResponse('0','您不是一级代理商');
        }
        $two_agent = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>3))->field('id')->select();
        foreach($two_agent as &$tv){
            $tincome = M('Income')->where(array('agent_id'=>$tv['id'],'day'=>$day))->field('car_washer_id,orderid,p_money,create_time')->order('create_time DESC')->select();
            foreach ($tincome as &$iv){
                $iv['detail'] = '';
                $iv['net_income'] = '';
                $iv['platform'] = '';
                $iv['plat_money'] = '';
                $iv['partner_money'] = '';
                $iv['trade'] = '';
                $iv['style'] = 2;
                $car = M('CarWasher')->where(array('id'=>$iv['car_washer_id']))->field('mc_code')->find();
                $iv['car_washer_id'] = $car['mc_code'];
            }
            if(!empty($tincome)){
                $incomes[] = $tincome;
            }
        }
        foreach($incomes as &$iv2){
            foreach($iv2 as &$iv3){
                $incomess[] = $iv3;
            }
        }
        $income = M('Income')->where(array('agent_id' => $agent['id'],'month'=>$month))->field('car_washer_id,orderid,detail,net_income,platform,plat_money,partner_money,create_time')->order('create_time DESC')->select();
        foreach($income as &$v){
            $car = M('CarWasher')->where(array('id'=>$v['car_washer_id']))->field('mc_code')->find();
            $trade = bcsub($v['detail'],$v['platform'],2);
            $v['car_washer_id'] = $car['mc_code'];
            $v['trade'] = $trade;
            $v['p_money'] = '';
            $v['style'] = 1;
        }
        $list=array_merge($income,$incomess);
        $lists = list_sort_by($list, 'create_time', 'desc');
        for($i = ($post['page'] - 1) * $post['size']; $i < $post['page'] * $post['size']; $i++){
            if(!empty($lists[$i])){
                $datas[] = $lists[$i];
            }
        }
        if(!empty($datas)){
            $this->apiResponse(1,'查询成功',$datas);
        }else{
            $this->apiResponse(1,'暂无数据');
        }
    }

    /**
     *一级代理商-二级代理商订单明细
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/22 15:16
     */
    public function twoAgentDetail(){
        $post = checkAppData('token,page,size','token-页数-个数');
//        $post['token'] = 'a8178ff7c6647e8e628971017ea4f55a';
//        $post['page'] = 1;
//        $post['size'] = 10;

        $request = $_REQUEST;
        $post['data_time'] = $request['data_time'];
        if($post['data_time'] == ''){
            $post['data_time'] = strtotime(date('Y-m'));
        }
//        $post['data_time'] = 1558686980;
        $day =strtotime(date('Y-m-d',$post['data_time'])) ;

        $agent = $this->getAgentInfo($post['token']);
        if($agent['grade'] != 3){
            $this->apiResponse('0','您不是二级代理商');
        }
        $income = M('Income')->where(array('agent_id' => $agent['id'],'day'=>$day))->field('car_washer_id,orderid,detail,net_income,platform,p_money,create_time')->limit(($post['page'] - 1) * $post['size'], $post['size'])->order('create_time DESC')->select();
        foreach($income as &$v){
            $car = M('CarWasher')->where(array('id'=>$v['car_washer_id']))->field('mc_code')->find();
            $trade = bcsub($v['detail'],$v['platform'],2);
            $v['car_washer_id'] = $car['mc_code'];
            $v['trade'] = $trade;
        }
        if(!empty($income)){
            $this->apiResponse(1,'查询成功',$income);
        }else{
            $this->apiResponse(1,'暂无数据');

        }
    }

    /**
     *二级代理商-订单明细
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/22 16:31
     */
    public function twoAgentOrder(){
        $post = checkAppData('token,page,size','token-页数-个数');
//        $post['token'] = 'a8178ff7c6647e8e628971017ea4f55a';
//        $post['page'] = 1;
//        $post['size'] = 10;

        $request = $_REQUEST;
        $post['data_time'] = $request['data_time'];
        if($post['data_time'] == ''){
            $post['data_time'] = strtotime(date('Y-m'));
        }
//        $post['data_time'] = 1558686980;
        $day =strtotime(date('Y-m-d',$post['data_time'])) ;

        $agent = $this->getAgentInfo($post['token']);
        if($agent['grade'] != 3){
            $this->apiResponse('0','您不是二级代理商');
        }
        $income = M('Income')->where(array('agent_id' => $agent['id'],'day'=>$day))->field('car_washer_id,orderid,detail,net_income,platform,p_money,plat_money,partner_money,create_time')->limit(($post['page'] - 1) * $post['size'], $post['size'])->order('create_time DESC')->select();
        foreach($income as &$v){
            $car = M('CarWasher')->where(array('id'=>$v['car_washer_id']))->field('mc_code')->find();
            $trade = bcsub($v['detail'],$v['platform'],2);
            $v['car_washer_id'] = $car['mc_code'];
            $v['trade'] = $trade;
        }
        if(!empty($income)){
            $this->apiResponse(1,'查询成功',$income);
        }else{
            $this->apiResponse(1,'暂无数据');

        }
    }

    /**
     *合作方-订单明细
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/22 16:56
     */
    public function partnerOrderDetail(){
        $post = checkAppData('token,page,size','token-页数-个数');
//        $post['token'] = 'd7b8e3afec48f4b75d1ea8ebb3182845';
//        $post['page'] = 1;
//        $post['size'] = 10;
        $request = $_REQUEST;
        $post['data_time'] = $request['data_time'];
        if($post['data_time'] == ''){
            $post['data_time'] = strtotime(date('Y-m'));
        }
//        $post['data_time'] = 1558686980;
        $day =strtotime(date('Y-m-d',$post['data_time'])) ;

        $agent = $this->getAgentInfo($post['token']);
        if($agent['grade'] != 4){
            $this->apiResponse(0,'您不是合作方');
        }
        $car = M('CarWasher')->where(array('partner_id'=>$agent['id']))->field('id,mc_code')->select();
        foreach ($car as $k=>$v){
            $income = M('Income')->where(array('car_washer_id'=>$car[$k]['id'],'day'=>$day))->field('orderid,detail,platform,partner_money,create_time')->select();
            foreach($income as &$cv){
                $cv['mc_code'] = $car[$k]['mc_code'];
            }
            if(!empty($income)){
                $incomes[] = $income;
            }
        }

        foreach ($incomes as &$iv){
            foreach($iv as &$iv2){
                $trade = bcsub($iv2['detail'],$iv2['platform'],2);
                $iv2['trade']= $trade;
                $incomess[] = $iv2;
            }
        }

        $lists = list_sort_by($incomess, 'create_time', 'desc');
        for($i = ($post['page'] - 1) * $post['size']; $i < $post['page'] * $post['size']; $i++){
            if(!empty($lists[$i])){
                $datas[] = $lists[$i];
            }
        }
        if($datas){
            $this->apiResponse(1,'查询成功',$datas);
        }
    }

    /**
     *区域合伙人-一级代理商
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/22 22:15
     */
    public function partnerOne(){
        $post = checkAppData('token','token');

//        $post['token'] = 'c00c797967b0d8480a1c8f9645bde388';

        $agent = $this->getAgentInfo($post['token']);

        if($agent['grade'] != 1){
            $this->apiResponse(0,'您的身份不是区域合伙人');
        }
        $agent = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>2))->field('id')->select();
        foreach ($agent as &$v){
            $two_agent = M('Agent')->where(array('p_id'=>$v['id'],'grade'=>3))->field('id')->select();
            if(!empty($two_agent)){
                foreach ($two_agent as &$tv){
                    $two_income = M('Income')->where(array('agent_id'=>$tv['id']))->field('status,SUM(p_money) as p_money')->select();
                    if($two_income){
                        $two_incomes[] = $two_income;
                    }
                }
            }
            $income[] = M('Income')->where(array('agent_id'=>$v['id']))->field('status,SUM(detail) as detail,SUM(net_income) as net_income,SUM(platform) as platform')->select();
        }
        foreach($income as &$iv){
            foreach($iv as &$iv2){
                if($iv2['detail'] != 0){
                    $iv2['p_money'] = 0;
                    $trade = bcsub($iv2['detail'],$iv2['platform'],2);
                    $iv2['trade'] = $trade;

                    $one_income[] = $iv2;
                }
            }
        }
        foreach($two_incomes as &$iv3){
            foreach($iv3 as &$iv4){
                if($iv4['status'] != 0){
                    $iv4['detail'] = 0;
                    $iv4['net_income'] = 0;
                    $iv4['platform'] = 0;
                    $iv4['trade'] = 0;
                    $two_incomess[] = $iv4;
                }
            }
        }
        $all_income = array_merge($one_income,$two_incomess);
        $result = array();
        foreach($all_income as $key=>$value){
            if(!isset($result[$value['status']])){
                $result[$value['status']]=$value;
            }else{
                $result[$value['status']]['detail']+=$value['detail'];
                $result[$value['status']]['net_income']+=$value['net_income'];
                $result[$value['status']]['platform']+=$value['platform'];
                $result[$value['status']]['p_money']+=$value['p_money'];
                $result[$value['status']]['trade']+=$value['trade'];
            }
        }
        $data = array(
            'detail' => $result[$value['status']]['detail'],
            'net_income' => $result[$value['status']]['net_income'],
            'p_money' => $result[$value['status']]['p_money'],
            'trade' => $result[$value['status']]['trade'],
        );
        if($result){
            $this->apiResponse(1,'查询成功',$data);
        }
    }

    /**
     *区域合伙人-二级代理商
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/23 01:59
     */
    public function partnerTwo(){
        $post = checkAppData('token','token');

//        $post['token'] = 'c00c797967b0d8480a1c8f9645bde388';

        $agent = $this->getAgentInfo($post['token']);

        if($agent['grade'] != 1){
            $this->apiResponse(0,'您的身份不是区域合伙人');
        }
        $agent = M('Agent')->where(array('p_id'=>$agent['id']))->field('id')->select();
        foreach ($agent as &$v){
            $two_agent = M('Agent')->where(array('p_id'=>$v['id']))->field('id')->select();
            if(!empty($two_agent)){
                foreach ($two_agent as &$tv){
                    $two_income = M('Income')->where(array('agent_id'=>$tv['id']))->field('status,SUM(detail) as detail,SUM(platform) as platform,SUM(net_income) as net_income')->select();
                    if($two_income){
                        $two_incomes[] = $two_income;
                    }
                }
            }
        }

        foreach($two_incomes as &$iv3){
            foreach($iv3 as &$iv4){
                if($iv4['status'] != 0){
                    $trade = bcsub($iv4['detail'],$iv4['platform'],2);
                    $iv4['trade'] = $trade;
                    $two_incomess[] = $iv4;
                }
            }
        }
        $result = array();
        foreach($two_incomess as $key=>$value){
            if(!isset($result[$value['status']])){
                $result[$value['status']]=$value;
            }else{
                $result[$value['status']]['detail']+=$value['detail'];
                $result[$value['status']]['net_income']+=$value['net_income'];
                $result[$value['status']]['platform']+=$value['platform'];
                $result[$value['status']]['trade']+=$value['trade'];
            }
        }
        $data = array(
            'net_income' => $result[$value['status']]['net_income'],
            'trade' => $result[$value['status']]['trade'],
        );
        if($result){
            $this->apiResponse(1,'查询成功',$data);
        }
    }


    /**
     *管理
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/22 01:26
     */
    public function board(){
        $post = checkAppData('token,page,size','token-页数-个数');
//        $post['token'] = 'c00c797967b0d8480a1c8f9645bde388';
//        $post['page'] = 1;
//        $post['size'] = 10;
        $agents = $this->getAgentInfo($post['token']);
        if($agents['grade'] != 1){
            $this->apiResponse(0,'您不是区域合伙人');
        }

        $agent = M('Agent')->where(array('grade'=>1,'status'=>array('neq',9)))->field('id,nickname')->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        foreach ($agent as $k=>$v){
            //一级代理商
            $one_agent = M('Agent')->where(array('p_id'=>$v['id'],'grade'=>2))->field('id')->select();
            foreach($one_agent as &$ov){
                //二级代理商
                $two_agent = M('Agent')->where(array('p_id'=>$ov['id'],'grade'=>3))->field('id')->select();
                if(!empty($two_agent)){
                    foreach($two_agent as &$tv){
                        $two_car = M('CarWasher')->where(array('agent_id'=>$tv['id']))->field('id,p_id')->select();
                        if(!empty($two_car)){
                            $two_cars[] = $two_car;
                        }
                    }
                    foreach ($two_cars as &$tvv){
                        foreach($tvv as &$tvv2){
                            $two_carss[] = $tvv2;
                        }
                    }
                    $results = array();
                    foreach($two_carss as $key1=>$value1){
                        if(!isset($results[$value1['p_id']])){
                            $results[$value1['p_id']]=$value1;
                        }else{
                            $results[$value1['p_id']]['id']+=$value1['id'];
                        }
                    }
                }
                $one_car = M('CarWasher')->where(array('agent_id'=>$ov['id']))->field('id,p_id')->select();

                foreach ($one_car as &$ov){
                    $one_cars[] = $ov;
                }


                //一级代理商洗车店数量
                $result = array();
                foreach($one_cars as $key=>$value){
                    if(!isset($result[$value['p_id']])){
                        $result[$value['p_id']]=$value;
                    }else{
                        $result[$value['p_id']]['id']+=$value['id'];
                    }
                }
            }
            $one_num = count($result);
            $two_num = count($results);
            $agent[$k]['car_num'] = $one_num+$two_num;
        }
        if($agent){
            $this->apiResponse(1,'查询成功',$agent);
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

    /**
     *区域合伙人-代理商收入-代理商
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/23 11:25
     */
    public function franchOne(){
        $post = checkAppData('token,page,size','token-页数-个数');

//        $post['token'] = 'c00c797967b0d8480a1c8f9645bde388';
//        $post['page'] = 1;
//        $post['size'] = 10;


        $agent = $this->getAgentInfo($post['token']);
        if($agent['grade'] != 1){
            $this->apiResponse(0,'您的身份不是区域合伙人');
        }
        $two_agent = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>2))->field('id,nickname,account,token')->limit(($post['page'] - 1) * $post['size'], $post['size'])->select();
        foreach($two_agent as $k=>$v){
            $n_income = M('Income')->where(array('agent_id'=>$v['id']))->field('SUM(net_income) as net_income,SUM(detail) as detail,SUM(platform) as platform,SUM(p_money) as p_money')->find();
            if($n_income['net_income'] == 0){
                $n_income['net_income'] = (int)0;
            }
            if($n_income['p_money'] == 0){
                $n_income['p_money'] = (int)0;
            }
            if($n_income['detail'] == 0){
                $n_income['detail'] = (int)0;
            }
            if($n_income['platform'] == 0){
                $n_income['platform'] = (int)0;
            }
            $two_agent[$k]['net_income'] = $n_income['net_income'];
            $two_agent[$k]['p_money'] = $n_income['p_money'];
            $trade = bcsub($n_income['detail'],$n_income['platform'],2);
            $two_agent[$k]['trade'] = $trade;
        }
        if(!empty($two_agent)){
            $this->apiResponse(1,'查询成功',$two_agent);
        }else{
            $this->apiResponse(0,'暂无数据');

        }
    }

    /**
     *区域合伙人-2级代理商收入-代理商
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/24 02:07
     */
    public function franchTwo(){
        $post = checkAppData('token,page,size','token-页数-个数');

//        $post['token'] = 'c00c797967b0d8480a1c8f9645bde388';
//        $post['page'] = 1;
//        $post['size'] = 10;

        $agent = $this->getAgentInfo($post['token']);
        if($agent['grade'] != 1){
            $this->apiResponse(0,'您的身份不是区域合伙人');
        }
        $one_agent = M('Agent')->where(array('p_id'=>$agent['id'],'grade'=>2))->field('id')->select();
        foreach($one_agent as $ok=>$ov){
            $two_agent = M('Agent')->where(array('p_id'=>$ov['id'],'grade'=>3))->field('token,create_time,id,nickname,account,token')->select();
//            dump($two_agent);
            foreach ($two_agent as $k=>$v){
                $n_income = M('Income')->where(array('agent_id'=>$v['id']))->field('SUM(net_income) as net_income,SUM(detail) as detail,SUM(platform) as platform')->find();
                if($n_income['net_income'] == 0){
                    $n_income['net_income'] = (int)0;
                }
                if($n_income['detail'] == 0){
                    $n_income['detail'] = (int)0;
                }
                if($n_income['platform'] == 0){
                    $n_income['platform'] = (int)0;
                }
                $data['trade'] = bcsub($n_income['detail'],$n_income['platform'],2);
                $data['nickname'] = $two_agent[$k]['nickname'];
                $data['account'] = $two_agent[$k]['account'];
                $data['net_income'] = $n_income['net_income'];
                $data['create_time'] = $two_agent[$k]['create_time'];
                $data['token'] = $two_agent[$k]['token'];
                $n_incomes[] = $data;

            }

        }
        $lists = list_sort_by($n_incomes, 'create_time', 'desc');
        for($i = ($post['page'] - 1) * $post['size']; $i < $post['page'] * $post['size']; $i++){
            if(!empty($lists[$i])){
                $datas[] = $lists[$i];
            }
        }
        if($datas){
            $this->apiResponse(1,'查询成功',$datas);
        }else{
            $this->apiResponse(0,'暂无数据');
        }
    }
}

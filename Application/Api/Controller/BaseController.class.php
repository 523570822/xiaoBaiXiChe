<?php
/**
 * Created by PhpStorm.
 * User: ľ
 * Date: 2018/8/13
 * Time: 9:20
 */
namespace Api\Controller;

use Common\Service\ControllerService;

class BaseController extends ControllerService
{
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 检查Token
     * @return int
     * User: hongwei.bai baihongweiaaa@163.com
     * Date: 2018/8/13 9:56
     */
    final protected function checkToken() {
         $token = $_REQUEST['token'];
         if (empty($token)) {
             return 0;
         }
         $w['token'] = $token;
         $w['expired_time'] = array('egt', time());
         $w['status'] = array('neq', 0);
         $m_id = D('Member')->queryField($w,'id'); //var_dump($m_id);die;
         if($m_id){
             $this->userId = $m_id;
             return $m_id;
         }else{
             $this->apiResponse('-1','登录失效，请重新登录');
         }
     }

    /**
     * 生成token
     */
    public function createToken(){
        $arr['token'] = md5(time().rand(10000,99999));
        $arr['expired_time'] = time()+86400*7;
        return $arr;
    }

    /**
     * 检查推荐码方案
     * invite_code 邀请码
     * User: hongwei.bai baihongweiaaa@163.com
     * Date: 2018/8/13 9:56
     */
    public function checkShareCodeBefore($invite_code) {
        $m_info = D('Member')->queryField(array('invite_code'=>$invite_code),'id');
        if (!$m_info) {
            $this->apiResponse('0','推荐码不存在');
        }
        return $m_info;
    }

    /**
     * 推广码绑定
     * invite_code 邀请码 h_id 填写邀请码的用户ID
     * User: hongwei.bai baihongweiaaa@163.com
     * Date: 2018/8/13 9:56
     */
    public function checkShareCode($invite_code='',$h_id = 0){

        $m_info = D('Member')->queryRow(array('invite_code'=>$invite_code),'p_id,id');
        if($m_info['p_id'] != 0) {
            $this->apiResponse(0,'您已经绑定过推荐人了，请不要填写推荐码');
        }
        if($m_info['id']==$h_id){
            $this->apiResponse(0,'您不能推荐您自己');
        }
        $data = array(
            'p_id'=>$h_id
        );
        D('Member')->querySave(array('id'=>$m_info['id']),$data);

    }

    public function setAbsoluteUrl($content){
        preg_match_all('/src=\"\/?(.*?)\"/',$content,$match);
        foreach($match[1] as $key => $src){
            if(!strpos($src,'://')){
                $content = str_replace('/'.$src,C('API_URL')."/".$src."\" width=100%", $content);
            }
        }
        return $content;
    }

    /**
     *代理商token判断
     * @param $token
     * @param string $type
     * @param string $field
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/21 09:01
     */
    public function getAgentInfo($token,$type='info',$field = ''){
        if(empty($token)){
            apiResponse('-1','登录失效，请重新登录');
        }
        if ($type == 'info') {
            $agent = D('Agent')->findAgent(array('token'=>$token),'id,account,token,nickname,balance,salt,password,grade');
        } elseif ($type == 'field') {
            $agent = D('Agent')->findAgent(array('token'=>$token),$field);
        } else {
            $agent = D('Agent')->findAgent(array('token'=>$token),'id,password,salt');
        }
        if(!$agent){
            apiResponse('0','Your account has been dropped. Please log in again.');
        }
        if($agent['status'] == 9){
            apiResponse('0','User information has been deleted');
        }
        if($agent['status'] == 2){
            apiResponse('0','You are temporarily unable to login');
        }
        return $agent;
    }

    /**
     *用户token判断
     * @param $token
     * @param string $type
     * @param string $field
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/18 17:04
     */
    public function getMemberInfo($token,$type='info',$field = ''){
        if(empty($token)){
            apiResponse('-1','登录失效，请重新登录');
        }
        if ($type == 'info') {
            $agent = D('Member')->findAgent(array('token'=>$token),'id,account,token,nickname,tel,realname,salt,password,');
        } elseif ($type == 'field') {
            $agent = D('Agent')->findAgent(array('token'=>$token),$field);
        } else {
            $agent = D('Agent')->findAgent(array('token'=>$token),'id,password,salt');
        }
        if(!$agent){
            apiResponse('0','Your account has been dropped. Please log in again.');
        }
        if($agent['status'] == 9){
            apiResponse('0','User information has been deleted');
        }
        if($agent['status'] == 2){
            apiResponse('0','You are temporarily unable to login');
        }
        return $agent;
    }

    /**
     *接口请求地址
     * @param $param
     * @param $heade
     * @param $postUrl
     */
    public function push_curl ($param = "" , $header = "" , $postUrl = "")
    {
        if ( empty($param) ) {
            return false;
        }
        $curlPost = $param;
        $ch = curl_init ();                                      //初始化curl
        curl_setopt ($ch , CURLOPT_URL , $postUrl);                 //抓取指定网页
        curl_setopt ($ch , CURLOPT_HEADER , 0);                    //设置header
        curl_setopt ($ch , CURLOPT_RETURNTRANSFER , 1);            //要求结果为字符串且输出到屏幕上
        curl_setopt ($ch , CURLOPT_POST , 1);                      //post提交方式
        curl_setopt ($ch , CURLOPT_POSTFIELDS , $curlPost);
        curl_setopt ($ch , CURLOPT_HTTPHEADER , $header);           // 增加 HTTP Header（头）里的字段
        curl_setopt ($ch , CURLOPT_SSL_VERIFYPEER , FALSE);        // 终止从服务端进行验证
        curl_setopt ($ch , CURLOPT_SSL_VERIFYHOST , FALSE);
        $data = curl_exec ($ch);                                 //运行curl
        curl_close ($ch);
        return $data;
    }

    /**
     * 接口请求封装模块
     * @param $deviceid 洗车机编号
     * @param $param_key 请求数组字段名
     * @param $param_array 请求数组内容
     */
    public function createJSON ($deviceid, $param_key, $param_array)
    {
        $array['devices'] = [];
        $array['devices'][] = [
            "deviceid" => $deviceid,
            $param_key => $param_array
        ];
        return $array;
    }

    /**
     *请求接口数据
     * @param $type //runtime_query 实时查询  device_manage 机器控制
     * @param $mc_id //机器编号
     * @param $suffix //查询数组
     * @param $mode //控制模式
     * @param $arr_param //固定请求格式
     */
    public function send_post ($type , $mc_id , $mode = '',$playtype='', $content='')
    {
        if ( $type ) {
            if ( $type == 'runtime_query') {            //实时状态查询
                $suffix = 'queryitem';
                $arr_param = [//json格式数据
                    "service_status" => true ,//设备在线状态 service_status≥ 8 在线 service_status<8 设备离线
                    "pressure" => true ,//请求查询进水压力值
                    "pump1_status" => true ,//清水泵状态 设备故障≥ 4
                    "pump2_status" => true ,//泡沫泵状态 设备故障≥ 4
                    "valve1_status" => true ,//进水阀状态 设备故障≥ 4
                    "valve2_status" => true ,//清水阀状态 有流量时阀状态为开，无流量时阀状态为关
                    "valve3_status" => true ,//泡沫液位状态 true 正常 false 液位不足
                    "level1_status" => true ,//清水液位状态
                    "level3_status" => true ,//泡沫液位状态
//                    "heater_status" => true ,//加热器状态
                    "env_temperature" => true ,//当前环境温度
                    "device_volt" => true ,//当前供电电压
                    "device_current" => true ,//当前整机电流
                    "device_power" => true ,//当前整机功耗
                    "device_energy" => true ,//当前电表读数 设备耗电量 单位 kWh
                    "clean_water_usage" => true ,//清水累计用量（L）
                    "clean_water_duration" => true ,//清水累计用时（秒）
                    "foam_usage" => true ,//泡沫累计用量（L）
                    "foam_duration" => true ,//泡沫累计用时（秒）
                    'level2_status' => true, //清水状态
                    "vacuum_info" => true ,//吸尘器用 设备故障status ≥ 4
                    // current吸尘器设备的电流值，单位 A lastmaint_uasge 上次维护后的使用时间，单位秒 accumulated_usage 累计使用时间，单位秒
                    "location" => true//机器坐标 longitud经度 latitud纬度
                ];
                $result_array = $this->createJSON ($mc_id , $suffix , $arr_param);
            } elseif ( $type == 'device_manage' ) {                   //设备控制
                $suffix = 'setitem';
                if ( $mode == 1 ) {//json格式数据
                    $arr_param = [//扫码 — 洗车机设置
                        "service_status" => 5 ,     //开启
                        "pump1_status" => 3 ,
                        "pump2_status" => 3 ,
                        "valve1_status" => 3 ,
                        "vacuum_status" => 2 ,
                        "heater_status" => 2 ,
                    ];
                } elseif ( $mode == 2 ) {
                    $arr_param = [//预约 — 洗车机设置
                        "service_status" => 6 ,       //预约
                        "pump1_status" => 0 ,
                        "pump2_status" => 0 ,
                        "valve1_status" => 0 ,
                        "vacuum_status" => 0 ,
                    ];
                } elseif ( $mode == 3 ) {
                    $arr_param = [//结算 — 洗车机设置
                        "service_status" => 4 ,         //结算
                        "pump1_status" => 0 ,
                        "pump2_status" => 0 ,
                        "valve1_status" => 0 ,
                    ];
                } elseif ( $mode == 4 ) {
                    $arr_param = [//恢复状态 — 洗车机设置
                        "service_status" => 0 ,         //恢复状态
                        "pump1_status" => 0 ,
                        "pump2_status" => 0 ,
                        "valve1_status" => 0 ,
                    ];
                } elseif ( $mode == 5 ) {      //语音播放请求
                    $arr_param= [
                        "ttsplay" => array(
                            "playtype" => $playtype,
                            "content" => $content,
                        )
                    ];
                }
                $result_array = $this->createJSON ($mc_id , $suffix , $arr_param,$playtype,$content);
            }
        }else {
            $php_errormsg = '查询失败，请传参数---->"type"';
            $this->apiResponse (0,$php_errormsg);
        }
        $response = $this->push_curl (json_encode ($result_array) , ["Content-Type" => "Content-Type:application/x-www-form-urlencoded"] , "http://washer.xiaojingxiche.com:18000/car_wash/" . $type);
        return json_decode ($response, true);
    }

    /**
     *洗车机结算
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/03/14 15:02
     */
    public function details($m_id,$orderid,$indication,$mc_id){
        $send_post = $this->send_post('runtime_query',$mc_id);       //查询洗车机状态
        $d_where = array(
            'o_id'=>$orderid,
            'm_id'=>$m_id,
            'status'=> 1,     //0代表未完成   订单还没结束
        );

        $details = M('Details')->where($d_where)->find();
        $car = M('CarWasher')->where(array('id'=>$details['c_id']))->find();
        $washing = round($send_post['devices'][0]['queryitem']['clean_water_duration']) - $details['washing_start_time'];
        $foam = round($send_post['devices'][0]['queryitem']['foam_duration']) - $details['foam_start_time'];
        $cleaner = round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']) - $details['cleaner_start_time'];
        if($washing >= 60 || $foam >= 60 || $cleaner>=60){
            $wash_fen = intval($washing/60).'分';
            $wash_miao = $washing % 60 . '秒';
            $wash_time = $wash_fen . $wash_miao;                //水枪时间
            $foam_fen = intval($foam/60).'分';
            $foam_miao = $foam % 60 . '秒';
            $foam_time = $foam_fen . $foam_miao;                  //泡沫枪时间
            $cleaner_fen = intval($cleaner/60).'分';
            $cleaner_miao = $cleaner % 60 . '秒';
            $cleaner_time = $cleaner_fen . $cleaner_miao;          //吸尘器时间
        }else if($washing < 60 || $foam < 60 || $cleaner < 60){
            $wash_time = 0 . '分' . $washing . '秒';    //水枪时间
            $foam_time = 0 . '分' . $foam . '秒';     //泡沫枪时间
            $cleaner_time = 0 . '分' . $cleaner . '秒';   //吸尘器时间
        }
        //去除多余时间
        if($washing <= 2){
            $washing = 0;
            $wash_time = 0 . '分' . 0 . '秒';    //水枪时间
        }
        if($foam <= 2){
            $foam = 0;
            $foam_time = 0 . '分' . 0 . '秒';    //水枪时间
        }
        if($cleaner <= 2){
            $cleaner = 0;
            $cleaner_time = 0 . '分' . 0 . '秒';    //水枪时间
        }
        $wash_money =  bcmul ($washing , $car['washing_money'],2);
        $foam_money = bcmul ($foam , $car['foam_money'],2);
        $cleaner_money = bcmul ($cleaner , $car['cleaner_money'],2);

        //存储结束时间
        $s_save = array(
            'washing_end_time' =>round($send_post['devices'][0]['queryitem']['clean_water_duration']),
            'foam_end_time' =>round($send_post['devices'][0]['queryitem']['foam_duration']),
            'cleaner_end_time' =>round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']),
            'washing' => $washing,
            'foam' => $foam,
            'cleaner' => $cleaner,
        );
        $s_details = M('Details')->where($d_where)->save($s_save);

        //订单金额添加
        $o_where = array(
            'id' =>$orderid,
            'm_id' => $m_id,
        );
//        var_dump($wash_money);exit;
        $sa_order = array(
            'money' => round($wash_money+$foam_money+$cleaner_money,2),
            'pay_money' => round($wash_money+$foam_money+$cleaner_money,2),
            'update_time' =>time(),
        );
        $f_order = M('Order')->where($o_where)->save($sa_order);
        $washing_price = bcmul(60 , $car['washing_money'],2);
        $foam_price = bcmul(60 ,  $car['foam_money'],2);
        $cleaner_price = bcmul(60 , $car['cleaner_money'],2);
        $data_money = array(
            'indication' => $indication,    //1  代表水枪    2代表泡沫枪   3代表吸尘器
            'washing' =>$wash_time,
            'foam'=>$foam_time,
            'cleaner'=>$cleaner_time,
            'all_money' =>round($wash_money+$foam_money+$cleaner_money,2),
            'off_on' => 1,
            'washing_price' => $washing_price,
            'foam_price' => $foam_price,
            'cleaner_price' => $cleaner_price
        );
        return $data_money;
    }

    /**
     *洗车机未结算
     * @param $m_id     //用户ID
     * @param $orderid      //订单ID
     * @param $indication     //使用状态
     * @param $mc_id          //洗车机编号
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/03/14 18:30
     */
    public function onDetails($m_id,$orderid,$indication,$mc_id){
        $send_post = $this->send_post('runtime_query',$mc_id);       //查询洗车机状态
        $d_where = array(
            'o_id'=>$orderid,
            'm_id'=>$m_id,
            'status'=> 0,     //0代表未完成   订单还没结束
        );
        $details = M('Details')->where($d_where)->find();
        $car = M('CarWasher')->where(array('id'=>$details['c_id']))->find();
        $washing = round($send_post['devices'][0]['queryitem']['clean_water_duration']) - $details['washing_start_time'];
        $foam = round($send_post['devices'][0]['queryitem']['foam_duration']) - $details['foam_start_time'];
        $cleaner = round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']) - $details['cleaner_start_time'];

        if($washing >= 60 || $foam >= 60 || $cleaner>=60){
            $wash_fen = intval($washing/60).'分';
            $wash_miao = $washing % 60 . '秒';
            $wash_time = $wash_fen . $wash_miao;                //水枪时间
            $foam_fen = intval($foam/60).'分';
            $foam_miao = $foam % 60 . '秒';
            $foam_time = $foam_fen . $foam_miao;                  //泡沫枪时间
            $cleaner_fen = intval($cleaner/60).'分';
            $cleaner_miao = $cleaner % 60 . '秒';
            $cleaner_time = $cleaner_fen . $cleaner_miao;          //吸尘器时间
        }else if($washing < 60 || $foam < 60 || $cleaner < 60){
            $wash_time = 0 . '分' . $washing . '秒';    //水枪时间
            $foam_time = 0 . '分' . $foam . '秒';     //泡沫枪时间
            $cleaner_time = 0 . '分' . $cleaner . '秒';   //吸尘器时间
        }
        //去除多余时间
        if($washing <= 2){
            $washing = 0;
            $wash_time = 0 . '分' . 0 . '秒';    //水枪时间
        }
        if($foam <= 2){
            $foam = 0;
            $foam_time = 0 . '分' . 0 . '秒';    //水枪时间
        }
        if($cleaner <= 2){
            $cleaner = 0;
            $cleaner_time = 0 . '分' . 0 . '秒';    //水枪时间
        }

        $wash_money =  bcmul($washing , $car['washing_money'],2);
        $foam_money = bcmul($foam , $car['foam_money'],2);
        $cleaner_money = bcmul($cleaner , $car['cleaner_money'],2);

        //存储结束时间
        $s_save = array(
            'washing_end_time' =>round($send_post['devices'][0]['queryitem']['clean_water_duration']),
            'foam_end_time' =>round($send_post['devices'][0]['queryitem']['foam_duration']),
            'cleaner_end_time' =>round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']),
            'washing' => $washing,
            'foam' => $foam,
            'cleaner' => $cleaner,
        );
        $s_details = M('Details')->where($d_where)->save($s_save);

        //订单金额添加
        $o_where = array(
            'id' =>$orderid,
            'm_id' => $m_id,
        );
//        var_dump($wash_money);exit;
        $sa_order = array(
            'money' => round($wash_money+$foam_money+$cleaner_money,2),
            'pay_money' => round($wash_money+$foam_money+$cleaner_money,2),
        );
        $f_order = M('Order')->where($o_where)->save($sa_order);
        $washing_price = bcmul(60 , $car['washing_money'],2);
        $foam_price = bcmul(60 ,  $car['foam_money'],2);
        $cleaner_price = bcmul(60 , $car['cleaner_money'],2);
        $data_money = array(
            'indication' => $indication,    //1  代表水枪    2代表泡沫枪   3代表吸尘器
            'washing' =>$wash_time,
            'foam'=>$foam_time,
            'cleaner'=>$cleaner_time,
            'all_money' =>round($wash_money+$foam_money+$cleaner_money,2),
            'off_on' => 0,
            'washing_price' => $washing_price,
            'foam_price' => $foam_price,
            'cleaner_price' => $cleaner_price
        );
        return $data_money;
    }

    /**
     *洗车使用时间
     * @param $mc_id    //洗车机编号
     * @param $o_id     //订单ID
     * @param $m_id     //用户ID
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/03/15 11:07
     */
    public function carWasherTime($mc_id,$o_id,$m_id=''){
        $send_post = $this->send_post('runtime_query',$mc_id);       //查询洗车机状态
        $d_where = array(
            'o_id'=>$o_id,
            'm_id'=>$m_id,
        );
        $details = M('Details')->where($d_where)->find();

        //水枪使用时间
        $w_end_data['washing_end_time'] = round($send_post['devices'][0]['queryitem']['clean_water_duration']);
        $w_end_data['washing'] = $w_end_data['washing_end_time'] - $details['washing_start_time'] ;
        if(($w_end_data['washing_end_time'] - $details['washing_start_time']) < 2 ){    //为-1就等于0
            $w_end_data['washing'] = 0;
        }
        $d_where['status'] = 0;
        $d_where['id'] = $details['id'];
        $w_start = M('Details')->where($d_where)->save($w_end_data);

        //泡沫枪使用时间
        $f_end_data['foam_end_time'] = round($send_post['devices'][0]['queryitem']['foam_duration']);
        $f_end_data['foam'] = $f_end_data['foam_end_time'] - $details['foam_start_time'];
        if(($f_end_data['foam_end_time'] - $details['foam_start_time']) < 2 ){    //为-1就等于0
            $f_end_data['foam'] = 0;
        }
        $d_where['status'] = 0;
        $d_where['id'] = $details['id'];
        $f_start = M('Details')->where($d_where)->save($f_end_data);

        //吸尘器使用时间
        $c_end_data['cleaner_end_time'] = round($send_post['devices'][0]['queryitem']['vacuum_info']['accumulated_usage']);
        $c_end_data['cleaner'] = $c_end_data['cleaner_end_time'] - $details['cleaner_start_time'];
        if(($c_end_data['cleaner_end_time'] - $details['cleaner_start_time']) < 2 ){    //为-1就等于0
            $c_end_data['cleaner'] = 0;
        }
        $d_where['status'] = 0;
        $d_where['id'] = $details['id'];
        $c_start = M('Details')->where($d_where)->save($c_end_data);
        return 'OK';
    }

    /**
     * 费用为0 自动删除订单
     * @param $m_id    //用户ID
     * @param $o_id    //订单ID
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/03/16 11:32
     */
    public function payZero($m_id,$o_id){
        //费用为0,自动结算
        $order_zero = M('Order')->where(array('m_id'=>$m_id,'id'=>$o_id))->find();
        if($order_zero['pay_money'] == 0 && $order_zero['money'] == 0 && $order_zero['status'] == 1 && $order_zero['button'] == 1){
            $order_pay_save = array(
                'status' =>9,
                'pay_time' => time(),
                'is_set'=>1
            );
            $order_pay = M('Order')->where(array('m_id'=>$m_id,'id'=>$o_id))->save($order_pay_save);
            return 1;
        }
    }

    /**
     *洗车机状态空闲
     * @param $c_id //洗车机ID
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/03/17 11:14
     */
    public function typeOne($c_id){
        $save['type'] = 1;
        $order = M('CarWasher')->where(array('id'=>$c_id))->save($save);
    }

    //洗车机故障
    public function typeFour($c_id){
        $save['type'] = 4;
        $order = M('CarWasher')->where(array('id'=>$c_id))->save($save);
    }
}
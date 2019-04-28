<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/7/26
 * Time: 11:59
 */

namespace Manager\Controller;
class IndexController extends BaseController {
    /**
     * 入口方法
     * User: 木
     * Date: 2018/7/30 15:52
     */
    public function index () {
        // 未登录状态跳转到登陆页面
        if ( !$this->isLogin ) {
            $this->redirect ('Admin/login');
        }
        $auth = json_decode (file_get_contents (FRAME_CACHE_PATH . 'AdminMenu/' . $this->userId) , true);
        $this->assign ('menu_top' , $auth['system']);
        $this->assign ('menu_left' , $auth['menus']);
        $this->assign ('userInfo' , $this->userInfo);
        $this->assign ('app_logo' , $this->getOnePath (C ('APP')['app_logo']));
        $this->display ();
    }

    /**
     * 图形验证码
     * User: 木
     * Date: time
     */
    public function verifyImage () {
        $config = array (
            'fontSize' => 30 ,    // 验证码字体大小
            'length' => 4 ,     // 验证码位数
            'useNoise' => false , // 关闭验证码杂点
        );
        $Verify = new \Think\Verify($config);
        $Verify->codeSet = '0123456789';
        $Verify->entry ();
    }

    /**
     * 上传文件
     * User: 木
     * Date: 2018/8/16 11:47
     */
    public function uploadFile () {
        $save_info = uploadimg ($_FILES , CONTROLLER_NAME);
        if ( !empty($save_info['save_id']) ) {
            $this->apiResponse (1 , '文件上传成功' , $save_info['save_id']);
        } else {
            $this->apiResponse (0 , $save_info);
        }
    }

    /**
     * 删除文件
     * User: 木
     * Date: 2018/8/16 11:48
     */
    public function delFile () {

        $id = $this->checkParam (array ('id' , 'int' , 'id不能为空'));
        $model = D ('Common/File');
        $res = $model->querySave ($id , array ('status' => 9));
        $res ? $this->apiResponse (1 , '删除成功') : $this->apiResponse (0 , '删除失败');
    }

    /**
     * welcome页面信息
     * User: bin.wang
     * Date: 2018/11/22 11:48
     */
    public function welcome () {
        //本月日期起止时间戳
        $begin_month = mktime (0 , 0 , 0 , date ('m') , 1 , date ('Y'));
        $end_month = mktime (23 , 59 , 59 , date ('m') , date ('t') , date ('Y'));
        //上月日期起止时间戳
        $begin_time = strtotime (date ('Y-m-01 00:00:00' , strtotime ('-1 month')));
        $end_time = strtotime (date ("Y-m-d 23:59:59" , strtotime (-date ('d') . 'day')));
        //注册用户的总量
        $all['status'] = array ('lt' , 9);
        $allNum = D ('Member')->queryCount ($all);
        //设备数
        $carWasher['status'] = array ('lt' , 9);
        $carWasherNum = D ('CarWasher')->queryCount ($carWasher);
        //上月设备增加数
        $where['create_time'] = array (array ('gt' , $begin_time) , array ('lt' , $end_time));
        $where['status'] = array ('lt' , 9);
        $carWasher = D ('CarWasher')->queryCount ($where);
        unset($where);
        //增长率
        $growthRate = round(($carWasher / $carWasherNum * 100),2) . '%';
        //订单的总量
        $orderNum = D ('Order')->queryCount (['status' => array ('neq' , 9)]);
        // 本月收入
        $where['pay_type'] = array ('neq' , 3);
        $where['pay_time'] = array (array ('gt' , $begin_month) , array ('lt' , $end_month));
        $where['status'] = '2';
        $money = M ('Order')->where ($where)->sum ('pay_money') ?: 0;
        unset($where);
        $where['status'] = array ('neq' , 9);
        $switch = D ('Details')->queryCount ($where);
        unset($where);
        $array = $this->weather ('天津');
        $humidity = $array['data']['shidu'] . 'RH';
        $temperature = $array['data']['wendu'] . '℃';
        $pm25 = $array['data']['pm25'] . 'μg/m³';
        $time = $array['data']['forecast'][0]['sunrise'].'pm';
        $timestamp=strtotime($time);
        $sunset= date('H:i',$timestamp);
        $wxsj= date('Y-m-d',time());
        $times=time ();
        $this->assign ('allNum' , $allNum);
        $this->assign ('money' , $money);
        $this->assign ('orderNum' , $orderNum);
        $this->assign ('carWasherNum' , $carWasherNum);
        $this->assign ('carWasher' , $carWasher);
        $this->assign ('growthRate' , $growthRate);
        $this->assign ('switch' , $switch);
        $this->assign ('humidity' , $humidity);
        $this->assign ('temperature' , $temperature);
        $this->assign ('pm25' , $pm25);
        $this->assign ('sunset' , $sunset);
        $this->assign ('wxsj' , $wxsj);
        $this->assign ('times' , $times);
        $this->display ();
    }

    public function weather ($address) {
        if ( !empty($address) ) {
            $html = file_get_contents ("http://t.weather.sojson.com/api/weather/city/101030100");
            $array = json_decode ($html , true);
            return $array;
        }
    }

}
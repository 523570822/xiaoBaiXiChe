<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/7/26
 * Time: 11:59
 */

namespace Manager\Controller;
class InvoiceController extends BaseController {
    /**
     *发票列表
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/04/25 10:03
     */
    public function index () {
        $where = array();
        //账号查找
        if(!empty($_REQUEST['o_id'])){
            $where['o_id'] = array('LIKE',"%".I('request.o_id')."%");
        }

        //状态查找
        if(!empty($_REQUEST['status'])){
            $where['status'] = I('request.status');
        }else{
            $where['status'] = array('lt',9);
        }

        //昵称查找
        if(!empty($_REQUEST['account'])){
            $account_where['account'] = array('LIKE',I('request.account')."%");
            $data = D('Member')->where ($account_where)->getField("id", true);
            $where["m_id"] = ["in", implode ($data, ',')];
            if (empty($data)) {
                $this->display();
            }
        }

        //注册时间查找
        if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
            $where['create_time'] =array('between',array(strtotime($_REQUEST['start_time']),strtotime($_REQUEST['end_time'])+86400));
        }elseif(!empty($_REQUEST['start_time'])){
            $where['create_time'] = array('egt',strtotime($_REQUEST['start_time']));
        }elseif(!empty($_REQUEST['end_time'])){
            $where['create_time'] = array('elt',strtotime($_REQUEST['end_time'])+86399);
        }

        //排序
        $param['order'] = 'sort desc , create_time desc';
        if(!empty($_REQUEST['sort_order'])){

            $sort = explode('-',$_REQUEST['sort_order']);
            $param['order'] = $sort[0].' '.$sort[1];
//            $parameter['sort_order'] = I('request.sort_order');
        }

        $param['page_size'] = 15;
        $data = D('Invoice')->queryList($where,'*',$param);
        foreach ($data['list'] as $k=>$v){
            $account = D('Member')->queryRow(array('id'=>$v['m_id']),'account');
            $data['list'][$k]['account'] = $account['account'];
        }
        $this->assign($data);
        //页数跳转
        $this->assign('url',$this->curPageURL());
        $this->display();
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


    /**
     * 编辑发票
     * User: admin
     * Date: 2019-04-26 11:06:03
     */
    public function editInvoice() {
        var_dump($_REQUEST);
        if(IS_POST) {
            $request = I('post.');
            $rule = array(
                array('picture_id','int','请上传电子发票图'),
                array('status','int','请选择发票状态'),
            );
            $data = $this->checkParam($rule);
            $where['id'] = $request['id'];
            $data['update_time'] = time();
            $res = D('Invoice')->querySave($where,$data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $id = $_REQUEST['id'];
            $row = D('Invoice')->queryRow($id);
            $nickname = D('Member')->queryRow(array('id'=>$row['m_id']),'account');
            $row['covers'] = $this->getOnePath($row['picture_id']);
            $row['account'] = $nickname['account'];
            $this->assign('row',$row);
            $this->display();
        }
    }
}
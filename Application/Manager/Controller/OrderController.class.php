<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/01/28
 * Time: 14:59
 */

namespace Manager\Controller;


class OrderController extends BaseController
{

    /**
     * 订单列表
     * User: admin
     * Date: 2019-01-28 15:10:37
     */
    public function index() {
        $where = array();
        //按订单编号查找
        if(!empty($_REQUEST['orderid'])){
            $where['orderid'] = array('LIKE',"%".I('request.orderid')."%");
        }
        //按洗车机编号查找
        if(!empty($_REQUEST['mc_code'])){
            $account_where['mc_code'] = array('LIKE',I('request.mc_code')."%");
            $data['id'] = D('CarWasher')->where ($account_where)->getField("id", true);
            $where["c_id"] = ["in", implode ($data['id'], ',')];
            if (empty($data)) {
                $this->display();
            }
        }
        //按用户账号查找
        if(!empty($_REQUEST['account'])){
            $account_where['account'] = array('LIKE',I('request.account')."%");
            $data = D('Member')->where ($account_where)->getField("id", true);
            $where["m_id"] = ["in", implode ($data, ',')];
            if (empty($data))
            {
                $this->display();
            }
        }
        //订单类型查找
        if ( !empty($_REQUEST['o_type']) ) {
            $where['o_type'] = I ('request.o_type');
        }
        //支付方式类型查找
        if ( !empty($_REQUEST['pay_type']) ) {
            $where['pay_type'] = I ('request.pay_type');
        }
        //按订单状态查找
        if ( !empty($_REQUEST['status']) ) {
            $where['status'] = I ('request.status');
        }
        if (!$_REQUEST['status']){
            $where['status'] = array('lt',9);
        }
        //注册时间查找
        if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
            $where['create_time'] =array('between',array(strtotime($_REQUEST['start_time']),strtotime($_REQUEST['end_time'])+86400));
        }elseif(!empty($_REQUEST['start_time'])){
            $where['create_time'] = array('egt',strtotime($_REQUEST['start_time']));
        }elseif(!empty($_REQUEST['end_time'])){
            $where['create_time'] = array('elt',strtotime($_REQUEST['end_time'])+86399);
        }
        $param['page_size'] = 15;
        $data = D('Order')->queryList($where, '*',$param);
        foreach ($data['list'] as $k=>$v){
            $data['list'][$k]['m_id']=$v['m_id'];
            $date = D('Member')->where (array ('id'=>$data['list'][$k]['m_id']))->field ('account')->find();
            $data['list'][$k]['account']=$date['account'];
            $mc_code = M('CarWasher')->where(array('id'=>$data['list'][$k]['c_id']))->field('mc_code')->find();
            $data['list'][$k]['mc_code']=$mc_code['mc_code'];
        }
        $this->assign($data);
        //页数跳转
        $this->assign('url',$this->curPageURL());
        $this->display();
    }

    /**
     * 订单详情
     * User: admin
     * Date: 2019-01-28 17:24:38
     */
    public function infoOrder() {
        $id = $_GET['id'];
        $row = D ('Order')->find ($id);
        $account = D('Member')->where (array ('id'=>$row['m_id']))->field ('account')->find();
        $this->assign ('row' , $row);
        $this->assign ('account' , $account);
        $this->display();
    }

    /**
     *导出搜索
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/05/13 09:25
     */
    public function deriveOrder() {
        $where = array();
        //按订单编号查找
        if(!empty($_REQUEST['orderid'])){
            $where['orderid'] = array('LIKE',"%".I('request.orderid')."%");
        }
        //按洗车机编号查找
        if(!empty($_REQUEST['mc_code'])){
            $account_where['mc_code'] = array('LIKE',I('request.mc_code')."%");
            $data['id'] = D('CarWasher')->where ($account_where)->getField("id", true);
            $where["c_id"] = ["in", implode ($data['id'], ',')];
            if (empty($data)) {
                $this->display();
            }
        }
        //按用户账号查找
        if(!empty($_REQUEST['account'])){
            $account_where['account'] = array('LIKE',I('request.account')."%");
            $data = D('Member')->where ($account_where)->getField("id", true);
            $where["m_id"] = ["in", implode ($data, ',')];
            if (empty($data))
            {
                $this->display();
            }
        }
        //订单类型查找
        if ( !empty($_REQUEST['o_type']) ) {
            $where['o_type'] = I ('request.o_type');
        }
        //支付方式类型查找
        if ( !empty($_REQUEST['pay_type']) ) {
            $where['pay_type'] = I ('request.pay_type');
        }
        //按订单状态查找
        if ( !empty($_REQUEST['status']) ) {
            if($_REQUEST['status'] == 2){
                $where['status'] = array('in','2,9');
            }elseif($_REQUEST['status'] == 1){
                $where['status'] = 1;
            }

        }
        if (!$_REQUEST['status']){
            $where['status'] = array('elt',9);
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
        $param['order'] = 'create_time desc';
        if ( !empty($_REQUEST['sort_order']) ) {
            $sort = explode ('-' , $_REQUEST['sort_order']);
            $param['order'] = $sort[0] . ' ' . $sort[1];
    }
        $field = 'orderid,c_id,m_id,pay_money,start_time,update_time,status';
        $data = D ('Order')->queryList ($where , $field , $param);
        if ( empty($data) ) {
            $this->display ('index');
        }
        //把对应的数据放到数组中
        foreach ( $data as $key => $val ) {
            $cid = M('CarWasher')->where(array('id' =>$val['c_id'] ))->find();
            $data[$key]['c_id'] = $cid['mc_code'];
            $mid = M('Member')->where(array('id' =>$val['m_id'] ))->find();
            $data[$key]['m_id'] = $mid['account'];
            if(!empty($data[$key]['start_time'])){
                $data[$key]['start_time'] = date ('Y-m-d H:i:s' , $data[$key]['start_time']);
            }else{
                $data[$key]['start_time'] = '';
            }
            if(!empty($data[$key]['update_time'])){
                $data[$key]['update_time'] = date ('Y-m-d H:i:s' , $data[$key]['update_time']);
            }else{
                $data[$key]['update_time'] = '';
            }

            if ( $data[$key]['status'] == '1' ) {
                $data[$key]['status'] = '待支付';
            } elseif ( $data[$key]['status'] == '2' ) {
                $data[$key]['status'] = '已支付';
            } elseif ( $data[$key]['status'] == '9' ) {
                $data[$key]['status'] = '已支付';
            }
        }
        //下面方法第一个数组，是需要的数据数组
        //第二个数组是excel的第一行标题,如果为空则没有标题
        //第三个是下载的文件名，现在用的是当前导出日期
        $header = array ('订单编号' , '洗车机编号' , '用户账号' , '花费金额' ,  '洗车时间' , '洗车结束时间' , '状态');
        $indexKey = array ('orderid' , 'c_id' , 'm_id' , 'pay_money' , 'start_time' , 'update_time' , 'status');
        exportExcels ($data , $indexKey , $header , date ('订单表' . 'Y-m-d' , NOW_TIME));
    }

}
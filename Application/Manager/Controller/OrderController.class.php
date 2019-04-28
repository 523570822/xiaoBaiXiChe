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
}
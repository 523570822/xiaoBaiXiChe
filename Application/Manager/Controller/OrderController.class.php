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
        $parameter = array();
        //按订单编号查找
        if(!empty($_REQUEST['orderid'])){
            $where['orderid'] = array('LIKE',"%".I('request.orderid')."%");
            $parameter['orderid'] = I('request.orderid');
        }
        //订单类型查找
        if(!empty($_REQUEST['o_type'])){
            $where['o_type'] = array('LIKE',"%".I('request.o_type')."%");
            $parameter['o_type'] = I('request.o_type');
        }
        //按订单状态查找
        if(!empty($_REQUEST['status'])){
            $where['status'] = array('LIKE',"%".I('request.status')."%");
            $parameter['status'] = I('request.status');
        }

        if (!$_REQUEST['status']){
            $where['status'] = array('lt',9);
        }
        $param['page_size'] = 15;
        $data = D('Order')->queryList($where, '*',$param);
        foreach ($data['list'] as $k=>$v){
            $data['list'][$k]['m_id']=$v['m_id'];
            $date = D('Member')->where (array ('id'=>$data['list'][$k]['m_id']))->field ('account')->find();
            $data['list'][$k]['account']=$date['account'];

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

    }
}
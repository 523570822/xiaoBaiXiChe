<?php

namespace Manager\Controller;
class BankCardController extends BaseController
{
    /**
     * 用户银行卡列表
     * User: admin
     * Date: 2019-03-05 17:06:42
     */
    public function index() {
        $where = array();
        if(!empty($_REQUEST['card_name'])){
            $where['card_name'] = array('LIKE',"%".I('request.card_name')."%");
        }
        if(!empty($_REQUEST['card_code'])){
            $where['card_code'] =array('LIKE',I('request.card_code')."%");
        }
        if(!empty($_REQUEST['ID_card'])){
            $where['ID_card'] = array('LIKE',I('request.ID_card')."%");
        }
        if(!empty($_REQUEST['phone'])){
            $where['phone'] =  array('LIKE',I('request.phone')."%");
        }
        $param['order'] = 'sort desc , create_time desc';
        if(!empty($_REQUEST['sort_order'])){
            $sort = explode('-',$_REQUEST['sort_order']);
            $param['order'] = $sort[0].' '.$sort[1];
        }
        if (!$_REQUEST['status']){
            $where['status'] = array('lt',9);
        }
        $param['page_size'] = 15;
        $data = D('BankCard')->queryList($where, '*',$param);
        foreach ($data['list'] as $k=>$v){
            $data['list'][$k]['agent_id']=$v['agent_id'];
            $data['list'][$k]['card_id']=$v['card_id'];
            $dates = D('BankType')->where (array ('id'=>$data['list'][$k]['card_id']))->field ('bank_name')->find();
            $date = D('Agent')->where (array ('id'=>$data['list'][$k]['agent_id']))->field ('nickname')->find();
            $data['list'][$k]['nickname']=$date['nickname'];
            $data['list'][$k]['bank_name']=$dates['bank_name'];
        }
        $this->assign($data);
        $this->assign('url',$this->curPageURL());
        $this->display();
    }
}
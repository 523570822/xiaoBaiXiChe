<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-02-13
 * Time: 14:29:51
 */

namespace Manager\Controller;


class WithdrawController extends BaseController
{


    /**
     *提现列表
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/13 14:37
     */
    public function index() {
        $where = array();
        $parameter = array();
        //账号查找
        /*if(!empty($_REQUEST['account'])){
            $where['account'] = array('LIKE',"%".I('request.account')."%");
            $parameter['account'] = I('request.account');
        }*/
//        //昵称查找
        if(!empty($_REQUEST['nickname'])){
            $where['nickname'] = array('LIKE',"%".I('request.nickname')."%");
            $parameter['nickname'] = I('request.nickname');
        }
        //使用状态查找
        if(!empty($_REQUEST['type'])){
            $where['type'] = I('request.type');
            $parameter['type'] = I('request.type');
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
            $parameter['sort_order'] = I('request.sort_order');
        }
        if (!$_REQUEST['status']){
            $where['status'] = array('lt',9);
        }
        $param['page_size'] = 15;
        $data = D('withdraw')->queryList($where, '*',$param);
        foreach ($data['list'] as $k=>$v){
            $data['list'][$k]['agent_id']=$v['agent_id'];
            $data['list'][$k]['card_id']=$v['card_id'];
            $date = D('Agent')->where (array ('id'=>$data['list'][$k]['agent_id']))->field ('nickname')->find();
            $card = D('BankCard')->where (array ('id'=>$data['list'][$k]['card_id']))->field ('card_code ,card_name,card_id')->find();
            //$card = D('BankCard')->where (array ('id'=>$data['list'][$k]['card_id']))->field ('card_code ,card_name,card_id')->find();
            $cards = D('BankType')->where (array ('id'=>$card['card_id']))->field ('bank_name,bank_pic')->find();
            $data['list'][$k]['card_type'] = $cards['bank_name'];
            $data['list'][$k]['card_name'] = $card['card_name'];
            $data['list'][$k]['bank_pic'] = $cards['bank_pic'];
            $data['list'][$k]['nickname']=$date['nickname'];
            $data['list'][$k]['card_code']=$card['card_code'];
        }
//        var_dump($data);exit;
        $this->assign($data);
        //页数跳转
        $this->assign('url',$this->curPageURL());
        $this->display();
    }

    /**
     * 改变状态
     * User: admin
     * Date: 2019-02-13 16:36:55
     */
    public function yesWithdraw() {
        $id = $_POST['id'];
        $data['status'] = 2;
        $Res = D('Withdraw')->querySave($id, $data);
        if($Res){
            $this->apiResponse('1','同意体现');
        }
    }

    /**
     * 拒绝提现
     * User: admin
     * Date: 2019-02-14 14:54:19
     */
    public function noWithdraw() {
        $id = $_POST['id'];
        $data['status'] = 3;
        $Res = D('Withdraw')->querySave($id, $data);
        if($Res){
            $this->apiResponse('1','拒绝提现');
        }
    }
}
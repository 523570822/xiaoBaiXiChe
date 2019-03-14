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
        //账号查找
        /*if(!empty($_REQUEST['account'])){
            $where['account'] = array('LIKE',"%".I('request.account')."%");
        }*/
//        //昵称查找
        if(!empty($_REQUEST['nickname'])){
            $where['nickname'] = array('LIKE',"%".I('request.nickname')."%");
        }
        //使用状态查找
        if(!empty($_REQUEST['type'])){
            $where['type'] = I('request.type');
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
            $this->apiResponse('1','同意提现');
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

    /**
     * 导出用户
     * User: admin
     * Date: 2018-08-25 17:19:41
     */
    public function exportMember() {
        $where = array();
        $parameter = array();
        //账号查找
        if(!empty($_REQUEST['account'])){
            $where['account'] = array('LIKE',"%".I('request.account')."%");
            $parameter['account'] = I('request.account');
        }
        //昵称查找
        if(!empty($_REQUEST['nickname'])){
            $where['nickname'] = array('LIKE',"%".I('request.nickname')."%");
            $parameter['nickname'] = I('request.nickname');
        }
        //性别查找
        if(!empty($_REQUEST['sex'])){
            $where['sex'] = I('request.sex');
            $parameter['sex'] = I('request.sex');
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

        $where['status'] = array('lt',9);
        $where['order'] = 'id asc';
        // $param['page_size'] = 15;
        $data = D('Member')->queryList($where,'id,account,nickname,sex,create_time');
        foreach ($data as $k=>$v){
            $data[$k]['sex']=$data[$k]['sex']=1?'男':'';
            $data[$k]['sex']=$data[$k]['sex']=2?'女':'';
            $data[$k]['sex']=$data[$k]['sex']=3?'保密':'';
            $data[$k]['create_time']=date ('Y-m-d H:i:s',$data[$k]['create_time']);
        }
        if(empty($data)){
            $this->display ('index');
        }

        //把对应的数据放到数组中
        foreach($data as $key =>$val){
            $data[$key]['account']= $val['account'];
            $data[$key]['nickname']= $val['nickname'];
            $data[$key]['sex']= $val['sex'];
        }
        //下面方法第一个数组，是需要的数据数组
        //第二个数组是excel的第一行标题,如果为空则没有标题
        //第三个是下载的文件名，现在用的是当前导出日期
        exportexcel($data,array('id','账号','昵称','性别','注册时间'),date('Y-m-d',NOW_TIME));
    }
}
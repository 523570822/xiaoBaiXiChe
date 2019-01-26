<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-01-25
 * Time: 14:51:30
 */

namespace Manager\Controller;


class CarWasherController extends BaseController
{


    /**
     * 洗车机列表
     * User: admin
     * Date: 2019-01-25 14:52:52
     */
    public function index() {
        $where = array();
        $parameter = array();
        //洗车机编号查找
        if(!empty($_REQUEST['mc_id'])){
            $where['mc_id'] = array('LIKE',"%".I('request.mc_id')."%");
            $parameter['mc_id'] = I('request.mc_id');
        }
//        //昵称查找
//        if(!empty($_REQUEST['nickname'])){
//            $where['nickname'] = array('LIKE',"%".I('request.nickname')."%");
//            $parameter['nickname'] = I('request.nickname');
//        }
        //使用状态查找
        if(!empty($_REQUEST['type'])){
            $where['type'] = I('request.type');
            $parameter['type'] = I('request.type');
        }
        //运行状态查找
        if(!empty($_REQUEST['status'])){
            $where['status'] = I('request.status');
            $parameter['status'] = I('request.status');
        }
//        //注册时间查找
//        if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
//            $where['create_time'] =array('between',array(strtotime($_REQUEST['start_time']),strtotime($_REQUEST['end_time'])+86400));
//        }elseif(!empty($_REQUEST['start_time'])){
//            $where['create_time'] = array('egt',strtotime($_REQUEST['start_time']));
//        }elseif(!empty($_REQUEST['end_time'])){
//            $where['create_time'] = array('elt',strtotime($_REQUEST['end_time'])+86399);
//        }
        //排序
//        $param['order'] = 'sort desc , create_time desc';
//        if(!empty($_REQUEST['sort_order'])){
//            $sort = explode('-',$_REQUEST['sort_order']);
//            $param['order'] = $sort[0].' '.$sort[1];
//            $parameter['sort_order'] = I('request.sort_order');
//        }
        if (!$_REQUEST['status']){
        $where['status'] = array('lt',9);
        }
        $param['page_size'] = 15;
        $data = D('CarWasher')->queryList($where, '*',$param);
        foreach ($data['list'] as $k=>$v){
            $data['list'][$k]['agent_id']=$v['agent_id'];
            $data['list'][$k]['p_id']=$v['p_id'];
            $date = D('Agent')->where (array ('id'=>$data['list'][$k]['agent_id']))->field ('nickname')->find();
            $data['list'][$k]['nickname']=$date['nickname'];
            $date = D('Washshop')->where (array ('id'=>$data['list'][$k]['p_id']))->field ('shop_name')->find();
            $data['list'][$k]['shop_name']=$date['shop_name'];
        }
        $this->assign($data);
        //页数跳转
        $this->assign('url',$this->curPageURL());
        $this->display();
    }

    /**
     * 编辑洗车机信息
     * User: admin
     * Date: 2019-01-25 17:30:49
     */
    public function editCarWasher() {
        if(IS_POST) {
            $request = I('post.');
            $rule = array(
                array('account','phone','用户名必须为手机号格式'),
                array('password','string','请输入密码'),
                array('nickname','string','请输入昵称'),
                array('head_pic','int','请上传头像'),
                array('email','email','请输入邮箱'),
                array('sex','int','请选择性别'),
            );
            $data = $this->checkParam($rule);
            $where['id'] = $request['id'];
            $data['update_time'] = time();
            $res = D('Member')->querySave($where,$data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $id = $_GET['id'];
            $row = D('Member')->queryRow($id);
            $row['covers'] = $this->getOnePath($row['head_pic'],0);
            $this->assign('row',$row);
            $this->display();
        }
    }

    /**
     * 添加洗车机
     * User: admin
     * Date: 2019-01-25 17:32:02
     */
    public function addCarWasher() {

    }

//    /**
//     * 删除洗车机
//     * User: admin
//     * Date: 2019-01-25 17:33:16
//     */
//    public function delete() {
//
//    }
}
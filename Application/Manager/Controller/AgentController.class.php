<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-02-12
 * Time: 02:02:43
 */

namespace Manager\Controller;


class AgentController extends BaseController
{
    

    /**
     * 代理商列表
     * User: admin
     * Date: 2019-01-25 14:52:52
     */
    public function index() {
        $where = array();
        $parameter = array();
        //账号查找
        if(!empty($_REQUEST['account'])){
            $where['account'] = array('LIKE',"%".I('request.account')."%");
            $parameter['account'] = I('request.account');
        }
//        //昵称查找
        if(!empty($_REQUEST['nickname'])){
            $where['nickname'] = array('LIKE',"%".I('request.nickname')."%");
            $parameter['nickname'] = I('request.nickname');
        }
//        //使用状态查找
//        if(!empty($_REQUEST['type'])){
//            $where['type'] = I('request.type');
//            $parameter['type'] = I('request.type');
//        }
        //等級查找
        if(!empty($_REQUEST['grade'])){
            $where['grade'] = I('request.grade');
            $parameter['grade'] = I('request.grade');
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
        $data = D('Agent')->queryList($where, '*',$param);
//        foreach ($data['list'] as $k=>$v){
//            $data['list'][$k]['agent_id']=$v['agent_id'];
//            $data['list'][$k]['p_id']=$v['p_id'];
//            $date = D('Agent')->where (array ('id'=>$data['list'][$k]['agent_id']))->field ('nickname')->find();
//            $data['list'][$k]['nickname']=$date['nickname'];
//            $date = D('Washshop')->where (array ('id'=>$data['list'][$k]['p_id']))->field ('shop_name')->find();
//            $data['list'][$k]['shop_name']=$date['shop_name'];
//        }
        $this->assign($data);
        //页数跳转
        $this->assign('url',$this->curPageURL());
        $this->display();
    }

    /**
     * 添加代理商
     * User: admin
     * Date: 2019-02-12 02:27:54
     */
    public function addAgent() {
        if(IS_POST) {
            $rule = array(
                array('account','phone','用户名必须为手机号格式'),
                array('passwords','string','请输入密码'),
                array('nickname','string','请输入昵称'),
                array('grade','int','请输入等级'),
                array('p_id','int','请输入父级代理商ID'),
                array('balance','int','请输入余额'),
            );
            $data = $this->checkParam($rule);
            $data['token'] = $this->createToken();
            $data['salt'] = NoticeStr(6);
            $data['create_time'] = time();
            $data['password'] = CreatePassword($rule['passwords'], $data['salt']);
//            $data['update_time'] = time();
            $res = D('Agent')->addRow($data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $this->display('addAgent');
        }    }

    /**
     * 编辑代理商
     * User: admin
     * Date: 2019-02-12 03:09:27
     */
    public function editAgent() {
        if(IS_POST) {
            $request = I('post.');
            $rule = array(
                array('account','phone','用户名必须为手机号格式'),
                array('nickname','string','请输入昵称'),
                array('grade','int','请输入等级'),
            );
            $data = $this->checkParam($rule);
            $where['id'] = $request['id'];
            $data = $this->checkParam($rule);
            $data['token'] = $this->createToken();
            $data['salt'] = NoticeStr(6);
            $data['update_time'] = time();
            $res = D('Agent')->querySave($where,$data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $id = $_GET['id'];
            $row = D('Agent')->queryRow($id);
            $this->assign('row',$row);
            $this->display();
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2018-08-18
 * Time: 11:00:39
 */

namespace Manager\Controller;


class MemberController extends BaseController
{

    /**
     * 用户列表
     * User: admin
     * Date: 2018-08-18 10:59:38
     */
    public function index() {
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
        $param['page_size'] = 15;
        $data = D('Member')->queryList($where,'*',$param);
        $this->assign($data);
        //页数跳转
        $this->assign('url',$this->curPageURL());
        $this->display();
    }

    /**
     * 添加用户
     * User: admin
     * Date: 2018-08-18 11:00:39
     */
    public function addMember() {
        if(IS_POST) {
            $rule = array(
                array('account','phone','用户名必须为手机号格式'),
                array('password','string','请输入密码'),
                array('nickname','string','请输入昵称'),
                array('head_pic','int','请上传头像'),
                array('email','email','请输入邮箱'),
                array('sex','int','请选择性别'),
            );
            $data = $this->checkParam($rule);
            $data['create_time'] = time();
            $data['update_time'] = time();
            $res = D('Member')->addRow($data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $this->display('editMember');
        }
    }

    /**
     * 编辑用户
     * User: admin
     * Date: 2018-08-18 11:01:52
     */
    public function editMember() {
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
            $where['head_pic'] = $request['head_pic'];
            $data['update_time'] = time();
            $res = D('Member')->querySave($where,$data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $id = $_GET['id'];
            $row = D('Member')->queryRow($id);
            $row['covers'] = $this->getOnePath($row['head_pic']);
            $this->assign('row',$row);
            $this->display();
        }
    }

    /**
     * 删除用户
     * User: admin
     * Date: 2018-08-18 11:00:57
     */
    public function delMember() {
        $id = $this->checkParam(array('id', 'int'));
        $Res = D('Member')->querySave($id, array('status'=>9));
        $Res ? $this->apiResponse(1, '删除成功') : $this->apiResponse(0, '删除失败');
    }

    /**
     * 禁用用户
     * User: admin
     * Date: 2018-08-18 11:01:29
     */
    public function lockMember() {
        $id = $this->checkParam(array('id', 'int'));
        $status = D('Member')->queryField($id, 'status');
        $data = $status == 1 ? array('status'=>0) : array('status'=>1);
        $Res = D('Member')->querySave($id, $data);
        $Res ? $this->apiResponse(1, $status == 1 ? '禁用成功' : '启用成功') : $this->apiResponse(0, $status == 1 ? '禁用失败' : '启用失败');

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
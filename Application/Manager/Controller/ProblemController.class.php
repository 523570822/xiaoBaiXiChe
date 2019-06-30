<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-06-29
 * Time: 02:19:23
 */

namespace Manager\Controller;


class ProblemController extends BaseController
{


    /**
     * 问题表
     * User: admin
     * Date: 2019-06-29 02:19:23
     */
    public function index() {
        $where = array ();
        //按用户账号查找
        if(!empty($_REQUEST['content'])){
            $where['content'] = array('LIKE',"%".I('request.content')."%");
        }
        //运行状态查找
        if ( !empty($_REQUEST['status']) ) {
            $where['status'] = I('request.status');
        }
        //运行状态查找
        if ( !empty($_REQUEST['type']) ) {
            $where['type'] = I('request.type');
        }
        $param['page_size'] = 15;
        dump($where);
        $data = D ('Problem')->queryList ($where , '*' , $param);
        $this->assign ($data);
        //页数跳转
        $this->assign ('url' , $this->curPageURL ());
        $this->display ();
    }

    /**
     * 添加问题
     * User: admin
     * Date: 2019-06-29 02:19:54
     */
    public function addProblem() {
        if(IS_POST) {
            $request = $_REQUEST;
            $rule = array(
                array('content','string','请填写问题'),
                array('type','int','请选择故障类型'),
            );
            $data = $this->checkParam($rule);
            $data['create_time'] = time();
//            $res = D('Problem')->addRow($data);
            $res = D('Problem')->addRow($data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $this->display('editProblem');
        }
    }

    /**
     * 编辑问题
     * User: admin
     * Date: 2019-06-29 02:20:24
     */
    public function editProblem() {
        if(IS_POST) {
            $request = $_REQUEST;
            $rule = array(
                array('content','string','请填写问题'),
                array('type','int','请选择故障类型'),
            );
            $data = $this->checkParam($rule);

            $where['id'] = $request['id'];
            $data['update_time'] = time();
            $res = D('Problem')->querySave($where,$data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $id = $_GET['id'];
            $row = D('Problem')->queryRow($id);
            $this->assign('row',$row);
            $this->display();
        }
    }

    /**
     * 锁定问题
     * User: admin
     * Date: 2019-06-29 09:07:29
     */
    public function lockProblem() {
        $id = $this->checkParam (array ('id' , 'int'));
        $status = D ('Problem')->queryField ($id , 'status');
        $data = $status == 1 ? array ('status' => 9) : array ('status' => 1);
        $Res = D ('Problem')->querySave ($id , $data);
        $Res ? $this->apiResponse (1 , $status == 1 ? '禁用成功' : '启用成功') : $this->apiResponse (0 , $status == 1 ? '禁用失败' : '启用失败');
    }
}
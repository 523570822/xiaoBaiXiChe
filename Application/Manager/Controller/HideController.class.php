<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-06-28
 * Time: 17:49:58
 */

namespace Manager\Controller;


class HideController extends BaseController
{


    /**
     * 隐藏功能列表
     * User: admin
     * Date: 2019-06-29 01:39:23
     */
    public function index() {
        if(IS_POST) {
            $request = I('post.');
            $rule = array(
                array('one_opera','int','请选择一级营业收入'),
                array('one_partner','int','请选择一级合作方分润'),
                array('one_platform','int','请选择一级平台分润'),
                array('two_opera','int','请选择二级营业收入'),
                array('two_partner','int','请选择二级合作方分润'),
                array('two_platform','int','请选择二级平台分润'),
                array('two_father','int','请选择上级分润'),
            );
            $data = $this->checkParam($rule);
            $where['id'] = 1;
            $res = D('Appsetting')->querySave($where,$data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $data = D('Appsetting')->queryRow(array('id'=>1));
            $this->assign('row', $data);
            $this->display();
        }
    }
}
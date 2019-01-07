<?php
namespace Home\Controller;
use Common\Service\ControllerService;

class IntegralController extends BaseController {
    public function index(){
        $this->display('integral');//download
    }
    function integral(){
        $m_id=$_REQUEST['m_id'];
        $param['field'] = 'id as m_id,account,nickname,head_pic,degree,invite_code';
        $member_info = M('Member')->where(array('id'=>$m_id))->field($param['field'])->find();
        if(!$member_info) {
            $member_info['invite_code']="0";
        }

        $this->assign('code',$member_info['invite_code']);
        $this->display('integral');
    }
    public function success(){
        $this->display('download');
    }
}
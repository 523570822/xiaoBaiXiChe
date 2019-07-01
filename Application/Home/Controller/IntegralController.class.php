<?php
namespace Home\Controller;
use Common\Service\ControllerService;

class IntegralController extends BaseController {
    public function index(){
        $this->display('integral');//download
    }
    //https://www.xiaojingxiche.com/Integral/integral?m_id=1 分享链接
    //https://www.xiaojingxiche.com/Integral/download 下载链接
    function integral(){
        dump($_REQUEST);exit;

        $m_id=$_REQUEST['m_id'];

        $param['field'] = 'id as m_id,account,nickname,head_pic,degree,invite_code';
        if(!empty($m_id)){
            $member_info = M('Member')->where(array('id'=>$m_id))->field($param['field'])->find();
        }else{
            $token=$_REQUEST['token'];
            $member_info = M('Member')->where(array('token'=>$token))->field($param['field'])->find();
        }
        if(!$member_info) {
            $member_info['invite_code']="0";
        }
        $this->assign('code',$member_info['invite_code']);
        $this->display();
    }

    public function success(){
        $this->display('download');
    }
}
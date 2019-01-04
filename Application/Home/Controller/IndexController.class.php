<?php
namespace Home\Controller;
use Common\Service\ControllerService;

class IndexController extends BaseController {

    public function index(){
        $this->display('index');
//        $rule = array(
//            array('email', 'email', '请输入邮箱'),
//            array('phone', 'phone', '请输入手机号'),
//            array('idcard', 'string#isIdcard', '输入正确的身份证号')
//        );
//
//        echo '<meta charset="utf8">';
//        $this->checkParam($rule);
//        $rule = array(
//            'id|string#is_string|请输入ID',
//            'username|string|请输入用户名',
//            'password|string=6|请输入用户名'
//        );
//        $rule = 'password|string=6|请输入用户名';
//        $rule = array('username');
//
//        $rule = array('username', 'password|string=6');
//        $rule = array(
//            'id|string#is_string|请输入ID',
//            'username|string|请输入用户名',
//            'password|string=6|请输入用户名'
//        );
//        $rule = array('username', 'string=6');
//        $rule = array(array('username', 'string=6'), array('password'));
//        $data = $this->checkParam($rule);
//        dump($data);
//        $a = call_user_func('NoticeStr', 10);
//        dump($a);
//        $a = D('User')->querySave(array('nickname'=>''), array('add_time'=>8));
//        echo D('User')->getLastSql();
//        dump($a);
//        $rule = array(
//        );
//        $rule = 'id|string#is_string|请输入ID';
//        $rule = array(
//            array('id', 'int=6', '请输入正确的ID'),
//            array('name', 'string=1')
//        );
//        $data = $this->CheckParam($rule);
//        dump($data);
//        vendor('Txunda.Txunda#Param');
//        $param = new \Param();
//        $param->checkParam();
    }
}
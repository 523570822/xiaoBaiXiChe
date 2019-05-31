<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/05/28
 * Time: 01:57
 */

namespace Home\Controller;
use Common\Service\ControllerService;



class ServiceController extends BaseController
{
    public function index(){
        $info = M('Info')->where(array('type'=>1))->field('content')->find();
        $this->assign('info',$info);
        $this->display('service');//download
    }
}
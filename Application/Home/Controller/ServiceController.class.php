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
        $this->display('service');//download
    }
}
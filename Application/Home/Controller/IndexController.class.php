<?php
namespace Home\Controller;
use Common\Service\ControllerService;

class IndexController extends BaseController {

    public function index(){
        $this->display('index');
    }
}
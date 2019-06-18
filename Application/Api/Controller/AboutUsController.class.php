<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/01/18
 * Time: 05:26
 */

namespace Api\Controller;
use Common\Service\ControllerService;

class AboutUsController extends BaseController
{

    /**
     *构造方法
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/18 14:34
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     *关于我们
     **/
    public function aboutUs(){
        $aboutus_info = C('APP');
        $picture['app_agent'] =$this->getOnePath ($aboutus_info['app_logo']);
        $this->apiResponse('1','查询成功',array ('app_agent'=>$picture['app_agent'],'app_name'=>$aboutus_info['app_name'],'app_version'=>$aboutus_info['app_version'],'app_intro'=>$aboutus_info['app_intro']));
    }
}
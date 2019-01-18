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
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/18 05:28
     */
    public function AboutUsList(){
        $find = M('AboutUs')->where(array('id'=>1))->field('us_name,us_pic,company,address')->find();
        $this->apiResponse('1','成功',$find);
    }
}
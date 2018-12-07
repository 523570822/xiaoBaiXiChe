<?php
namespace Api\Controller;

class UpgradeController extends BaseController{

    /**
     * 版本检测
     * 传递参数的方式：post
     * 需要传递的参数：
     */

    public function updateAndroid(){

        $AndroidPull = C('APP');


        $this->apiResponse('1','请求成功',$AndroidPull);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/19
 * Time: 15:28
 */

namespace Api\Controller;
use Common\Service\ControllerService;

class IncomeController extends BaseController
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
     *收益
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/19 02:01
     */
    public function income(){
        $post = checkAppData();
        $post['time'] = date('Y-m-d');
        $incomel = D('Income')->where(array())->field('id')->select();
        echo D('Income')->_sql();
        if($incomel){
//            foreach($incomel as $k=>$v){
//                $create[] = date('Y-m-d',$v['update_time']);
//            }
            var_dump($incomel);exit;
        }
    }


}
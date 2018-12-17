<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 10:02
 */
namespace Api\Controller;
use Common\Service\ControllerService;
/**
 * 订单模块
 * Class MsgController
 * @package Api\Controller
 */
class OrderController extends BaseController{
    /**
     * 初始化方法
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 下订单
     * 没写
     */
    public function placingOrder()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $rule = array(
            array('id','string','三方登录唯一标识不能为空'),
            // array('nickname','string','昵称不能为空'),
            array('type','string','类型错误'),
        );
        $this->checkParam($rule);
    }

    /**
     * 我的订单
     */
    public function myOrder(){
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        unset($param);
//        $param['where']['m_id'] = $m_id;
//        $param['where']['status'] = array('neq', 9);
//        $member_info = D('Order')->where('wc_id')->queryList($param['where']);
        $orderList = M('Order')
            ->where(array('m_id' => $m_id))
            ->where(array('status' => array('neq', 9)))
            ->field('id,wc_id,orderid,status,money,pay_money' )
            ->order('update_time asc')
            ->select();
        $this->apiResponse('1', '请求成功', $orderList);
    }

    /**
     * 订单详情
     * 没写
     */
    public function orderDetails(){
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        unset($param);
//        $param['where']['m_id'] = $m_id;
//        $param['where']['status'] = array('neq', 9);
//        $member_info = D('Order')->where('wc_id')->queryList($param['where']);
        $orderList = M('Order')
            ->where(array('m_id' => $m_id))
            ->where(array('status' => array('neq', 9)))
            ->field('*' )
            ->order('update_time asc')
            ->select();
        $this->apiResponse('1', '请求成功', $orderList);
    }
}
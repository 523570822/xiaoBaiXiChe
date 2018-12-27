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
class OrderController extends BaseController
{
    /**
     * 初始化方法
     */
    public function _initialize ()
    {
        parent::_initialize ();
    }

    /**
     *洗车机列表
     **/
    public function carwashList ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('id' , 'string' , '请选择洗车门店');
        $this->checkParam ($rule);
        $car_washer = M ('CarWasher')->where (array ('p_id' => $request['id']))->select ();
        $this->apiResponse ('1' , '洗车机列表' , $car_washer);
    }

    /**
     * 下订单
     * o_type 订单类型//1洗车订单 2小鲸卡购买
     * w_type 洗车类型//1普通洗车订单 2预约洗车订单
     * 机器运作状态//1正常 2故障 3报警 4不在线 9删除
     * 机器使用状态//1空闲中 2使用中 3预订中 4暂停中
     * 暂时这样写
     */
    public function placingOrder ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('o_type' , 'string' , '请输入订单类型');
        $this->checkParam ($rule);
        if ( $request['o_type'] == '1' ) {
            $rule = array ('w_type' , 'string' , '请输入洗车类型');
            $this->checkParam ($rule);
            if ( $request['w_type'] == '1' ) {
                $rule = array ('mc_id' , 'string' , '请输入洗车机编号');
                $this->checkParam ($rule);
                $car_washer_info = M ('CarWasher')->where (array ('mc_id' => $request['mc_id']))->find ();
                if ( !$request['mc_id'] = $car_washer_info['mc_id'] ) {
                    $this->apiResponse ('0' , '找不到该机器' , $php_errormsg);
                }
                $member_info = M ('Member')->where (array ('id' => $m_id))->find ();
                $data['m_id'] = $m_id;
                $data['w_id'] = $car_washer_info['p_id'];
                $data['orderid'] = date ('YmdHi') . rand (100 , 999);
                $data['o_type'] = '1';
                $data['w_type'] = '1';
                $data['create_time'] = time ();
                $data['mc_id'] = $car_washer_info['mc_id'];
                $data['mobile'] = $member_info['account'];
                $res = M ('Order')->data ($data)->add ();
                if ( $res ) {
                    $this->apiResponse ('1' , '下单成功' , array ('orderid' => $data['orderid']));
                } else {
                    $this->apiResponse ('0' , '下单失败');
                }
            }
            if ( $request['w_type'] == 2 ) {
                $rule = array (
                    array ('' , 'string' , '预约洗车') ,
                    array ('' , 'string' , '预约洗车') ,
                );
                $this->checkParam ($rule);
                $car_washer_info = M ('CarWasher')->where (array ('mc_id' => $request['mc_id']))->find ();
                if ( !$request['mc_id'] = $car_washer_info['mc_id'] ) {
                    $this->apiResponse ('0' , '找不到该机器' , $php_errormsg);
                }
                $member_info = M ('Member')->where (array ('id' => $m_id))->find ();
                $data['m_id'] = $m_id;
                $data['w_id'] = $car_washer_info['p_id'];
                $data['orderid'] = date ('YmdHi') . rand (100 , 999);
                $data['o_type'] = '1';
                $data['w_type'] = '2';
                $data['create_time'] = time ();
                $data['mc_id'] = $car_washer_info['mc_id'];
                $data['mobile'] = $member_info['account'];
                $res = M ('Order')->data ($data)->add ();
                if ( $res ) {
                    $this->apiResponse ('1' , '预约成功' , array ('orderid' => $data['orderid']));
                } else {
                    $this->apiResponse ('0' , '预约失败');
                }
            }
        }
        if ( $request['o_type'] == 2 ) {
            $rule = array (
                array ('' , 'string' , '小鲸卡') ,
                array ('' , 'string' , '小鲸卡') ,
            );
            $this->checkParam ($rule);
        }
    }

    /**
     * 我的订单
     */
    public function myOrder ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        unset($param);
//        $param['where']['m_id'] = $m_id;
//        $param['where']['status'] = array('neq', 9);
//        $member_info = D('Order')->where('wc_id')->queryList($param['where']);
        $orderList = M ('Order')
            ->where (array ('m_id' => $m_id))
            ->where (array ('status' => array ('neq' , 9)))
            ->field ('id,mc_id,orderid,status,money,pay_money')
            ->order ('create_time desc')
            ->select ();
        $this->apiResponse ('1' , '请求成功' , $orderList);
    }

    /**
     * 订单详情
     **/
    public function orderDetails ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('orderid' , 'string' , '参数不能为空');
        $this->checkParam ($rule);
        $orderList = M ('Order')
            ->where (array ('m_id' => $m_id))
            ->where (array ('status' => array ('neq' , 9)))
//            ->where(array('pay_type' => array('neq', 9)))
            ->where (array ('orderid' => $request['orderid']))
            ->field ('*')
            ->order ('update_time asc')
            ->select ();
        $this->apiResponse ('1' , '请求成功' , $orderList);
    }

    /**
     * 取消订单
     **/
    function orderCancel ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('orderid' , 'string' , '参数不能为空'
//            array(),
//            array('type','string','参数不能为空'),
        );
        $this->checkParam ($rule);
        $where['orderid'] = $request['orderid'];
        D ('Order')->where ($where)->find ();
//        if($_REQUEST['type']==1){
        $datal['pay_type'] = "9";
        $where['orderid'] = $request['orderid'];
        //  $where['pay_type']=array("neq"=>"9");
        $orderid = D ('Order')->where ($where)->find ();
        if ( $orderid ) {
        } else {
            $this->apiResponse (0 , '订单不存在');
        }
        $datal['update_time'] = time ();
        D ('Order')->where ($where)->save ($datal);
        $this->apiResponse (1 , '取消订单成功');
//        }elseif($_REQUEST['type']==1){
//            $where['orderid']=$request['orderid'];
//            $where['status']=array("neq","9");
//            $orderid=D('Carwash')->where($where)->find();//var_dump($orderid);die;
//            if($orderid){}else{
//                $this->apiResponse(0, '订单不存在');
//            }
//            $datal['status']="9";
//            D('Order')->where($where)->save($datal);
//            $this->apiResponse(1, '取消订单成功');
//        }
    }

    /**
     * 可开发票的订单列表
     * o_type 订单类型//1洗车订单 2小鲸卡订单
     */
    public function canInvoice ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('o_type' , 'string' , "请选择开票订单类型");
        $this->checkParam ($rule);
        unset($param);
        $page = (I ("p")) ? I ("p") : 1;
        $param['where']['m_id'] = $m_id;
        $param['where']['status'] = 2;
        $param['where']['invoice'] = 0;
        $param['where']['o_type'] = $request['o_type'];
        $order_info = D ('Order')
            ->where ($param['where'])
            ->where (array ('pay_type' => array ('neq' , 9)))
            ->field ('pay_money,orderid,create_time')
            ->order ("create_time desc")
            ->page ($page , 15)
            ->select ();
        foreach ( $order_info as $key => $v ) {
//            $order_info[$key]['order_reserve'] =mdate($v['order_reserve']);//预约时间
            $order_info[$key]['create_time'] = date ("Y/m/d H:i" , $v['create_time']);  //洗车时间
        }
        if ( $order_info ) {
            $message = '请求成功';
        } else {
            $message = '没有更多数据了';
        }
        $this->apiResponse (1 , $message , $order_info);
    }

    /**
     * 开具发票
     **/
    public function invoice ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = I("");
        $rule = array (
            array ('orderid' , 'string' , "请输入订单编号") ,
            array ('b_method' , 'int' , '请选择开票方式') ,//开票方式 1商品类别 2商品明细
            array ('type' , 'int' , '请选择发票抬头') ,//发票抬头1个人 2单位
        );
        $this->checkParam ($rule);
        if ( $request['type'] == '1' ) {
            $rule = array ('email' , 'email' , '请输入电子邮件');
            $this->checkParam ($rule);
        } elseif ( $request['type'] == '2' ) {
            $rule = array (
                array ('title' , 'string' , '请输入公司抬头') ,
                array ('email' , 'email' , '请输入电子邮件') ,
            );
            $this->checkParam ($rule);
        }
        if ( $request['ti_num'] ) {
            if ( strlen ($request['ti_num']) < 18 ) {
                $this->apiResponse (0 , "请输入正确的纳税人识别号");
            }
        }
        $request['o_id'] = $request['orderid'];
        $request['create_time'] = time ();
        $request['m_id'] = $m_id;
        $request['status'] = 3;
        $order_info = D ('Order')->where (array ('orderid' => $request['orderid']))->field ('pay_money')->find ();
        $save = D ("Order")->where (array ('orderid' => $request['orderid']))->save (array ('invoice' => 1));
        if ( $save ) {
            $request['money'] = $order_info['pay_money'];
            $add = M ('Invoice')->add ($request);
            $this->apiResponse (1 , "申请成功" , array ('invoice_id' => $add));
        } else {
            $this->apiResponse (0 , "申请失败");
        }
    }

    /**
     * 发票记录
     */
    public function invoiceRecord ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $invoice_list = M ('Invoice')
            ->where (array ('m_id' => $m_id))
            ->where (array ('status' => array ('neq' , 9)))
            ->field ('o_id,money,content,type,create_time')
            ->order ('update_time asc')
            ->select ();
        foreach ( $invoice_list as $key => $v ) {
            $invoice_list[$key]['create_time'] = date ("Y/m/d H:i" , $v['create_time']);
        }
        $this->apiResponse ('1' , '请求成功' , $invoice_list);
    }

    /**
     * 发票详情
     */
    public function invoiceDetails ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('o_id' , 'string' , "请输入订单编号");
        $this->checkParam ($rule);
        $invoice_xq = M ('Invoice')
            ->where (array ('o_id' => $request['o_id']))
            ->where (array ('status' => array ('neq' , 9)))
            ->field ('content,status,money,type,create_time,email')
            ->order ('update_time asc')
            ->select ();
        foreach ( $invoice_xq as $key => $v ) {
            $invoice_xq[$key]['create_time'] = date ("Y/m/d H:i" , $v['create_time']);
        }
        $this->apiResponse ('1' , '请求成功' , $invoice_xq);
    }

    /**
     * 电子发票图
     **/
    public function invoicePicture ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('o_id' , 'string' , "请输入订单编号");
        $this->checkParam ($rule);
        $data = D ("Invoice")->where (array ('o_id' => $request['o_id']))->field ('*')->find ();
        $picture['picture_id'] = C ('API_URL') . $this->getOnePath ($data['picture_id'] , C ('API_URL') . '/Uploads/Member/default.png');
        $this->apiResponse (1 , '请求成功' , array ("Picture_id" => $picture['picture_id']));
    }

    /**
     * 确认邮箱
     **/
    public function confirmEmail ()
    {
        $request = I ("");
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $rule = array (
            array ('o_id' , 'string' , "请输入订单编号") ,
//            array ('email' , 'email' , "请输入邮箱号") ,
        );
        $this->checkParam ($rule);
        $email = D ("invoice")->where (array ('o_id' => $request['o_id']))->field ('email')->find ();
        if (empty($request['email'])||$request['email'] == $email['email'] ) {
            $this->apiResponse (1 , '请求成功' , $email);
        } else {
            D ("invoice")->where (array ('o_id' => $request['o_id']))->save (array ('email' => $request['email']));
            $find = D ("invoice")->where (array ('o_id' => $request['o_id']))->field ('email')->find ();
            $this->apiResponse (1 , '修改成功' , $find);
        }
    }

    /**
     * 开发票规则
     **/
    public function invoiceRlue ()
    {
        $data = D ("Article")->where (array ('id' => 1 , 'status' => 1 , 'sort' => 1))->field ('title,content')->find ();
        $this->apiResponse (1 , '请求成功' , $data);
    }

    /**
     * 添加发票抬头
     **/
    public function invoiceRise ()
    {
        $request = I ("");
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $rule = array (
            array ('title' , 'string' , "请填写发票抬头") ,
            array ('email' , 'email' , '请填写电子邮箱') ,
//            array('default', 'int', '是否设为默认抬头'),//0不设置 1设置
        );
        $this->checkParam ($rule);
        if ( $request['ti_num'] ) {
            if ( strlen ($request['ti_num']) < 18 ) {
                $this->apiResponse (0 , "请输入正确的纳税人识别号");
            }
        }
        if ( !empty($request['default']) && $request['default'] == 1 ) {
            D ('InvoiceRise')->where (array ('m_id' => $m_id , 'default' => '1' , 'status' => '1'))->save (array ('default' => 0));
        }
        $request['m_id'] = $m_id;
        $request['status'] = 1;
        $request['create_time'] = time ();
        D ('InvoiceRise')->add ($request);
        $this->apiResponse (1 , '添加成功');
    }

    /**
     * 发票抬头列表
     **/
    public function invoiceRiseList ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $page = (I ("p")) ? I ("p") : 1;
        $param['where']['m_id'] = $m_id;
        $param['where']['status'] = 1;
        $order_info = D ('InvoiceRise')
            ->where ($param['where'])
            ->field ('id,title')
            ->order ("create_time desc")
            ->page ($page , 15)
            ->select ();
        if ( $order_info ) {
            $message = '请求成功';
        } else {
            $message = '没有更多数据了';
        }
        $this->apiResponse (1 , $message , $order_info);
    }

    /**
     * 编辑发票抬头
     **/
    public function invoiceRiseDetails ()
    {
        $request = I ("");
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $rule = array ('id' , 'string' , "请选择发票抬头");
        $this->checkParam ($rule);
        $where['id'] = $request['id'];
        $where['m_id'] = $m_id;
        $where['status'] = 1;
        $data = [];
        if ( count ($request) == 2 ) {
            $data = D ('InvoiceRise')->where ($where)->select ();
            $message = "查询成功";
        } else {
            if ( $request['ti_num'] ) {
                if ( strlen ($request['ti_num']) < 18 ) {
                    $this->apiResponse (0 , "请输入正确的纳税人识别号");
                }
            }
            if ( !empty($request['default']) && $request['default'] == 1 ) {
                D ('InvoiceRise')->where (array ('m_id' => $m_id , 'default' => '1' , 'status' => '1'))->save (array ('default' => 0));
            }
            $result = D ('InvoiceRise')->where (array ('id' => $request['id'] , 'm_id' => $m_id))->save ($request);
            if ( $result ) {
                $message = "修改成功";
                $data = D ('InvoiceRise')->where ($where)->select ();
            } else {
                $message = "修改失败";
            }
        }
        $this->apiResponse (1 , $message , $data);
    }
}
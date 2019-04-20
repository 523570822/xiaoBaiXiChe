<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 10:02
 */
namespace Api\Controller;
use Common\Service\ControllerService;
class InvoiceController extends BaseController
{
    /**
     * 可开发票的订单列表
     * o_type 订单类型//1洗车订单 2小鲸卡订单 3钱包充值
     */
    public function canInvoice ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('o_type' , 'string' , "请选择开票订单类型");
        $this->checkParam ($rule);
        unset($param);
        $page = (I ("page")) ? I ("page") : 1;
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
     *开具发票页面
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/04/20 13:36
     */
    public function invoicePage(){
        $post = checkAppData('token,orderid','token,订单编号');
        $m_id = M('Member')->where(array('token'=>$post['token']))->field('id')->find();
//        $m_id = 14;
        $invoice = M('InvoiceRise')->where(array('m_id'=>$m_id))->find();
        if(!empty($invoice)){
            $data = array(
                'title' => $invoice['title'],
                'ti_num' => $invoice['ti_num'],
                'r_adddress' => $invoice['r_adddress'],
                'r_tel	' => $invoice['r_tel'],
                'bank' => $invoice['bank'],
                'b_account' => $invoice['b_account'],
                'email' => $invoice['email'],
            );
            $this->apiResponse(1,'查询成功',$data);
        }else{
            $data = array();
            $this->apiResponse(1,'查询成功',$data);
        }
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
        $invoice = M('InvoiceRise')->where(array('m_id'=>$m_id))->find();
        $data = array(
            'title'=> $request['title'],
            'ti_num' => $request['ti_num'],
            'email' =>  $request['email'],
            'r_adddress' => $request['r_adddress'],
            'bank' => $request['bank'],
            'b_account' => $request['b_account'],
        );
        if(!empty($invoice)){
            $data['update_time'] = time();
            $invoice_rise = M('InvoiceRise')->where(array('m_id'=>$m_id))->save($data);
        }else{
            $data['m_id'] = $m_id;
            $data['create_time'] = time();
            $invoice_rise = M('InvoiceRise')->add($data);
        }
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
        $page = (I ("page")) ? I ("page") : 1;
        $invoice_list = M ('Invoice')
            ->where (array ('m_id' => $m_id))
            ->where (array ('status' => array ('neq' , 9)))
            ->field ('o_id,money,content,type,create_time')
            ->order ('update_time asc')
            ->page ($page , 15)
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
        $invoice= M ('Invoice')
            ->where (array ('o_id' => $request['o_id']))
            ->where (array ('status' => array ('neq' , 9)))
            ->field ('content,status,money,type,create_time,email')
            ->order ('update_time asc')
            ->select ();
        foreach ( $invoice as $key => $v ) {
            $invoice[$key]['create_time'] = date ("Y/m/d H:i" , $v['create_time']);
        }
        if($invoice['status'] == 4){
            $invoice_xq['invoice_count']= 1;
        }else{
            $invoice_xq['invoice_count']= 0;
        }
        $invoice_xq['order_count']= 1;
        $invoice_xq['details']=$invoice;
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
        $picture['picture_id'] = $this->getOnePath ($data['picture_id'] );
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
        $rule = array ('o_id' , 'string' , "请输入订单编号");
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
        $data = D ("Article")->where (array ('type' => 1 , 'status' => 1 ))->field ('title,content')->find ();
        $this->apiResponse (1 , '请求成功' , $data);
    }

    /**
     * 取消发票
     **/
    public function invoiceCancel ()
    {
        $request = I ("");
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);

    }
}
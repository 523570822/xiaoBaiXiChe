<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 10:02
 */
namespace Api\Controller;
use Common\Service\ControllerService;
class InvoiceRiseController extends BaseController
{
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

    /**
     * 删除发票抬头
     **/
    public function delInvoice ()
    {
        $request = I ("");
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $rule = array ('id' , 'string' , "请选择发票抬头");
        $this->checkParam ($rule);
        $result = D ('InvoiceRise')->queryDelete(array ('id'=>$request['id']));
        if ( $result ) {
            $message = "删除成功";
        } else {
            $message = "删除失败";
        }
        $this->apiResponse (1 , $message);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-06-27
 * Time: 14:47:49
 */

namespace Manager\Controller;


class BatchController extends BaseController
{


    /**
     * 代金券表
     * User: admin
     * Date: 2019-06-27 14:47:49
     */
    public function index() {
        $where = array ();
        //昵称查找
        if ( !empty($_REQUEST['title']) ) {
            $where['title'] = array ('LIKE' , I ('request.title') . "%");
//            $data = D ('Member')->where ($nickname_where)->getField ("id" , true);
//            $where["m_id"] = ["in" , implode ($data , ',')];
//            if ( empty($data) ) {
//                $this->display ();
//            }
        }
        //状态查找
        if ( !empty($_REQUEST['status']) ) {
            $where['status'] = I('request.status');
        }
        //开始使用时间查找
        if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
            $where['start_time'] =array('between',array(strtotime($_REQUEST['start_time']),strtotime($_REQUEST['end_time'])+86400));
        }elseif(!empty($_REQUEST['start_time'])){
            $where['start_time'] = array('egt',strtotime($_REQUEST['start_time']));
        }elseif(!empty($_REQUEST['end_time'])){
            $where['start_time'] = array('elt',strtotime($_REQUEST['end_time'])+86399);
        }

        //结束使用时间查找
        if(!empty($_REQUEST['starts_time']) && !empty($_REQUEST['ends_time'])){
            $where['end_time'] =array('between',array(strtotime($_REQUEST['starts_time']),strtotime($_REQUEST['ends_time'])+86400));
        }elseif(!empty($_REQUEST['starts_time'])){
            $where['end_time'] = array('egt',strtotime($_REQUEST['starts_time']));
        }elseif(!empty($_REQUEST['ends_time'])){
            $where['end_time'] = array('elt',strtotime($_REQUEST['ends_time'])+86399);
        }

        //        //排序
        //        $param['order'] = 'create_time desc';
        //        if(!empty($_REQUEST['sort_order'])){
        //            $sort = explode('-',$_REQUEST['sort_order']);
        //            $param['order'] = $sort[0].' '.$sort[1];
        //
        //        }
        $param['order'] = 'create_time desc';
        $param['page_size'] = 15;
        $data = D ('Batch')->queryList ($where , '*' , $param);
//        foreach ( $data['list'] as $k => $v ) {
//            $data['list'][$k]['m_id'] = $v['m_id'];
//            $data['list'][$k]['code_id'] = $v['code_id'];
//            $date = D ('Member')->where (array ('id' => $data['list'][$k]['m_id']))->field ('nickname')->find ();
//            $dates = D ('batch')->where (array ('id' => $data['list'][$k]['code_id']))->field ('title')->find ();
//            $data['list'][$k]['nickname'] = $date['nickname'];
//            $data['list'][$k]['title'] = $dates['title'];
//        }
        $this->assign ($data);
        //页数跳转
        $this->assign ('url' , $this->curPageURL ());
        $this->display ();
    }

    /**
     * 添加代金券
     * User: admin
     * Date: 2019-06-27 15:58:44
     */
    public function addBatch() {
        if(IS_POST) {
            $request = $_REQUEST;
            $rule = array(
                array('title','string','请填写批次'),
                array('num','string','请输入数量'),
                array('price','string','请输入价格'),
                array('start_time','string','请选择开始时间'),
                array('end_time','string','请选择过期时间'),
            );
            $data = $this->checkParam($rule);
            $data['create_time'] = time();
            $data['update_time'] = time();
            $start = strtotime($data['start_time']);
            $end = strtotime($data['end_time']);

//            $res = D('Batch')->addRow($data);
            $res = $this->redirect('Api/Coupon/couponCode',array('end_time'=>$end,'start_time'=>$start,'remark'=>$request['remark'],'prefix'=>$request['prefix'],'code_length'=>$request['code_length'],'title'=>$data['title'],'nums'=>$data['num'],'price'=>$data['price'],));
            dump($res);exit;
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $this->display('editBatch');
        }
    }

    /**
     * 编辑代金券
     * User: admin
     * Date: 2019-06-27 15:59:27
     */
    public function editBatch() {
        if(IS_POST) {
            $request = I('post.');
            $rule = array(
                array('title','string','请填写批次'),
                array('price','string','请输入价格'),
                array('start_time','string','请选择开始时间'),
                array('end_time','string','请选择过期时间'),
            );
            $data = $this->checkParam($rule);
            $where['id'] = $request['id'];
            $data['update_time'] = time();
            $res = D('Batch')->querySave($where,$data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $id = $_GET['id'];
            $row = D('Batch')->queryRow($id);
            $param['order'] = 'create_time desc';
            $param['page_size'] = 15;
            $code = D('RedeemCode')->queryList(array('b_id'=>$row['id']),'id,create_time,exchange,is_activation',$param);
            $this->assign('row',$row);
            $this->assign($code);
            $this->display();
        }
    }

    /**
     * 锁定代金券
     * User: admin
     * Date: 2019-06-27 16:08:24
     */
    public function lockBatch() {
        $id = $this->checkParam(array('id', 'int'));
        $status = D('Batch')->queryField($id, 'status');
        $data = $status == 1 ? array('status'=>9) : array('status'=>1);
        $Res = D('Batch')->querySave($id, $data);
        $Res ? $this->apiResponse(1, $status == 1 ? '禁用成功' : '启用成功') : $this->apiResponse(0, $status == 1 ? '禁用失败' : '启用失败');

    }
}
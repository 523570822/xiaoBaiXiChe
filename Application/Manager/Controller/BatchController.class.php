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
            $find = M('Batch')->where(array('title'=>$data['title']))->find();
            if(!empty($find)){
                $this->apiResponse(0,'该批次名称已存在');
            }
            $data['create_time'] = time();
            $data['update_time'] = time();
            $start = strtotime($data['start_time']);
            $end = strtotime($data['end_time']);

//            $res = D('Batch')->addRow($data);
            //调用生成代金券接口
            $res = $this->redirect('Api/Coupon/couponCode',array('end_time'=>$end,'start_time'=>$start,'remark'=>$request['remark'],'prefix'=>$request['prefix'],'code_length'=>$request['code_length'],'title'=>$data['title'],'nums'=>$data['num'],'price'=>$data['price'],));
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
            $find = M('Batch')->where(array('title'=>$data['title']))->find();
            if(!empty($find)){
                $this->apiResponse(0,'该批次名称已存在');
            }
            $where['id'] = $request['id'];
            $data['update_time'] = time();
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $res = D('Batch')->querySave($where,$data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $id = $_GET['id'];
            $row = D('Batch')->queryRow($id);
            $param['order'] = 'create_time desc';
            $param['page_size'] = 15;
            //调用生成代金券接口
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

    /**
     *代金券发放页面
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/07/02 01:41
     */
    public function editSendRedBag() {
        $param['order'] = 'create_time desc';
        $param['page_size'] = 15;
        $code = D('RedeemCode')->queryList(array('is_activation'=>0,'end_time'=>array('gt',time())),'id,b_id,create_time,exchange,is_activation,create_time,end_time',$param);
        foreach ($code['list'] as &$v){
            $find_batch = M('Batch')->where(array('id'=>$v['b_id']))->find();
            $v['title'] = $find_batch['title'];
            $v['price'] = $find_batch['price'];
        }
        $this->assign($code);
        $this->display();
    }

    /**
     *代金券发放
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/07/02 02:08
     */
    public function SendRedBag() {
        $rule = array(
            array('red_bag_id','string>0','请输入兑换码'),
            array('m_id','string>0','请输入用户账号'),
        );
        $data = $this->checkParam($rule);
        $wheres['exchange'] = array('LIKE','%'.$data['red_bag_id'].'%');
        $wheres['is_activation'] = 0;
        $red_bag_info = D('RedeemCode')->queryRow($wheres);
        $batch = D('Batch')->queryRow(array('id'=>$red_bag_info['b_id']));
        if(!$red_bag_info){
            $this->apiResponse(0,'该代金券不存在');
        }
        unset($wheres['exchange']);
        $wheres['account'] = array('LIKE','%'.$data['m_id'].'%');
        $m_id = D('Member')->queryField($wheres,'id');
        if(!$m_id){
            $this->apiResponse(0,'该用户不存在');
        }

        if($m_id && $red_bag_info) {
            $wheress['exchange'] = array('LIKE','%'.$data['red_bag_id'].'%');
            $wheress['is_activation'] = 0;
            $save['is_activation'] = 1;
            D('RedeemCode')->querySave($wheress,$save);
            $where['m_id'] = $m_id;
            $where['is_bind'] = 1;
            $where['type'] = 2;
            $where['create_time'] = time ();
            $where['end_time'] = $red_bag_info['end_time'];
            $where['comes'] = $batch['title'];
            $where['money'] = $batch['price'];
            $where['code_id'] = $red_bag_info['id'];
            $BD = D ('CouponBind')->add ($where);
            $this->apiResponse(1, '发送完成');
        }
    }


    /**
     * 批量发放代金券页面
     * User: admin
     * Date: 2019-07-15 09:58:25
     */
    public function editSendRedBags() {
        $param['order'] = 'id desc,create_time desc';
        $param['page_size'] = 15;
        $code = D('Batch')->queryList(array('status'=>1,'end_time'=>array('gt',time())),'id,title,price,start_time,end_time',$param);
        foreach ($code['list'] as &$v ){
            $v['num'] = M('RedeemCode')->where(array('b_id'=>$v['id'],'is_activation'=>0))->count();
        }
        $this->assign($code);
        $this->display();
    }

    /**
     *取出随机数
     * @param $a
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/07/23 17:55
     */
    function get_one(&$a){
        if(count($a)>=1){
            $key=array_rand($a,1);
            $value=$a[$key];
            unset($a[$key]);
            return $value;
        }else{
            return "都取光了";
        }
    }

    /**
     * 批量发放
     * User: admin
     * Date: 2019-07-15 09:59:42
     */
    public function SendRedBags() {
        $rule = array(
            array('red_bag_id','string>0','请输入批次名称'),
            array('m_id','string>0','请输入用户账号'),
        );
        $data = $this->checkParam($rule);
        $str = $data['m_id'];
        $data['account'] = explode(',',$data['m_id']);
        $data['account'] = array_filter($data['account']);
        foreach ($data['account'] as &$vs){
            $wheres['account'] = array('LIKE','%'.$vs.'%');
            $wheres['status'] = 1;
            $m_id = D('Member')->where($wheres)->field('id')->find();
            if(empty($m_id)){
                $this->apiResponse(0,$m_id['account'].'用户不存在');
            }
            $m_ids[] = $m_id;
        }
        $where_batch['title'] = array('LIKE','%'.$data['red_bag_id'].'%');
        $where_batch['end_time'] = array('gt',time());
        $batch = D('Batch')->queryRow($where_batch);
        if(empty($batch)){
            $this->apiResponse(0,'该优惠卷不存在');
        }
        $m_num = count($m_ids);
        $find_batch = M('RedeemCode')->where(array('b_id'=>$batch['id'],'is_activation'=>0))->select();
        foreach ($find_batch as &$fb){
            $b_arr[] = $fb['exchange'];
        }
        $b_num = count($find_batch);
        if($b_num<$m_num){
            $this->apiResponse(0,'优惠卷剩余数量不足');
        }
        if($m_ids && $batch) {
            foreach ($m_ids as &$mv){
                $code = $this->get_one($b_arr);
                $wheress['b_id'] = $batch['id'];
                $wheress['exchange'] = $code;
                $wheress['is_activation'] = 0;
                $save['is_activation'] = 1;
                M('RedeemCode')->where($wheress)->save($save);
                $where['m_id'] = $mv['id'];
                $where['is_bind'] = 1;
                $where['type'] = 2;
                $where['create_time'] = time ();
                $where['end_time'] = $batch['end_time'];
                $where['comes'] = $batch['title'];
                $where['money'] = $batch['price'];
                $find = M('RedeemCode')->where(array('exchange'=>$code))->find();
                $where['code_id'] = $find['id'];
                $BD = D ('CouponBind')->add ($where);
            }
            $this->apiResponse(1, '发送完成');
        }
    }
}
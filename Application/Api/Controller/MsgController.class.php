<?php
namespace Api\Controller;

/**
 * 消息模块
 * Class MsgController
 * @package Api\Controller
 */
class MsgController extends BaseController{
    /**
     * 初始化方法
     */
    public $msg_obj = '';
    public $member_obj ='';
    public $msg_read_log ='';
    public function _initialize()
    {
        parent::_initialize();
        $this->msg_obj = D('Msg');
        $this->member_obj = D('Member');
        $this->msg_read_log = D('MsgReadLog');
    }
    /**
     * 系统消息详情
     * 消息id act_msg_id
     */
    public function actMsgInfo()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $info = M('Msg')->where(array('id' => $request['act_msg_id']))
            ->field('id as act_msg_id,sys_msg_content as act_msg_content,sys_msg_title as act_msg_title')
            ->find();
        if (empty($info)) {
            $this->apiResponse('0', '查询失败');
        }
        $info['act_msg_content'] = $this->setAbsoluteUrl($info['act_msg_content']);
        $info['act_msg_content'] =htmlspecialchars_decode($info['act_msg_content']);
        $info['act_msg_content'] = str_replace('img src="', 'img src = "' . C('API_URL'), $info['act_msg_content']);
        $res = M('msg_read_log')->where(array('m_id' => $m_id, 'msg_id' => $info['act_msg_id']))->find();
        if (empty($res)) {
            M('MsgReadLog')->data(array('m_id' => $m_id, 'msg_id' => $info['act_msg_id'], 'create_time' => time()))->add();
        }
        $this->apiResponse('1', '请求成功', $info);
    }

    /**
     * 消息列表
     * */
    public function myMsgList()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $list = M('Msg')
            ->where(array('status' => array('neq', 9),))
            ->order('create_time desc')
            ->page($request['page'], '15')
            ->order('create_time desc')
            ->select();

        if (empty($list)) {
            $message = $request['page'] == 1 ? '暂无消息' : '无更多消息';
            $this->apiResponse('1', $message);
        }
        foreach ($list as $k => $v) {
            $list[$k]['msg_content'] = htmlspecialchars_decode($list[$k]['msg_content']);

            $list[$k]['create_time'] = date('Y/m/d', $v['create_time']);
            unset($list[$k]['act_pic']);
            $res = M('msg_read_log')->where(array('m_id' => $m_id, 'msg_id' => $v['id']))->find();
            $list[$k]['is_read'] = $res ? 0 : 1;//1未读0已读
        }
        $this->apiResponse('1', '请求成功', $list);
    }

//    /**
//     * 消息通知
//     * token  type//1系统消息 2 订单消息
//     * */
//    public function info()
//    {
//        $m_id = $this->checkToken();
//        $this->errorTokenMsg($m_id);
//        $request=$_REQUEST;
//        $rule=array('type','string','参数类型不正确');
//        $this->checkParam($rule);
//        $type=$request['type'];
//        $sys_msg_info = M('Msg')->where(array('type' => $type, 'status' => array('neq', 9)))->order('create_time desc')->find();
//        if ($sys_msg_info) {
//            $check_log = D("MsgReadLog")->where(array('m_id' => $m_id, 'msg_id' => $sys_msg_info['id']))->find();
//            if ($check_log) {
//                $data['sys_msg_code'] = 0; //没有未读消息
//            } else {
//                $data['sys_msg_code'] = 1; //有未读系统消息
//            }
//        }
//        $this->apiResponse('1', '请求成功', $data);
//    }
//    /**
//     * 订单消息详情
//     * 消息id id
//     */
//    public function orderDetail()
//    {
//        $m_id = $this->checkToken();
//        $this->errorTokenMsg($m_id);
//        $request = $_REQUEST;
//        $list = M('Msg')->where(array('m_id' => $m_id, 'type' => 2, 'status' => array('neq', 9)))->order('create_time desc')
//            ->field('id as act_msg_id,sys_msg_title as act_msg_title, orderid,order_msg_content as act_msg_content, create_time')
//            ->page($request['page'], '15')
//            ->order('create_time desc')
//            ->select();
//        if (empty($list)) {
//            $message = $request['page'] == 1 ? '暂无消息' : '无更多消息';
//            $this->apiResponse('1', $message);
//        }
//        foreach ($list as $k => $v) {
//            $list[$k]['act_msg_content'] = htmlspecialchars_decode($list[$k]['act_msg_content']);
//
//            $list[$k]['create_time'] = date('Y/m/d', $v['create_time']);
//            unset($list[$k]['act_pic']);
//                $res = M('msg_read_log')->where(array('m_id' => $m_id, 'msg_id' => $v['act_msg_id']))->find();
//            $list[$k]['is_read'] = $res ? 0 : 1;//1未读0已读
//        }
//        $this->apiResponse('1', '请求成功', $list);
//    }
//    /**
//     * 消息首页
//     */
//    public function msgIndex(){
//        $m_id = $this->checkToken();
//        $this->errorTokenMsg($m_id);
//        $result_data = array();
//        $sys_msg_info = M('Msg')->where(array('type'=>1,'status'=>array('neq',9)))->order('create_time desc')->find();
//        if($sys_msg_info){
//            $result_data['sys_msg_content'] = $sys_msg_info['sys_msg_title'];
//            $result_data['sys_msg_time'] = date('Y/m/d',$sys_msg_info['create_time']);
//            $check_log = $this->msg_read_log->where(array('m_id'=>$m_id,'msg_id'=>$sys_msg_info['id']))->find();
//            if($check_log){
//                $result_data['sys_msg_code'] = 0;
//            } else {
//                $result_data['sys_msg_code'] = 1;
//            }
//        }else{
//            $result_data['sys_msg_content'] = '';
//            $result_data['sys_msg_time'] = '';
//            $result_data['sys_msg_code'] = 0;
//        }
//
//        $act_msg_info = M('Msg')->where(array('type'=>2,'status'=>array('neq',9)))->order('create_time desc')->find();
//        if($act_msg_info){
//            $result_data['act_msg_content'] = $act_msg_info['sys_msg_title'];
//            $result_data['act_msg_time'] = date('Y/m/d',$act_msg_info['create_time']);
//            $check_log = $this->msg_read_log->where(array('m_id'=>$m_id,'msg_id'=>$act_msg_info['id']))->find();
//            if($check_log){
//                $result_data['act_msg_code'] = 0;
//            } else {
//                $result_data['act_msg_code'] = 1;
//            }
//        }else{
//            $result_data['act_msg_content'] = '';
//            $result_data['act_msg_time'] = '';
//            $result_data['act_msg_code'] = 0;
//        }
//        $m_msg_info = M('Msg')->where(array('type'=>3,'m_id'=>$m_id,'status'=>array('neq',9)))->order('create_time desc')->find();//var_dump($m_msg_info);die;
//        if($m_msg_info){
//            $result_data['m_msg_content'] = $m_msg_info['sys_msg_title'];
//            $result_data['m_msg_time'] = date('Y/m/d',$m_msg_info['create_time']);
//            $check_log = $this->msg_read_log->where(array('m_id'=>$m_id,'msg_id'=>$m_msg_info['id']))->find();
//            if($check_log){
//                $result_data['m_msg_code'] = 0;
//            } else {
//                $result_data['m_msg_code'] = 1;
//            }
//        }else{
//            $result_data['m_msg_content'] = '';
//            $result_data['m_msg_time'] = '';
//            $result_data['m_msg_code'] = 0;
//        }
//       $this->apiResponse('1','请求成功',$result_data);
//    }
//
//
//    /**
//     *  消息列表
//     * 参数：p
//     */
//    public function myMsgList1(){
//        $m_id = $this->checkToken();
//        $this->errorTokenMsg($m_id);
//        $request =$_REQUEST;
//           /* $param = array(
//                array('check_type'=>'is_null','parameter' =>$request['p'],'error_msg'=>'参数错误'),
//            );
//            check_param($param);//检查参数*/
//        $type = empty($_REQUEST['type']) ? 0 : $_REQUEST['type'];
//        if(empty($type)) {
//           $this->apiResponse(0, '缺少type参数');
//        }
//        if($type==3){
//            $list = M('Msg')->where(array('type'=>$type,'m_id'=>$m_id,'status'=>array('neq',9)))->order('create_time desc')
//                ->field('id as act_msg_id,sys_msg_title as act_msg_title,sys_msg_content as act_msg_content,create_time,act_pic')
//                ->page($request['p'].',15')
//                ->order('create_time desc')
//                ->select();
//            if(empty($list)){
//                $message = $request['p']==1?'暂无消息':'无更多消息';
//               $this->apiResponse('1',$message);
//            }
//            foreach($list as $k => $v){
//                $list[$k]['act_msg_content'] = htmlspecialchars_decode($list[$k]['act_msg_content']);
//                $path = D('File')->where('id='.$v['act_pic'])->getField('path');
//                $list[$k]['act_msg_img'] ="0";// C('API_URL').$path;
//                unset($list[$k]['act_pic']);
//                $res = M('msg_read_log')->where(array('m_id'=>$m_id,'msg_id'=>$v['act_msg_id']))->find();
//                // var_dump($v['act_msg_id']);
//                if(empty($res)){
//                    M('MsgReadLog')->data(array('m_id'=>$m_id,'msg_id'=>$v['act_msg_id'],'create_time'=>time()))->add();
//                }
//            }
//        }else{
//        $list = M('Msg')->where(array('type'=>$type,'status'=>array('neq',9)))->order('create_time desc')
//            ->field('id as act_msg_id,sys_msg_title as act_msg_title,sys_msg_content as act_msg_content,create_time,act_pic')
//            ->page($request['p'].',15')
//            ->order('create_time desc')
//            ->select();
//        if(empty($list)){
//            $message = $request['p']==1?'暂无消息':'无更多消息';
//           $this->apiResponse('1',$message);
//        }
//        foreach($list as $k => $v){
//            $list[$k]['act_msg_content'] = htmlspecialchars_decode($list[$k]['act_msg_content']);
//            $path = D('File')->where('id='.$v['act_pic'])->getField('path');
//            $list[$k]['act_msg_img'] = C('API_URL').$path;
//            unset($list[$k]['act_pic']);
//            $res = M('msg_read_log')->where(array('m_id'=>$m_id,'msg_id'=>$v['act_msg_id']))->find();
//           // var_dump($v['act_msg_id']);
//           if(empty($res)){
//                M('MsgReadLog')->data(array('m_id'=>$m_id,'msg_id'=>$v['act_msg_id'],'create_time'=>time()))->add();
//          }
//        }
//        }
//       $this->apiResponse('1','请求成功',$list);
//    }
//
//    /**
//     * 活动消息详情
//     * 消息id act_msg_id
//     */
//        public function actMsgInfo1(){
//        $m_id = $this->checkToken();
//        $this->errorTokenMsg($m_id);
//        $request = $_REQUEST;
//        $info = M('Msg')->where(array('id'=>$request['act_msg_id']))
//            ->field('id as act_msg_id,sys_msg_content as act_msg_content,sys_msg_title as act_msg_title')
//            ->find();
//        if(empty($info)){
//           $this->apiResponse('0','查询失败');
//        }
//        $info['act_msg_content'] = $this->setAbsoluteUrl($info['act_msg_content']);
//        $info['act_msg_content'] = htmlspecialchars_decode($info['act_msg_content']);
//        $info['act_msg_content']=  str_replace('img src="','img src = "'.C('API_URL'),$info['act_msg_content']);
//        $res = M('msg_read_log')->where(array('m_id'=>$m_id,'msg_id'=>$info['act_msg_id']))->find();
//        if(empty($res)){
//            M('MsgReadLog')->data(array('m_id'=>$m_id,'msg_id'=>$info['act_msg_id'],'create_time'=>time()))->add();
//        }
//       $this->apiResponse('1','请求成功',$info);
//    }
//

}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 10:02
 */

namespace Api\Controller;


use Common\Service\ControllerService;

class MemberController extends BaseController
{

    public function _initialize()
    {
        parent::_initialize();

    }


    /**
     * 注册
     * 参数：account(手机号),verify（短信验证码），password(密码)
     * User: jiajia.zhao 18210213617@163.com
     * Date: 2018/8/18 9:38
     */
    public function register(){
        $request  =$_REQUEST;// I('post.');
        $share_from_code = $request['share_code'];
        $rule = array(
            array('account','string','请输入手机号'),
            array('verify','string','请输入短信验证码'),
            array('password','string','请输入密码'),
            array('repassword','string','请输入确认密码')
        );
        $this->checkParam($rule);
        if(!empty($request['password'])&&!empty($request['repassword'])){
            if($request['password']!=$request['repassword']) {
                $this->apiResponse('0','确认密码和密码不一致');
            }
        }
        if (!empty($share_from_code)) {
            $this->checkShareCodeBefore($share_from_code);
        }
        $param['account'] = $request['account'];
        $member_info = D('Member')->querySave($param);
        if($member_info){
            $this->apiResponse('0','该手机号已被注册');
        }
        //检查短信验证码
        $res = D('Sms')->checkVerify($request['account'],$request['verify'],'register');
        if($res['error']){
            $this->apiResponse('0',$res['error']);
        }
        //注册用户
        $member_add_info = D('Member')->addRow($request);
        if(empty($member_add_info)){
            $this->apiResponse('0','注册失败');
        }

        //创建并更新token
        $token_arr = $this->createToken();
        D('Member')->querySave(array('id'=>$member_add_info),array('token'=>$token_arr['token'],'expired_time'=>$token_arr['expired_time']));

        unset($param);
        unset($member_info);
        $param['where']['id'] = $member_add_info;
        $param['field'] = 'id as m_id,account,nickname,head_pic,degree';
        $member_info =  D('Member')->querySave($param);
        if(empty($member_info)){
            $this->apiResponse('0','注册失败');
        }
        if (!empty($share_from_code)) {
            $this->checkShareCode($share_from_code,$member_add_info['id']);
        }
        $this->apiResponse('1','注册成功',$member_info);
    }


    /**  * 账号密码登录
     * 参数：account(手机号),password（密码）
     * User: jiajia.zhao 18210213617@163.com
     * Date: 2018/8/18 9:39
     */
    public function login(){
        $request =$_REQUEST;// I('post.');
        $rule = array(
            array('account','string','请输入手机号'),
            array('password','string','请输入密码'),
        );
        $this->checkParam($rule);
        $param['account'] = $request['account'];
        $param['status'] = array('neq',9);
        $member_info = D('Member')->queryRow($param);
        if(!$member_info){
            $this->apiResponse('0','该手机号尚未注册');
        }
        $check_password = checkPassword($request['password'],$member_info['salt'],$member_info['password']);

        if($check_password == 1){
            $this->apiResponse('0','密码错误');
        }

        unset($member_info['password']);
        unset($member_info['salt']);
        //创建并更新token
        $token_arr = $this->createToken();
        D('Member')->saveMember(array('id'=>$member_info['m_id']),array('token'=>$token_arr['token'],'expired_time'=>$token_arr['expired_time']));
        $member_info['token'] = $token_arr['token'];
        $this->apiResponse('1','登录成功',$member_info);
    }
    /**
     * 三方登录
     * 参数：openid(三方登录唯一标识),nickname（昵称）,head_pic（头像）,type( 1QQ登录 2微信登录 3 微博登录，4支付宝，5淘宝)
     */
    public function threeLogin()
    {
        $request = $_REQUEST;
        $rule = array(
            array('openid','string','三方登录唯一标识不能为空'),
            array('nickname','string','昵称不能为空'),
            array('type','string','类型错误'),
        );
        $this->checkParam($rule);
        $param['where']['openid'] = $request['openid'];
        $param['where']['type'] = $request['type'];
        $bind_info =  D('MemberBind')->queryRow($param['where']);
        if (!$bind_info) {
            //还未三方登录过
            $request['create_time']=time();
            $bind_id = D('MemberBind')->addRow($request);
            if ($bind_id) {
                $result_data['bind_id'] = $bind_id . '';
                $result_data['account'] = '';
                $this->apiResponse('1', '登录成功', $result_data);
            } else {
                $this->apiResponse('0', '登录失败');
            }
        }
        //已经三方登录过
        if ($bind_info['m_id']) {
            unset($param);
            $param['where']['id'] = $bind_info['m_id'];
            $param['where']['status'] = array('neq', 9);
            $param['field'] = 'id as m_id,account,nickname,head_pic,degree';
            $member_info = D('Member')->querySave($param);
            if (!$member_info) {
                $where['id'] = $bind_info['id'];
                $data['m_id'] = 0;
                D('Member')->querySave($where, $data);
            }
            //创建并更新token
            $token_arr =createToken();
            D('Member')->querySave(array('id' => $member_info['m_id']), array('token' => $token_arr['token'], 'expired_time' =>                  $token_arr['expired_time']));

            $member_info['head_pic'] =$this->getOnePath($member_info['head_pic'], C('API_URL') . '/Uploads/Member/default.png');
            $member_info['token'] = $token_arr['token'];
            $member_info['expired_time'] = $token_arr['expired_time'];
             $member_info['no_read_msg'] = D('Msg')->isHaveMsg($member_info['m_id']);
            $this->apiResponse('1', '登录成功', $member_info);
        } else {
            $result_data['bind_id'] = $bind_info['id'];
            $result_data['account'] = '';
            $this->apiResponse('1', '登录成功', $result_data);
        }
    }

    /**
     * 三方登录绑定手机号
     * bind_id,account verify
     */
    public function threeLoginBind(){
        $request = $_REQUEST;

        $rule = array(
            array('bind_id','string','bind_id不能为空'),
            array('account','string','请输入手机号'),
            array('verify','string','请输入验证码'),
        );
        $this->checkParam($rule);

        //检查短信验证码
        $res = D('Sms')->checkVerify($request['account'],$request['verify'],'re_bind');
       if($res['error']){
           $this->apiResponse('0',$res['error']);
        }

        unset($param);
        $param['where']['id'] = $request['bind_id'];
        $bind_info = D('MemberBind')->queryRow($param['where']);
        if(!$bind_info){
           $this->apiResponse('0','绑定手机号失败');
        }

        unset($param);
        $param['where']['account'] = $request['account'];
        $param['where']['status'] = array('neq', 9);
        $param['field'] = 'id as m_id,account,nickname,head_pic,degree';
        $member_info = D('Member')->queryRow($param['where'],$param['field']);
        if($member_info){
            $res = D('MemberBind')->querySave(array('id'=>$bind_info['id']),array('m_id'=>$member_info['m_id']));

            if(!$res){
               $this->apiResponse('0','绑定手机号失败');
            }
            $m_id = $member_info['m_id'];
        }else{
            $m_id = D('Member')->addRow($request);
            if(empty($m_id)){
               $this->apiResponse('0','绑定手机号失败');
            }
            $res = D('MemberBind')->querySave(array('id'=>$bind_info['id']),array('m_id'=>$m_id));

            if(!$res){
               $this->apiResponse('0','绑定手机号失败 ');
            }
        }

        unset($param);
        $param['where']['id'] = $m_id;
        $param['where']['status'] = array('neq',9);
        $param['field'] = 'id as m_id,account,nickname,head_pic,degree';
        $member_info = D('Member')->queryRow($param['where'],$param['field']);

        //创建并更新token
        $token_arr = createToken();
        D('Member')->querySave(array('id'=>$member_info['m_id']),array('token'=>$token_arr['token'],'expired_time'=>$token_arr['expired_time']));

        $member_info['head_pic'] =$this->getOnePath($member_info['head_pic'],C('API_URL').'/Uploads/Member/default.png');
        $member_info['token'] = $token_arr['token'];
        $member_info['expired_time'] = $token_arr['expired_time'];
       $member_info['no_read_msg'] = D('Msg')->isHaveMsg($member_info['m_id']);
       $this->apiResponse('1','绑定手机号成功',$member_info);
    }

    /**
     * 个人中心
     * 参数：null
     */
    public function memberCenter(){
        $m_id =$this->checkToken();
        $this->errorTokenMsg($m_id);
        unset($param);
        $param['where']['id'] = $m_id;
        $param['where']['status'] = array('neq',9);
        $param['field'] = 'id as m_id,account,nickname,head_pic,degree';
        $member_info = D('Member')->queryRow($param['where'],$param['field']);
        $member_info['head_pic'] = $this->getOnePath($member_info['head_pic'],C('API_URL').'/Uploads/Member/default.png');

        $member_info['no_read_msg'] = D('Msg')->isHaveMsg($member_info['m_id']);
       /* $member_info['service_qq']    = $this->config['SERVICE_QQ'];*/
       $this->apiResponse('1','请求成功',$member_info);
    }

    /**
     * 找回密码
     * account,verify password
     */
    public function resetPassword(){
        $request = $_REQUEST;
        $rule = array(
            array('account','string','请输入手机号'),
            array('password','string','请输入密码'),
            array('verify','string','请输入验证码'),
        );
        $this->checkParam($rule);
        //检查短信验证码
       $res = D('Sms')->checkVerify($request['account'],$request['verify'],'retrieve');
        if($res['error']){
           $this->apiResponse('0',$res['error']);
        }

        unset($param);
        $param['where']['account'] = $request['account'];
        $param['where']['status'] = array('neq',9);
        $member_info = D('Member')->queryRow($param['where']);
        if(!$member_info){
           $this->apiResponse('0','该手机号尚未注册');
        }
        unset($where);
        $where['id'] = $member_info['id'];
        $data['salt'] = NoticeStr(6);
        $data['password'] = CreatePassword($request['password'], $data['salt']);
        $res = D('Member')->querySave($where,$data);
        if($res){
           $this->apiResponse('1','找回密码成功');
        }else{
           $this->apiResponse('0','找回密码失败');
        }
    }

    /**
     * 设置密码
     * password
     */
    public function setPassword(){
        $request = $_REQUEST;

        $rule =   array('password','string','请输入密码');

        $this->checkParam($rule);
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        unset($where);
        $where['id'] = $m_id;
        $data['salt'] = NoticeStr(6);
        $data['password'] = CreatePassword($request['password'], $data['salt']);
        $res = D('Member')->querySave($where,$data);
        if($res){
           $this->apiResponse('1','设置密码成功');
        }else{
           $this->apiResponse('0','设置密码失败');
        }
    }

    /**
     * 修改密码
     * old_password,password
     */
    public function modPassword(){
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $rule = array(
            array('old_password','string','请输入旧密码'),
            array('password','string','请输入密码'),
        );
        $this->checkParam($rule);
        unset($param);
        $param['where']['id'] = $m_id;
        $member_info = D('Member')->queryRow($param['where'],$param['field']);
       /* if($member_info['password'] != md5($request['old_password'])){
           $this->apiResponse('0','旧密码错误');
        }*/
        $check_password = checkPassword($request['old_password'],$member_info['salt'],$member_info['password']);

        if($check_password == 1){
            $this->apiResponse('0','旧密码错误');
        }
        $where['id'] = $m_id;
        $data['salt'] = NoticeStr(6);
        $data['password'] = CreatePassword($request['password'], $data['salt']);
        $res = D('Member')->querySave($where,$data);
        if($res){
           $this->apiResponse('1','修改密码成功');
        }else{
           $this->apiResponse('0','修改密码失败');
        }
    }

    /**
     * 个人资料
     */
    public function memberBaseData(){
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $param['where']['id'] = $m_id;
        $param['field'] = 'id as m_id,account,head_pic,nickname,password';
        $member_info = D('Member')->queryRow($param['where'],$param['field']);
        $member_info['head_pic'] = $this->getOnePath($member_info['head_pic'],C('API_URL').'/Uploads/Member/default.png');
        $member_info['is_password'] = $member_info['password']?'1':'0';
        unset($member_info['password']);

       $this->apiResponse('1','请求成功',$member_info);
    }

    /**
     * 修改个人资料
     * head_pic nickname  head_pic_id
     */
    public function modBaseData(){
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $param = array(
            array('check_type'=>'is_null','parameter' => $request['nickname'],'condition'=>'','error_msg'=>'请输入昵称'),
        );
        $request = $_REQUEST;
        $rule = array(
            array('nickname','string','请输入姓名'),
            array('sex','string','请输入性别'),
        );
        $this->checkParam($rule);

        if(!empty($_FILES['head_pic']['name'])){
            $res = api('UploadPic/upload', array(array('save_path' => 'Member')));
            foreach ($res as $value) {
                $data['head_pic'] = $value['id'];
            }
        }
        if($request['head_pic_id']){
            $data['head_pic'] = $request['head_pic_id'];
        }
        if($request['sex']){
            $data['sex'] = $request['sex'];
        }
        $data['nickname'] = $request['nickname'];

        $where['id'] = $m_id;
        $res = D('Member')->querySave($where,$data);
        if($res){
           $this->apiResponse('1','修改个人资料成功');
        }else{
           $this->apiResponse('0','修改个人资料失败');
        }
    }

}
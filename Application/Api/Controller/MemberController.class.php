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
     * 参数：account(手机号),verify（短信验证码），password(密码),repassword(确认密码)
     *update by 谢吉发
     */
    public function register()
    {
        $request = I('post.');
        $share_from_code = $request['invite_code'];
        $rule = array(
            array('account', 'string', '请您输入手机号码'),
            array('verify', 'string', '请您输入短信验证码'),
            array('password', 'notnull', '请您输入密码'),
            array('repassword', 'notnull', '请您再次输入密码')
        );
        $this->checkParam($rule);
        //密码判空
        if(empty($request['password'])){
            $this->apiResponse('0', '请输入密码');
        }
        if(empty($request['repassword'])){
            $this->apiResponse('0', '请再次输入密码');
        }
        //检查手机号是否存在
        $param['account'] = $request['account'];
        $param['status'] = 1;
        $member_info = D('Member')->queryRow($param);
        if ($member_info) {
            $this->apiResponse('0', '该手机号已被注册');
        }
        //检查两次密码是否匹配
        if (!empty($request['password']) && !empty($request['repassword'])) {
            if ($request['password'] != $request['repassword']) {
                $this->apiResponse('0', '两次密码不一致，请重试');
            }
        }
        //检查邀请码是否存在
        if (!empty($share_from_code)) {
            $this->checkShareCodeBefore($share_from_code);
        }
        //检查密码长度不得小于6位
        $long = strlen($request['password']);
        if($long < 6){
            $this->apiResponse('0', '密码长度不得小于6位');

        }
        //邀请码
        if ($request['invite_code'] == '' || $request['invite_code'] == null) {
            $request['parent_id'] = 0;
        } else {
            $mem = M("member")->where(array('invite_code' => $request['invite_code']))->field("id")->find();
            $request['parent_id'] = $mem['id'];
        }
        //检查短信验证码
//        $res = D('Sms')->checkVerify($request['account'], $request['verify'], 'register');
//        if ($res['error']) {
//            $this->apiResponse('0', $res['error']);
//        }
        //注册用户
        $member_add_info = D('Member')->addRow($request);
        if (empty($member_add_info)) {
            $this->apiResponse('0', '注册失败');
        }
        //创建并更新token
        $token_arr = $this->createToken();
        //创建邀请码
        $invite_code = $this->buildCode();
        D('Member')->querySave(array('id' => $member_add_info), array('token' => $token_arr['token'], 'expired_time' => $token_arr['expired_time'], "invite_code" => $invite_code));
        //创建邀请码
        $membe = M("member")->where(array('invite_code' => $request['invite_code']))->field("integral")->find();
        //增加积分
        $relust['integral'] = $membe['integral'] + 1;
        //绑定推荐人id
        D('Member')->where(array('parent_id' => $request['parent_id']))->querySave($relust);
        unset($param);
        unset($member_info);
        $param['where']['id'] = $member_add_info;
        $param['field'] = 'id as m_id,account,nickname,head_pic,degree';
        $member_info = D('Member')->queryRow($param);
        if (empty($member_info)) {
            $this->apiResponse('0', '注册失败');
        }
        $data['token'] = $token_arr['token'];
        $invite_code = $request['invite_code'];
        if (!empty($invite_code)) {
            $this->checkShareCodeBefore($invite_code);
        }
        if (!empty($invite_code)) {
            $this->checkShareCode($invite_code, $member_add_info);
        }
        $this->apiResponse('1', '注册成功', $data);
    }

    /**
     * 账号密码登录
     * 参数：account(手机号),password（密码）
     * User: jiajia.zhao 18210213617@163.com
     * Date: 2018/8/18 9:39
     */
    public function login()
    {
        $request = $_REQUEST;// I('post.');
        $rule = array(
            array('account', 'string', '请输入手机号'),
            array('password', 'string', '请输入密码'),
        );
        $this->checkParam($rule);
        $param['account'] = $request['account'];
        $param['status'] = array('neq', 9);
        $member_info = D('Member')->queryRow($param);
        if (!$member_info) {
            $this->apiResponse('0', '该手机号尚未注册');
        }
        $check_password = checkPassword($request['password'], $member_info['salt'], $member_info['password']);
        if ($check_password == 1) {
            $this->apiResponse('0', '密码错误');
        }
        //创建并更新token
        $token_arr = $this->createToken();
        D('Member')
            ->where(array('id' => $member_info['id']))
            ->save (array ('token' => $token_arr['token'],'expired_time' => $token_arr['expired_time']));
        $data['token'] = $token_arr['token'];
        $this->apiResponse('1', '登录成功', $data);
    }

    /**
     * 三方登录
     * 参数：openid(三方登录唯一标识),nickname（昵称）,head_pic（头像）,type( 1QQ登录 2微信登录 3 微博登录，4支付宝，5淘宝)
     */
    public function threeLogin()
    {
        $request = $_REQUEST;
        $rule = array(
            array('openid', 'string', '三方登录唯一标识不能为空'),
            array('type', 'string', '请输入绑定类型'),
        );
        $this->checkParam($rule);
        $param['where']['openid'] = $request['openid'];
        $param['where']['type'] = $request['type'];
        $bind_info = D('MemberBind')->queryRow($param['where']);
        if (!$bind_info) {
            //还未三方登录过
            $request['create_time'] = time();
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
            $member_info = D('Member')->where($param['where'])->field($param['field'])->find();
            if (!$member_info) {
                $where['id'] = $bind_info['id'];
                $data['m_id'] = 0;
                D('Member')->querySave($where, $data);
            }
            //创建并更新token
            $token_arr = createToken();
            D('Member')->where(array('id' => $member_info['m_id']))->save(array('token' => $token_arr['token'], 'expired_time' => $token_arr['expired_time']));
            $member_info['head_pic'] =$this->getOnePath($member_info['head_pic']);
            $member_info['token'] = $token_arr['token'];
            $member_info['expired_time'] = $token_arr['expired_time'];
            $member_info['no_read_msg'] = D('Msg')->isHaveMsg($member_info['m_id']);
//            $member_info['bind_id'] = $bind_info['id'];
//
//            $member_info['account'] = '';

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
    public function threeLoginBind()
    {
        $request = $_REQUEST;
        $rule = array(
            array('bind_id', 'string', 'bind_id不能为空'),
            array('account', 'string', '请输入手机号'),
            array('verify', 'string', '请输入验证码'),
        );
        $this->checkParam($rule);
        //检查短信验证码
        $res = D('Sms')->checkVerify($request['account'], $request['verify'], 're_bind');
        if ($res['error']) {
            $this->apiResponse('0', $res['error']);
        }
        unset($param);
        $param['where']['id'] = $request['bind_id'];
        $bind_info = D('MemberBind')->queryRow($param['where']);
        if (!$bind_info) {
            $this->apiResponse('0', '绑定手机号失败');
        }
        unset($param);
        $param['where']['account'] = $request['account'];
        $param['where']['status'] = array('neq', 9);
        $param['field'] = 'id as m_id,account,nickname,head_pic,degree';
        $member_info = D('Member')->queryRow($param['where'], $param['field']);
        if ($member_info) {
            $res = D('MemberBind')->querySave(array('id' => $bind_info['id']), array('m_id' => $member_info['m_id']));
            if (!$res) {
                $this->apiResponse('0', '绑定手机号失败');
            }
            $m_id = $member_info['m_id'];
        } else {
            $m_id = D('Member')->addRow($request);
            if (empty($m_id)) {
                $this->apiResponse('0', '绑定手机号失败');
            }
            $res = D('MemberBind')->querySave(array('id' => $bind_info['id']), array('m_id' => $m_id));
            if (!$res) {
                $this->apiResponse('0', '绑定手机号失败 ');
            }
        }
        unset($param);
        $param['where']['id'] = $m_id;
        $param['where']['status'] = array('neq', 9);
        $param['field'] = 'id as m_id,account,nickname,head_pic,degree';
        $member_info = D('Member')->queryRow($param['where'], $param['field']);
        //创建并更新token
        $token_arr = createToken();
        D('Member')->querySave(array('id' => $member_info['m_id']), array('token' => $token_arr['token'], 'expired_time' => $token_arr['expired_time']));
        $member_info['head_pic'] =$this->getOnePath($member_info['head_pic']);
        $member_info['token'] = $token_arr['token'];
        $member_info['expired_time'] = $token_arr['expired_time'];
        $member_info['no_read_msg'] = D('Msg')->isHaveMsg($member_info['m_id']);
        $this->apiResponse('1', '绑定手机号成功', $member_info);
    }

    /**
     * 更换手机号
     * account  verify
     * */
    public function changeAccount()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $rule = array(
            array('account', 'string', '请输入新手机号'),
            array('verify', 'string', '请输入验证码'),
        );
        $this->checkParam($rule);
        //检查短信验证码
        $res = D('Sms')->checkVerify($request['account'], $request['verify'], 'mod_bind');
        if ($res['error']) {
            $this->apiResponse('0', $res['error']);
        }
        $param['where']['account'] = $request['account'];
        $member_info = D('Member')->queryRow($param['where']);
        if ($member_info) {
            $this->apiResponse('0', '请更换新的手机号');
        }
        $where['id'] = $m_id;
        $data['account'] = $request['account'];
        $res = D('Member')->querySave($where, $data);
        if ($res) {
            $this->apiResponse('1', '手机号更换成功');
        } else {
            $this->apiResponse('0', '手机号更换失败');
        }
    }

    /**
     * 个人中心
     * 参数：null
     */
    public function memberCenter()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $card = M('CardUser')->where(array('m_id'=>$m_id))->find();
        $member_info = D('Member')->where (array ('id'=>$m_id,'status' => 1))->field ('id as m_id,head_pic,nickname,account as tel,degree')->find();
        if(!empty($card)){
            $time = time();
            if($card['end_time'] > $time){
                if($card['l_id'] == 1){
                    $member_info['degree'] = 1;
                }elseif($card['l_id'] == 2){
                    $member_info['degree'] = 2;
                }
            }else{
                $member_info['degree'] = 0;
            }
        }else{
            $member_info['degree'] = 0;
        }
        $member_info['head_pic'] =$this->getOnePath($member_info['head_pic']);
        $this->apiResponse('1', '请求成功', $member_info);
    }

    /**
     * 找回密码
     * account,verify password
     */
    public function resetPassword()
    {
        $request = $_REQUEST;
        $rule = array(
            array('account', 'string', '请您输入手机号'),
            array('verify', 'string', '请您输入验证码'),
            array('password', 'string', '请输入新密码'),
            array('repassword', 'string', '请再次输入密码'),
        );
        $this->checkParam($rule);
        //密码判空
        if(empty($request['password'])){
            $this->apiResponse('0', '请输入密码');
        }
        if(empty($request['repassword'])){
            $this->apiResponse('0', '请再次输入密码');
        }
        if (!empty($request['password']) && !empty($request['repassword'])) {
            if ($request['password'] != $request['repassword']) {
                $this->apiResponse('0', '两次密码不一致，请重试');
            }
        }
        //检查密码长度不得小于6位
        $long = strlen($request['password']);
        if($long < 6){
            $this->apiResponse('0', '密码长度不得小于6位');

        }
        //检查短信验证码
        $res = D('Sms')->checkVerify($request['account'], $request['verify'], 'retrieve');
        if ($res['error']) {
            $this->apiResponse('0', $res['error']);
        }
        unset($param);
        $param['where']['account'] = $request['account'];
        $param['where']['status'] = array('neq', 9);
        $member_info = D('Member')->queryRow($param['where']);
        if (!$member_info) {
            $this->apiResponse('0', '该手机号尚未注册');
        }
        unset($where);
        $where['id'] = $member_info['id'];
        $data['salt'] = NoticeStr(6);
        $data['password'] = CreatePassword($request['password'], $data['salt']);
        $res = D('Member')->querySave($where, $data);
        if ($res) {
            $this->apiResponse('1', '找回密码成功');
        } else {
            $this->apiResponse('0', '找回密码失败');
        }
    }

    /**
     * 设置密码
     * password
     */
    public function setPassword()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $rule =array (
            array('password', 'string', '请设置密码'),
            array('repassword', 'string', '请验证密码'),
        );
        $this->checkParam($rule);
        //密码判空
        if(empty($request['password'])){
            $this->apiResponse('0', '请输入密码');
        }
        if(empty($request['repassword'])){
            $this->apiResponse('0', '请再次输入密码');
        }
        //检查密码长度不得小于6位
        $long = strlen($request['password']);
        if($long < 6){
            $this->apiResponse('0', '密码长度不得小于6位');

        }
        if (!empty($request['password']) && !empty($request['repassword'])) {
            if ($request['password'] != $request['repassword']) {
                $this->apiResponse('0', '两次密码不一致，请重试');
            }
        }
        unset($where);
        $where['id'] = $m_id;
        $data['salt'] = NoticeStr(6);
        $data['password'] = CreatePassword($request['password'], $data['salt']);
        $res = D('Member')->querySave($where, $data);
        if ($res) {
            $this->apiResponse('1', '设置密码成功');
        } else {
            $this->apiResponse('0', '设置密码失败');
        }
    }

    /**
     * 修改密码
     * old_password,password
     */
    public function modPassword()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $rule = array(
            array('old_password', 'string', '请输入旧密码'),
            array('password', 'string', '请输入新密码'),
            array('repassword', 'string', '请再次输入密码'),
        );
        $this->checkParam($rule);
        //密码判空
        if(empty($request['old_password'])){
            $this->apiResponse('0', '请输入旧密码');
        }
        if(empty($request['password'])){
            $this->apiResponse('0', '请输入密码');
        }
        if(empty($request['repassword'])){
            $this->apiResponse('0', '请再次输入密码');
        }
        //检查密码长度不得小于6位
        $long = strlen($request['password']);
        if($long < 6){
            $this->apiResponse('0', '密码长度不得小于6位');

        }
        if (!empty($request['old_password']) && !empty($request['password'])) {
            if ($request['old_password'] == $request['password']) {
                $this->apiResponse('0', '新旧密码一致，请重试');
            }
        }
        if (!empty($request['password']) && !empty($request['repassword'])) {
            if ($request['password'] != $request['repassword']) {
                $this->apiResponse('0', '两次密码不一致，请重试');
            }
        }
//        if($request['password'] || $request['repassword']){
//
//        }
        unset($param);
        $param['where']['id'] = $m_id;
        $member_info = D('Member')->queryRow($param['where'], $param['field']);
        $check_password = checkPassword($request['old_password'], $member_info['salt'], $member_info['password']);
        if ($check_password == 1) {
            $this->apiResponse('0', '原始密码错误');
        }
        $where['id'] = $m_id;
        $data['salt'] = NoticeStr(6);
        $data['password'] = CreatePassword($request['password'], $data['salt']);
        $res = D('Member')->querySave($where, $data);
        if ($res) {
            $this->apiResponse('1', '密码修改成功');
        } else {
            $this->apiResponse('0', '密码修改失败');
        }
    }

    /**
     * 个人资料
     */
    public function memberBaseData()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $member_info = D('Member')->where (array ('id'=>$m_id,'status' => 1))->field ('id as m_id,head_pic,nickname,realname,sex,tel,account')->find();
        if(empty($member_info['tel'])){
            D('Member')->where (array ('id'=>$m_id))->save(array ('tel'=>$member_info['account']));
        }
        $member_info['head_pic'] =$this->getOnePath($member_info['head_pic']);
        $member_info['is_password'] = $member_info['password'] ? '1' : '0';
        unset($member_info['password']);
        $this->apiResponse('1', '请求成功', $member_info);
    }

    /**
     * 修改个人资料
     * head_pic nickname  head_pic_id
     */
    public function modBaseData()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $rule = array(
            array('nickname', 'string', '请输入昵称'),
            array('realname', 'string', '请输入姓名'),
            array('sex', 'string', '请输入性别'),
        );
        $this->checkParam($rule);
        if (!empty($_FILES['head_pic']['name'])) {
            $res = api('UploadPic/upload', array(array('save_path' => 'Member')));
            foreach ($res as $value) {
                $data['head_pic'] = $value['id'];
            }
        }
        if ($request['head_pic_id']) {
            $data['head_pic'] = $request['head_pic_id'];
        }
        if ($request['sex']) {
            $data['sex'] = $request['sex'];
        }
        $data['nickname'] = $request['nickname'];
        $data['realname'] = $request['realname'];
        $where['id'] = $m_id;
        $res = D('Member')->querySave($where, $data);
        if ($res) {
            $this->apiResponse('1', '修改个人资料成功');
        }
    }

    /**
     * 上传头像
     * head_pic
     */
    public function uploadpic()
    {
        $request = $_REQUEST;
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        if (!empty($_FILES['head_pic']['name'])) { //头像
            $res = uploadimg($_FILES, CONTROLLER_NAME);
            $data['head_pic'] = $res['save_id'];
        }
        if ($request['head_pic_id']) {
            $data['head_pic'] = $request['head_pic_id'];
        }
        $where['id'] = $m_id;
        $res = D('Member')->querySave($where, $data);
        if ($res) {
            $this->apiResponse('1', '上传头像成功');
        } else {
            $this->apiResponse('0', '上传头像失败');
        }
    }

    /**
     * 生成推广码方案
     */
    public function buildCode()
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = array('1', '2', '3', '4',
            '5', '6', '7', '8', '9', '0');
        // 在 $chars 中随机取 $length 个数组元素键名
        $length = 6;
        $value = array_rand($chars, $length);
//        $shuff = shuffle($value);
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            // 将 $length 个数组元素连接成字符串
            $password .= $chars[$value[$i]];
        }
        $check_code = D("Member")->where('invite_code=' . $password)->field('id')->find();
        if (!empty($check_code)) {
            $this->buildCode();
        } else {
            return $password;
        }
    }

    /**
     * 先检查推荐码是否存在
     **/
    public function checkShareCodeBefore($invite_code = '')
    {
        $m_info = D("Member")->where('invite_code=' . $invite_code)->field('id')->find();
        if (!$m_info) {
            $this->apiResponse('0', '推荐码不存在');
        }
    }

    /**
     * 检查推广码方案
     **/
    public function checkShareCode($invite_code='', $s_id = 0)
    {
        $m_info = D ("Member")->where (array ('invite_code=' . $invite_code))->find ();
        $check_hid = M ("CouponLog")->where ('s_id=' . $s_id)->find ();
        $appsetting = M ('Appsetting')->find ();
        if ( $check_hid ) {
            $this->apiResponse (0 , '您已经绑定过推荐人了，请不要填写推荐码');
        }
        if ( $m_info['id'] == $s_id ) {
            $this->apiResponse (0 , '您不能推荐您自己');
        }
        if ( $m_info ) {
            $data_ext = array (
                'm_id' => $m_info['id'] ,
                's_id' => $s_id ,
                'create_time' => time () ,
                'status' => 1 ,
                'desc' => '邀请好友获得洗车代金劵' ,
            );
            $data = array (
                'm_id' => $m_info['id'] ,
                'create_time' => time () ,
                'end_time' => time () + ($appsetting['expire_time'] * 24 * 3600) ,
                'money'=>$appsetting['price'],
                'type' => 1 ,
                'is_bind' => 1 ,
                'comes' => '邀请好友赠送代金券' ,
            );
            $log = M ("CouponLog")->data ($data_ext)->add ();
            $bind = M ("CouponBind")->data ($data)->add ();
            if ( $log && $bind ) {
                $m_info = D ("Member")->where ('invite_code=' . $invite_code)->find ();
                $content = '您成功邀请了一位好友，获得一张现金抵用劵，快来看看吧！';
                pushOneUser ($content , $m_info['token'] , '1');
            }
        } else {
            $this->apiResponse ('0' , '推荐码不存在');
        }
    }
//    /**
//     * 验证手机号
//     * bind_id,account verify
//     */
//    public function verifyAccount()
//    {
//        $m_id = $this->checkToken();
//        $this->errorTokenMsg($m_id);
//        $request = $_REQUEST;
//        $param['where']['id'] = $m_id;
//        $param['where']['status'] = array('neq', 9);
//        $member_info = D('Member')->queryRow($param['where']);
//        $rule = array(
//            array('account','string','请输入手机号'),
//            array('verify','string','请输入验证码'),
//        );
//        $this->checkParam($rule);
//        if ($request['account'] != $member_info['account']) {
//            $this->apiResponse('0', '手机号错误,请重试');
//        }
//        //检查短信验证码
//        $res = D('Sms')->checkVerify($request['account'], $request['verify'], 'mod_bind');
//        if ($res['error']) {
//            $this->apiResponse('0', $res['error']);
//        }else{
//            $this->apiResponse('1', '验证成功');
//        }
//    }

//    /**
//     * 修改密码
//     * old_password   password   repassword
//     */
//    public function changePassword()
//    {
//        $m_id = $this->checkToken();
//        $this->errorTokenMsg($m_id);
//        $request = $_REQUEST;
//        $rule = array(
//            array('old_password', 'string', '请输入原来密码'),
//            array('password', 'string', '请输入新密码'),
//            array('repassword', 'string', '请再次输入密码'),
//        );
//        $this->checkParam($rule);
//        $param['where']['id'] = $m_id;
//        $param['where']['status'] = array('neq', 9);
//        $member_info = D('Member')->queryRow($param['where']);
//        $old_password = CreatePassword($request['old_password'], $member_info['salt']);
//        if ($request['old_password'] == '' && $request['password'] == '' && $request['password'] == '') {
//            $this->apiResponse('0', '必填参数不能为空');
//        } else if ($old_password != $member_info['password']) {
//            $this->apiResponse('0', '旧密码错误');
//        } elseif ($request['password'] != $request['repassword']) {
//            $this->apiResponse('0', '两次输入密码不一致');
//        } else {
//            $data['salt'] = NoticeStr(6);
//            $data['password'] = CreatePassword($request['password'], $data['salt']);
//            $res = D("member")->where(array('id' => $this->userId))->save($data);
//            if ($res) {
//                $this->apiResponse('1', '修改密码成功');
//            } else {
//                $this->apiResponse('0', '修改密码失败');
//            }
//        }
//    }
}
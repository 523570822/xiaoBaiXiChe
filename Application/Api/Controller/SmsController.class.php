<?php
namespace Api\Controller;
/**
 * 用户端短信模块
 * Class SmsController
 * @package Api\Controller
 */
class SmsController extends BaseController{
    /**
     * 初始化方法
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 发送验证码
     * 传递参数的方式：post
     * 需要传递的参数：
     * 手机号：account
     * 发送类型：send_type： login(登录)，register（注册）,retrieve（找回密码），mod_bind（修改绑定账号），
     * re_bind（绑定新账号，三方登录绑定手机号）,bind_bank(绑定银行卡)，bind_alipay(绑定支付宝)
     */
    public function sendVerify(){
        $request =$_REQUEST;
        $param = array(
            array('account','string','请输入手机号'),
            array('send_type','string','请输入短信类型'),
            );
        $this->checkparam($param);//判断参数的合法性
        if($request['send_type'] == 'register'){
            $res = M('Member')->where(array('account'=>$request['account'],'status'=>array('neq',9)))->find();
            if($res){
                $this->apiResponse('0','您已注册，请直接登录');
            }
        }
       // $result = D('Sms')->sendVerify($request['account'],$request['send_type']);
        $result=getVerification($request['account'],$request['send_type']);
        if($result['success']){
            $this->apiResponse('1',$result['success']);
        }else{
            $this->apiResponse('0',$result);
        }
    }

    /**
     * 验证短信验证码
     * 传参方式：post
     * 手机号：account
     * 验证码：verify
     * 发送类型：send_type： login(登录)，register（注册）,retrieve（找回密码），mod_bind（修改绑定账号），
     * re_bind（绑定新账号，三方登录绑定手机号）,bind_bank(绑定银行卡)，bind_alipay(绑定支付宝)
     */
    public  function  checkVerify(){
        $request = $_REQUEST;
        $param = array(
            array('account','string','请输入手机号'),
            array('send_type','string','请输入短信类型'),
            array('verify','string','请输入验证码'),
        );

        $this->checkparam($param);//判断参数的合法性

        $res = D('Sms')->checkVerify($request['account'],$request['verify'],$request['send_type']);
        if($res['error']){
            $this->apiResponse('0',$res['error']);
        }else{
            $this-> apiResponse('1',$res['success']);
        }
    }
}
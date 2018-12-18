<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/18
 * Time: 14:30
 */

namespace Api\Controller;
use Think\Controller;

/**
 * 登录接口
 * Class LoginController
 * @package Api\Controller
 */
class LoginController extends BaseController
{
    /**
     *构造方法
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/18 14:34
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     *登录方法
     *user:jiaming.wang  459681469@qq.com
     *Date:2018/12/18 16:22
     */
    public function login(){
//        $post = checkAppData('phone','手机号');
        $post['phone'] = 17622818248;
        $post['password'] = 1762818248;
        if (!isMobile($post['phone'])) {
            $this->apiResponse('0','手机号格式有误');
        }
        $member = D('Member')->where(array('account'=>$post['phone']))->find();
        $check_password = checkPassword($post['password'], $member['salt'], $member['password']);
        if ($member) {
            if ($check_password != 1) {
                $this->apiResponse('1','登陆成功',array('token'=>$member['token']));
            }else{
                $this->apiResponse('0','密码错误');
            }
        } else {
            $this->apiResponse('0','用户不存在',array('token'=>''));
        }
    }
}
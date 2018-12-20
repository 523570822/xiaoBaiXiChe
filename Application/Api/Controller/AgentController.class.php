<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/20
 * Time: 09:27
 */

namespace Api\Controller;
use Common\Service\ControllerService;

/**
 *代理商模块
 * Class AgentController
 * @package Api\Controller
 */
class AgentController extends BaseController
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
        $post = checkAppData('phone，password','手机号-密码');
        /*$post['phone'] = 17622818248;
        $post['password'] = 123456;*/
        if (!isMobile($post['phone'])) {
            $this->apiResponse('0','手机号格式有误');
        }
        $member = D('Agent')->where(array('account'=>$post['phone']))->find();
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
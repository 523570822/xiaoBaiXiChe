<?php
/**
 * Created by PhpStorm.
 * User: 木公子
 * Date: 2018/7/21
 * Time: 15:03
 */

namespace Common\Service;


use Think\Controller;

class ControllerService extends Controller
{
    // 登录属性
    protected $isLogin=false; // 判断SESSION得到是否登录
    protected $userId; // SESSION中取出的用户ID
    protected $userInfo; // SESSION内取出的用户信息
    // 框架属性
    protected $redirectLogin; // 跳转登陆页
    protected $currentModule; // 当前模块
    protected $currentRouter; // 当前路由地址
    protected $loginModule; // 当前登录信息继承模块
    protected $allowRouter; // 未登录登陆可访问的路由
    protected $basicRouter; // 不参与权限控制的路由
    protected $adminModule='Manager'; // 后台权限控制模块
    protected $apiModule='Api'; // Api接口模块
    // Vendor/Txunda/类实例化
    protected $paramClass; // 参数检查类实例
    protected $uploadClass; // 上传文件类实例
    protected $verificationClass; // 上传文件类实例
    /**
     * 框架初始化方法
     * User: 木
     * Date: 2018/7/21 15:22
     */
    public function _initialize() {
        // 设置当前路由地址
        $this->currentModule = ucfirst(strtolower(MODULE_NAME));
        $this->currentRouter = $this->currentModule.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
        // 1.检查登录
        if($this->currentRouter !== $this->apiModule) $this->checkLogin();
        // 2.访问权限
        if($this->currentModule === $this->adminModule && !in_array($this->currentRouter, $this->allowRouter) && !in_array($this->currentRouter, $this->basicRouter)) {
            $this->checkAccess();
        }
    }
    /**
     * 检查参数
     * User: 木
     * Date: 2018/7/21 15:47
     * @param array $fields
     * @return mixed
     */
    final protected function checkParam($fields=array()) {
        // 实例化Param类
        if(!$this->paramClass) {
            vendor('Txunda.Txunda#Param');
            $this->paramClass = new \Param();
        }
        // 执行参数检查操作
        $Res =  $this->paramClass->checkParam($fields);
        if($Res === false) {
            $this->apiResponse(0, $this->paramClass->getError());
        }
        return $Res;
    }
    /**
     * Api返回数据
     * User: 木
     * Date: 2018/8/2 17:28
     * @param int $code
     * @param string $message
     * @param array $data
     */
    final protected function apiResponse($code=0, $message='', $data=array()) {
        $response = array(
            'code' => $code,
            'message' => $message,
            'data' => $data
        );
        exit(json_encode($response, JSON_UNESCAPED_UNICODE));
    }
//    /**
//     * 上传文件
//     * User: 木
//     * Date: 2018/7/21 15:48
//     * @param array $files
//     */
//    final protected function uploadFile($files=array()) {
//        // 实例化Upload类
//        if(!$this->paramClass) {
//            vendor('Txunda.Txunda#Upload');
//            $this->paramClass = new \Upload();
//        }
//        // 执行上传文件操作
//    }
    /**
     * 设置登录信息
     * User: 木
     * Date: 2018/7/21 15:44
     * @param $data
     */
    final protected function setLoginInfo($data=array()) {
        $key = $this->_getLoginInfoKey();
        session($key, $data);
    }
    /**
     * 清除登陆信息
     * User: 木
     * Date: 2018/8/7 15:18
     */
    final protected function clearLoginInfo() {
        $key = $this->_getLoginInfoKey();
        session($key, null);
    }
    /**
     * 获取登录信息SESSION存储名称
     * User: 木
     * Date: 2018/7/21 15:53
     * @return string
     */
    final private function _getLoginInfoKey() {
        $key = $this->loginModule ? strtoupper($this->loginModule) : strtoupper($this->currentModule);
        $key .= '_USER';
        return $key;
    }
    /**
     * 检查登录方法
     * User: 木
     * Date: 2018/7/21 15:23
     */
    final protected function checkLogin() {
        // 获取SESSION
        $key = $this->_getLoginInfoKey();
        $user_info = session($key);
        if(empty($user_info)) {
            // 未登录
            $this->isLogin = false;
        }else {
            // 登录信息赋值
            $this->isLogin = true;
            $this->userInfo = $user_info;
            $this->userId = isset($user_info['id']) ? $user_info['id'] : '';
        }
    }
    /**
     * token过期的时返回错误信息
     */
    function errorTokenMsg($m_id){
        if($m_id==0){
            $this->apiResponse('-1','登录失效，请重新登录');
        }
    }
    /**
     * 检查访问权限
     * User: 木
     * Date: 2018/7/21 15:42
     */
    final protected function checkAccess() {
        if(!$this->isLogin) {
            $this->redirectLogin = true;
            return ;
        }
        $auth = json_decode(file_get_contents(FRAME_CACHE_PATH.'AdminMenu/'.$this->userId), true);
        if(!in_array($this->currentRouter, array_keys($auth['auth']))) {
            if(IS_AJAX) $this->apiResponse(0, '没有访问权限');
            // 模板提示
            $this->assign('error', '没有访问权限');
            exit($this->display('Public/error'));
        }
        $title = $auth['auth'][$this->currentRouter];
        $this->assign('title', $title);
        // 记录行为日志
        $data = array(
            'manager_id' => $this->userId,
            'username' => $this->userInfo['username'],
            'name' => $title,
            'router' => $this->currentRouter,
            'url' => $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"],
            'param' => 'POST: '.http_build_query($_POST)."\r\nCookie:".http_build_query($_COOKIE),
            'is_ajax' => IS_AJAX ? 1 : 0,
            'add_time' => time(),
        );
        D('Manager/AdminBehavior')->addRow($data);
    }
    /**
     * 获取一张图片的链接地址
     * @param $id
     * @param string $default_path
     * @return string
     */
    final protected  function getOnePath($id,$default_path = ''){
        $path = M('File')->where(array('id'=>$id))->getField('path');
        return $path?$path:$default_path;
    }

}
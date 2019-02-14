<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/7/26
 * Time: 11:47
 */

namespace Manager\Controller;


use Common\Service\ControllerService;

class BaseController extends ControllerService
{
    public function _initialize() {
        // 设置权限控制模块
        $this->allowRouter = array('Manager/Index/index','Manager/Admin/login','Manager/Index/verifyImage');
        // 开发者配置不受权限控制
        $debugRouter = array(
            'Manager/AdminMenu/index','Manager/AdminMenu/addMenu','Manager/AdminMenu/system','Manager/AdminMenu/addSystemMenu','Manager/AdminMenu/delMenu','Manager/AdminMenu/lockMenu','Manager/AdminMenu/editMenu',
            'Manager/AdminMenu/editSort',
            'Manager/Debug/index',
            'Manager/Admin/info', 'Manager/Admin/editPwd',
            'Manager/Index/uploadFile', 'Manager/Index/delFile'
        );
        $this->basicRouter = array_merge($debugRouter, array('Manager/Index/index','Manager/Index/welcome','Manager/Admin/logout'));
        // ControllerService初始化
        parent::_initialize();
        // 未登录状态访问登录后的页面
        if($this->redirectLogin) {
            $this->redirect('Admin/login');
        }
        $this->assign('website', C('WEBSITE'));
    }
    /**
     * 获取当前页面的url地址
     * @return mixed|string
     * User: yongjin.xiong 98383028@qq.com
     * Date:2018/05/14 下午1:17
     */
    public function curPageURL()
    {
        $pageURL = 'http';
        if (!empty($_SERVER['HTTPS'])) {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        //去除url结尾的.html或者.shtml
        if(substr($pageURL,-5) == '.html'){
            $pageURL = str_replace('.html','',$pageURL);
        }
        if(substr($pageURL,-6) == '.shtml'){
            $pageURL = str_replace('.shtml','',$pageURL);
        }
        return $pageURL;
    }

    /**
     * 删除语言
     * User: admin
     * Date: 2018-08-15 13:34:52
     */
    public function delete() {
        $this->checkParam(array('ids','array','请选择至少一条'));
        $request  = I('Request.');
        //判断是数组ID还是字符ID
        if(is_array($request['ids'])) {
            //数组ID
            $where['id'] = array('in',$request['ids']);
        } elseif (is_numeric($request['ids'])) {
            //数字ID
            $where['id'] = $request['ids'];
        }

        $data = array(
            'status'        => 9,
            'update_time'   => time()
        );
        $Res = D($request['model'])->where($where)->data($data)->save();
        $Res ? $this->apiResponse(1, '删除成功') : $this->apiResponse(0, '删除失败');
    }




}
<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/7/30
 * Time: 14:39
 */

namespace Manager\Controller;


class AdminMenuController extends BaseController
{
    public function _initialize() {
        parent::_initialize();
        if(!APP_DEBUG) die;
        $path = APP_PATH.$this->currentModule.'/Controller';
        if(ACTION_NAME == 'addMenu' || ACTION_NAME == 'addSystemMenu') {
            if(!is_writable($path)) {
                echo '<script>alert("'.$path.'目录没有写入权限, 不能自动生成方法，请手动生成或者添加权限");</script>';
            }
        }
    }
    /**
     * 菜单首页
     * User: 木
     * Date: 2018/8/4 14:10
     */
    public function index() {
        $list = D('Manager/AdminMenu')->getSortList(0);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 系统菜单首页
     * User: 木
     * Date: 2018/8/6 17:19
     */
    public function system() {
        $list = D('AdminMenu')->getSortList(1);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加菜单
     * User: 木
     * Date: 2018/8/4 15:19
     */
    public function addSystemMenu() {
        if(IS_POST) {
            $rule = array(
                array('name', 'string>0', '请输入菜单名称'),
                array('p_id', 'notnull'),
                array('module', 'string>0', '请输入模块名'),
                array('controller', 'string>0', '请输入控制器名'),
                array('action', 'string', '请输入方法名'),
                array('icon', 'notnull'),
                array('sort', 'notnull'),
                array('type', 'int=1'),
                array('status', 'int=1')
            );
            $data = $this->checkParam($rule);
            $data['system'] = 1;
            $data['router'] = empty($data['action']) ? '' : $data['module'].'/'.$data['controller'].'/'.$data['action'];
            // 判断路由是否存在
            if(D('AdminMenu')->queryHave(array('router'=>$data['router'])))  $this->apiResponse(0, '路由地址已存在, 不可重复添加');
            // 添加菜单
            $res = D('AdminMenu')->addRow($data);
            // 创建PHP类并写入方法
            if($res) {
                $this->cerateCtrl($data['module'], $data['controller'], $data['action'], $data['name']);
            }
            $res ?  $this->apiResponse(1, '添加菜单成功') : $this->apiResponse(0, $data);
        }else {
            $where = array('status'=>1, 'p_id'=>0, 'system'=>1);
            $field = 'id, name, controller';
            $list = D('AdminMenu')->queryList($where, $field);
            $this->assign('list', $list);
            $this->display();
        }
    }

    /**
     * 添加菜单
     * User: 木
     * Date: 2018/8/4 15:19
     */
    public function addMenu() {
        if(IS_POST) {
            $rule = array(
                array('name', 'string>0', '请输入菜单名称'),
                array('p_id', 'notnull'),
                array('module', 'string>0', '请输入模块名'),
                array('controller', 'string>0', '请输入控制器名'),
                array('action', 'string', '请输入方法名'),
                array('icon', 'notnull'),
                array('sort', 'notnull'),
                array('type', 'int=1'),
                array('status', 'int=1')
            );
            $data = $this->checkParam($rule);
            $data['router'] = empty($data['action']) ? '' : $data['module'].'/'.$data['controller'].'/'.$data['action'];
            // 判断路由是否存在
            if($data['p_id'] > 0 && !empty($data['action']) && D('AdminMenu')->queryHave(array('router'=>$data['router'])))  $this->apiResponse(0, '路由地址已存在, 不可重复添加');
            // 添加菜单
            $res = D('AdminMenu')->addRow($data);
            // 创建PHP类并写入方法
            if($res) {
                $this->cerateCtrl($data['module'], $data['controller'], $data['action'], $data['name']);
            }
            $res ?  $this->apiResponse(1, '添加菜单成功') : $this->apiResponse(0, $data);
        }else {
            $where = array('status'=>1, 'p_id'=>0, 'system'=>0);
            $field = 'id, name, controller';
            $list = D('AdminMenu')->queryList($where, $field);
            $this->assign('list', $list);
            $this->display();
        }
    }
    /**
     * 删除菜单
     * User: 木
     * Date: 2018/8/8 10:20
     */
    public function delMenu() {
        $id = $this->checkParam(array('id', 'int'));
        // 检查是否存在二级菜单
        if(D('AdminMenu')->queryHave(array('p_id'=>$id, 'status'=>array('neq',9)))) {
            $this->apiResponse(0, '删除失败, 存在二级菜单');
        }
        $Res = D('AdminMenu')->querySave($id, array('status'=>9));
        $Res ? $this->apiResponse(1, '删除成功') : $this->apiResponse(0, '删除失败');
    }

    /**
     * 锁定菜单
     * User: 木
     * Date: 2018/8/8 11:07
     */
    public function lockMenu() {
        $id = $this->checkParam(array('id', 'int'));
        // 检查是否存在二级菜单
        $status = D('AdminMenu')->queryField($id, 'status');
        $data = $status == 1 ? array('status'=>0) : array('status'=>1);
        $Res = D('AdminMenu')->querySave($id, $data);
        $Res ? $this->apiResponse(1, $status == 1 ? '禁用成功' : '启用成功') : $this->apiResponse(0, $status == 1 ? '禁用失败' : '启用失败');
    }

    /**
     * 修改菜单
     * User: 木
     * Date: 2018/8/8 11:29
     */
    public function editMenu() {
        $id = $this->checkParam(array('id', 'int'));
        if(IS_POST) {
            $id = $this->checkParam(array('id', 'int'));
            $rule = array(
                array('name', 'string>0', '请输入菜单名称'),
                array('p_id', 'notnull'),
                array('module', 'string>0', '请输入模块名'),
                array('controller', 'string>0', '请输入控制器名'),
                array('action', 'string', '请输入方法名'),
                array('icon', 'notnull'),
                array('sort', 'notnull'),
                array('type', 'int=1'),
                array('status', 'int=1')
            );
            $data = $this->checkParam($rule);
            $data['router'] = !empty($data['action']) ? $data['module'].'/'.$data['controller'].'/'.$data['action'] : '';
            $Res = D('AdminMenu')->querySave($id, $data);
            $Res ? $this->apiResponse(1, '修改成功') : $this->apiResponse(0, '修改失败');

        }else {
            $system = $this->checkParam(array('system', 'int'));
            $where = array('status'=>array('neq', 9), 'p_id'=>0, 'system'=>$system);
            $field = 'id, name, controller';
            $list = D('AdminMenu')->queryList($where, $field);
            $data = D('AdminMenu')->queryRow($id);
            $this->assign('data', $data);
            $this->assign('list', $list);
            $this->display();
        }
    }

    /**
     * 修改排序
     * User: 木
     * Date: 2018/8/8 15:57
     */
    public function editSort() {
        $id = $this->checkParam(array('id', 'int'));
        $val = $this->checkParam(array('value', 'int'));
        $res = D('AdminMenu')->querySave($id, array('sort'=>$val));
        $res ? $this->apiResponse(1, '修改成功') : $this->apiResponse(0, '修改失败');
    }

    /**
     * 创建控制器
     * User: 木
     * Date: 2018/8/13 11:06
     */
    private function cerateCtrl($module, $ctrlName='', $actionName='', $title='') {
        if(empty($module) || empty($ctrlName)) return false;
        $controller = APP_PATH.$module.'/Controller/'.$ctrlName.'Controller.class.php';
        if(!file_exists($controller)) {
            $str = '<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By '.$this->userInfo['username'].'
 * Date: '.date('Y-m-d').'
 * Time: '.date('H:i:s').'
 */

namespace Manager\Controller;


class '.$ctrlName.'Controller extends BaseController
{

}';
            file_put_contents($controller, $str);
        }
        // 写入方法
        if(!empty($actionName)) {
            $this->appendAction($controller, $actionName, $title);
        }
    }

    /**
     * 追加方法
     * User: 木
     * Date: 2018/8/13 11:06
     */
    private function appendAction($path, $actionName, $title='') {
        // 控制器备份 runtime目录不考虑写入权限问题 如果没权限程序无法运行
        $bakPath = RUNTIME_PATH . 'PhpBak/';
        if(!$bakPath) {
            mkdir($bakPath, 0777, true);
        }
        // 创建目录
        $dir = date('Y-m');
        if(!is_dir($bakPath.$dir)) {
            mkdir($bakPath.$dir, 0777, true);
        }
        // 写入缓存
        $filename = $bakPath.$dir . '/' . date('Y m d H i s') . str_replace(dirname($path).'/', '', $path) . '_bak';
        file_put_contents($bakPath.$dir . '/1', '1');
        if(file_put_contents($filename, file_get_contents($path))) {
            // 添加方法名
            $str = '
    /**
     * '.$title.'
     * User: '.$this->userInfo['username'].'
     * Date: '.date('Y-m-d H:i:s').'
     */
    public function '.$actionName.'() {

    }';
            // 追加文本
            $con = file_get_contents($path);
            $con = substr($con, 0, strripos($con, '}')) . $str;
            file_put_contents($path, $con."\r\n}");
        }

    }

}
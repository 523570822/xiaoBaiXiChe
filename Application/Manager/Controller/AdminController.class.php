<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/7/30
 * Time: 14:39
 */

namespace Manager\Controller;


class AdminController extends BaseController
{
    /**
     * 管理员列表
     * User: 木
     * Date: 2018/8/6 16:59
     */
    public function index() {
        $data = D('Admin')->queryList('', '', array('page_size'=>15));
        foreach($data['list'] as &$item) {
            $item['group_name'] = D('AdminGroup')->queryField($item['group_id'], 'name');
        }
        $this->assign($data);
        $this->display();
    }


    /**
     * 管理员登陆
     * User: 木
     * Date: 2018/7/30 14:51
     */
    public function login() {
        if($this->isLogin) {
            $this->redirect('Index/index');
        }
        if(IS_POST) {
            $rule = array(
                array('username', 'string', '请输入用户名'),
                array('password', 'string', '请输入密码'),
                array('verify_code', 'int=4', '请输入正确的验证码'),
                array('remember', 'notnull')
            );
            $data = $this->checkParam($rule);
            // 验证码
            $verify = new \Think\Verify();
            if(!$verify->check($data['verify_code'])) {
                $this->apiResponse(0, '验证码错误');
            }
            // 查询账号信息
            $adminInfo = D('Admin')->queryRow(array('username'=>$data['username']), 'id, group_id, username, password, salt, status');
            if(!$adminInfo) $this->apiResponse(0, '用户名不存在');
            if($adminInfo['status'] != 1) $this->apiResponse(0, '用户已被锁定');
            // 验证密码
            if(CreatePassword($data['password'], $adminInfo['salt']) !== $adminInfo['password']) {
                $this->apiResponse(0, '密码错误');
            }
            // 缓存菜单权限
            if(!D('AdminGroup')->getMenu($adminInfo['id'], $adminInfo['group_id'])) {
                $this->apiResponse(0, '缓存权限失败, 请检查目录读写权限');
            }
            // 记住用户名
            if($data['remember'] == 1) cookie('ADMUSERNAME', $data['username']);
            // 存储登录信息
            $this->setLoginInfo($adminInfo);
            $this->apiResponse(1, '登陆成功');
        }else {
            $this->assign('username', cookie('ADMUSERNAME'));
            $this->display();
        }
    }

    /**
     * 退出登录
     * User: 木
     * Date: 2018/8/7 15:19
     */
    public function logout() {
        $this->clearLoginInfo();
        $this->redirect('Admin/login');
    }

    /**
     * 添加管理员
     * User: admin
     * Date: 2018-08-15 09:57:42
     */
    public function addAdmin() {
        if(IS_POST) {
            $rule = array(
                array('group_id', 'int>0', '请选择管理员分组'),
                array('username', 'string>0', '请输入管理员用户名'),
                array('password', 'string>0', '请输入管理员密码'),
                array('status', 'int=1', '请选择管理员状态'),
            );
            $data = $this->checkParam($rule);
            $data['salt'] = NoticeStr(6);
            $data['password'] = CreatePassword($data['password'], $data['salt']);
            $data['add_time'] = time();
            $res = D('Admin')->addRow($data);
            $res ? $this->apiResponse(1, '添加管理员成功') : $this->apiResponse(1, '添加管理员失败');
        }else {
            $where = array('p_id'=>0, 'status'=>array('neq', 9));
            $field = 'id, p_id, name, status';
            $group_list = D('AdminGroup')->queryList($where, $field);
            $this->assign('group_list', $group_list);
            $this->display();
        }
    }

    /**
     * 编辑管理员信息
     * User: admin
     * Date: 2018-08-15 09:59:30
     */
    public function editAdmin() {

    }


}
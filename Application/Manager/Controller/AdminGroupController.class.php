<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/7/30
 * Time: 14:45
 */

namespace Manager\Controller;


class AdminGroupController extends BaseController
{
    /**
     * 管理员分组
     * User: 木
     * Date: 2018/8/6 16:27
     */
    public function index() {
        $list = D('AdminGroup')->getSortList();
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加管理员分组
     * User: 木
     * Date: 2018/8/6 16:27
     */
    public function addAdminGroup() {
        if(IS_POST) {
            $rule = array(
                array('name', 'string>0', '请输入管理组名称'),
//                array('p_id', 'notnull', ''),
                array('status', 'int=1', '请选择状态'),
                array('menu', 'array', '请选择菜单权限')
            );
            $data = $this->checkParam($rule);
            $data['menu'] = implode('|', $data['menu']);
            $res = D('AdminGroup')->addRow($data);
            $res ? $this->apiResponse(1, '添加管理员分组成功') : $this->apiResponse(0, '添加管理员分组失败');
        }else {
//            $where = array('p_id'=>0, 'status'=>array('neq', 9));
//            $field = 'id, p_id, name, status';
//            $group_list = D('AdminGroup')->queryList($where, $field);
            $menu_list = D('AdminMenu')->getSortList();
            $this->assign('menu_list', $menu_list);
//            $this->assign('group_list', $group_list);
            $this->display();
        }
    }

    /**
     * 编辑管理员分组
     * User: 木
     * Date: 2018/8/15 16:36
     */
    public function editAdminGroup() {
        $id = $this->checkParam(array('id', 'int'));
        if(IS_POST) {
            $rule = array(
                array('name', 'string>0', '请输入管理组名称'),
//                array('p_id', 'notnull', ''),
                array('status', 'int=1', '请选择状态'),
                array('menu', 'array', '请选择菜单权限')
            );
            $data = $this->checkParam($rule);
            $data['menu'] = implode('|', $data['menu']);
            $res = D('AdminGroup')->querySave($id, $data);
            $res ? $this->apiResponse(1, '编辑管理员分组成功') : $this->apiResponse(0, '编辑管理员分组失败');
        }else {
            $data = D('AdminGroup')->queryRow($id);
            $data['menu'] = explode('|', $data['menu']);
            $menu_list = D('AdminMenu')->getSortList();
            $this->assign('menu_list', $menu_list);
            $this->assign('data', $data);
            $this->display();
        }
    }


    /**
     * 管理员分组排序
     * User: admin
     * Date: 2018-08-15 16:59:56
     */
    public function editSort() {
        $id = $this->checkParam(array('id', 'int'));
        $val = $this->checkParam(array('value', 'int'));
        $res = D('AdminGroup')->querySave($id, array('sort'=>$val));
        $res ? $this->apiResponse(1, '修改成功') : $this->apiResponse(0, '修改失败');
    }
}
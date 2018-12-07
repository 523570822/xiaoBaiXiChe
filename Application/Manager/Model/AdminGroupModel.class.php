<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/8/6
 * Time: 13:37
 */

namespace Manager\Model;


use Common\Service\ModelService;

class AdminGroupModel extends ModelService
{
    /**
     * 获取菜单列表
     * User: 木
     * Date: 2018/8/7 15:08
     * @return array
     */
    public function getSortList() {
        $where = array('status'=>array('neq', 9));
        $field = 'id, p_id, name, sort, status';
        $param['order'] = 'sort ASC, id ASC';
        $list = $this->queryList($where, $field, $param);
        foreach($list as &$item) {
            $item['num'] = D('Admin')->queryCount(array('group_id'=>$item['id']));
        }
        return $list;
//        return sunTree($list, 'id', 'p_id');
    }

    /**
     * 获取菜单权限
     * User: 木
     * Date: 2018/8/7 14:05
     * @param $admin_id
     * @param $group_id
     * @return array
     */
    public function getMenu($admin_id, $group_id) {
        $menu = $admin_id == 1 ? '*' : $this->queryField($group_id, 'menu');
        $where = array('status'=> array('neq', 9));
        if($menu != '*') {
            $where['id'] = array('in', explode('|', $menu));
        }
        if($menu == '*') {
            $list = D('AdminMenu')->getSortList();
        }else {
            $list = sunTree(D('AdminMenu')->queryList($where));
        }
        $arr =array('system'=>array(), 'menus'=>array(), 'auth'=>array());
        foreach($list as $key=>&$item) {
            if($item['status'] == 0 || $item['type'] != 1) {
                unset($list[$key]);
                continue;
            }
            unset($item['p_id'], $item['router'], $item['level']);
            foreach($item['sub_list'] as $sub_key=>&$sub_item) {
                $arr['auth'][$sub_item['router']] = $sub_item['name'];
                if($sub_item['status'] == 0 || $sub_item['type'] != 1) {
                    unset($item['sub_list'][$sub_key]);
                    continue;
                }
                unset($sub_item['p_id'], $sub_item['system'], $sub_item['icon'], $sub_item['level'], $sub_item['sub_list']);
            }
            if($item['system'] == 1) {
                unset($item['system']);
                $arr['system'][] = $item;
            }else {
                unset($item['system']);
                $arr['menus'][] = $item;
            }
        }
        return file_put_contents(FRAME_CACHE_PATH.'AdminMenu/'.$admin_id, json_encode($arr, JSON_UNESCAPED_UNICODE));
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/8/6
 * Time: 10:36
 */

namespace Manager\Model;


use Common\Service\ModelService;

class AdminMenuModel extends ModelService
{
    /**
     * 获取排序后的菜单列表
     * User: 木
     * Date: 2018/8/6 11:08
     */
    public function getSortList($system=null) {
        $param['order'] = 'sort ASC, id ASC';
        $where = array('status'=>array('neq', 9));
        if($system !== null) {
            $where['system'] = $system;
        }
        $list = $this->queryList($where, 'id, p_id, name, router, type, icon, system, sort, status', $param);
        return sunTree($list, 'id', 'p_id');
    }
}
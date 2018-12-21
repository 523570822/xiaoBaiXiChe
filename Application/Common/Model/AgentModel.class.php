<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/19
 * Time: 11:38
 */

namespace Common\Model;
use Common\Service\ModelService;

/**
 * 代理商模块
 * Class AgentModel
 * @package Common\Model
 */
class AgentModel extends ModelService
{
    public function findAgent($where,$field=''){
        if(empty($where)){
            return false;
        }else{
            if($where['status'] == '' || empty($where['status'])){
                $where['status'] = array('neq','9');
            }
            if ($field == '') {
                $result = $this->where($where)->find();
            } else {
                if (count(explode(',',$field)) == 1) {
                    $result = $this->where($where)->getField($field);
                } else {
                    $result = $this->where($where)->field($field)->find();
                }
            }
            foreach ($result as $k=>$v) {
                if (strpos($k,'pic')) {
                    $result[$k.'_path'] = getPic($v);
                }
            }
            return $result;
        }
    }

}
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
 * 合作方模块
 * Class PartnerModel
 * @package Common\Model
 */
class PartnerModel extends ModelService
{
    public function findPartner($where,$field=''){
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
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 11:37
 */
namespace Common\Model;
use Common\Service\ModelService;
/**
 * 消息模型
 * Class MsgModel
 * @package Common\Model
 */
class MsgModel extends ModelService{

    protected $tableName = 'msg';

    /**
     * 是否有未读消息
     * @param $m_id
     * @return string
     */
    public function isHaveMsg($m_id){
        if(empty($m_id)){
            return 0;
        }else{
            $list = M('Msg')
                ->alias('msg')
                ->field('*')
                ->where(array('msg.status'=>array('neq',9)))
                ->join(array("LEFT JOIN db_msg_read_log m_r ON m_r.msg_id = msg.id AND m_r.m_id = ".$m_id))
                ->order('msg.create_time desc')
                ->having('m_r.id IS NULL')
                ->select();
            return $list?count($list):0;
        }
    }
}
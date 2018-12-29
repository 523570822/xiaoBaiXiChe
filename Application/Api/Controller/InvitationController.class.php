<?php
/**
 * Created by PhpStorm.
 * User: Txunda13
 * Date: 2018/11/26
 * Time: 14:31
 */

namespace Api\Controller;


class InvitationController extends BaseController
{
    /**
     * 初始化方法
     */
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
 * 推广好友首页
 * **/
    public function Invitationindex()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $param['where']['id'] = $m_id;
        $param['field'] = '*';
        $member_info = D('Member')->queryRow($param['where'], $param['field']);
        $data['integral'] = $member_info['integral'];
        $invite_code = $member_info['invite_code'];
        $param['where']['id'] = $member_info['parent_id'];
        $member = D('Member')->queryRow($param['where']);
        $inviterphone=$member['account'];
        $list_info = D('ExtensionLog')
            ->where(array('db_extension_log.m_id' => $m_id))
            ->join("db_member ON db_extension_log.s_id = db_member.s_id")
            ->field('db_extension_log.id,db_extension_log.create_time,db_member.nickname')
            ->select();

        $list = D('Coupon') ->where('c_type' == 1)->field('db_create_time') ->count();
        if ($list_info) {
            foreach ($list_info as $k => $v) {
                $list_info[$k]['create_time'] = date('Y/m/d', $v['create_time']);
            }
        }
        $this->apiResponse(1, '����ɹ�', array("count" => count($list_info), "invite_code" => $invite_code, "myinviter"=>$inviterphone, "couponLiset"=>$list));
    }
    /**
     * 推广记录
     * **/
    public function Invitationdetails()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $param['where']['id'] = $m_id;
        $param['field'] = '*';
        $member_info = D('Member')->queryRow($param['where'], $param['field']);
        $data['integral'] = $member_info['integral'];
        $param['where']['id'] = $member_info['parent_id'];
        $list_info = D('ExtensionLog')
            ->where(array('db_extension_log.m_id' => $m_id))
            ->join("db_member ON db_extension_log.m_id = db_member.id")
            ->field('db_extension_log.id,db_extension_log.create_time,db_member.nickname')
            ->select();
        if ($list_info) {//被邀请的好友
            foreach ($list_info as $k => $v) {
                $list_info[$k]['create_time'] = date('Y/m/d', $v['create_time']);
            }
        }
        $this->apiResponse(1, '����ɹ�', array("list" => $list_info));
    }
}
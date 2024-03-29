<?php
/**
 * Created by PhpStorm.
 * User: Txunda13
 * Date: 2018/11/26
 * Time: 14:31
 */

namespace Api\Controller;


class InvitationController extends BaseController {
    /**
     * 初始化方法
     */
    public function _initialize () {
        parent::_initialize ();
    }

    /**
     * 推广好友首页
     * **/
    public function Invitationindex () {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $param['where']['id'] = $m_id;
        $param['field'] = '*';
        $member_info = D ('Member')->queryRow ($param['where'] , $param['field']);
        $data['integral'] = $member_info['integral'];
        $invite_code = $member_info['invite_code'];
        $param['where']['id'] = $member_info['parent_id'];
        $member = D ('Member')->queryRow ($param['where']);
        $inviterphone = $member['account'];
        $list_info = D ('CouponLog')->where (array ('db_coupon_log.m_id' => $m_id))->field ('id,create_time,s_id')->select ();
        $list = D ('CouponBind')->where (array ('m_id' => $m_id , 'type' => 1))->field ('create_time')->count ();
        if ( $list_info ) {
            foreach ( $list_info as $k => $v ) {
                $list_info[$k]['create_time'] = date ('Y/m/d' , $v['create_time']);
            }
        }
        $this->apiResponse (1 , '请求成功' , array ("count" => count ($list_info) , "invite_code" => $invite_code , "myinviter" => $inviterphone , "couponLiset" => $list));
    }

    /**
     * 推广记录
     * **/
    public function Invitationdetails () {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $param['where']['id'] = $m_id;
        $param['field'] = '*';
        $member_info = D ('Member')->queryRow ($param['where'] , $param['field']);
        $data['integral'] = $member_info['integral'];
        $param['where']['id'] = $member_info['parent_id'];
        $list_info = D ('CouponLog')->where (array ('m_id' => $m_id))->field ('id,create_time,s_id')->select ();

        if ( $list_info ) { //被邀请的好友
            foreach ( $list_info as $k => $v ) {
                $nickname = D('Member')->where(array('id'=>$v['s_id']))->find();
                $list_info[$k]['create_time'] = date ('Y/m/d' , $v['create_time']);
                $list_info[$k]['nickname'] = $nickname['nickname'];
            }
        }
        $this->apiResponse (1 , '请求成功' , array ("list" => $list_info));
    }

    /**
     *奖励规则
     **/
    public function InvitationDetail () {
        $data = D ("Article")->where (array ('type' => 4 , 'status' => 1))->field ('id,title,content')->find ();
        $data['content'] = $this->setAbsoluteUrl ($data['content']);
        $data['content'] = htmlspecialchars_decode ($data['content']);
        $data['content'] = str_replace ('img src="' , 'img src = "' . C ('API_URL') , $data['content']);
        $this->apiResponse (1 , '查询成功' , $data);
    }
}
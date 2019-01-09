<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 10:02
 */

namespace Api\Controller;
/**
 * 小鲸卡模块
 * Class MsgController
 * @package Api\Controller
 */
class CardController extends BaseController
{
    /**
     *
     **/
    public function card ()
    {

    }

    /**
     *小鲸卡购买列表
     **/
    public function cardList ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $wallet = D ('WashCard')->where ((array ('card_type' => 1 , 'status' => 1)))->field ('*')->select ();
        $picture['picture_id'] = C ('API_URL') . $this->getOnePath ($data['picture_id'] , C ('API_URL') . '/Uploads/Member/default.png');
        $this->apiResponse ('1' , '小鲸卡购买列表' , $wallet);
    }

    /**
     *我的小鲸卡
     **/
    public function myCard ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $list_info = D ('VipCard')
            ->where (array ('db_vip_card.m_id' => $m_id))
            ->where (array ('db_vip_card.status' => array ('neq' , 9)))
            ->join ("db_wash_card ON db_vip_card.card_id = db_wash_card.id")
            ->field ('db_vip_card.id,db_vip_card.end_time,db_vip_card.create_time,db_vip_card.status,db_wash_card.name,db_wash_card.rebate')
            ->select ();
        $time = time ();//1549693253;
        if ( $time > $list_info[0]['end_time'] ) {
            $mgs = '已过期';
            $gq = $mgs;
        } else {
            $day = ($list_info[0]['end_time'] - $time);
            if ( $list_info[0]['status'] == 2 ) {
                $mgs = '已过期';
            } else {
                $mgs = intval ($day / (60 * 60 * 24)) . '天';
            }
        }
        if ( $gq ) {
            D ('VipCard')
                ->where (array ('m_id' => $m_id , 'status' => array ('neq' , 9)))
                ->save (array ('status' => 2));
        }
        $this->apiResponse ('1' , '查询成功' , array ($mgs , $list_info[0]['name'] , ($list_info[0]['rebate'] * 10) . '折',$list_info[0]));
    }
}
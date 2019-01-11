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
     *购卡须知
     **/
    public function card ()
    {
        $data = D ("Article")->where (array ('id' => 2 , 'status' => 1 , 'sort' => 2))->field ('title,content')->find ();
        $this->apiResponse (1 , '请求成功' , $data);
    }

    /**
     *小鲸卡封面上传
     **/
    public function uploadpic()
    {
        $request = $_REQUEST;
        $rule = array ('id' , 'int' , '请填入上传的卡片ID');
        $this->checkParam ($rule);
        if (!empty($_FILES['card_pic']['name'])) {
            $res = uploadimg($_FILES, CONTROLLER_NAME);
            $data['card_pic'] = $res['save_id'];
        }
        if ($request['head_pic_id']) {
            $data['card_pic'] = $request['head_pic_id'];
        }
        $res = D('WashCard')->where (array ('id'=>$request['id']))->Save($data);
        if ($res) {
            $this->apiResponse('1', '上传成功');
        } else {
            $this->apiResponse('0', '上传失败');
        }
    }

    /**
     *小鲸卡购买列表
     **/
    public function cardList ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $wallet = D ('WashCard')->where ((array ('card_type' => 1 , 'status' => 1)))->field ('id,name,card_price,rebate,card_type,card_pic,content')->select ();
        foreach ($wallet as $k => $v) {
            $wallet[$k]['card_pic'] =C ('API_URL') . $this->getOnePath ($wallet[$k]['card_pic'] , C ('API_URL') . '/Uploads/Member/default.png');
            $wallet[$k]['rebate'] = $wallet[$k]['rebate']*10;
        }
        $this->apiResponse ('1' , '小鲸卡购买列表' ,$wallet);
    }

    /**
     *我的小鲸卡
     **/
    public function myCard ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $list_info = D ('VipCard')
            ->where (array ('db_vip_card.m_id' => $m_id,array ('db_vip_card.status' => array ('neq' , 9),'c_type'=>1)))
            ->join ("db_wash_card ON db_vip_card.card_id = db_wash_card.id")
            ->field ('db_vip_card.id,db_vip_card.end_time,db_vip_card.create_time,db_vip_card.status,db_wash_card.name,db_wash_card.rebate')
            ->select ();
        foreach ($list_info as $k => $v) {
            $list_info[$k]['rebate'] = $list_info[$k]['rebate']*10;
        }
        $time = time ();//1549693253;
        if ( $time > $list_info[0]['end_time'] ) {
            $mgs = '已过期';
            $gq = $mgs;
        } else {
            $day = ($list_info[0]['end_time'] - $time);
            if ( $list_info[0]['status'] == 3 ) {
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
        $this->apiResponse ('1' , $mgs ,$list_info[0]);
    }
}
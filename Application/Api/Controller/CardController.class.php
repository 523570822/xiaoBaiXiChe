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
        $rule = array ('id' , 'int' , '请填入要上传的卡片ID');
        $this->checkParam ($rule);
        if (!empty($_FILES['card_pic']['name'])) {
            $res = uploadimg($_FILES, CONTROLLER_NAME);
            $data['card_pic'] = $res['save_id'];
        }
        if ($request['card_pic_id']) {
            $data['card_pic'] = $request['card_pic_id'];
        }
        $res = D('LittlewhaleCard')->where (array ('id'=>$request['id']))->Save($data);
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
        $request = $_REQUEST;
        $wallet = D ('LittlewhaleCard')->where ((array ('status' => 1)))->field ('*') ->page($request['page'], '10')->select ();
        foreach ($wallet as $k => $v) {
            $wallet[$k]['card_pic'] =C ('API_URL') . $this->getOnePath ($wallet[$k]['card_pic'] ,'/Uploads/Member/default.png');
            $wallet[$k]['rebate'] = $wallet[$k]['rebate']*10;
        }
        if (!$wallet) {
            $message = $request['page'] == 1 ? '暂无消息' : '无更多消息';
            $this->apiResponse('1', $message);
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
        $list_info = D ('CardUser')
            ->where (array ('db_card_user.m_id' => $m_id,array ('db_card_user.status' => array ('neq' , 9))))
            ->join ("db_littlewhale_card ON db_card_user.l_id = db_littlewhale_card.id")
            ->field ('db_card_user.id,db_card_user.l_id,db_card_user.end_time,db_littlewhale_card.name,db_littlewhale_card.rebate')
            ->find ();
            $time = time ();//1549693253;
        if ($list_info['end_time']){
            if ( $time > $list_info['end_time'] ) {
                $mgs = '已过期';
            } else {
                $day = ($list_info['end_time'] - $time);
                $mgs = intval ($day / (60 * 60 * 24)) . '天';
            }
            $list_info['date'] = $mgs;
            $list_info['name']=$list_info['name'].'会员';
            $list_info['rebate']=($list_info['rebate']*10).'折';
            $this->apiResponse ('1' ,'查询成功' ,$list_info);
        }else{
            $this->apiResponse ('1' ,'查询成功');
        }
    }
}
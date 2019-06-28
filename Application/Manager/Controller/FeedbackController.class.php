<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2018-08-15
 * Time: 16:30:24
 */

namespace Manager\Controller;


class FeedbackController extends BaseController
{
    /**
     * 意见反馈
     * User: admin
     * Date: 2018-08-15 16:30:24
     */
    public function index()
    {
        $where = array ();
        //按用户账号查找
        if(!empty($_REQUEST['nickname'])){
            $nickname_where['nickname'] = array('LIKE',"%".I('request.nickname')."%");
            $data = D('Member')->where ($nickname_where)->getField("id", true);
            $where["m_id"] = ["in", implode ($data, ',')];
            if (empty($data))
            {
                $this->display();
            }
        }
        //运行状态查找
        if ( !empty($_REQUEST['status']) ) {
            if ( $_REQUEST['status'] == 1 ) {
                $where['status'] = 0;
            } elseif ( $_REQUEST['status'] == 2 ) {
                $where['status'] = 1;
            }
        }
        if ( !$_REQUEST['status'] ) {
            $where['status'] = array ('lt' , 9);
        }
        $param['page_size'] = 15;
        $data = D ('Feedback')->queryList ($where , '*' , $param);
        foreach ($data['list'] as $k=>$v){
            $data['list'][$k]['contents']=$v['content'];
            $data['list'][$k]['m_id']=$v['m_id'];
            $data['list'][$k]['pro_id']=$v['pro_id'];
            $dates = D('Member')->where (array ('id'=>$data['list'][$k]['m_id']))->field ('nickname')->find();
            $date = D('Problem')->where (array ('id'=>$data['list'][$k]['pro_id']))->field ('content')->find();
            $data['list'][$k]['nickname']=$dates['nickname'];
            $data['list'][$k]['content']=$date['content'];
        }
        $this->assign ($data);
        //页数跳转
        $this->assign ('url' , $this->curPageURL ());
        $this->display ();
    }

    public function saveFeedback()
    {
        $id = $this->checkParam(array('id', 'int'));
        $status = D('Feedback')->where(array('id'=>$id))->getField('status');
        $data = $status == 1 ? array('status'=>0) : array('status'=>1);
        D('Feedback')->where(array('id'=>$id))->save($data);
        $this->apiResponse(1, $status ==1 ? '已处理' : "" ) ;
    }

    /**
     * 反馈编辑
     * User: admin
     * Date: 2019-06-29 01:48:03
     */
    public function editFeedback() {
        if(IS_POST) {
            $request = I('post.');
            $where['id'] = $request['id'];
            $requests['update_time'] = time();
            $requests['reply'] = $request['reply'];
            $res = D('Feedback')->querySave($where,$requests);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $requests);
        }else {
            $id = $_GET['id'];
            $row = D('Feedback')->queryRow($id);
            $row['nickname'] = D('Member')->queryField(array('id'=>$row['m_id']),'nickname');
            $pro = array(
                'id'=>$row['pro_id'],
                'type'=> 1,
            );
            $row['pro'] = D('Problem')->queryField($pro,'content');
            $this->assign('row',$row);
            $this->display();
        }
    }
}
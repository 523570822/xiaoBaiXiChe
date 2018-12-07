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
        $where['status'] = array('lt',9);
        $param['order'] = 'id  desc';
        $param['page_size'] =15;
        $data = D('Feedback')->queryList($where, '*',$param);
        $this->assign($data);
        $this->display();
    }

    public function saveFeedback()
    {
        $id = $this->checkParam(array('id', 'int'));
        $status = D('Feedback')->where(array('id'=>$id))->getField('status');
        $data = $status == 1 ? array('status'=>0) : array('status'=>1);
        $Res = D('Feedback')->where(array('id'=>$id))->save($data);
        $this->apiResponse(1, $status ==1 ? '已处理' : "" ) ;
    }
}
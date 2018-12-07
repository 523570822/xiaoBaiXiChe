<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2018-08-28
 * Time: 16:27:04
 */

namespace Manager\Controller;


class SendLogController extends BaseController
{

    /**
     * 发信记录
     * User: admin
     * Date: 2018-08-28 16:27:04
     */
    public function index()
    {
        $where['status'] = array('lt',9);
        $param['order'] = 'id  desc';
        $param['page_size'] =15;
        $data = D('SendLog')->queryList($where, '*',$param);
        $this->assign($data);
        $this->display();
    }

    /**
     * 删除发信记录
     * User: admin
     * Date: 2018-08-28 16:27:53
     */
    public function delSendLog()
    {
        $id = $this->checkParam(array('id', 'int'));
        $Res = D('SendLog')->where(array('id'=>$id))->save(array('status'=>9));
        $Res ? $this->apiResponse(1, '删除成功') : $this->apiResponse(0, '删除失败');


    }
}
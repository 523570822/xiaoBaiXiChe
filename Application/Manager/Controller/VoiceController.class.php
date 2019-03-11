<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-01-25
 * Time: 14:51:30
 */

namespace Manager\Controller;

use Vendor\phpqrcode\QRcode;

class VoiceController extends BaseController {
    /**
     * 提示音列表
     * User: admin
     * Date: 2019-03-08 11:56:31
     */
    public function index () {
        $where = array ();
        //使用状态查找
        if ( !empty($_REQUEST['voice_type']) ) {
            $where['voice_type'] = I ('request.voice_type');
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
        $data = D ('Voice')->queryList ($where , '*' , $param);
        $this->assign ($data);
        //页数跳转
        $this->assign ('url' , $this->curPageURL ());
        $this->display ();
    }

    /**
     * 编辑语音
     * User: admin
     * Date: 2019-03-08 11:57:31
     */
    public function editVoice () {
        if ( IS_POST ) {
            $request = I ('post.');
            $rule = array (
                array ('voice_type' , 'string' , '语音播报类型') ,
                array ('content' , 'string' , '语音播报内容') ,
                array ('status' , 'string' , '状态') ,
            );
            $data = $this->checkParam ($rule);
            $where['id'] = $request['id'];
            $data['update_time'] = time ();
            $res = D ('Voice')->querySave ($where , $data);
            $res ? $this->apiResponse (1 , '修改成功') : $this->apiResponse (0 , "修改失败" , $data);
        } else {
            $id = $_GET['id'];
            $row = D ('Voice')->queryRow ($id);
            $this->assign ('row' , $row);
            $this->display ('Voice');
        }
    }

    /**
     * 添加语音
     * User: admin
     * Date: 2019-03-08 11:57:06
     */
    public function addVoice () {
        if ( IS_POST ) {
            $rule = array (
                array ('voice_type' , 'string' , '语音播报类型') ,
                array ('content' , 'string' , '语音播报内容') ,
                array ('status' , 'string' , '状态') ,
            );
            $data = $this->checkParam ($rule);
            $data['update_time'] = time ();
            $data['create_time'] = time ();
            $data['sort'] = '9999';
            $res = D ('Voice')->add ($data);
            $res ? $this->apiResponse (1 , '添加成功') : $this->apiResponse (0 , '添加失败' , $data);
        } else {
            $this->display ('Voice');
        }
    }

    /**
     * 启用语音
     * User: admin
     * Date: 2019-03-08 14:12:08
     */
    public function lockVoice () {
        $id = $this->checkParam (array ('id' , 'int'));
        $status = D ('Voice')->queryField ($id , 'status');
        $data = $status == 1 ? array ('status' => 0) : array ('status' => 1);
        $Res = D ('Voice')->querySave ($id , $data);
        $Res ? $this->apiResponse (1 , $status == 1 ? '关闭成功' : '启用成功') : $this->apiResponse (0 , $status == 1 ? '关闭失败' : '启用失败');
    }
}
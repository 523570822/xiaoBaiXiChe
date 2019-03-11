<?php

namespace Api\Controller;
/**
 * Created by PhpStorm.
 * User: Txunda
 * Date: 2018/7/6
 * Time: 13:06
 */
class FaultController extends BaseController {
    public function _initialize () {
        parent::_initialize ();
    }

    /**
     *机器故障反馈选择列表
     * type//1软件意见反馈 2洗车问题反馈
     **/
    public function issue () {
        $problem_info = M ('Problem')
            ->where (array ('status' => 1 , 'type' => 2))
            ->field ('id,content')
            ->order ('sort asc')
            ->select ();
        $this->apiResponse ('1' , '查询成功' , $problem_info);
    }

    /**
     *机器故障
     **/
    public function fault () {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = I ('post.');
        $rule = array (
            array ('mc_code' , 'string' , '请输入机器编码') ,
            array ('pro_id' , 'string' , '请选择反馈原因') ,
            array ('content' , 'string' , '请输入反馈内容') ,
        );
        $this->checkParam ($rule);
        if ( empty($request['content']) ) {
            $this->apiResponse ('0' , '反馈内容不能为空');
        } else {
            $where['content'] = $request['content'];
        }
        $is = M ('CarWasher')->where (array ('mc_code' => $request['mc_code']))->field ('*')->find ();
        if ( $is ) {
            $request['mc_id'] = $is['mc_id'];
            $where['mc_id'] = $request['mc_id'];
        } else {
            $this->apiResponse ('0' , '找不到该机器');
        }
        if ( $request['type'] ) {
            $this->apiResponse ($_FILES);
        } else {
            if ( empty($_FILES['pic_id']['name']) ) {
                $this->apiResponse (0 , '请上传问题机器故障照片');
            } elseif ( !empty($_FILES['pic_id']['name']) ) {
                $res = api ('UploadPic/upload' , array (array ('save_path' => 'Fault')));
                foreach ( $res as $key => $value ) {
                    $pic[$key] = $value['id'];
                }
                $where['pic_id'] = implode (',' , $pic);
            }
        }
        $where['pro_id'] = $request['pro_id'];
        $where['m_id'] = $m_id;
        $member_info = M ('Member')->where (array ('id' => $m_id))->find ();
        $where['contact'] = $member_info ['account'];
        $where['create_time'] = time ();
        $add = D ('Fault')->add ($where);
        if ( $add ) {
            $this->apiResponse ('1' , '提交成功');
        } else {
            $this->apiResponse ('0' , '提交失败');
        }
    }

    /**
     *机器故障图片上传测试
     **/
    public function faultPicture () {
        if ( !empty($_FILES['pic_id']['name']) ) {
            $res = api ('UploadPic/upload' , array (array ('save_path' => 'Fault')));
            foreach ( $res as $key => $value ) {
                $pic[$key] = $value['id'];
            }
            $where['pic_id'] = implode (',' , $pic);
        }
        $add = D ('Fault')->add ($where);
        if ( $add ) {
            $this->apiResponse ('1' , '提交成功');
        } else {
            $this->apiResponse ('0' , '提交失败');
        }
    }

}
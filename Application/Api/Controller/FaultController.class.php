<?php
namespace Api\Controller;
/**
 * Created by PhpStorm.
 * User: Txunda
 * Date: 2018/7/6
 * Time: 13:06
 */
class FaultController extends BaseController
{
    public function _initialize ()
    {
        parent::_initialize ();
    }

    /**
     *机器故障反馈选择列表
     * type//1软件意见反馈 2洗车问题反馈
     **/
    public function issue ()
    {
        $problem_info = M ('Problem')
            ->where (array ('status' => 1 , 'type' => 2))
            ->field ('id,content')
            ->order('sort asc')
            ->select ();
        $this->apiResponse ('1' , '查询成功' , $problem_info);
    }

    /**
     *机器故障
     **/
    public function fault ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = I ('post.');
        $rule = array (
            array('mc_code','string','请输入机器编码'),
            array ('pro_id' , 'string' , '请选择反馈原因') ,
            array ('content' , 'string' , '请输入反馈内容') ,
        );
        $this->checkParam ($rule);
        $is = M ('CarWasher')->where (array ('mc_code' => $request['mc_code']))->field ('*')->find ();
        $request['mc_id'] = $is['mc_id'];
        if($request['mc_id']){
            $car_washer_info = M ('CarWasher')->where (array ('mc_id' => $request['mc_id']))->find ();
            if ( !$request['mc_id'] = $car_washer_info['mc_id'] ) {
                $this->apiResponse ('0' , '找不到该机器');
            }
        }
        $member_info = M ('Member')->where (array ('id' => $m_id))->find ();
        $where['contact'] = $member_info ['account'];
        $where['m_id'] = $m_id;
        $where['content'] = $request['content'];
        $where['create_time'] = time ();
        $where['mc_id'] = $request['mc_id'];
        $add = D ('Fault')->add ($where);
        if ( $add ) {
            $this->apiResponse ('1' , '提交成功');
        } else {
            $this->apiResponse ('0' , '提交失败');
        }
    }
    /**
     *机器故障
     **/
    public function faultPicture ()
    {
        $request = $_REQUEST;
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        if(!empty($_FILES['pic_id']['name'])){
            $res = api('UploadPic/upload', array(array('save_path' => 'Fault')));
            foreach ($res as $key=>$value) {
                $pic[$key] = $value['id'];
            }
            $where['pic_id'] = implode(',',$pic);
        }
        $add = D ('Fault')->add ($where);
        if ( $add ) {
            $this->apiResponse ('1' , '提交成功');
        } else {
            $this->apiResponse ('0' , '提交失败');
        }

        if (!empty($_FILES['head_pic']['name'])) { //头像
            $res = uploadimg($_FILES, CONTROLLER_NAME);
            $data['head_pic'] = $res['save_id'];
        }
        if ($request['head_pic_id']) {
            $data['head_pic'] = $request['head_pic_id'];
        }
        $where['id'] = $m_id;
        $res = D('Member')->querySave($where, $data);
        if ($res) {
            $this->apiResponse('1', '上传头像成功');
        } else {
            $this->apiResponse('0', '上传头像失败');
        }




    }

}
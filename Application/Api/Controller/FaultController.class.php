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
            array('mc_id','string','请输入机器编码'),
            array ('pro_id' , 'string' , '请选择反馈原因') ,
            array ('content' , 'string' , '请输入反馈内容') ,
        );
        $this->checkParam ($rule);
        if($request['mc_id']){
            $car_washer_info = M ('CarWasher')->where (array ('mc_id' => $request['mc_id']))->find ();
            if ( !$request['mc_id'] = $car_washer_info['mc_id'] ) {
                $this->apiResponse ('0' , '找不到该机器' , $php_errormsg);
            }
        }
        if(!empty($_FILES['pic_id']['name'])){
            $res = api('UploadPic/upload', array(array('save_path' => 'Fault')));
            foreach ($res as $key=>$value) {
                $pic[$key] = $value['id'];
            }
            $request['pic_id'] = implode(',',$pic);
        }
        $member_info = M ('Member')->where (array ('id' => $m_id))->find ();
        $request['contact'] = $member_info ['account'];
        $request['m_id'] = $m_id;
        $request['content'] = $request['content'];
        $request['create_time'] = time ();
        $add = D ('Fault')->add ($request);
        if ( empty($add) ) {
            $this->apiResponse ('0' , '提交失败');
        } else {
            $this->apiResponse ('1' , '提交成功');
        }
    }
}
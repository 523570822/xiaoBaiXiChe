<?php
namespace Api\Controller;
/**
 * Created by PhpStorm.
 * User: Txunda
 * Date: 2018/7/6
 * Time: 13:06
 */
class FeedbackController extends BaseController {

    public function _initialize()
    {
        parent::_initialize();

    }
    public function submit() {
        $request = $_REQUEST;
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $data['m_id']=$m_id;
        $data['content']=$request['content'];
        $data['create_time']=time();
        $rule =   array('content','string','请输入反馈内容');
        $this->checkParam($rule);
        $add = D('Feedback')->add($data);
        if(empty($add)){
            $this->apiResponse('0','提交失败');
        }else{
            $this->apiResponse('1','提交成功');
        }
    }
}
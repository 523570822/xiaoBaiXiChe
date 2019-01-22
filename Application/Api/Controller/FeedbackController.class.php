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

    /**
     *软件意见反馈选择列表
     * type//1软件意见反馈 2洗车问题反馈
    **/
    public function problem(){
        $problem_info = M ('Problem')
            ->where (array ('status' => 1,'type'=>1))
            ->field ('id,content')
            ->order('sort asc')
            ->select();
        $this->apiResponse('1','查询成功',$problem_info);
    }

    /**
     *意见反馈
     **/
    public function feedback() {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = I('post.');
        $rule =array (
            array('pro_id','string','请选择反馈原因'),
            array('content','string','请输入反馈内容'),
        );
        $this->checkParam($rule);
        $member_info = M ('Member')->where (array ('id'=>$m_id))->find ();
        $request['contact']=$member_info ['account'];
        $request['m_id']=$m_id;
        $request['content']=$request['content'];
        $request['create_time']=time();
        $add = D('Feedback')->add($request);
        if(empty($add)){
            $this->apiResponse('0','提交失败');
        }else{
            $this->apiResponse('1','提交成功');
        }
    }

    /**
     *关于我们
     **/
    public function aboutUs(){
        $aboutus_info = C('APP');
        $picture['app_logo'] = C ('API_URL') . $this->getOnePath ($aboutus_info['app_logo'] , C ('API_URL') . '/Uploads/Member/default.png');
        $this->apiResponse('1','查询成功',array ('app_logo'=>$picture['app_logo'],'app_name'=>$aboutus_info['app_name'],'app_version'=>$aboutus_info['app_version'],'app_intro'=>$aboutus_info['app_intro']));
    }

    /**
     *客服中心列表
     **/
    public function customerCenter(){
        $request = I('post.');
        $data = D ("Article")->where (array ('type' => 3 , 'status' => 1 ))->order('sort asc')->field ('id,title')->page($request['page'], '10')->select ();
        if (!$data) {
            $message = $request['page'] == 1 ? '暂无消息' : '无更多消息';
            $this->apiResponse('1', $message);
        }
        $this->apiResponse (1 , '请求成功' , $data);
    }

    /**
     *用户指南内容
     **/
    public function centerDetail(){
        $request = I('post.');
        $rule =array('id','int','请选择反馈原因');
        $this->checkParam($rule);
        $data = D ("Article")->where (array ('id'=>$request['id'],'type' => 3 , 'status' => 1 ))->field ('id,title,content')->find();
        $data['content'] = $this->setAbsoluteUrl($data['content']);
        $data['content'] =htmlspecialchars_decode($data['content']);
        $data['content'] = str_replace('img src="', 'img src = "' . C('API_URL'), $data['content']);
        $this->apiResponse (1,'查询成功',$data);
    }
}
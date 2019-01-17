<?php
namespace Api\Controller;

/**
 * 消息模块
 * Class MsgController
 * @package Api\Controller
 */
class MsgController extends BaseController{
    /**
     * 初始化方法
     */
    public $msg_obj = '';
    public $member_obj ='';
    public $msg_read_log ='';
    public function _initialize()
    {
        parent::_initialize();
        $this->msg_obj = D('Msg');
        $this->member_obj = D('Member');
        $this->msg_read_log = D('MsgReadLog');
    }

    /**
     * 消息详情
     * 消息id msg_id
     */
    public function actMsgInfo()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $info = M('Msg')->where(array('id' => $request['msg_id']))
            ->field('*')
            ->find();
        if (empty($info)) {
            $this->apiResponse('0', '查询失败');
        }
        $info['msg_content'] = $this->setAbsoluteUrl($info['msg_content']);
        $info['msg_content'] =htmlspecialchars_decode($info['msg_content']);
        $info['msg_content'] = str_replace('img src="', 'img src = "' . C('API_URL'), $info['msg_content']);
        $res = M('msg_read_log')->where(array('m_id' => $m_id, 'msg_id' => $info['id']))->find();
        if (empty($res)) {
            M('MsgReadLog')->data(array('m_id' => $m_id, 'msg_id' => $info['id'], 'create_time' => time()))->add();
        }
        $this->apiResponse('1', '请求成功', $info);
    }

    /**
     * 消息列表
     * */
    public function myMsgList()
    {
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $request = $_REQUEST;
        $list = M('Msg')//查询系统消息列表
        ->where(array('m_id'=>['in',['0,',$m_id]],'status' => array('neq', 9),))
            ->field ('id,type,create_time')
            ->page($request['page'], '15')
            ->order('create_time desc')
            ->select();

        if (empty($list)) {
            $message = $request['page'] == 1 ? '暂无消息' : '无更多消息';
            $this->apiResponse('1', $message);
        }
        foreach ($list as $k => $v) {
            $list[$k]['mgs_type'] =$list[$k]['type']==1 ? '系统消息': '订单消息';
            $list[$k]['mgs_tip'] =$list[$k]['type']==1 ? '您收到一条系统消息': '您收到一条订单消息';
            $list[$k]['create_time'] = date('Y/m/d', $v['create_time']);
            unset($list[$k]['pic']);
            $res = M('MsgReadLog')->where(array('m_id' => $m_id, 'msg_id' => $v['id']))->find();
            $list[$k]['is_read'] = $res ? 0 : 1;//1未读0已读
        }
        $this->apiResponse('1', '请求成功', $list);
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2018-08-18
 * Time: 11:00:39
 */

namespace Manager\Controller;


class MemberController extends BaseController
{

    /**
     * 用户列表
     * User: admin
     * Date: 2018-08-18 10:59:38
     */
    public function index() {
        $where = array();
        //账号查找
        if(!empty($_REQUEST['account'])){
            $where['account'] = array('LIKE',"%".I('request.account')."%");
        }
        //昵称查找
        if(!empty($_REQUEST['nickname'])){
            $where['nickname'] = array('LIKE',"%".I('request.nickname')."%");
        }
        //性别查找
        if(!empty($_REQUEST['sex'])){
            $where['sex'] = I('request.sex');
        }

        //注册时间查找
        if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
            $where['create_time'] =array('between',array(strtotime($_REQUEST['start_time']),strtotime($_REQUEST['end_time'])+86400));
        }elseif(!empty($_REQUEST['start_time'])){
            $where['create_time'] = array('egt',strtotime($_REQUEST['start_time']));
        }elseif(!empty($_REQUEST['end_time'])){
            $where['create_time'] = array('elt',strtotime($_REQUEST['end_time'])+86399);
        }

        //排序
        $param['order'] = 'sort desc , create_time desc';
        if(!empty($_REQUEST['sort_order'])){
            $sort = explode('-',$_REQUEST['sort_order']);
            $param['order'] = $sort[0].' '.$sort[1];
//            $parameter['sort_order'] = I('request.sort_order');
        }
        $where['status'] = array('lt',9);
        $param['page_size'] = 15;
        $data = D('Member')->queryList($where,'*',$param);
        $this->assign($data);
        //页数跳转
        $this->assign('url',$this->curPageURL());
        $this->display();
    }

    

    


}
<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-02-12
 * Time: 02:02:43
 */

namespace Manager\Controller;


class PartnerController extends BaseController {


    /**
     * 合作方列表
     * User: admin
     * Date: 2019-01-25 14:52:52
     */
    public function index () {
        $where = array ();
        //账号查找
        if ( !empty($_REQUEST['account']) ) {
            $where['account'] = array ('LIKE' , "%" . I ('request.account') . "%");
        }
        //        //昵称查找
        if ( !empty($_REQUEST['nickname']) ) {
            $where['nickname'] = array ('LIKE' , "%" . I ('request.nickname') . "%");
        }
        //        //使用状态查找
        //        if(!empty($_REQUEST['type'])){
        //            $where['type'] = I('request.type');
        //        }
        //等級查找
        if ( !empty($_REQUEST['grade']) ) {
            $where['grade'] = I ('request.grade');
        }
        //注册时间查找
        if ( !empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time']) ) {
            $where['create_time'] = array ('between' , array (strtotime ($_REQUEST['start_time']) , strtotime ($_REQUEST['end_time']) + 86400));
        } elseif ( !empty($_REQUEST['start_time']) ) {
            $where['create_time'] = array ('egt' , strtotime ($_REQUEST['start_time']));
        } elseif ( !empty($_REQUEST['end_time']) ) {
            $where['create_time'] = array ('elt' , strtotime ($_REQUEST['end_time']) + 86399);
        }
        //排序
        //        $param['order'] = 'sort desc , create_time desc';
        //        if(!empty($_REQUEST['sort_order'])){
        //            $sort = explode('-',$_REQUEST['sort_order']);
        //            $param['order'] = $sort[0].' '.$sort[1];
        //        }
        if ( !$_REQUEST['status'] ) {
            $where['status'] = array ('lt' , 9);
        }
        $param['page_size'] = 15;
        $data = D ('Partner')->queryList ($where , '*' , $param);
        //        foreach ($data['list'] as $k=>$v){
        //            $data['list'][$k]['agent_id']=$v['agent_id'];
        //            $data['list'][$k]['p_id']=$v['p_id'];
        //            $date = D('Agent')->where (array ('id'=>$data['list'][$k]['agent_id']))->field ('nickname')->find();
        //            $data['list'][$k]['nickname']=$date['nickname'];
        //            $date = D('Washshop')->where (array ('id'=>$data['list'][$k]['p_id']))->field ('shop_name')->find();
        //            $data['list'][$k]['shop_name']=$date['shop_name'];
        //        }
        $this->assign ($data);
        //页数跳转
        $this->assign ('url' , $this->curPageURL ());
        $this->display ();
    }

    /**
     * 添加合作方
     * User: admin
     * Date: 2019-02-12 02:27:54
     */
    public function addPartner () {
        if ( IS_POST ) {
            $rule = array (
                array ('h_account' , 'phone' , '用户名必须为手机号格式') ,
                array ('h_password' , 'string' , '请输入密码') ,
                array ('h_nickname' , 'string' , '请输入昵称') ,
                array ('balance' , 'int' , '请输入余额') ,
            );
            $data = $this->checkParam ($rule);
            $data['token'] = $this->createToken ();
            $data['salt'] = NoticeStr (6);
            $data['create_time'] = time ();
            $data['password'] = CreatePassword ($rule['passwords'] , $data['salt']);
            //            $data['update_time'] = time();
            $res = D ('Partner')->addRow ($data);
            $res ? $this->apiResponse (1 , '提交成功') : $this->apiResponse (0 , $data);
        } else {
            $this->display ('addPartner');
        }
    }

    /**
     * 编辑合作方
     * User: admin
     * Date: 2019-02-12 03:09:27
     */
    public function editPartner () {
        if ( IS_POST ) {
            $request = I ('post.');
            $rule = array (
                array ('acch_accountount' , 'phone' , '用户名必须为手机号格式') ,
                array ('h_nickname' , 'string' , '请输入昵称') ,
            );
            $data = $this->checkParam ($rule);
            $where['id'] = $request['id'];
            $data = $this->checkParam ($rule);
            $data['token'] = $this->createToken ();
            $data['salt'] = NoticeStr (6);
            $data['update_time'] = time ();
            $res = D ('Partner')->querySave ($where , $data);
            $res ? $this->apiResponse (1 , '提交成功') : $this->apiResponse (0 , $data);
        } else {
            $id = $_GET['id'];
            $row = D ('Partner')->queryRow ($id);
            $this->assign ('row' , $row);
            $this->display ();
        }
    }

    /**
     *导出搜索
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/04/30 15:01
     */
    public function derivePartner() {
        $where = array ();
        //账号查找
        if ( !empty($_REQUEST['account']) ) {
            $where['account'] = array ('LIKE' , "%" . I ('request.account') . "%");
        }
        //        //昵称查找
        if ( !empty($_REQUEST['nickname']) ) {
            $where['nickname'] = array ('LIKE' , "%" . I ('request.nickname') . "%");
        }
        //        //使用状态查找
        //        if(!empty($_REQUEST['type'])){
        //            $where['type'] = I('request.type');
        //        }
        //等級查找
        if ( !empty($_REQUEST['grade']) ) {
            $where['grade'] = I ('request.grade');
        }
        //注册时间查找
        if ( !empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time']) ) {
            $where['create_time'] = array ('between' , array (strtotime ($_REQUEST['start_time']) , strtotime ($_REQUEST['end_time']) + 86400));
        } elseif ( !empty($_REQUEST['start_time']) ) {
            $where['create_time'] = array ('egt' , strtotime ($_REQUEST['start_time']));
        } elseif ( !empty($_REQUEST['end_time']) ) {
            $where['create_time'] = array ('elt' , strtotime ($_REQUEST['end_time']) + 86399);
        }
        //排序
        $param['order'] = 'sort desc , create_time desc';
        if ( !empty($_REQUEST['sort_order']) ) {
            $sort = explode ('-' , $_REQUEST['sort_order']);
            $param['order'] = $sort[0] . ' ' . $sort[1];
        }
        $field = 'account,nickname,grade,create_time';
        $data = D ('Partner')->queryList ($where , $field , $param);
        if ( empty($data) ) {
            $this->display ('index');
        }
        //把对应的数据放到数组中
        foreach ( $data as $key => $val ) {
            $data[$key]['create_time'] = date ('Y-m-d H:i:s' , $data[$key]['create_time']);
            if ( $data[$key]['grade'] == '1' ) {
                $data[$key]['grade'] = '区域合伙人';
            } elseif ( $data[$key]['grade'] == '2' ) {
                $data[$key]['grade'] = '一级合作商';
            } elseif ( $data[$key]['grade'] == '3' ) {
                $data[$key]['grade'] = '二级合作商';
            }
        }
        //下面方法第一个数组，是需要的数据数组
        //第二个数组是excel的第一行标题,如果为空则没有标题
        //第三个是下载的文件名，现在用的是当前导出日期
        $header = array ('账号' , '昵称' , '等级' , '创建时间');
        $indexKey = array ('account' , 'nickname' , 'grade' , 'create_time' );
        exportExcels ($data , $indexKey , $header , date ('合作商表' . 'Y-m-d' , NOW_TIME));
    }

}
<?php

namespace Manager\Controller;
class BankTypeController extends BaseController {
    /**
     * 银行卡列表
     * User: admin
     * Date: 2019-03-05 16:44:38
     */
    public function index () {
        $where = array ();
        //账号查找
        if ( !empty($_REQUEST['account']) ) {
            $where['account'] = array ('LIKE' , "%" . I ('request.account') . "%");
        }
        //昵称查找
        if ( !empty($_REQUEST['nickname']) ) {
            $where['nickname'] = array ('LIKE' , "%" . I ('request.nickname') . "%");
        }
        //使用状态查找
        if ( !empty($_REQUEST['status']) ) {
            if ( $_REQUEST['status'] == 1 ) {
                $where['status'] = 0;
            } elseif ( $_REQUEST['status'] == 2 ) {
                $where['status'] = 1;
            }
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
        if ( !$_REQUEST['status'] ) {
            $where['status'] = array ('lt' , 9);
        }
        $param['page_size'] = 15;
        $data = D ('BankType')->queryList ($where , '*' , $param);
        foreach ( $data['list'] as $k => $v ) {
            $data['list'][$k]['bank_pic'] = C ('API_URL') . $this->getOnePath ($data['list'][$k]['bank_pic']);
        }
        $this->assign ($data);
        //页数跳转
        $this->assign ('url' , $this->curPageURL ());
        $this->display ();
    }

    public function addBankType () {
        if(IS_POST) {
            $rule = array(
                array ('bank_name' , 'string' , '请输入银行名称') ,
                array ('bank_pic' , 'int' , '请上传银行图标') ,
                array ('status' , 'int' , '请选择状态') ,
            );
            $data = $this->checkParam($rule);
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['sort'] = '9999';
            $res = D('Member')->addRow($data);
            $res ?  $this->apiResponse(1, '添加成功') : $this->apiResponse(0,'添加失败');
        }else {
            $this->display ('infoBankType');
        }
    }

    public function editBankType () {
        if ( IS_POST ) {
            $request = I ('post.');
            $rule = array (
                array ('bank_name' , 'string' , '请输入银行名称') ,
                array ('bank_pic' , 'int' , '请上传银行图标') ,
                array ('status' , 'int' , '请选择状态') ,
            );
            $data = $this->checkParam ($rule);
            $where['id'] = $request['id'];
            $data['update_time'] = time ();
            $res = D ('BankType')->querySave ($where , $data);
            $res ? $this->apiResponse (1 , '修改成功') : $this->apiResponse (0 , '修改失败');
        } else {
            $id = $_GET['id'];
            $row = D ('BankType')->queryRow ($id);
            $row['covers'] = C('API_URL').$this->getOnePath ($row['head_pic'] , 0);
            $this->assign ('row' , $row);
            $this->display ('infoBankType');
        }
    }
}
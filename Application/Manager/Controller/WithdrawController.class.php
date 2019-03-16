<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-02-13
 * Time: 14:29:51
 */

namespace Manager\Controller;


class WithdrawController extends BaseController {


    /**
     *提现列表
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/02/13 14:37
     */
    public function index () {
        $where = array ();
        //昵称查找
        if ( !empty($_REQUEST['card_name']) ) {
            $card_name_where['card_name'] = array ('LIKE' , I ('request.card_name') . "%");
            $data = D ('BankCard')->where ($card_name_where)->getField ("id" , true);
            $where["card_id"] = ["in" , implode ($data , ',')];
            if ( empty($data) ) {
                $this->display ();
            }
        }
        //使用状态查找
        if ( !empty($_REQUEST['status']) ) {
            $where['status'] = I ('request.status');
        } else {
            $where['status'] = array ('lt' , 9);
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
            $parameter['sort_order'] = I ('request.sort_order');
        }
        $param['page_size'] = 15;
        $data = D ('withdraw')->queryList ($where , '*' , $param);
        foreach ( $data['list'] as $k => $v ) {
            $data['list'][$k]['agent_id'] = $v['agent_id'];
            $data['list'][$k]['card_id'] = $v['card_id'];
            $date = D ('Agent')->where (array ('id' => $data['list'][$k]['agent_id']))->field ('nickname')->find ();
            $card = D ('BankCard')->where (array ('id' => $data['list'][$k]['card_id']))->field ('card_code ,card_name,card_id')->find ();
            $cards = D ('BankType')->where (array ('id' => $card['card_id']))->field ('bank_name,bank_pic')->find ();
            $data['list'][$k]['card_type'] = $cards['bank_name'];
            $data['list'][$k]['card_name'] = $card['card_name'];
            $data['list'][$k]['bank_pic'] = $cards['bank_pic'];
            $data['list'][$k]['nickname'] = $date['nickname'];
            $data['list'][$k]['card_code'] = $card['card_code'];
        }
        $this->assign ($data);
        //页数跳转
        $this->assign ('url' , $this->curPageURL ());
        $this->display ();
    }

    /**
     * 改变状态
     * User: admin
     * Date: 2019-02-13 16:36:55
     */
    public function yesWithdraw () {
        $id = $_POST['id'];
        $data['status'] = 2;
        $Res = D ('Withdraw')->querySave ($id , $data);
        if ( $Res ) {
            $this->apiResponse ('1' , '同意提现');
        }
    }

    /**
     * 拒绝提现
     * User: admin
     * Date: 2019-02-14 14:54:19
     */
    public function noWithdraw () {
        $id = $_POST['id'];
        $data['status'] = 3;
        $Res = D ('Withdraw')->querySave ($id , $data);
        if ( $Res ) {
            $this->apiResponse ('1' , '拒绝提现');
        }
    }

    /**
     * 导出提现
     * User: admin
     * Date: 2019-03-16 16:28:50
     */
    public function exportWithdraw () {
        $where = array ();
        if ( !empty($_REQUEST['card_name']) ) {
            $card_name_where['card_name'] = array ('LIKE' , I ('request.card_name') . "%");
            $data = D ('BankCard')->where ($card_name_where)->getField ("id" , true);
            $where["card_id"] = ["in" , implode ($data , ',')];
            if ( empty($data) ) {
                $this->display ();
            }
        }
        //使用状态查找
        if ( !empty($_REQUEST['status']) ) {
            $where['status'] = I ('request.status');
        } else {
            $where['status'] = array ('lt' , 9);
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
        $field = 'agent_id,money,card_id,create_time,status';
        $data = D ('Withdraw')->queryList ($where , $field , $param);
        if ( empty($data) ) {
            $this->display ('index');
        }
        //把对应的数据放到数组中
        foreach ( $data as $key => $val ) {
            if ( $data[$key]['status'] == '1' ) {
                $data[$key]['status'] = '审核中';
            } elseif ( $data[$key]['status'] == '2' ) {
                $data[$key]['status'] = '提现成功';
            } elseif ( $data[$key]['status'] == '3' ) {
                $data[$key]['status'] = '提现失败';
            }
            $data[$key]['create_time'] = date ('Y-m-d H:i:s' , $data[$key]['create_time']);
            $data[$key]['agent_id'] = $val['agent_id'];
            $data[$key]['card_id'] = $val['card_id'];
            $date = D ('Agent')->where (array ('id' => $data[$key]['agent_id']))->field ('nickname')->find ();
            $card = D ('BankCard')->where (array ('id' => $data[$key]['card_id']))->field ('card_code,card_name,phone,card_id')->find ();
            $cards = D ('BankType')->where (array ('id' => $card['card_id']))->field ('bank_name')->find ();
            $data[$key]['card_type'] = $cards['bank_name'];
            $data[$key]['card_name'] = $card['card_name'];
            $data[$key]['phone'] = $card['phone'];
            $data[$key]['nickname'] = $date['nickname'];
            $data[$key]['card_code'] = $card['card_code'] . " ";
            //            foreach ( $val as $key_1 => $val_1 ) {
            //                $data[$key][$key_1] = $data[$key][$key_1] . " ";
            //            }
        }
        //下面方法第一个数组，是需要的数据数组
        //第二个数组是excel的第一行标题,如果为空则没有标题
        //第三个是下载的文件名，现在用的是当前导出日期
        $header = array ('持卡人姓名' , '持卡人联系方式' , '提现金额（元）' , '提现银行' , '提现卡号' , '代理商昵称' , '审核状态' , '创建时间');
        $indexKey = array ('card_name' , 'phone' , 'money' , 'card_type' , 'card_code' , 'nickname' , 'status' , 'create_time');
        exportExcels ($data , $indexKey , $header , date ('用户提现表' . 'Y-m-d' , NOW_TIME));
    }


}
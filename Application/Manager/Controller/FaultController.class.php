<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-06-29
 * Time: 01:59:12
 */

namespace Manager\Controller;


class FaultController extends BaseController
{


    /**
     * 故障表
     * User: admin
     * Date: 2019-06-29 01:59:12
     */
    public function index() {
        $where = array ();
        //按用户账号查找
        if(!empty($_REQUEST['nickname'])){
            $nickname_where['nickname'] = array('LIKE',"%".I('request.nickname')."%");
            $data = D('Member')->where ($nickname_where)->getField("id", true);
            $where["m_id"] = ["in", implode ($data, ',')];
            if (empty($data))
            {
                $this->display();
            }
        }
        //运行状态查找
        if ( !empty($_REQUEST['status']) ) {
            if ( $_REQUEST['status'] == 1 ) {
                $where['status'] = 0;
            } elseif ( $_REQUEST['status'] == 2 ) {
                $where['status'] = 1;
            }
        }
        if ( !$_REQUEST['status'] ) {
            $where['status'] = array ('lt' , 9);
        }
        $param['page_size'] = 15;
        $data = D ('Fault')->queryList ($where , '*' , $param);
        foreach ($data['list'] as $k=>$v){
            $data['list'][$k]['contents']=$v['content'];
            $data['list'][$k]['m_id']=$v['m_id'];
            $data['list'][$k]['pro_id']=$v['pro_id'];
            $dates = D('Member')->where (array ('id'=>$data['list'][$k]['m_id']))->field ('nickname')->find();
            $date = D('Problem')->where (array ('id'=>$data['list'][$k]['pro_id']))->field ('content')->find();
            $data['list'][$k]['nickname']=$dates['nickname'];
            $data['list'][$k]['content']=$date['content'];
        }
        $this->assign ($data);
        //页数跳转
        $this->assign ('url' , $this->curPageURL ());
        $this->display ();
    }

    /**
     * 编辑故障表
     * User: admin
     * Date: 2019-06-29 01:59:39
     */
    public function editFault() {
        if(IS_POST) {
            $request = I('post.');
            $where['id'] = $request['id'];
            $requests['update_time'] = time();
            $requests['reply'] = $request['reply'];
            $res = D('Fault')->querySave($where,$requests);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $requests);
        }else {
            $id = $_GET['id'];
            $row = D('Fault')->queryRow($id);
            $row['nickname'] = D('Member')->queryField(array('id'=>$row['m_id']),'nickname');
            $pro = array(
                'id'=>$row['pro_id'],
                'type'=> 2,
            );

            if(!empty($row['pic_id'])){
                $row['pic_id'] = explode(',',$row['pic_id']);
                for($i = 0;$i < count($row['pic_id']); $i++){
                    $row['pic_id'][$i] = $this->getOnePath($row['pic_id'][$i], 0);
                }
            }
            dump($row);exit;
            $row['pro'] = D('Problem')->queryField($pro,'content');
            $this->assign('row',$row);
            $this->display();
        }
    }



    public function saveFault()
    {
        $id = $this->checkParam(array('id', 'int'));
        $status = D('Fault')->where(array('id'=>$id))->getField('status');
        $data = $status == 1 ? array('status'=>0) : array('status'=>1);
        D('Fault')->where(array('id'=>$id))->save($data);
        $this->apiResponse(1, $status ==1 ? '已处理' : "" ) ;
    }
}
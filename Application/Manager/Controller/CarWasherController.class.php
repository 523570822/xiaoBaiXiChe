<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-01-25
 * Time: 14:51:30
 */

namespace Manager\Controller;


class CarWasherController extends BaseController
{


    /**
     * 洗车机列表
     * User: admin
     * Date: 2019-01-25 14:52:52
     */
    public function index() {
        $where = array();
        $parameter = array();
        //洗车机编号查找
        if(!empty($_REQUEST['mc_id'])){
            $where['mc_id'] = array('LIKE',"%".I('request.mc_id')."%");
            $parameter['mc_id'] = I('request.mc_id');
        }
//        //昵称查找
//        if(!empty($_REQUEST['nickname'])){
//            $where['nickname'] = array('LIKE',"%".I('request.nickname')."%");
//            $parameter['nickname'] = I('request.nickname');
//        }
        //使用状态查找
        if(!empty($_REQUEST['type'])){
            $where['type'] = I('request.type');
            $parameter['type'] = I('request.type');
        }
        //运行状态查找
        if(!empty($_REQUEST['status'])){
            $where['status'] = I('request.status');
            $parameter['status'] = I('request.status');
        }
//        //注册时间查找
//        if(!empty($_REQUEST['start_time']) && !empty($_REQUEST['end_time'])){
//            $where['create_time'] =array('between',array(strtotime($_REQUEST['start_time']),strtotime($_REQUEST['end_time'])+86400));
//        }elseif(!empty($_REQUEST['start_time'])){
//            $where['create_time'] = array('egt',strtotime($_REQUEST['start_time']));
//        }elseif(!empty($_REQUEST['end_time'])){
//            $where['create_time'] = array('elt',strtotime($_REQUEST['end_time'])+86399);
//        }
        //排序
//        $param['order'] = 'sort desc , create_time desc';
//        if(!empty($_REQUEST['sort_order'])){
//            $sort = explode('-',$_REQUEST['sort_order']);
//            $param['order'] = $sort[0].' '.$sort[1];
//            $parameter['sort_order'] = I('request.sort_order');
//        }
        if (!$_REQUEST['status']){
        $where['status'] = array('lt',9);
        }
        $param['page_size'] = 15;
        $data = D('CarWasher')->queryList($where, '*',$param);
        foreach ($data['list'] as $k=>$v){
            $data['list'][$k]['agent_id']=$v['agent_id'];
            $data['list'][$k]['p_id']=$v['p_id'];
            $date = D('Agent')->where (array ('id'=>$data['list'][$k]['agent_id']))->field ('nickname')->find();
            $data['list'][$k]['nickname']=$date['nickname'];
            $date = D('Washshop')->where (array ('id'=>$data['list'][$k]['p_id']))->field ('shop_name')->find();
            $data['list'][$k]['shop_name']=$date['shop_name'];
        }
        $this->assign($data);
        //页数跳转
        $this->assign('url',$this->curPageURL());
        $this->display();
    }

    /**
     * 编辑洗车机信息
     * User: admin
     * Date: 2019-01-25 17:30:49
     */
    public function editCarWasher() {
        if(IS_POST) {
            $request = I('post.');
            $rule = array(
                array('mc_id','string','洗车机编号'),
                array('p_id','string','请选择店铺'),
                array('agent_id','string','请选择加盟商'),
                array('lon','string','经度'),
                array('lat','string','纬度'),
//                array('sort','int','排序'),
                array('province','string','省份'),
                array('city','string','城市'),
                array('address','string','具体地址'),
                array('area','string','区、县'),
//                array('washcar_pic','string','机器照片'),
            );
            $data = $this->checkParam ($rule);
            $data['create_time']=time ();
            $data['washcar_pic']=$request['washcar_pic'];
            $data['area']=$request['area'];
            $data['old_address']=$request['address'];
            $data['sort']=$request['sort'];
            $res = D('CarWasher')->querySave(["id"=>I('post.id')], $data);
            $res ?  $this->apiResponse(1, '修改成功') : $this->apiResponse(0,"44444" ,$data);
        }else {
            $id = $_GET['id'];
            $row = D('CarWasher')->queryRow($id);
            $province = D('Region')->select(array('parent_id'=>1,'region_type'=>'1'),'region_name,id');
            $where = array('status'=>array('neq', 9));
            $field = 'id, shop_name, status';
            $shop_list = D('Washshop')->queryList($where, $field);
            $this->assign('shop_list', $shop_list);
            $PAP = array('status'=>array('neq', 9));
            $ARA = 'id, p_id , nickname, status';
            $list = D('Agent')->queryList($PAP, $ARA);
            $this->assign('list', $list);
            $this->assign('province',$province);
            $this->assign('row',$row);
            //下拉框选择
            $id = $_GET['id'];//用户id
            $row = D('CarWasher')->queryRow($id);
            $province = D('Region')->queryList(array('region_type'=>'1'),'region_name,id');
            $this->assign('province',$province);
            $this->assign('row',$row);
            //显示
            $row['province'];                                                     //取字段
            $data = D('Region')->queryRow($row['province']);    /*地区表id，单条查询*/
            $this->assign('pp',$data);                                      //传字段
            $row['city'];
            $fall = D('Region')->queryRow($row['city']);        /*地区表id，单条查询*/
            $this->assign('city',$fall);                                    //传字段
            $row['area'];
            $all = D('Region')->queryRow($row['area']);         /*地区表id，单条查询*/
            $this->assign('area',$all);                                     //传字段
            $this->display();
        }
    }

    /**
     * 添加洗车机
     * User: admin
     * Date: 2019-01-25 17:32:02
     */
    public function addCarWasher() {
        if(IS_POST) {
            $request = I('post.');
            $rule = array(
                array('mc_id','string','洗车机编号'),
                array('p_id','string','请选择店铺'),
                array('agent_id','string','请选择加盟商'),
                array('lon','string','经度'),
                array('lat','string','纬度'),
                array('sort','int','排序'),
                array('province','int','省份'),
                array('city','int','城市'),
                array('address','string','具体地址'),
//                array('area','int','区、县'),
//                array('washcar_pic','string','机器照片'),
            );
            $data = $this->checkParam ($rule);
            $data['create_time']=time ();
            $data['washcar_pic']=$request['washcar_pic'];
            $data['area']=$request['area'];
            $data['old_address']=$request['address'];
            $res = D('CarWasher')->add ($data);
            $res ?  $this->apiResponse(1, '提交成功') : $this->apiResponse(0, $data);
        }else {
            $province = D('Region')->select(array('parent_id'=>1,'region_type'=>'1'),'region_name,id');
            $where = array('status'=>array('neq', 9));
            $field = 'id, shop_name, status';
            $shop_list = D('Washshop')->queryList($where, $field);
            $this->assign('shop_list', $shop_list);
            $PAP = array('status'=>array('neq', 9));
            $ARA = 'id, p_id , nickname, status';
            $list = D('Agent')->queryList($PAP, $ARA);
            $this->assign('list', $list);
            $this->assign('province',$province);
            $this->display();
        }
    }

    /**
     * 禁启洗车机
     * User: admin
     * Date: 2019-02-13 11:37:25
     */
    public function lockCarWasher() {
        $id = $this->checkParam(array('id', 'int'));
        $status = D('CarWasher')->queryField($id, 'status');
        $data = $status == 1 ? array('status'=>4) : array('status'=>1);
        $Res = D('CarWasher')->querySave($id, $data);
        $Res ? $this->apiResponse(1, $status == 1 ? '关闭成功' : '处理成功') : $this->apiResponse(0, $status == 1 ? '关闭失败' : '处理成功');

    }

    /**
     * 三级联动
     * User: admin
     * Date: 2019-02-13 15:22:46
     */
    public function ajaxGetRegion()
    {
        $request = I('POST.');
        $region = D('Region')->queryList(array('parent_id'=>$request['id']),'region_name,id');
        $this->ajaxReturn($region , 'JSON');
    }
}
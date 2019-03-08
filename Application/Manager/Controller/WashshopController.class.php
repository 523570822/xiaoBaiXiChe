<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-01-25
 * Time: 14:51:30
 */

namespace Manager\Controller;

use Vendor\phpqrcode\QRcode;

class WashshopController extends BaseController {
    /**
     * 洗车店列表
     * User: admin
     * Date: 2019-03-01 10:53:17
     */
    public function index () {
        $where = array ();
        //店铺名称查找
        if ( !empty($_REQUEST['shop_name']) ) {
            $where['shop_name'] = array ('LIKE' , "%" . I ('request.shop_name') . "%");
        }
        //运行状态查找
        if ( !empty($_REQUEST['status']) ) {
            $where['status'] = I ('request.status');
            $parameter['status'] = I ('request.status');
        }
        if ( !$_REQUEST['status'] ) {
            $where['status'] = array ('lt' , 9);
        }
        $param['page_size'] = 15;
        $data = D ('Washshop')->queryList ($where , '*' , $param);
        $this->assign ($data);
        //页数跳转
        $this->assign ('url' , $this->curPageURL ());
        $this->display ();
    }

    /**
     * 编辑店铺
     * User: admin
     * Date: 2019-03-01 11:58:12
     */
    public function editWashshop () {
        if ( IS_POST ) {
            $request = I ('post.');
            $rule = array (
                array ('shop_name' , 'string' , '店铺名称') ,
                array ('shop_phone' , 'string' , '联系电话') ,
                array ('status' , 'string' , '请选择运行状态') ,
                array ('lon' , 'string' , '经度') ,
                array ('lat' , 'string' , '纬度') ,
                array ('province' , 'string' , '省份') ,
                array ('city' , 'string' , '城市') ,
                array ('area' , 'string' , '区、县') ,
                array ('address' , 'string' , '具体地址') ,
                array ('env_pic' , 'string' , '机器照片') ,
                array ('startime' , 'string' , '店铺开始营业时间') ,
                array ('endtime' , 'string' , '店铺结束营业时间') ,
                array ('mtime' , 'string' , '早晨营业时间点') ,
                array ('etime' , 'string' , '晚上营业时间点') ,
            );
            $data = $this->checkParam ($rule);
            $data['env_pic'] = $request['env_pic'];
            $data['update_time'] = time ();
            $data['area'] = $request['area'];
            $data['old_address'] = $request['address'];
            $data['sort'] = $request['sort'];
            $res = D ('Washshop')->querySave (["id" => I ('post.id')] , $data);
            $res ? $this->apiResponse (1 , '修改成功') : $this->apiResponse (0 , "修改失败" , $data);
        } else {
            $id = $_GET['id'];
            $row = D ('Washshop')->queryRow ($id);
            $row['covers'] =$this->getOnePath($row['env_pic']);
            $province = D ('Region')->select (array ('parent_id' => 1 , 'region_type' => '1') , 'region_name,id');
            $this->assign ('province' , $province);
            $this->assign ('row' , $row);
            //下拉框选择
            $id = $_GET['id'];//用户id
            $row = D ('Washshop')->queryRow ($id);
            $row['covers'] =$this->getOnePath($row['env_pic']);
            $province = D ('Region')->queryList (array ('region_type' => '1') , 'region_name,id');
            $this->assign ('province' , $province);
            $this->assign ('row' , $row);
            //显示
            $fall = [];
            $all = [];
            if ( $row['province'] != 0 ) $fall = D ('Region')->queryList (array ('parent_id' => $row['province']) , 'region_name,id');
            if ( $row['city'] != 0 ) $all = D ('Region')->queryList (array ('parent_id' => $row['city']) , 'region_name,id');
            $this->assign ('city' , $fall);
            $this->assign ('area' , $all);
            $this->display ('Washshop');
        }
    }
    //    public function map ($address) {
    //        url = "http://api.map.baidu.com/geocoder?address=.$address.&output=json&key=NcMnc56RX48MjpsOfP4ZEW5GVHmCCmeg";
    //    }


    /**
     * 添加店铺
     * User: admin
     * Date: 2019-03-01 11:57:16
     */
    public function addWashshop () {
        if ( IS_POST ) {
            $request = I ('post.');
            $rule = array (
                array ('shop_name' , 'string' , '店铺名称') ,
                array ('shop_phone' , 'string' , '联系电话') ,
                array ('status' , 'string' , '请选择运行状态') ,
                array ('lon' , 'string' , '经度') ,
                array ('lat' , 'string' , '纬度') ,
                array ('province' , 'string' , '省份') ,
                array ('city' , 'string' , '城市') ,
                array ('area' , 'string' , '区、县') ,
                array ('address' , 'string' , '具体地址') ,
                array ('env_pic' , 'string' , '机器照片') ,
                array ('startime' , 'string' , '店铺开始营业时间') ,
                array ('endtime' , 'string' , '店铺结束营业时间') ,
                array ('mtime' , 'string' , '早晨营业时间点') ,
                array ('etime' , 'string' , '晚上营业时间点') ,
            );
            $data = $this->checkParam ($rule);
            $data['create_time'] = time ();
            $data['env_pic'] = $request['env_pic'];
            $data['area'] = $request['area'];
            $data['old_address'] = $request['address'];
            $res = D ('Washshop')->add ($data);
            $res ? $this->apiResponse (1 , '添加成功') : $this->apiResponse (0 , '添加失败' , $data);
        } else {
            $province = D ('Region')->select (array ('parent_id' => 1 , 'region_type' => '1') , 'region_name,id');
            $this->assign ('province' , $province);
            $this->display ('Washshop');
        }
    }

    /**
     * 三级联动
     * User: admin
     * Date: 2019-03-02 15:02:49
     */
    public function ajaxGetRegion () {
        $request = I ('POST.');
        $region = D ('Region')->queryList (array ('parent_id' => $request['id']) , 'region_name,id');
        $this->ajaxReturn ($region , 'JSON');
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2019-01-25
 * Time: 14:51:30
 */

namespace Manager\Controller;

use Vendor\phpqrcode\QRcode;

class CarWasherController extends BaseController {
    /**
     * 洗车机列表
     * User: admin
     * Date: 2019-01-25 14:52:52
     */
    public function index () {
        $where = array ();
        //二维码编号查找
        if ( !empty($_REQUEST['mc_code']) ) {
            $where['mc_code'] = array ('LIKE' , "%" . I ('request.mc_code') . "%");
        }
        //洗车机编号查找
        if ( !empty($_REQUEST['mc_id']) ) {
            $where['mc_id'] = array ('LIKE' , "%" . I ('request.mc_id') . "%");
        }
        //        //昵称查找
        //        if(!empty($_REQUEST['nickname'])){
        //            $where['nickname'] = array('LIKE',"%".I('request.nickname')."%");
        //            $parameter['nickname'] = I('request.nickname');
        //        }
        //使用状态查找
        if ( !empty($_REQUEST['type']) ) {
            $where['type'] = I ('request.type');
        }
        //运行状态查找
        if ( !empty($_REQUEST['status']) ) {
            $where['status'] = I ('request.status');
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
        if ( !$_REQUEST['status'] ) {
            $where['status'] = array ('lt' , 9);
        }
        $param['page_size'] = 15;
        $data = D ('CarWasher')->queryList ($where , '*' , $param);
        foreach ( $data['list'] as $k => $v ) {
            $data['list'][$k]['agent_id'] = $v['agent_id'];
            $data['list'][$k]['p_id'] = $v['p_id'];
            $date = D ('Agent')->where (array ('id' => $data['list'][$k]['agent_id']))->field ('nickname')->find ();
            $data['list'][$k]['nickname'] = $date['nickname'];
            $date = D ('Washshop')->where (array ('id' => $data['list'][$k]['p_id']))->field ('shop_name')->find ();
            $data['list'][$k]['shop_name'] = $date['shop_name'];
            $data['list'][$k]['washing_money'] = round($v['washing_money']*60,2);
            $data['list'][$k]['foam_money'] = round($v['foam_money']*60,2);
            $data['list'][$k]['cleaner_money'] = round($v['cleaner_money']*60,2);
        }
        $this->assign ($data);
        //页数跳转
        $this->assign ('url' , $this->curPageURL ());
        $this->display ();
    }

    /**
     * 编辑洗车机信息
     * User: admin
     * Date: 2019-01-25 17:30:49
     */
    public function editCarWasher () {
        if ( IS_POST ) {
            $request = I ('post.');
            $rule = array (
                array ('mc_code' , 'string' , '二维码编号') ,
                array ('mc_id' , 'string' , '洗车机编号') ,
                array ('washing_money' , 'int' , '水枪价格￥/min') ,
                array ('foam_money' , 'int' , '泡沫价格￥/min') ,
                array ('cleaner_money' , 'int' , '吸尘器价格￥/min') ,
                array ('service_money' , 'int' , '平添运营服务费') ,
                array ('p_rate' , 'int' , '上级代理商分润') ,
                array ('h_rate' , 'int' , '合作方分润') ,
                array ('pt_rate' , 'int' , '平台分润') ,
                array ('p_id' , 'string' , '请选择店铺') ,
                array ('agent_id' , 'string' , '请选择加盟商') ,
                array ('lon' , 'string' , '经度') ,
                array ('lat' , 'string' , '纬度') ,
                array ('province' , 'string' , '省份') ,
                array ('city' , 'string' , '城市') ,
                array ('area' , 'string' , '区、县') ,
                array ('address' , 'string' , '具体地址') ,
                array ('status' , 'string' , '请选择运行状态') ,
                array ('type' , 'string' , '请选择使用状态') ,
                //                array('sort','int','排序'),
                //                array('washcar_pic','string','机器照片'),
            );
            $data = $this->checkParam ($rule);
            $data['washing_money'] = round($data['washing_money']/60,5);
            $data['foam_money'] = round($data['foam_money']/60,5);
            $data['cleaner_money'] = round($data['cleaner_money']/60,5);
            $data['p_rate'] = round($data['p_rate']/100,2);
            $data['h_rate'] = round($data['h_rate']/100,2);
            $data['pt_rate'] = round($data['pt_rate']/100,2);
            $data['update_time'] = time ();
            $data['washcar_pic'] = $request['washcar_pic'];
            $data['area'] = $request['area'];
            $data['old_address'] = $request['address'];
            $data['sort'] = $request['sort'];
            $data['mc_id'] = strtolower($request['mc_id']);
            $data['partner_id'] = $request['partner_id'];
            $find_agent = M('Agent')->where(array('id'=>$data['agent_id']))->find();
            //增加管理
            if($find_agent['grade'] == 2){
                M('Management')->where(array('agent_id'=>$find_agent['p_id']))->setInc('car_num',1);
                $find_car = M('CarWasher')->where(array('id'=>$request['id']))->find();
                $find_ag = M('Agent')->where(array('id'=>$find_car['agent_id']))->find();
                if($find_ag['grade'] == 2){
                    M('Management')->where(array('agent_id'=>$find_ag['p_id']))->setDec('car_num',1);
                }elseif ($find_ag['grade'] == 3){
                    $find_two = M('Agent')->where(array('id'=>$find_ag['p_id']))->find();
                    M('Management')->where(array('agent_id'=>$find_two['p_id']))->setDec('car_num',1);
                }
            }elseif($find_agent['grade'] == 3){
                $find_one = M('Agent')->where(array('id'=>$find_agent['p_id']))->find();
                M('Management')->where(array('agent_id'=>$find_one['p_id']))->setInc('car_num',1);
                $find_cars = M('CarWasher')->where(array('id'=>$request['id']))->find();
                $find_age = M('Agent')->where(array('id'=>$find_cars['agent_id']))->find();
                if($find_age['grade'] == 2){
                    M('Management')->where(array('agent_id'=>$find_age['p_id']))->setDec('car_num',1);
                }elseif ($find_age['grade'] == 3){
                    $find_twos = M('Agent')->where(array('id'=>$find_age['p_id']))->find();
                    M('Management')->where(array('agent_id'=>$find_twos['p_id']))->setDec('car_num',1);
                }
            }
            $res = D ('CarWasher')->querySave (["id" => I ('post.id')] , $data);
            $res ? $this->apiResponse (1 , '修改成功') : $this->apiResponse (0 , "修改失败" , $data);
        } else {
            $id = $_GET['id'];
            $row = D ('CarWasher')->queryRow ($id);

            $province = D ('Region')->select (array ('parent_id' => 1 , 'region_type' => '1') , 'region_name,id');
            $where = array ('status' => array ('neq' , 9));
            $field = 'id, shop_name, status';
            $shop_list = D ('Washshop')->queryList ($where , $field);
            $this->assign ('shop_list' , $shop_list);
            $PAP = array (
                'status' => array ('neq' , 9),
                'grade' => array('in','2,3'),
            );
            $ARA = 'id, p_id , nickname, status ,grade';
            $list = D ('Agent')->queryList ($PAP , $ARA);
            $pa_where = array(
                'status' => array ('neq' , 9),
                'grade' => 4,
            );
            $partner = D('Agent')->queryList($pa_where,'*');
            $this->assign ('list' , $list);
            $this->assign ('partner' , $partner);
            $this->assign ('province' , $province);
            $this->assign ('row' , $row);
            //下拉框选择
            $id = $_GET['id'];//用户id
            $row = D ('CarWasher')->queryRow ($id);
            $row['washing_money'] = rtrim(rtrim($row['washing_money'], '0'), '.');
            $row['foam_money'] = rtrim(rtrim($row['foam_money'], '0'), '.');
            $row['cleaner_money'] = rtrim(rtrim($row['cleaner_money'], '0'), '.');
            $province = D ('Region')->queryList (array ('region_type' => '1') , 'region_name,id');
            $this->assign ('province' , $province);
            $row['washing_money'] = round($row['washing_money']*60,2);
            $row['foam_money'] = round($row['foam_money']*60,2);
            $row['cleaner_money'] = round($row['cleaner_money']*60,2);
            $row['p_rate'] = round($row['p_rate']*100,2);
            $row['h_rate'] = round($row['h_rate']*100,2);
            $row['pt_rate'] = round($row['pt_rate']*100,2);
            $this->assign ('row' , $row);
            //显示
            $fall = [];
            $all = [];
            if ($row['province'] != 0) $fall = D ('Region')->queryList (array ('parent_id' => $row['province']) , 'region_name,id');
            if ($row['city'] != 0) $all = D ('Region')->queryList (array ('parent_id' => $row['city']) , 'region_name,id');
            $this->assign ('city' , $fall);
            $this->assign ('area' , $all);
            $this->display ();
        }
    }

    /**
     * 添加洗车机
     * User: admin
     * Date: 2019-01-25 17:32:02
     */
    public function addCarWasher () {
        if ( IS_POST ) {
            $request = I ('post.');
            $rule = array (
                array ('mc_code' , 'string' , '二维码编号') ,
                array ('mc_id' , 'string' , '洗车机编号') ,
                array ('washing_money' , 'int' , '水枪价格￥/min') ,
                array ('foam_money' , 'int' , '泡沫价格￥/min') ,
                array ('cleaner_money' , 'int' , '吸尘器价格￥/min') ,
                array ('service_money' , 'int' , '平添运营服务费') ,
                array ('p_rate' , 'int' , '上级代理商分润') ,
                array ('h_rate' , 'int' , '合作方分润') ,
                array ('pt_rate' , 'int' , '平台分润') ,
                array ('p_id' , 'string' , '请选择店铺') ,
//                array ('agent_id' , 'int' , '请选择加盟商') ,
                array ('partner_id' , 'string' , '请选择合作方') ,
                array ('lon' , 'string' , '经度') ,
                array ('lat' , 'string' , '纬度') ,
                array ('province' , 'int' , '省份') ,
                array ('city' , 'int' , '城市') ,
                array ('area' , 'int' , '区、县') ,
                array ('address' , 'string' , '具体地址') ,
                array ('status' , 'string' , '请选择运行状态') ,
                array ('type' , 'string' , '请选择使用状态') ,
                //                array ('sort' , 'int' , '排序') ,
                //                array('washcar_pic','string','机器照片'),
            );
            $data = $this->checkParam ($rule);
            $grade = $_REQUEST['grade'];
            if ($grade == 2) {
                if(empty($request['agent_id'])){
                    $this->apiResponse(0,'请选择一级代理商');
                }else{
                    $data['agent_id'] = $request['agent_id'];
                }
            } elseif ($grade == 3) {
                if(empty($request['agent_ids'])){
                    $this->apiResponse(0,'请选择二级代理商');
                }else{
                    $data['agent_id'] = $request['agent_ids'];
                }
            }
            $wheress['mc_code']  = $data['mc_code'];
            $wheress['mc_id']  = $data['mc_id'];
            $wheress['_logic'] = 'or';
            $map['_complex'] = $wheress;
            $map['status']  = array('neq',9);
            $car = M('CarWasher')->where($map)->find();
            if(!empty($car)){
                $this->apiResponse(0,'洗车机编号已存在');
            }
            $data['washing_money'] = round($data['washing_money']/60,5);
            $data['foam_money'] = round($data['foam_money']/60,5);
            $data['cleaner_money'] = round($data['cleaner_money']/60,5);
            $data['p_rate'] = round($data['p_rate']/100,2);
            $data['h_rate'] = round($data['h_rate']/100,2);
            $data['pt_rate'] = round($data['pt_rate']/100,2);
            $data['create_time'] = time ();
            $data['washcar_pic'] = $request['washcar_pic'];
            $data['area'] = $request['area'];
            $data['old_address'] = $request['address'];
            $data['mc_id'] = strtolower($request['mc_id']);
            //增加管理
            $find_agent = M('Agent')->where(array('id'=>$data['agent_id']))->find();
            if($find_agent['grade'] == 2){
                M('Management')->where(array('agent_id'=>$find_agent['p_id']))->setInc('car_num',1);
            }elseif($find_agent['grade'] == 3){
                $find_one = M('Agent')->where(array('id'=>$find_agent['p_id']))->find();
                M('Management')->where(array('agent_id'=>$find_one['p_id']))->setInc('car_num',1);
            }
            $res = D ('CarWasher')->add ($data);
            $res ? $this->apiResponse (1 , '添加成功') : $this->apiResponse (0 , '添加失败' , $data);
        } else {
            $province = D ('Region')->select (array ('parent_id' => 1 , 'region_type' => '1') , 'region_name,id');
            $where = array ('status' => array ('neq' , 9));
            $field = 'id, shop_name, status';
            $shop_list = D ('Washshop')->queryList ($where , $field);
            $this->assign ('shop_list' , $shop_list);
            $PAP = array (
                'status' => array ('neq' , 9),
                'grade' => 2,
            );
            $ARA = 'id, p_id , nickname, status';
            $list = D ('Agent')->queryList ($PAP , $ARA);
            $pa_where = array(
                'status' => array ('neq' , 9),
                'grade' => 4,
            );
            $region = D('Agent')->queryList(array('grade' => 2,'status'=>array('neq',9)), 'nickname,id');
            $this->assign('region', $region);
            $partner = D('Agent')->queryList($pa_where,'*');
            $this->assign ('partner' , $partner);
            $this->assign ('list' , $list);
            $this->assign ('province' , $province);
            $this->display ();
        }
    }

    /**
     * 禁启洗车机
     * User: admin
     * Date: 2019-02-13 11:37:25
     */
    public function lockCarWasher () {
        $id = $this->checkParam (array ('id' , 'int'));
        $status = D ('CarWasher')->queryField ($id , 'status');
        $data = $status == 1 ? array ('status' => 4) : array ('status' => 1);
        $Res = D ('CarWasher')->querySave ($id , $data);
        $Res ? $this->apiResponse (1 , $status == 1 ? '处理成功' : '处理成功') : $this->apiResponse (0 , $status == 1 ? '关闭成功' : '关闭失败');
    }

    /**
     * 恢复操作
     * User: admin
     * Date: 2019-02-20 12:00:40
     */
    public function recoveryCarWasher() {
        $this->checkParam(array('ids','array','请选择至少一条'));
        $request  = I('Request.');
        //判断是数组ID还是字符ID
        if(is_array($request['ids'])) {
            //数组ID
            $where['id'] = array('in',$request['ids']);
        } elseif (is_numeric($request['ids'])) {
            //数字ID
            $where['id'] = $request['ids'];
        }
        $type = D ('CarWasher')->select($where['id'] , 'type');
        $data = $type == 1 ? array ('type' => 4) : array ('type' => 1);
        $Res = D($request['model'])->where($where)->data($data)->save();
        $Res ? $this->apiResponse (1 , $type == 1 ? '恢复成功' : '恢复成功') : $this->apiResponse (0 , $type == 1 ? '恢复成功' : '该机器已为空闲状态');
    }

    /**
     * 三级联动
     * User: admin
     * Date: 2019-02-13 15:22:46
     */
    public function ajaxGetRegion () {
        $request = I ('POST.');
        $region = D ('Region')->queryList (array ('parent_id' => $request['id']) , 'region_name,id');
        $this->ajaxReturn ($region , 'JSON');
    }

    /**/
    /**
     * 三级联动
     * User: admin
     * Date: 2019-02-13 15:22:46
     */
    public function ajaxGetAgent() {
        $request = I ('POST.');
        $region = D ('Agent')->queryList (array ('p_id' => $request['id']) , 'nickname,id');
        $this->ajaxReturn ($region , 'JSON');
    }

    /**
     * 二维码生成、下载
     * User: admin
     * Date: 2019-02-14 16:52:23
     */
    public function down () {
        $url = 'https://www.xiaojingxiche.com/index.php';//路径
        $nickname = M ('CarWasher')->where (array ('id' => $_REQUEST['id']))->getField ('mc_code');
        $url = $url . "?mc_code=" . $nickname;
        Vendor ('Txunda.phpqrcode.phpqrcode');//引入集成包
        $errorCorrectionLevel = intval (3);//容错级别
        $matrixPointSize = intval (6);//生成图片大小
        $file = QRcode::png ($url , false , $errorCorrectionLevel , $matrixPointSize); //生成二维码图片
        //下载二维码图片
        header ('Content-Disposition:attachment; filename="二维码编号：' . $nickname . '.png"');
        echo $file;
    }

}
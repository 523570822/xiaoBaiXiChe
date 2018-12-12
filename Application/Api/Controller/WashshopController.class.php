<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 10:02
 */

namespace Api\Controller;

use Common\Service\ControllerService;

/**
 * 商家门店模块
 * Class SmsController
 * @package Api\Controller
 */
class WashshopController extends BaseController
{
    /**
     * 初始化方法
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 店铺列表
     * 传递参数的方式：post
     * 需要传递的参数：
     * 经度：lon
     * 纬度：lat
     */
    public function WashshopList()
    {
        $lon = empty($_REQUEST['lon']) ? 0 : $_REQUEST['lon'];  // 经度
        $lat = empty($_REQUEST['lat']) ? 0 : $_REQUEST['lat'];  // 纬度
        if (empty($lon) && empty($lat)) {
            $this->apiResponse('0', '缺少坐标参数');
        }
        $wh3 = '(2 * 6378.137* ASIN(SQRT(POW(SIN(3.1415926535898*(' . $lat . '-lat)/360),2)+COS(3.1415926535898*' . $lat . '/180)* COS(lat * 3.1415926535898/180)*POW(SIN(3.1415926535898*(' . $lon . '-lon)/360),2))))*1000';
        $shopList = M('Washshop')->where(array('status' => 1))->field('id as shop_id,shop_pic,shop_name,lon,lat,shop_phone,address,startime,create_time,endtime,mtime,etime,' . $wh3 . ' as distance')->order('distance asc')->select();
        if ($shopList) {
            foreach ($shopList as $k => $v) {
                if ($lat == "") {
                } else {
                    $shopList[$k]['distance'] = round($v['distance'] / 1000, 2) . 'Km';// '距离我' .;
                }
                $week = date('w');
                $day = date('md');
                $time = date('G');
                if ($week < $shopList[$k]['startime'] || $week > $shopList[$k]['endtime']) {
                    $shopList[$k]['workstatus'] = '0';
                } else if ($time >= $shopList[$k]['mtime'] && $time < $shopList[$k]['etime']) {
                    $shopList[$k]['workstatus'] = '1';
                } else {
                    $shopList[$k]['workstatus'] = '0';
                }
                $shopList[$k]['shop_pic'] = $this->getOnePath($shopList[$k]['shop_pic'], C('API_URL') . '/Uploads/Member/default.png');
            }
        }
        $m_id = $this->checkToken();
        if ($m_id) {
            $this->errorTokenMsg($m_id);
            $param['where']['id'] = $m_id;
            $param['where']['status'] = array('neq', 9);
            $param['field'] = 'id as m_id,degree,db_integral';
            $member_info = $this->findRow($param);
            $data['degree'] = $member_info['degree'];
            if ($m_id === 0) {
                $data['sys_msg_code'] = 0;
            } else {
                $data['sys_msg_code'] = $this->checkMsg($m_id);
            }
        }
        $this->apiResponse('1', '请求成功', $shopList);
    }

    /**
     * 商店详情
     * User: jiajia.zhao 18210213617@163.com
     * Date: 2018/7/5 10:59
     */
    public function WashDetail()
    {
        $m_id = $this->checkToken();
        $lon = empty($_REQUEST['lon']) ? 0 : $_REQUEST['lon'];  // 经度
        $lat = empty($_REQUEST['lat']) ? 0 : $_REQUEST['lat'];  // 纬度
        $shop_id = empty($_REQUEST['shop_id']) ? 0 : $_REQUEST['shop_id'];
        if (empty($shop_id)) {
            $this->apiResponse(0, '缺少shop_id参数');
        }
        $wh3 = '(2 * 6378.137* ASIN(SQRT(POW(SIN(3.1415926535898*(' . $lat . '-lat)/360),2)+COS(3.1415926535898*' . $lat . '/180)* COS(lat * 3.1415926535898/180)*POW(SIN(3.1415926535898*(' . $lon . '-lon)/360),2))))*1000';
        $shopDetail = M('Washshop')->where(array('id' => $shop_id))->field('id as shop_id,shop_pic,shop_name,env_pic,shop_phone,address,startime,lon,lat,create_time,endtime,mtime,etime,' . $wh3 . ' as distance')->find();
        $shopDetail['distance'] = round($shopDetail['distance'] / 1000, 2) . 'Km';// '距离我' .;
        $show = explode(",", $shopDetail['env_pic']);
        foreach ($show as $key => &$v) {//var_dump($key);die;
            $v = $this->getOnePath($v, C('API_URL') . '/Uploads/Member/default.png');
            $new[$key]['env'] = $v;
        }
//        $count = M('Evaluation')->where(array("parameter" => $shopDetail['shop_id'], 'type' => "2"))->count('star');
//        $star = M('Evaluation')->where(array("parameter" => $shopDetail['shop_id'], 'type' => "2"))->sum('star');
//        if ($star) {
//            $shopDetail['star'] = $star / $count . "";
//        } else {
//            $shopDetail['star'] = "0";
//        }
        $desc = 'id desc';
        $data['status'] = array('neq', 9);
        $data['ws_id'] = $shop_id;
        $list = M('Goods')->where($data)
            ->page('1', 2)
            ->order('' . $desc . '')
            ->select();
        if (empty($list)) {
            $shopDetail['is_goods'] = "1";
        } else {
            $shopDetail['is_goods'] = "0";
        }
        if ($list) {
            foreach ($list as $k => $v) {
                //$list[$k]['commodity_detail'] = $this->setAbsoluteUrl($v['commodity_detail']);
                //$list[$k]['commodity_detail'] = htmlspecialchars_decode($list[$k]['commodity_detail']);
                //$list[$k]['commodity_detail'] = str_replace('img src="','img src = "'.C('API_URL'),$list[$k]['commodity_detail']);
                $path = D('File')->where('id=' . $v['commodity_logo'])->getField('path');
                $list[$k]['commodity_logo'] = C('API_URL') . $path;
                $list[$k]['shop_name'] = M('Washshop')->where(array('id' => $v['ws_id']))->getField('shop_name');
            }
        } else {
            $list = array();
        }
        $shopDetail['goods_list'] = $list;
        $week = date('w');
        $day = date('md');
        $time = date('G');
        if ($week < $shopDetail['startime'] || $week > $shopDetail['endtime']) {
            $shopDetail['workstatus'] = '0';
        } else if ($time >= $shopDetail['mtime'] && $time < $shopDetail['etime']) {
            $shopDetail['workstatus'] = '1';
        } else {
            $shopDetail['workstatus'] = '0';
        }
        if ($shopDetail['startime'] == 1) {
            $shopDetail['startime'] = '周一';
        } else if ($shopDetail['startime'] == 2) {
            $shopDetail['startime'] = '周二';
        } else if ($shopDetail['startime'] == 3) {
            $shopDetail['startime'] = '周三';
        } else if ($shopDetail['startime'] == 4) {
            $shopDetail['startime'] = '周四';
        } else if ($shopDetail['startime'] == 5) {
            $shopDetail['startime'] = '周五';
        } else if ($shopDetail['startime'] == 6) {
            $shopDetail['startime'] = '周六';
        } else if ($shopDetail['startime'] == 7) {
            $shopDetail['startime'] = '周日';
        }
        if ($shopDetail['endtime'] == 1) {
            $shopDetail['endtime'] = '周一';
        } else if ($shopDetail['endtime'] == 2) {
            $shopDetail['endtime'] = '周二';
        } else if ($shopDetail['endtime'] == 3) {
            $shopDetail['endtime'] = '周三';
        } else if ($shopDetail['endtime'] == 4) {
            $shopDetail['endtime'] = '周四';
        } else if ($shopDetail['endtime'] == 5) {
            $shopDetail['endtime'] = '周五';
        } else if ($shopDetail['endtime'] == 6) {
            $shopDetail['endtime'] = '周六';
        } else if ($shopDetail['endtime'] == 7) {
            $shopDetail['endtime'] = '周日';
        }
        $shopDetail['mtime'] = $shopDetail['mtime'] . ":00";
        $shopDetail['etime'] = $shopDetail['etime'] . ":00";
        $shopDetail['shop_pic'] = $this->getOnePath($shopDetail['shop_pic'], C('API_URL') . '/Uploads/Member/default.png');
        $shopDetail['env_pic'] = $new;
        //zsl 增加详情
        $data['status'] = array('neq', 9);
        $data['parameter'] = $shop_id;
        $data['type'] = '2';
        $listev = D('Evaluation')->where($data)->order('create_time desc')->select();
        if (!empty($listev)) {
            foreach ($listev as $k1 => $v1) {
                $member = D('Member')->where('id=' . $v1['m_id'])->field('id,head_pic,nickname')->find();
                $head_path = D('File')->where('id=' . $member['head_pic'])->getField('path');
                $listev[$k1]['m_pic'] = C('API_URL') . $head_path;
                $listev[$k1]['m_name'] = $member['nickname'];
                $path = D('File')->where('id=' . $v1['evaluation_pic'])->getField('path');
                $listev[$k1]['evaluation_pic'] = C('API_URL') . $path;
            }
        }
        $data['evaluation_list'] = $listev;
        $data['Detail'] = $shopDetail;
        $wash = M('Appsetting')->where(array('id' => 1))
            ->find();
        $data['wash_price'] = $wash['wash_price'];//单次洗车价格
        $where1['id'] = $m_id;
        $where1['status'] = array('eq', 1);
        $memberinfo = M('Member')->where($where1)->field('card_id,card_type,startime,m_endtime')->find();
        if (time() <= $memberinfo['m_endtime']) {//1当前有会员卡且尚未过期
        } else {
            M('Member')->where(array('status' => 1, 'id' => $m_id))->save(array('card_id' => '0', "m_endtime" => '0', 'card_type' => '0'));
        }
        unset($param);
        $param['where']['id'] = $m_id;
        $param['where']['status'] = array('neq', 9);
        $param['field'] = 'id as m_id,account,nickname,head_pic,degree,balance,card_type,card_id';
        $member_info = M('Member')->find($param);
        $data['card_type'] = $member_info['card_type'];
        $this->apiResponse('1', '请求成功', $data);
    }
}
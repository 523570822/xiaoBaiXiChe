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
    public function _initialize ()
    {
        parent::_initialize ();
    }

    /**
     *未读消息
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/04/12 11:45
     */
    public function unread(){
        $m_id = $this->checkToken();
        $this->errorTokenMsg($m_id);
        $list = M('Msg')->where(array('m_id'=>['in',['0,',$m_id]],'status' => array('neq', 9),))->field ('id,type,create_time,m_id')->order('create_time desc')->select();
        foreach ($list as $k => $v) {
            $res = M('MsgReadLog')->where(array('m_id' => $m_id, 'msg_id' => $v['id']))->find();
            $list[$k]['is_read'] = $res ? 0 : 1;//1未读0已读
            if($list[$k]['is_read'] == 1){
                $count = count($list[$k]['is_read']);
            }
        }
        if(!empty($count)) {
            $this->apiResponse('1', '你有未读消息');
        }else{
            $this->apiResponse('0', '无未读消息');
        }

    }

    /**
     * 店铺列表
     * 传递参数的方式：post
     * 需要传递的参数：
     * 经度：lon
     * 纬度：lat
     */
    public function WashshopList ()
    {
        $lon = empty($_REQUEST['lon']) ? 0 : $_REQUEST['lon'];  // 经度
        $lat = empty($_REQUEST['lat']) ? 0 : $_REQUEST['lat'];  // 纬度
        if ( empty($lon) && empty($lat) ) {
            $this->apiResponse ('0' , '缺少坐标参数');
        }

        $wh3 = '(2 * 6378.137* ASIN(SQRT(POW(SIN(3.1415926535898*(' . $lat . '-lat)/360),2)+COS(3.1415926535898*' . $lat . '/180)* COS(lat * 3.1415926535898/180)*POW(SIN(3.1415926535898*(' . $lon . '-lon)/360),2))))*1000';
        $shopList = M ('Washshop')->where (array ('status' => 1))->field ('id,shop_pic,shop_name,lon,lat,shop_phone,address,startime,create_time,endtime,mtime,etime,' . $wh3 . ' as distance')->order ('distance asc')->select ();
        if ( $shopList ) {
            foreach ( $shopList as $k => $v ) {
                if($shopList[$k]['lon']>180){
                    $shopList[$k]['lon'] = bcsub (180,$shopList[$k]['lon'],12);
                }
                if($shopList[$k]['lat']>90){
                    $shopList[$k]['lat'] = bcsub (90,$shopList[$k]['lat'],12);
                }
                if ( $lat == "" ) {
                } else {
                    $shopList[$k]['distance'] = round ($v['distance'] / 1000 , 2) . 'Km';// '距离我' .;
                }
                $week = date ('w');
                $day = date ('md');
                $time = date ('G');
                if ( $week < $shopList[$k]['startime'] || $week > $shopList[$k]['endtime'] ) {
                    $shopList[$k]['workstatus'] = '0';
                } else if ( $time >= $shopList[$k]['mtime'] && $time < $shopList[$k]['etime'] ) {
                    $shopList[$k]['workstatus'] = '1';
                } else {
                    $shopList[$k]['workstatus'] = '0';
                }
                $shopList[$k]['shop_pic'] = $this->getOnePath ($shopList[$k]['shop_pic'] );
            }
        }
        $m_id = $this->checkToken ();
        if ( $m_id ) {
            $this->errorTokenMsg ($m_id);
            $param['where']['id'] = $m_id;
            $param['where']['status'] = array ('neq' , 9);
            $param['field'] = 'id as m_id,degree,db_integral';
            $member_info = $this->findRow ($param);
            $data['degree'] = $member_info['degree'];
            if ( $m_id === 0 ) {
                $data['sys_msg_code'] = 0;
            } else {
                $data['sys_msg_code'] = $this->checkMsg ($m_id);
            }
        }
        $this->apiResponse ('1' , '请求成功' , $shopList);
    }

    /**
     * 商店详情
     * User: jiajia.zhao 18210213617@163.com
     * Date: 2018/7/5 10:59
     */
    public function WashDetail ()
    {
        $m_id = $this->checkToken ();
        $lon = empty($_REQUEST['lon']) ? 0 : $_REQUEST['lon'];  // 经度
        $lat = empty($_REQUEST['lat']) ? 0 : $_REQUEST['lat'];  // 纬度
        $shop_id = empty($_REQUEST['shop_id']) ? 0 : $_REQUEST['shop_id'];
        if ( empty($shop_id) ) {
            $this->apiResponse (0 , '缺少shop_id参数');
        }
        $wh3 = '(2 * 6378.137* ASIN(SQRT(POW(SIN(3.1415926535898*(' . $lat . '-lat)/360),2)+COS(3.1415926535898*' . $lat . '/180)* COS(lat * 3.1415926535898/180)*POW(SIN(3.1415926535898*(' . $lon . '-lon)/360),2))))*1000';
        $shopDetail = M ('Washshop')->where (array ('id' => $shop_id))->field ('id as shop_id,shop_pic,shop_name,env_pic,shop_phone,address,startime,lon,lat,create_time,endtime,mtime,etime,' . $wh3 . ' as distance')->find ();
        $shopDetail['distance'] = round ($shopDetail['distance'] / 1000 , 2) . 'Km';// '距离我' .;
        $show = explode ("," , $shopDetail['env_pic']);
        foreach ( $show as $key => &$v ) {//var_dump($key);die;
            $v = $this->getOnePath ($v);
            $new[$key]['env'] = $v;
        }
        $desc = 'id desc';
        $data['status'] = array ('neq' , 9);
        $data['ws_id'] = $shop_id;
        $list = M ('Goods')->where ($data)
            ->page ('1' , 2)
            ->order ('' . $desc . '')
            ->select ();
        if ( empty($list) ) {
            $shopDetail['is_goods'] = "1";
        } else {
            $shopDetail['is_goods'] = "0";
        }
        if ( $list ) {
            foreach ( $list as $k => $v ) {
                $path = D ('File')->where ('id=' . $v['commodity_logo'])->getField ('path');
                $list[$k]['commodity_logo'] = C ('API_URL') . $path;
                $list[$k]['shop_name'] = M ('Washshop')->where (array ('id' => $v['ws_id']))->getField ('shop_name');
            }
        } else {
            $list = array ();
        }
        $shopDetail['goods_list'] = $list;
        $week = date ('w');
        $day = date ('md');
        $time = date ('G');
        if ( $week < $shopDetail['startime'] || $week > $shopDetail['endtime'] ) {
            $shopDetail['workstatus'] = '0';
        } else if ( $time >= $shopDetail['mtime'] && $time < $shopDetail['etime'] ) {
            $shopDetail['workstatus'] = '1';
        } else {
            $shopDetail['workstatus'] = '0';
        }
        if ( $shopDetail['startime'] == 1 ) {
            $shopDetail['startime'] = '周一';
        } else if ( $shopDetail['startime'] == 2 ) {
            $shopDetail['startime'] = '周二';
        } else if ( $shopDetail['startime'] == 3 ) {
            $shopDetail['startime'] = '周三';
        } else if ( $shopDetail['startime'] == 4 ) {
            $shopDetail['startime'] = '周四';
        } else if ( $shopDetail['startime'] == 5 ) {
            $shopDetail['startime'] = '周五';
        } else if ( $shopDetail['startime'] == 6 ) {
            $shopDetail['startime'] = '周六';
        } else if ( $shopDetail['startime'] == 7 ) {
            $shopDetail['startime'] = '周日';
        }
        if ( $shopDetail['endtime'] == 1 ) {
            $shopDetail['endtime'] = '周一';
        } else if ( $shopDetail['endtime'] == 2 ) {
            $shopDetail['endtime'] = '周二';
        } else if ( $shopDetail['endtime'] == 3 ) {
            $shopDetail['endtime'] = '周三';
        } else if ( $shopDetail['endtime'] == 4 ) {
            $shopDetail['endtime'] = '周四';
        } else if ( $shopDetail['endtime'] == 5 ) {
            $shopDetail['endtime'] = '周五';
        } else if ( $shopDetail['endtime'] == 6 ) {
            $shopDetail['endtime'] = '周六';
        } else if ( $shopDetail['endtime'] == 7 ) {
            $shopDetail['endtime'] = '周日';
        }
        $shopDetail['mtime'] = $shopDetail['mtime'] . ":00";
        $shopDetail['etime'] = $shopDetail['etime'] . ":00";
        $shopDetail['shop_pic'] = $this->getOnePath ($shopDetail['shop_pic'] );
        $shopDetail['env_pic'] = $new;
        //zsl 增加详情
        $data['status'] = array ('neq' , 9);
        $data['parameter'] = $shop_id;
        $data['type'] = '2';
        $listev = D ('Evaluation')->where ($data)->order ('create_time desc')->select ();
        if ( !empty($listev) ) {
            foreach ( $listev as $k1 => $v1 ) {
                $member = D ('Member')->where ('id=' . $v1['m_id'])->field ('id,head_pic,nickname')->find ();
                $head_path = D ('File')->where ('id=' . $member['head_pic'])->getField ('path');
                $listev[$k1]['m_pic'] = C ('API_URL') . $head_path;
                $listev[$k1]['m_name'] = $member['nickname'];
                $path = D ('File')->where ('id=' . $v1['evaluation_pic'])->getField ('path');
                $listev[$k1]['evaluation_pic'] = C ('API_URL') . $path;
            }
        }
        $data['evaluation_list'] = $listev;
        $data['Detail'] = $shopDetail;
        $wash = M ('Appsetting')->where (array ('id' => 1))
            ->find ();
        $data['wash_price'] = $wash['wash_price'];//单次洗车价格
        $where1['id'] = $m_id;
        $where1['status'] = array ('eq' , 1);
        $memberinfo = M ('Member')->where ($where1)->field ('card_id,card_type,startime,m_endtime')->find ();
        if ( time () <= $memberinfo['m_endtime'] ) {//1当前有会员卡且尚未过期
        } else {
            M ('Member')->where (array ('status' => 1 , 'id' => $m_id))->save (array ('card_id' => '0' , "m_endtime" => '0' , 'card_type' => '0'));
        }
        unset($param);
        $param['where']['id'] = $m_id;
        $param['where']['status'] = array ('neq' , 9);
        $param['field'] = 'id as m_id,account,nickname,head_pic,degree,balance,card_type,card_id';
        $member_info = M ('Member')->find ($param);
        $data['card_type'] = $member_info['card_type'];
        $this->apiResponse ('1' , '请求成功' , $data);
    }

    /**
     * 洗车机列表
     * 传递参数的方式：post
     * 需要传递的参数：
     * 经度：lon
     * 纬度：lat
     */
    public function washcarList ()
    {
        $m_id = $this->checkToken ();
        $lon = empty($_REQUEST['lon']) ? 0 : $_REQUEST['lon'];  // 经度
        $lat = empty($_REQUEST['lat']) ? 0 : $_REQUEST['lat'];  // 纬度
//        $lon = 116.4072154982;
//        $lat = 39.9047253699;
        if ( empty($lon) && empty($lat) ) {
            $this->apiResponse ('0' , '缺少坐标参数');
        }
        $wh3 = '(2 * 6378.137* ASIN(SQRT(POW(SIN(3.1415926535898*(' . $lat . '-lat)/360),2)+COS(3.1415926535898*' . $lat . '/180)* COS(lat * 3.1415926535898/180)*POW(SIN(3.1415926535898*(' . $lon . '-lon)/360),2))))*1000';
        $washer_where['status'] = array('neq',9);
        $washcar = M ('CarWasher')->where ($washer_where)->field ('id,p_id,lon,lat,address,mc_code as mc_id ,status,type,' . $wh3 . ' as distance')->order ('distance ASC')->select ();
        if ( $washcar ) {
//            $this->apiResponse(1,'查询',$washcar);

            foreach ( $washcar as $k => $v ) {
                if($washcar[$k]['lon']>180){
                    $washcar[$k]['lon'] = bcsub ($washcar[$k]['lon'],180,12);
                }
                if($washcar[$k]['lat']>90){
                    $washcar[$k]['lat'] = bcsub ($washcar[$k]['lat'],90,12);
                }
                if ( $lat == "" ) {
                } else {
                    $washcar[$k]['distance'] = round ($v['distance'] / 1000 , 2) . 'Km';// '距离我' .;
                }
                $name = D ('Washshop')->where (array ('id' => $washcar[$k]['p_id']))->field ('shop_name')->find ();
                $washcar[$k]['shop_name'] = $name['shop_name'];
                if ( $washcar[$k]['type'] == '1' ) {
                    $washcar[$k]['is_yy'] = '闲置中';
                } elseif ( $washcar[$k]['type'] == '2' ) {
                    $washcar[$k]['is_yy'] = '使用中';
                } elseif($washcar[$k]['type'] == '3') {
                    $washcar[$k]['is_yy'] = '预约中';
                }elseif($washcar[$k]['type'] == '4'){
                    $washcar[$k]['is_yy'] = '故障中';
                }
            }
        }
        if ( $m_id ) {
            $this->errorTokenMsg ($m_id);
            $param['where']['id'] = $m_id;
            $param['where']['status'] = array ('neq' , 9);
            $param['field'] = 'id as m_id,degree,integral';
            $member_info = M ('Member')->find ($param);
            $data['degree'] = $member_info['degree'];
            if ( $m_id === 0 ) {
                $data['sys_msg_code'] = 0;
            } else {
                $data['sys_msg_code'] = $this->checkMsg ($m_id);
            }
        }

        $this->apiResponse ('1' , '请求成功' , $washcar);
    }
}
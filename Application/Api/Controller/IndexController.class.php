<?php
/**
 * Created by PhpStorm.
 * User: ľ
 * Date: 2018/8/13
 * Time: 9:21
 */

namespace Api\Controller;
class IndexController extends BaseController
{
    public function index ()
    {
        echo 'Hello World!';
    }

    /**
     * 获取openid
     **/
    public function getOpenid ()
    {
        $appid = 'wxf348bbbcc28d7e10';
        $secret = '2501eb21dd9346f91e9b612b0097b50f';
        $js_code = $_REQUEST['js_code'];
        if ( empty($js_code) ) {
            $this->apiResponse (0 , '缺少code');
        }
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$secret&js_code=$js_code&grant_type=authorization_code";
        $openid = file_get_contents ($url);//var_dump($openid);die;
        $openid = json_decode ($openid);
        $session_key = $openid->session_key;
        $openid = $openid->openid;

        $this->apiResponse (1 , '成功' , $openid);
    }

    /**
     * 微信小程序获取手机号
     **/
    public function getPhoneNumber ()
    {
        $request = $_REQUEST;
        $rule = array ('openid' , 'string' , 'openid不能为空');
        $this->checkParam ($rule);
        $param['where']['openid'] = $request['openid'];
        $param['field'] = 'm_id';
        $param['status'] = array ('neq' , 9);
        $res = D ('MemberBind')->queryRow ($param['where'] , $param['field']);
        if ( $res ) {
            foreach ( $res as $k => $v ) {
                $resu = D ("Member")->where (array ('id' => $v))->find ();
            }
            if ( $resu ) {
                $this->apiResponse (1 , '请求成功' , array ('account' => $resu['account']));
            } else {
                $this->apiResponse (0 , '请求失败');
            }
        } else {
            $this->apiResponse (0 , '请求失败');
        }
    }

    /**
     * 首页
     **/
    public function Msg ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $param['where']['status'] = 1;
        $param['where']['o_type'] = 1;
        $param['where']['m_id'] = $m_id;
        //有无消息未读
        $list = M ('Msg')->where (array ('m_id' => ['in' , ['0,' , $m_id]] , 'status' => array ('neq' , 9)))->field ('id,o_id')->select ();
        foreach ( $list as $k => $v ) {
            $list['id'] = $list[$k]['id'];
        }
        if ( $list ) {
            $res = M ('MsgReadLog')->where (array ('m_id' => $m_id , 'msg_id' => $list['id']))->find ();
            $list1['is_read'] = $res ? 0 : 1;//0已读 1未读
        }
        //有无订单未支付
        $is_pay = D ('Order')->where ($param['where'])->find ();
        $list1['is_pay'] = $is_pay ? 1 : 0;//0订单已支付 1订单待付
        $this->apiResponse ('1' , '查询成功' , $list1);
    }


    public function push_curl ($param = "" , $header = "" , $postUrl = "")
    {
        if ( empty($param) ) {
            return false;
        }
        $curlPost = $param;
        $ch = curl_init ();                                      //初始化curl
        curl_setopt ($ch , CURLOPT_URL , $postUrl);                 //抓取指定网页
        curl_setopt ($ch , CURLOPT_HEADER , 0);                    //设置header
        curl_setopt ($ch , CURLOPT_RETURNTRANSFER , 1);            //要求结果为字符串且输出到屏幕上
        curl_setopt ($ch , CURLOPT_POST , 1);                      //post提交方式
        curl_setopt ($ch , CURLOPT_POSTFIELDS , $curlPost);
        curl_setopt ($ch , CURLOPT_HTTPHEADER , $header);           // 增加 HTTP Header（头）里的字段
        curl_setopt ($ch , CURLOPT_SSL_VERIFYPEER , FALSE);        // 终止从服务端进行验证
        curl_setopt ($ch , CURLOPT_SSL_VERIFYHOST , FALSE);
        $data = curl_exec ($ch);                                 //运行curl
        curl_close ($ch);
        return $data;
    }

    public function fg ()
    {
        $param1 = "{
        \"devices\": [{
        \"deviceid\":\"510042001451373435363337\",
        \"setitem\":{
            \"service_status\":true,
            \"valve1_status\":true,
            \"pump1_status\":true,
            \"pump2_status\":true,
            \"vacuum_status\":true,
            \"heater_status\":true,
            \"valid_voltage\":true,
            \"valid_temperature\":true,
            \"ttsplay\":true
            }
        ]}
    }";

        $param = "{
	    \"devices\": [{
		\"deviceid\": \"510042001451373435363337\",
		\"queryitem\": {
			\"service_status\": true,
			\"pressure\": true,
			\"pump1_status\": true,
			\"pump2_status\": true,
			\"valve1_status\": true,
			\"valve2_status\": true,
			\"valve3_status\": true,
			\"clean_water_usage\": true,
			\"foam_usage\": true,
			\"lastfilled_foam_uasge\": true,
			\"vacuum_info\": true,
			\"heater_status\": true,
			\"env_temperature\": true,
			\"device_volt\": true,
			\"device_current\": true,
			\"device_power\": true,
			\"device_energy\": true,
			\"location\": true
		}
	}]
}";
        //runtime_query
        $response = $this->push_curl ($param1 , "" , "http://guojiulin.gicp.net:18000/car_wash/device_manage");
        $this->ajaxReturn ($response);
    }


}
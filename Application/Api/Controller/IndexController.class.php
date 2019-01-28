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
    /**
     * 检查更新
     */
    public function checkUpdate ()
    {
        $request = I ("");
        $this->checkParam (array (
            array ('version' , 'string' , '请输入当前版本号') ,
            array ('device' , 'string' , '请输入终端系统') ,
            array ('app' , 'string' , "请输入使用的APP类型")
        ));
        $data = [];
        $version = M ("Version")->where (["app" => $request['device']])->order ("create_time desc")->find ();
        if ( $request['version'] == $version['version'] ) {
            $this->apiResponse (0 , "已是最新版本" , $data);
        } else {
            $download_link = json_decode ($version['version_url'] , true);
            $data = [
                "version_num" => $version['version'] ,
                "version_url" => $download_link[$request['app']] ,
                "version_log" => $version['update_log']
            ];
            $this->apiResponse (1 , "找到新版本" , $data);
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
        $this->apiResponse (1 , '成功' , array ('openid' => $openid , 'session_key' => $session_key));
    }

    /**
     * 获取小程序手机号
     **/
    public function getPhoneNumber ($value = '')
    {
        $encryptedData = I ('get.encryptedData');
        $iv = I ('get.iv');
        $this->sessionKey = I ('get.session_key');
        $res = $this->decryptData ($encryptedData , $iv);
//         $res = json_decode($res);
        if ( $res->phoneNumber ) {
//             $res->phoneNumbe;
        }
        $this->ajaxReturn (['msg' => $res , 'status' => '1']); //把手机号返
    }

    // 小程序解密
    public function decryptData ($encryptedData , $iv)
    {
        if ( strlen ($this->sessionKey) != 24 ) {
            return self::$IllegalAesKey;
        }
        $aesKey = base64_decode ($this->sessionKey);
        if ( strlen ($iv) != 24 ) {
            return self::$IllegalIv;
        }
        $aesIV = base64_decode ($iv);
        $aesCipher = base64_decode ($encryptedData);
        $result = openssl_decrypt ($aesCipher , "AES-128-CBC" , $aesKey , 1 , $aesIV);
        $dataObj = json_decode ($result);
        if ( $dataObj == NULL ) {
            return self::$IllegalBuffer;
        }
        if ( $dataObj->watermark->appid != self::$appid ) {
            return self::$IllegalBuffer;
        }
        return $dataObj;
        // return self::$OK;
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

    public function createJSON ($deviceid, $param_key, $param_array)
    {
        $array['devices'] = [];
        $array['devices'][] = [
            "deviceid" => $deviceid,
            $param_key => $param_array
        ];

        return $array;
    }

    public function send_post ()
    {
        $request = $_REQUEST;//device_manage  runtime_query
        if ( $request['type'] ) {
            if ( $request['type'] == 'runtime_query' ) {
                //json格式数据
                $arr_param = [
                    "service_status" => true ,//设备在线状态 service_status≥ 8 在线 service_status<8 设备离线
                    "pressure" => true ,//请求查询进水压力值
                    "pump1_status" => true ,//清水泵状态 设备故障≥ 4
                    "pump2_status" => true ,//泡沫泵状态 设备故障≥ 4
                    "valve1_status" => true ,//进水阀状态 设备故障≥ 4
                    "valve2_status" => true ,//清水阀状态 有流量时阀状态为开，无流量时阀状态为关
                    "valve3_status" => true ,//泡沫液位状态 true 正常 false 液位不足
                    "level1_status" => true ,//清水液位状态
                    "level3_status" => true ,//泡沫液位状态
                    "heater_status" => true ,//加热器状态
                    "env_temperature" => true ,//当前环境温度
                    "device_volt" => true ,//当前供电电压
                    "device_current" => true ,//当前整机电流
                    "device_power" => true ,//当前整机功耗
                    "device_energy" => true ,//当前电表读数 设备耗电量 单位 kWh
                    "clean_water_usage" => true ,//清水累计用量（L）
                    "clean_water_duration" =>true,//清水累计用时（秒）
                    "foam_usage" => true ,//泡沫累计用量（L）
                    "foam_duration"=>true,//泡沫累计用时（秒）
                    "vacuum_info" => true ,//吸尘器用 设备故障status ≥ 4
                    // current吸尘器设备的电流值，单位 A lastmaint_uasge 上次维护后的使用时间，单位秒 accumulated_usage 累计使用时间，单位秒
                    "location" => true//机器坐标 longitud经度 latitud纬度
                ];
                $result_array = $this->createJSON ($request['mc_id'], "queryitem", $arr_param);
            } elseif ( $request['type'] == 'device_manage' ) {
                //json格式数据  扫码 — 洗车机设置
                $arr_param = [
                    "service_status"=> 5,
                    "pump1_status"=> 3,
                    "pump2_status"=> 3,
                    "valve1_status"=> 3,
                    "vacuum_status"=> 2,
//                    "heater_status"=> 2,
//                    "valid_voltage"=> [
//                        "low"=> "190",
//                        "high"=> "240"
//                    ],
//                    "valid_temperature"=> [
//                        "low"=> "5",
//                        "high"=> "40"
//                    ]
                ];
                //预约 — 洗车机设置
                $arr_param = [
                    "service_status"=> 6,
                    "pump1_status"=> 0,
                    "pump2_status"=> 0,
                    "valve1_status"=> 0,
                    "vacuum_status"=> 0,
                    ];
                //结算 — 洗车机设置
                $arr_param = [
                    "service_status"=> 4,
                    "pump1_status"=> 0,
                    "pump2_status"=> 0,
                    "valve1_status"=> 0,
//                    "vacuum_status"=> 0,
                    ];
                $result_array = $this->createJSON ($request['mc_id'], "setitem", $arr_param);
            }
        } else {
            $php_errormsg = '查询失败，请传参数---->"type"';
            $this->ajaxReturn ($php_errormsg);
        }
        $response = $this->push_curl (json_encode ($result_array) , ["Content-Type" => "Content-Type:application/x-www-form-urlencoded"] , "http://guojiulin.gicp.net:18000/car_wash/" . $request['type']);
        $this->ajaxReturn (json_decode ($response));
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: ľ
 * Date: 2018/8/13
 * Time: 9:21
 */

namespace Api\Controller;
class IndexController extends BaseController {
    /**
     * 检查更新
     */
    public function checkUpdate () {
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
    public function Msg () {
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
    public function getOpenid () {
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

    // 小程序获取预约订单号
    public function BookingOrderNumber () {
        $request = I ("");
        $rule = array ('openid' , 'string' , '请输入openid');
        $this->checkParam ($rule);
        $open_id = M ('MemberBind')->where (array ('openid' => $request['openid']))->find ();
        $res = M ('Order')->where (array ('m_id' => $open_id['m_id'] , 'o_type' => '1' , 'w_type' => '2' , 'status' => '1' , 'is_set' => '0' , 'button' => '0'))->find ();
        $ress['id'] = $res['id'];
        $ress['orderid'] = $res['orderid'];
        $this->apiResponse (1 , '成功' , $ress);
    }

    /**
     * 获取小程序手机号
     **/
    public function getPhoneNumber ($value = '') {
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
    public function decryptData ($encryptedData , $iv) {
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

    public function testCronTab ($txt) {
        $myfile = fopen ("./Uploads/testFile.txt" , "a") or die("Unable to open file!");
        // $txt = "testFile\n";
        fwrite ($myfile , $txt);
        fclose ($myfile);
    }


    public function test () {
        $url_data = [
            "methods" => 'methods',
            "methods_id" => 'methods_id',
        ];
        $notify_url = C ('API_URL') . '/index.php/Api/Pay/AlipayNotify?' . http_build_query ($url_data);
        $this->ajaxReturn ($notify_url);
    }
}
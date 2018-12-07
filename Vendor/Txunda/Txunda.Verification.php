<?php

class Verification
{
    /**
     * 发送验证码
     * @param $mobile 发送账号
     * @param string $unique_code  发送类型（需要在信息模板表“tb_send_template”配置好）
     * @param string $sender 发信者ID  系统默认0
     * User: jinrui.wang wangjinrui2010@163.com
     * Date: 2018/8/10 11:23
     */
    public function sendVerification($mobile,$username,$passwd,$company,$unique_code = '',$sender = 0){
        $sms_info = D('Sms')->where(array('way' => $mobile, 'type' => $unique_code))->find();
        $expire_time = time() + 600;//获取过期时间
        $vc = get_vc(6, 2);//获取验证码
        if ($sms_info) {
            //有发信记录
            if ($sms_info['create_time'] > strtotime(date('Y-m-d')) && $sms_info['create_time'] < strtotime(date('Y-m-d 23:59:59')) && intval($sms_info['times']) % 3 == 0) {
                return array('error' => '今天已达最大发送次数');
            } else {
                //次数未达到上限，判断如果上一次发送验证码的时间是今天，次数+1，否则次数设置为1；
                if ($sms_info['create_time'] < strtotime(date('Y-m-d'))) {
                    $times = 1;
                } else {
                    $times = intval($sms_info['times']) + 1;
                }
                //修改记录
                $res = D('Sms')->where(array('id' => $sms_info['id']))->data(array('vc' => $vc, 'expire_time' => $expire_time, 'times' => $times, 'create_time' => time()))->save();
          }
        } else {
            //无发信记录
            $res = D('Sms')->data(array('way' => $mobile, 'vc' => $vc, 'times' => 1, 'expire_time' => $expire_time, 'type' => $unique_code, 'create_time' => time()))->add();

        }
        if ($res) {
            $send =$this->sendMsg($mobile,$unique_code,array('vc'=>$vc),$username,$passwd,$company);
            if ($send['success']) {
                return array('success' => '信息已送达，10分钟内有效');
            } else {
                return array($send['error']);
            }
        } else {
            return array('error' => '发信失败');
        }
    }

    private function sendMsg($receiver, $unique_code, $params,$username,$passwd,$company,$sender = 0) {
        //判断是否存在模板标识  存在模板标识情况 根据标识获取发信模板信息 调用模板的标题、内容 并根据$params替换  不存在情况直接发送 $subject $content
        if(!empty($unique_code)) {
            //根据标识获取发信模板信息 //TODO 附件
            $tpl = M('SendTemplate')->field('id,type,subject,template,attachment,status')->where(array('unique_code'=>$unique_code,'status'=>array('lt',9)))->find();
            if($tpl) {
                if($tpl['status'] == 0) {
                    return array('error'=>'模板错误！');
                }
                //存在模板情况调用模板的标题、内容 并根据$params替换
                $subject = $tpl['subject'];
                $type    = $tpl['type'];
                $att     = $tpl['attachment'];
                //替换赋值
                foreach ($params as $key => $param) {
                    $content = preg_replace("/{" . $key . "}/i", $param, $tpl['template']);
                }
            } else {
                return array('error'=>'模板不存在！');
            }
        } elseif(empty($unique_code) && empty($content)) {
            return array('error'=>'缺少参数！');
        }
        $data = array(
            'm_id'          => empty($m_id) ? 0 : $m_id, //会员ID
            'receiver'      => $receiver,                //接受者账号
            'sender'        => $sender,                  //发送者 0系统 其他:管理员ID
            'content'       => $content,                 //发送内容
            'type'          => $type,                    //发送类型  1.短信  2.邮件
            'template_id'   => empty($tpl['id']) ? 0 : $tpl['id'], //模板ID
            'create_time'   => time(),                    //发送时间
            'attachment'    => $att
        );
        //判断发送类型
        if($type == 1) {
            //发送短信
            $res =$this->send($receiver,$content,$username,$passwd,$company,$att);
        } elseif($type == 2) {
            //发送邮件 //TODO 发送带有附件的邮件
            $res = semdMail($receiver, $subject, $content);
        } else {
            return array('error'=>'发送类型不正确！');
        }

        //判断发信是否成功
        if($res === true) {
            //发送成功
            $data['status'] = 1;
            M('SendLog')->data($data)->add();
            return array('success'=>'发送成功');
        } else {
            //发送失败
            M('SendLog')->data($data)->add();
            return array('error'=>$res);
        }
    }
    public function send($receiver,$content,$username,$passwd,$company){

//        $config = D('Config')->parseList();
//        S('Config_Cache',$config);

        $data['username'] = $username;
        $data['tkey']     = date('YmdHis');
        $passwd = $passwd;
        $data['password'] = md5(md5($passwd).$data['tkey']);
        $data['mobile']	  = $receiver;	//号码
        $data['content']  = $content;		//内容
        $data['content']  = '【'.$company.'】'.iconv("UTF-8", "UTF-8", $data['content']);
        $data['xh']       = '';
        $url = 'http://www.api.zthysms.com/sendSms.do';
        $sms_content = $this->httpPost($url, $data);
        $sms_response   = explode(",",$sms_content);  //处理返回信息
        if($sms_response[0] != 1) {
            return array('error' => '发送失败');
        } else {
            return true;
        }
    }

    /**
     * 发送短信
     * User: jinrui.wang wangjinrui2010@163.com
     * Date: 2018/5/25 15:47
     */
//    public function send($phone, $msg){
//        $fp = "https://www.smshubs.net/api/sendsms.php";
//        $data = array(
//            'email' => 'skfoo@bigorder.online', // 账号 email || account email
//            'key' => 'f67ecba9c049bae9360091b1651826b8', // 账号API钥匙 || account api key
//            'recipient'=> $phone, // 收信人 || recipient
//            'message'=> $msg // 讯息内容 || message
//        );
//        $result = $this->httpGet($fp,$data);
//        $r = xmlToArray($result);
////        apiResponse($r['statusCode']);
//        if ($r['statusCode'] == '1606') {
//            return true;
//        } else {
//            apiResponse('0','网络错误');
//        }
//    }
    public function httpPost($url, $data){ // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS,  http_build_query($data)); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, false); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 获取的信息以文件流的形式返回
        $result = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Error POST'.curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        return $result; // 返回数据
    }
    /**
     * curl
     * @param $url 网络地址
     * User: jinrui.wang wangjinrui2010@163.com
     * Date: 2018/5/25 15:47
     */
    public function httpGet($url,$data) {
        $curl=curl_init($url);

        $url .= '?' . http_build_query($data);
//        echo "\nFull url : $url\n\n";

        /* Set URL */
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        /* Set Mathod */
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");

        /* Tell cURL to return the output */
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        /* Tell cURL NOT to return the headers */
        curl_setopt($curl, CURLOPT_HEADER, 0);

        /* for debug */
        curl_setopt($curl, CURLOPT_VERBOSE, 0);

        $response = curl_exec($curl);
        curl_close($curl);

        // 返回数据/资料 || resaponse data/infomation
        return $response;
    }
}
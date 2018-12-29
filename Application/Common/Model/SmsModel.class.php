<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 11:37
 */
namespace Common\Model;
use Common\Service\ModelService;
/**
 * Class SmsModel
 * @package Home\Model
 * 会员操作  找回密码  激活账号
 * 发送邮件 发送短信
 */
class SmsModel extends ModelService{

    protected $tableName = 'sms';


    /**
     * 获取短信验证码
     * @param $mobile
     * @param $unique_code
     * @return array
     */
    public function sendVerify($mobile,$unique_code = ''){
        $sms_info = $this->where(array('way' => $mobile, 'type' => $unique_code))->find();
        $expire_time = time() + 600;//获取过期时间
        $vc = get_vc(6, 2);//获取验证码
        $send = api('System/sendMsg',array($mobile,$unique_code,array('vc'=>$vc)));
        if ($sms_info) {
            //有发信记录
            if ($sms_info['create_time'] > strtotime(date('Y-m-d')) && $sms_info['create_time'] < strtotime(date('Y-m-d 23:59:59')) && intval($sms_info['times']) % 10 == 0) {
                return array('error' => '你今天获取验证码次数已达到上限');
            } else {
                //次数未达到上限，判断如果上一次发送验证码的时间是今天，次数+1，否则次数设置为1；
                if ($sms_info['create_time'] < strtotime(date('Y-m-d'))) {
                    $times = 1;
                } else {
                    $times = intval($sms_info['times']) + 1;
                }
                //修改记录
                $res = $this->where(array('id' => $sms_info['id']))->data(array('vc' => $vc, 'expire_time' => $expire_time, 'times' => $times, 'create_time' => time()))->save();
            }
        } else {
            //无发信记录
            $res = $this->data(array('way' => $mobile, 'vc' => $vc, 'times' => 1, 'expire_time' => $expire_time, 'type' => $unique_code, 'create_time' => time()))->add();
        }
        if ($res) {
            $send = api('System/sendMsg',array($mobile,$unique_code,array('vc'=>$vc)));
            if ($send['success']) {
                return array('success' => '信息已送达,10分钟内有效');
            } else {
                return array($send['error']);
            }
        } else {
            return array('error' => '操作失败');
        }
    }

    /**
     * 检查验证码是否正确
     * @param $account
     * @param $verify
     * @param $type
     * @return array
     */
    public function checkVerify($account,$verify,$type){
        $where['way']  = $account;
        $where['vc']   = $verify;
        $where['type'] = $type;
        //检查验证码是否错误
        $sms_info = $this->where($where)->find();
        if (empty($sms_info)) {
            return array('error' => '验证码错误');
        }
        //检查验证码是否过期
        if ($sms_info['expire_time'] < time()) {
            return array('error' => '验证码已过期');
        }

        return array('success'=>'验证通过');
    }
}
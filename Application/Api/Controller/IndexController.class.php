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
    public function getPhoneNumber()
    {
        $request = $_REQUEST;
        $rule = array('openid', 'string', 'openid不能为空');
        $this->checkParam($rule);
        $param['where']['openid'] = $request['openid'];
        $param['field'] = 'm_id';
        $param['status'] = array('neq', 9);
        $res = D('MemberBind')->queryRow($param['where'], $param['field']);
        if ($res) {
            foreach ($res as $k => $v) {
                $resu = D("Member")->where(array('id' => $v))->find();
            }
            if ($resu) {
                $this->apiResponse(1, '请求成功', array('account' => $resu['account']));
            } else {
                $this->apiResponse(0, '请求失败');
            }
        } else {
            $this->apiResponse(0, '请求失败');
        }
    }
}
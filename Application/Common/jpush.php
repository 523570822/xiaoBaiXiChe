<?php
require_once("./Vendor/Txunda/JPush/Config.php");
require_once("./Vendor/Txunda/JPush/PushPayload.php");
require_once("./Vendor/Txunda/JPush/SchedulePayload.php");
require_once("./Vendor/Txunda/JPush/Client.php");
require_once("./Vendor/Txunda/JPush/Http.php");
require_once("./Vendor/Txunda/JPush/Exceptions/JPushException.php");
require_once("./Vendor/Txunda/JPush/Exceptions/APIRequestException.php");

/**
 * @param $content
 * @param $msg_user  token_1 用户标示 token_2 农服标示
 * @param $msg_type  1.msg 系统消息 2.order 订单消息 3.极速订单消息 4.account账户明细消息 5 评价
 * @param $send_type 1用户端 2工长端
 */
function pushOneUser($content,$msg_user,$msg_type,$member_type,$jump_id = ''){
    if($member_type == 1){
        $app_key = '9715d71c70b3094286d7e701';//环信账号
        $master_secret = 'f21ec6057647aa03819e5dc6';    //环信密码
    }else{
        $app_key = 'af204a3c32a25ab866834fd7'; //环信账号
        $master_secret = '95e4bcf207b19c690b9ae413';    //环信密码
    }
    $client = new \JPush\Client($app_key,$master_secret);//初始化JPush
    $payload  = $client->push()
        ->setPlatform('all')
//        ->addTag($msg_user)
        ->addAlias($msg_user)
        ->iosNotification($content, array(
            'sound' => 'sound.caf',
            'category' => 'jiguang',
            'extras' => array(
                'msg_type'=>$msg_type,
                'jump_id' => $jump_id
            ),
        ))
        ->androidNotification($content, array(
            'title' => '田警',
            'extras' => array(
                'msg_type'=>$msg_type,
                'jump_id' => $jump_id
            ),
        ))
        ->message($content, array(
            'title' => '田警',
            'extras' => array(
                'msg_type'=>$msg_type,
                'jump_id' => $jump_id
            ),
        ))
        ->options(array(
            'apns_production' => false,
        ));
    //正式环境为true 测试环境为false
    try {
        $payload->send();
        $msg_user = substr($msg_user, 0, -2);
        if($member_type == 1){
            $m_id = D('Member')->queryField(array('token'=>$msg_user),'id');
        }else{
            $m_id = D('Expert')->queryField(array('token'=>$msg_user),'id');
        }
        $send_log = array(
            'msg'=>$content,
            'receive_id'=> $m_id,
            'receive_type' => $member_type,
            'create_time' => time(),
            'status'=>1
        );
        D('PushLog')->add($send_log);
        return '发送成功';

    } catch (\JPush\Exceptions\JPushException $e) {
        // try something else here
        $msg_user = substr($msg_user, 0, -2);
        if($member_type == 1){
            $m_id = D('Member')->queryField(array('token'=>$msg_user),'id');
        }else{
            $m_id = D('Expert')->queryField(array('token'=>$msg_user),'id');
        }
        $send_log = array(
            'msg'=>$content,
            'receive_id'=> $m_id,
            'receive_type' => $member_type,
            'create_time' => time(),
            'push_msg'=>(string)$e,
            'status'=>2
        );
        D('PushLog')->add($send_log);
       return '发送失败';
    }

}

function pushAllUser($content,$msg_type,$member_type){
    if($member_type == 1){
        $app_key = '9715d71c70b3094286d7e701';//环信账号
        $master_secret = 'f21ec6057647aa03819e5dc6';    //环信密码
    }else{
        $app_key = 'af204a3c32a25ab866834fd7'; //环信账号
        $master_secret = '95e4bcf207b19c690b9ae413';    //环信密码
    }
    $client = new \JPush\Client($app_key,$master_secret);//初始化JPush
    $payload  = $client->push()
        ->setPlatform('all')
        ->setAudience('all')
        ->iosNotification($content, array(
            'sound' => 'sound.caf',
            'category' => 'jiguang',
            'extras' => array(
                'msg_type'=>$msg_type
            ),
        ))
        ->androidNotification($content, array(
            'title' => '田警',
            'extras' => array(
                'msg_type'=>$msg_type,
            ),
        ))
        ->message($content, array(
            'title' => '田警',
            'extras' => array(
                'msg_type'=>$msg_type
            ),
        ))
        ->options(array(
            'apns_production' => false,
        ));
    //正式环境为true 测试环境为false
    try {
        $payload->send();
        $send_log = array(
            'msg'=>$content,
            'type'=>2,
            'create_time' => time(),
            'status'=>1
        );
        D('PushLog')->add($send_log);
        return '发送成功';
    } catch (\JPush\Exceptions\JPushException $e) {
        // try something else here
        $send_log = array(
            'msg'=>$content,
            'type'=>2,
            'create_time' => time(),
            'push_msg'=>(string)$e,
            'status'=>2
        );
        D('PushLog')->add($send_log);
        return '发送失败';
    }
}



<?php
vendor('Txunda.JPush.Config');
vendor('Txunda.JPush.PushPayload');
vendor('Txunda.JPush.SchedulePayload');
vendor('Txunda.JPush.Client');
vendor('Txunda.JPush.Http');
vendor('Txunda.JPush.Exceptions.JPushException');
vendor('Txunda.JPush.Exceptions.APIRequestException');


/**
 * @param $content
 * @param 推送给个人
 * @param $msg_type  1系统消息 2订单消息
 */
function pushOneUser($content,$msg_user,$msg_type){
    $app_key = "4e80dea3d357d0ee26b873dd";
    $master_secret = "290bcbb6f70aa304682261c7";
    $client = new \JPush\Client($app_key,$master_secret);//初始化JPush
    $payload  = $client->push()
        ->setPlatform('all')
        ->addAlias($msg_user)
        ->iosNotification($content, array(
            'sound' => 'sound.caf',
            'category' => 'jiguang',
            'extras' => array(
                'msg_type'=>$msg_type
            ),
        ))
        ->androidNotification($content, array(
            'title' => '小鲸洗车',
            'extras' => array(
                'msg_type'=>$msg_type,
            ),
        ))
        ->message($content, array(
            'title' => '小鲸洗车',
            'extras' => array(
                'msg_type'=>$msg_type
            ),
        ))
        ->options(array(
            'apns_production' => false,//false 开发 true 生产
        ));
    //正式环境为true 测试环境为false
    try {
        $payload->send();
        return '发送成功';
    } catch (\JPush\Exceptions\JPushException $e) {
        // try something else here
        return '发送失败';
    }

}

/**
 * 方法释义
 * @param $content
 * @param 推送给所有用户
 * User: jiajia.zhao 18210213617@163.com
 * Date: 2018/7/17 11:34
 */
function pushAllUser($content,$msg_type){
    $app_key = "4e80dea3d357d0ee26b873dd";
    $master_secret = "290bcbb6f70aa304682261c7";
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
            'title' => '小鲸洗车',
            'extras' => array(
                'msg_type'=>$msg_type,
            ),
        ))
        ->message($content, array(
            'title' => '小鲸洗车',
            'extras' => array(
                'msg_type'=>$msg_type
            ),
        ))
        ->options(array(
            'apns_production' => false,//false 开发 true 生产
        ));
    //正式环境为true 测试环境为false
    try {
        $payload->send();
        return '发送成功';
    } catch (\JPush\Exceptions\JPushException $e) {
        // try something else here
        return '发送失败';
    }
}
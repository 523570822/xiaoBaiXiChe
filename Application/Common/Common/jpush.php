<?php
 require_once("./JPush/Config.php");
require_once("./JPush/PushPayload.php");
require_once("./JPush/SchedulePayload.php");
require_once("./JPush/Client.php");
require_once("./JPush/Http.php");
require_once("./JPush/Exceptions/JPushException.php");
require_once("./JPush/Exceptions/APIRequestException.php");
/*vendor('Txunda.JPush.Config');
vendor('Txunda.JPush.PushPayload');
vendor('Txunda.JPush.SchedulePayload');
vendor('Txunda.JPush.Client');
vendor('Txunda.JPush.Http');
vendor('Txunda.JPush.Exceptions.JPushException');
vendor('Txunda.JPush.Exceptions.APIRequestException');*/
/**
 * @param $content
 * @param 推送给个人
 * @param $msg_type  1匹配成功 2邀请好友成功注册
 */
function pushOneUser($content,$msg_user,$msg_type,$nickname,$company){
    $app_key = "251fbd8cebcccf850fd8bd73";
    $master_secret = "d51d737658870d3e94708a56";
    $client = new \JPush\Client($app_key,$master_secret);//初始化JPush
    $payload  = $client->push()
        ->setPlatform('all')
        ->addAlias($msg_user)
        ->iosNotification($content, array(
            'sound' => 'sound.caf',
            'category' => 'jiguang',
            'extras' => array(
                'msg_type'=>$msg_type,
                'nickname'=>$nickname,
                'company'=>$company
            ),
        ))
        ->androidNotification($content, array(
            'title' => '电商人生',
            'extras' => array(
                'msg_type'=>$msg_type,
                'nickname'=>$nickname,
                'company'=>$company
            ),
        ))
        ->message($content, array(
            'title' => '电商人生',
            'extras' => array(
                'msg_type'=>$msg_type,
                'nickname'=>$nickname,
                'company'=>$company
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
function pushAllUser($content,$msg_type,$nickname,$company,$head_pic){
    $app_key = "251fbd8cebcccf850fd8bd73";
    $master_secret = "d51d737658870d3e94708a56";
    $client = new \JPush\Client($app_key,$master_secret);//初始化JPush
    $payload  = $client->push()
        ->setPlatform('all')
        ->setAudience('all')
        ->iosNotification($content, array(
            'sound' => 'sound.caf',
            'category' => 'jiguang',
            'extras' => array(
                'msg_type'=>$msg_type,
                'nickname'=>$nickname,
                'company'=>$company,
                'head_pic'=>$head_pic,
            ),
        ))
        ->androidNotification($content, array(
            'title' => '电商人生',
            'extras' => array(
                'msg_type'=>$msg_type,
                'nickname'=>$nickname,
                'company'=>$company,
                'head_pic'=>$head_pic,
            ),
        ))
        ->message($content, array(
            'title' => '电商人生',
            'extras' => array(
                'msg_type'=>$msg_type,
                'nickname'=>$nickname,
                'company'=>$company,
                'head_pic'=>$head_pic,
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
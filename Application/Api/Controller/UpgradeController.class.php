<?php
namespace Api\Controller;

class UpgradeController extends BaseController{
    /**
     * 版本检测
     * 传递参数的方式：post
     * 需要传递的参数：
     */
    public function upgrade(){
        $request = I('post.');
        $result_data['code'] = '1';
        $result_data['message'] = '正式版';
        if($request['member_type'] == 1){
            $result_data['uri']  = C('API_URL').'/Api/Upgrade/memberUpgrade';
            $result_data['name'] = substr(C('APP')['app_version'],1);
            $result_data['ag_code'] = substr(C('APP')['ag_code'],1);
        }
        if($request['member_type'] == 2){
            $result_data['uri']  = C('API_URL').'/Api/Upgrade/memberUpgrades';
            $result_data['name'] = substr(C('APP')['app_version_foreman'],1);
        }

        $this->apiResponse('1','请求成功',$result_data);
    }
    public function iosUpgrade(){
        $request = I('post.');
        $result_data['code'] = '1';
        $result_data['message'] = '正式版';
        if($request['member_type'] == 1){
            $result_data['uri']  = "https://www.pgyer.com/lOj6";
            $result_data['name'] = substr(C('APP')['app_version'],1);
        }
        if($request['member_type'] == 2){
            $result_data['uri']  = "https://www.pgyer.com/G6aQ";
            $result_data['name'] = substr(C('APP')['app_version_foreman'],1);
        }

        $this->apiResponse('1','请求成功',$result_data);
    }

    //安卓用户端
    public function memberUpgrade(){
        $file = "./Uploads/Version/tianjing.apk";
        header("Content-type: application/vnd.android.package-archive;");
        header('Content-Disposition: attachment; filename="' . 'tianjing.apk' . '"');
        header("Content-Length: ". filesize($file));
        readfile($file);
    }

    //安卓农服端
    public function memberUpgrades(){
        $file = "./Uploads/Version/tianjinge.apk";
        header("Content-type: application/vnd.android.package-archive;");
        header('Content-Disposition: attachment; filename="' . 'tianjinge.apk' . '"');
        header("Content-Length: ". filesize($file));
        readfile($file);
    }

    /**
     * 关于我们
     */
    public function aboutUs () {
        $request = I('post.');
        $data = D('Config')->queryList('', 'key, value');
        $config = array();
        foreach($data as $item) {
            if($item['key'] == 'app_logo' || $item['key'] == 'app_foreman_logo' || $item['key'] == 'app_name' || $item['key'] == 'app_version' || $item['key'] == 'app_intro' || $item['key'] == 'website_phone' || $item['key'] == 'app_member_info'|| $item['key'] == 'app_expert_info')
                $config[$item['key']] = $item['value'];
        }
        if($request['member_type'] == 1){
            $config['app_logos'] = D('File')->getOnePath($config['app_logo']);
            $config['app_info'] = $config['app_member_info'];
        }elseif($request['member_type'] == 2){
            $config['app_logos'] = D('File')->getOnePath($config['app_foreman_logo']);
            $config['app_info'] = $config['app_expert_info'];
        }
        unset($config['app_expert_info']);
        unset($config['app_member_info']);
        unset($config['app_logo']);
        unset($config['app_foreman_logo']);
        $this->apiResponse(1,'请求成功',$config);
    }

}
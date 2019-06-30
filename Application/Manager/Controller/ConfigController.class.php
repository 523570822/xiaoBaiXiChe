<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/04/25
 * Time: 17:42
 */

namespace Manager\Controller;
use Common\Service\ControllerService;

class ConfigController extends ControllerService
{
    /**
     * 网站配置
     * User: 木
     * Date: 2018/8/14 11:32
     */
    public function index() {
        if(IS_POST) {
            $data = $this->checkParam(array(
                'website_name',
                'website_phone',
                'app_version_foreman',
                'app_foreman_logo',
                'app_member_info',
                'app_expert_info',
                'website_url',
                'website_keyword',
                'website_description',
                'app_logo',
                'app_name',
                'app_version',
                'app_intro',
            ));
            foreach($data as $key=>$value) {
                D('Config')->querySave(array('key'=>$key), array('value'=>$value));
            }
            $data = D('Config')->queryList('', 'key, value');
            $config = array();
            foreach($data as $item) {
                list($pk, $key) = explode('_', $item['key']);
                $pk = strtoupper($pk);
                if(!isset($config[$pk])) $config[$pk] = array();
                $config[$pk][$item['key']] = $item['value'];
            }
//            APP_PATH.'Common/conf/website.php'
            $res = $this->arrayToFile($config, dirname(dirname(__DIR__)).'/Common/Conf/website.php');
            $res ? $this->apiResponse(1, '修改成功') : $this->apiResponse(0, '写入缓存失败, 没有写入权限');
        }else {
            $data = D('Config')->queryList('', 'key, value');
            $config = array();
            foreach($data as $item) {
                $config[$item['key']] = $item['value'];
            }
            $this->assign('config', $config);
            $this->display();
        }
    }
//
    /**
     * 清除缓存
     * User: 木
     * Date: 2018/8/9 13:25
     */
    public function refreshConfig() {
        if(I('success') == 1) {
            exit('<script>parent.location.reload();</script>');
        }
        if(!D('AdminGroup')->getMenu($this->userId, $this->userInfo['group_id'])) {
            $this->error('缓存权限失败, 请检查目录读写权限');
        }
        $this->success('清除缓存成功', U('Config/refreshConfig', array('success'=>1)));
    }


    /**
     * 数组转php文件
     * User: 木
     * Date: 2018/8/14 14:08
     * @param $arr
     * @param string $path
     * @return int|string
     */
    private function arrayToFile($arr, $path='', $level=1) {
        $str = " array(\r\n";
        foreach($arr as $key=>$value) {
            if(is_array($value)) {
                $str .= str_repeat(' ', $level*4)."'$key' => ". $this->arrayToFile($value, '', $level+1) . ",\r\n";
            }else {
                $str .= str_repeat(' ', $level*4)."'$key' => '$value',\r\n";
            }
        }
        $str .= str_repeat(' ', $level*4-4).")";
        return empty($path) ? $str : file_put_contents($path, "<?php\r\nreturn$str;\r\n");
    }

    /**
     * 三方配置
     * User: admin
     * Date: 2018-08-15 13:22:23
     */
    public function thirdConfig() {
        if(IS_POST) {
            $data = $this->checkParam(array(
                // 短信平台参数验证
                'sms_type',
                'sms_url',
                'sms_key',
                'sms_secret',
                'sms_sign',
                // 推送平台参数验证
                'push_type',
                'push_key',
                'push_secret',
                // 微信JSAPI支付参数验证
                'wxjsapi_appid',
                'wxjsapi_appsecret',
                'wxjsapi_mch_id',
                'wxjsapi_key',
                // 微信APP支付参数验证
                'wxapp_appid',
                'wxapp_appsecret',
                'wxapp_mch_id',
                'wxapp_key',
                // 支付宝H5支付参数验证
                'alih5_appid',
                'alih5_public_key',
                'alih5_private_key',
                // 支付宝APP支付参数验证
                'aliapp_appid',
                'aliapp_public_key',
                'aliapp_private_key'
            ));
            foreach($data as $key=>$value) {
                D('Config')->querySave(array('key'=>$key), array('value'=>$value));
            }
            $data = D('Config')->queryList('', 'key, value');
            $config = array();
            foreach($data as $item) {
                list($pk, $key) = explode('_', $item['key']);
                $pk = strtoupper($pk);
                if(!isset($config[$pk])) $config[$pk] = array();
                $config[$pk][$item['key']] = $item['value'];
            }
            $res = $this->arrayToFile($config, dirname(dirname(__DIR__)).'/Common/Conf/website.php');
            $res ? $this->apiResponse(1, '修改成功') : $this->apiResponse(0, '写入缓存失败, 没有写入权限');
        }else {
            $data = D('Config')->queryList('', 'key, value');
            $config = array();
            foreach($data as $item) {
                $config[$item['key']] = $item['value'];
            }
            $this->assign('config', $config);
            $this->display();
        }

    }
}
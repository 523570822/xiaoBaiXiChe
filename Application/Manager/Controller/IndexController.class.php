<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/7/26
 * Time: 11:59
 */
namespace Manager\Controller;
class IndexController extends BaseController
{
    /**
     * 入口方法
     * User: 木
     * Date: 2018/7/30 15:52
     */
    public function index() {
        // 未登录状态跳转到登陆页面
        if(!$this->isLogin) {
            $this->redirect('Admin/login');
        }
        $auth = json_decode(file_get_contents(FRAME_CACHE_PATH.'AdminMenu/'.$this->userId), true);
        $this->assign('menu_top', $auth['system']);
        $this->assign('menu_left', $auth['menus']);
        $this->assign('userInfo', $this->userInfo);
        $this->assign('app_logo',$this->getOnePath(C('APP')['app_logo']));
        $this->display();
    }
    /**
     * 图形验证码
     * User: 木
     * Date: time
     */
    public function verifyImage() {
        $config =    array(
            'fontSize'    =>    30,    // 验证码字体大小
            'length'      =>    4,     // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
        );
        $Verify = new \Think\Verify($config);
        $Verify->codeSet = '0123456789';
        $Verify->entry();
    }
    /**
     * 上传文件
     * User: 木
     * Date: 2018/8/16 11:47
     */
    public function uploadFile() {
        $save_info = uploadimg($_FILES,CONTROLLER_NAME);
        if(!empty($save_info['save_id'])){
            $this->apiResponse(1,'文件上传成功',$save_info['save_id']);
        }else{
            $this->apiResponse(0, $save_info);
        }
    }
    /**
     * 删除文件
     * User: 木
     * Date: 2018/8/16 11:48
     */
    public function delFile() {
        $id = $this->checkParam(array('id', 'int', 'id不能为空'));
        $model = D('Common/File');
        $res = $model->querySave($id, array('status'=>9));
        $res ? $this->apiResponse(1, '删除成功') : $this->apiResponse(0, '删除失败');
    }
}
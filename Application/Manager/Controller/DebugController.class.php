<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/7/30
 * Time: 14:46
 */

namespace Manager\Controller;


class DebugController extends BaseController
{
    public function _initialize() {
        parent::_initialize();
        if(!APP_DEBUG) die;
    }

    /**
     * 生成模板
     * User: 木
     * Date: 2018/8/6 17:09
     */
    public function index() {
        if(IS_POST) {
            $data = $this->checkParam(array(
                array('type', 'int=1'),
                array('router', 'string>0')
            ));
            $path = APP_PATH.$this->currentModule.'/View/';
            // 判断目录是否存在
            $dir = dirname($path.$data['router'].'.html');
            if(!is_dir($dir) && !mkdir($dir, 0777, true)) {
                $this->apiResponse(0, '创建目录失败');
            }
            // 判断是否存在模板
            if(file_exists($path.$data['router'].'.html')) {
                $this->apiResponse(0, '模板已存在, 不可覆盖');
            }
            $res = file_put_contents($path.$data['router'].'.html', file_get_contents($path.'Debug/'.($data['type'] == 1 ? 'list.html' : 'form.html')));
            $res ? $this->apiResponse(1, '生成模板成功') : $this->apiResponse(0, '写入模板失败');
        }else {
            $this->display();
        }
    }

}
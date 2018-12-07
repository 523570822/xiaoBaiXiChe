<?php
/**
 * Created by PhpStorm.
 * User: 木
 * Date: 2018/7/30
 * Time: 14:46
 */

namespace Manager\Controller;


class AdminBehaviorController extends BaseController
{
    public function index() {
        $data = D('AdminBehavior')->queryList('', '*', array('page_size'=>15));
        foreach($data['list'] as &$item) {
            $item['param'] = str_replace("\r\n", '<br>', $item['param']);
        }
        $this->assign($data);
        $this->display();
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 权限控制自动生成 By admin
 * Date: 2018-08-18
 * Time: 13:43:51
 */

namespace Manager\Controller;


class AdvertController extends BaseController
{



    /**
     * 广告位列表
     * User: admin
     * Date: 2018-08-18 13:50:04
     */
    public function indexsAdvert() {
        $where = array();

        $where['status'] = array('lt',9);
        $param['order'] = 'id  desc';
        $param['page_size'] =15;
        $data = D('AdvertPosition')->queryList($where, '*',$param);
        $this->assign($data);
    $this->display('indexsAdvert');
    }
    /**
     * 广告列表
     * User: admin
     * Date: 2018-08-18 13:50:04
     */
    public function index() {
        $where = array();

        $where['status'] = array('lt',9);
        $param['order'] = 'sort desc , id  desc';
        $param['page_size'] =15;
        $data = D('Advert')->queryList($where, '*',$param);
        $this->assign($data);
        $this->display('index');
    }
    /**
     * 新增广告
     * User:jiajia
     * Date: 2018/5/22 15:35
     */
    public function addAdvert(){
        if(IS_POST) {

            $rule = array(
                array('position_id', 'notnull'),
                array('name', 'notnull'),
                array('sort', 'notnull'),
                array('file_id', 'notnull'),
                array('type', 'notnull'),
                array('value', 'notnull'),

            );
            $data = $this->checkParam($rule);
            $Res = D('Advert') ->add($data);
            $Res ? $this->apiResponse(1, '新增广告成功') : $this->apiResponse(0, '新增广告失败');

        }else {
            $position = D('AdvertPosition')->where(array('status' => 1, 'p_id' => 0))->select();
            $this->assign('cate', $position);

            $this->display();
        }
    }

    /**
     * 编辑广告
     * User: admin
     * Date: 2018-08-22 08:45:02
     */

    public function editAdvert(){
        if(IS_POST) {

            $rule = array(
                array('position_id', 'notnull'),
                array('id', 'notnull'),
                array('name', 'notnull'),
                array('sort', 'notnull'),
                array('file_id', 'notnull'),
                array('type', 'notnull'),
                array('value', 'notnull'),

            );
            $data = $this->checkParam($rule);
            $id=$data['id'];
            $Res = D('Advert')->where(array('id'=>$id))->save($data);
            $Res ? $this->apiResponse(1, '编辑广告成功') : $this->apiResponse(0, '编辑广告失败');

        }else {

            $id= I('id');
            $web = D('Advert')->where(array('id'=>$id))->find();
            $web['file_ids']=$this->getOnePath($web['file_id'],0);//第二位放默认图
            $web['position_id']=   D('AdvertPosition')->where(array('position_id' => $web['position_id']))->find();
            $position = D('AdvertPosition')->where(array('status' => 1, 'p_id' => 0))->select();
            $this->assign('cate', $position);
            $this->assign('row',$web);
            $this->display('addAdvert');
        }
    }

    /**
     * 添加广告位
     * User: admin
     * Date: 2018-08-22 08:44:19
     */
    public function AddAdvertposition() {
        if(IS_POST) {

            $rule =
                array('name', 'notnull');


            $data = $this->checkParam($rule);
            $value['name']=$data;
            $Res = D('AdvertPosition') ->add($value);
            $Res ? $this->apiResponse(1, '新增广告位成功') : $this->apiResponse(0, '新增广告位失败');

        }else {
            $this->display('addAdvertPosition');
        }
    }


    /**
     * 编辑广告位
     * User: admin
     * Date: 2018-08-22 08:45:36
     */
    public function editAdvertposition() {
        if(IS_POST) {

            $rule = array(
                array('id', 'notnull'),
                array('name', 'notnull')
            );


            $data = $this->checkParam($rule);
            $id=$data['id'];
            $Res = D('AdvertPosition')->where(array('id'=>$id))->save($data);
            $Res ? $this->apiResponse(1, '编辑广告位成功') : $this->apiResponse(0, '编辑广告位失败');

        }else {
            $id= I('id');
            $web = D('AdvertPosition')->where(array('id'=>$id))->find();

            $this->assign('row',$web);
            $this->display('addAdvertPosition');
        }
    }

    /**
     * 锁定广告
     * User: admin
     * Date: 2018-08-22 08:46:24
     */
    public function lockAdvert() {
        $id = $this->checkParam(array('id', 'int'));
        $status = D('Advert')->where(array('id'=>$id))->getField('status');
        $data = $status == 1 ? array('status'=>0) : array('status'=>1);
        $Res = D('Advert')->where(array('id'=>$id))->save($data);
        $Res ? $this->apiResponse(1, $status ==1 ? '禁用成功' : '启用成功') : $this->apiResponse(0, $status == 1 ? '禁用失败' : '启用失败');
    }

    /**
     * 锁定广告位
     * User: admin
     * Date: 2018-08-22 08:46:52
     */
    public function lockAdvertposition() {
        $id = $this->checkParam(array('id', 'int'));
        $status = D('AdvertPosition')->where(array('id'=>$id))->getField('status');
        $data = $status == 1 ? array('status'=>0) : array('status'=>1);
        $Res = D('AdvertPosition')->where(array('id'=>$id))->save($data);
        $Res ? $this->apiResponse(1, $status ==1 ? '禁用成功' : '启用成功') : $this->apiResponse(0, $status == 1 ? '禁用失败' : '启用失败');
    }

    /**
     * 删除广告
     * User: admin
     * Date: 2018-08-22 08:47:10
     */
    public function delAdvert() {
        $id = $this->checkParam(array('id', 'int'));
        $Res = D('Advert')->where(array('id'=>$id))->save(array('status'=>9));
        $Res ? $this->apiResponse(1, '删除成功') : $this->apiResponse(0, '删除失败');
    }

    /**
     * 删除广告位
     * User: admin
     * Date: 2018-08-22 08:47:32
     */
    public function delAdvertposition() {
        $id = $this->checkParam(array('id', 'int'));
        $Res = D('AdvertPosition')->where(array('id'=>$id))->save(array('status'=>9));
        $Res ? $this->apiResponse(1, '删除成功') : $this->apiResponse(0, '删除失败');
    }

    /**
     * 修改排序
     * User: admin
     * Date: 2018-08-23 10:04:59
     */
    public function editAdvertSort() {
        $id = $this->checkParam(array('id', 'int'));
        $val = $this->checkParam(array('value', 'int'));
        $res = D('Advert')->querySave($id, array('sort'=>$val));
        $res ? $this->apiResponse(1, '修改成功') : $this->apiResponse(0, '修改失败');
    }

}
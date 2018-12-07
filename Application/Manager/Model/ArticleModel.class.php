<?php
namespace Manager\Model;

use Common\Service\ModelService;

/**
 * Class WebModel
 * @package Manager\Model
 * 汇总记录
 * 2018/05/13 上午10:40
 */
class ArticleModel extends ModelService {


    /**
     * @var array
     * 自动验证   使用create()方法时自动调用
     */
    protected $_validate = array();
    /**
     * @var array
     * 自动完成   新增时
     */
    protected $_auto = array ();

    /**
     * 查询多条数据
     * @param array $where
     * @param string $order
     * @param string $page_size
     * @param array $parameter
     * @return array
     * User: yongjin.xiong 98383028@qq.com
     * Date:2018/05/13 上午10:40
     */
    public function selectArt($where = array(),$order = '',$page_size = '',$parameter = array()){
        if($where['status'] == ''|| empty($where['status'])){
            $where['status'] = array('neq',9);
        }
        if ($order == '') {
            $order = 'id asc';
        }
        if($page_size == ''){
            $result = $this->where($where)->order($order)->select();
        }else{
            $count = $this->where($where)->order($order)->count();
            $page = new \Think\Page($count,$page_size);
            $page->parameter = $parameter;
            $page_info =$page->show();
            $list = $this->where($where)
                         ->order($order)
                         ->limit($page->firstRow,$page_size)
                         ->select();
            $result = array('page'=>$page_info,'list'=>$list);
        }
        return $result;
    }

    /**
     * 方法释义
     * @param array $where
     * @return array
     * User: jinrui.wang wangjinrui2010@163.com
     * Date: 2018/5/22 16:02
     */
    public function findArt($where){
        if(empty($where)){
            return false;
        }else{
            if($where['status'] == '' || empty($where['status'])){
                $where['status'] = array('neq','9');
            }
            $result = $this->where($where)->find();
            return $result;
        }
    }
    /**
     * 编辑数据
     */
    public function saveArt($where,$data){
        if(empty($where) || empty($data)){
            return false;
        }
        $result = $this->where($where)->data($data)->save();
        return $result;
    }
    /**
     * 删除数据
     */
    public function deleteArt($where, $del = 'del'){
        if(empty($where)){
            return false;
        }
        if ($del != 'remove') {
            $result = $this->where($where)->delete();
        } else {
            $result = $this->where($where)->save(array('status'=>9));
        }
        return $result;
    }


}
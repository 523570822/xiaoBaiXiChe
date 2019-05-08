<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/13
 * Time: 11:37
 */
namespace Common\Model;
use Common\Service\ModelService;
class MemberModel extends ModelService
{
    /**
     * 查询一条数据
     * @param $where
     * @return bool|mixed
     * User: yongjin.xiong 98383028@qq.com
     * Date:2018/05/13 上午10:41
     */
    public function findMember($where, $field= ''){
        if(empty($where)){
            return false;
        }else{
            if($where['status'] == '' || empty($where['status'])){
                $where['status'] = array('neq','9');
            }
            if ($field == '') {
                $result = $this->where($where)->find();
            } else {
                if (count(explode(',',$field)) == 1) {
                    $result = $this->where($where)->getField($field);
                } else {
                    $result = $this->where($where)->field($field)->find();
                }
            }
            return $result;
        }
    }

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
    public function selectMember($where = array(),$order = '',$field='',$p = '',$page_size = '',$parameter = array()){
        if($where['status'] == ''|| empty($where['status'])){
            $where['status'] = array('neq',9);
        }
        if($page_size == ''){
            $result = $this->where($where)->order($order)->select();
        }else{
            $count = $this->where($where)->order($order)->count();
            if($p){
                $list = $this->where($where)
                    ->order($order)
                    ->page($p, $page_size);
                if($field){
                    $list = $list->field($field)->select();
                }else{
                    $list = $list->select();
                }
                $result = array('list' => $list);
            }else{
                $page = new \Think\Page($count,$page_size);
                $page->parameter = $parameter;
                $page_info =$page->show();
                $list = $this->where($where)
                    ->order($order)
                    ->limit($page->firstRow,$page_size)
                    ->select();
                $result = array('page'=>$page_info,'list'=>$list);
            }
        }
        return $result;
    }

    /**
     * 新增单条数据
     */
    public function addRow($param){
        $data['account']  = $param['account'];
//        if($param['password']){
//            $data['salt'] = NoticeStr(6);
//            $data['password'] = CreatePassword($param['password'], $data['salt']);
//        }
        if($param['parent_id']){
            $data['parent_id'] = $param['parent_id'];
        }
        $data['nickname'] = get_vc(6,0);
        $data['create_time'] = time();
        $m_id = $this->data($data)->add();
        return $m_id;
    }


    /**
     * 编辑数据
     * @param $where
     * @param $data
     * @return bool
     * User: yongjin.xiong 98383028@qq.com
     * Date:2018/05/13 上午10:42
     */
    public function saveMember($where,$data){
        if(empty($where) || empty($data)){
            return false;
        }
        $data['update_time'] =time();
        $result = $this->where($where)->data($data)->save();
        return $result;
    }
    /**
     * 删除数据
     * @param $where
     * @param string $del
     * @return boolean
     * User: jinrui.wang wangjinrui2010@163.com
     * Date: 2018/6/13 8:44
     */
    public function deleteMember($where, $del='del') {
        if ($del == 'remove') {
            $result = $this->where($where)->delete();
        } else {
            $data['update_time'] =time();
            $result = $this->where($where)->save(array('status'=>9));
        }
        return $result;
    }
}
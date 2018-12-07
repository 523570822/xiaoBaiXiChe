<?php
/**
 * Created by PhpStorm.
 * User: 木公子
 * Date: 2018/7/21
 * Time: 15:03
 */

namespace Common\Service;


use Think\Model;

class ModelService extends Model
{
    private $_error; // 错误信息

    /**
     * 添加一行数据
     * User: 木
     * Date: 2018/8/6 10:37
     * @param $data
     * @return mixed|string
     */
    public function addRow($data) {
        if($this->create($data)) {
            return $this->add($data);
        }else {
            return $this->error;
        }
    }

    /**
     * 查一行数据
     * User: 木
     * Date: 2018/7/23 15:09
     * @param string $where
     * @param string $field
     * @param array $param
     * @return mixed
     */
    public function queryRow($where='', $field='', $param=array()) {
        $param['where'] = $this->_ParseWhere($where);
        $param['field'] = $field;
        return $this->_getCURD($param)->find();
    }

    /**
     * 查多行数据
     * User: 木
     * Date: 2018/7/23 15:09
     * @param string $where
     * @param string $field
     * @param array $param
     * @return array
     */
    public function queryList($where='', $field='', $param=array()) {
        $param['where'] = $this->_ParseWhere($where);
        $param['field'] = $field;
        //分页对象
        $Page = null;
        //是否分页
        if(!empty($param['page_size'])) {
            //数据总数
            $total = $this->_getCURD($param, null)->count();
            //获取分类对象
            $Page = $this->getPage($total, $param['page_size'], $param['parameter']);
            //分页信息
            $page_show = $Page->show();
        }
        //数据列表
        $list  = $this->_getCURD($param, $Page)->select();
        //返回列表和分页信息
        return empty($page_show) ? $list : array('list'=>$list,'page'=>$page_show);
    }

    /**
     * 查字段
     * User: 木
     * Date: 2018/7/23 15:09
     * @param string $where
     * @param string $field
     * @param array $param
     * @return mixed
     */
    public function queryField($where='', $field='', $param=array()) {
        $param['where'] = $this->_ParseWhere($where);
        return $this->_getCURD($param)->getField($field);
    }

    /**
     * 数据是否存在
     * User: 木
     * Date: 2018/7/23 10:49
     * @param array $where
     * @return bool
     */
    public function queryHave($where=array()) {
        $param['where'] = $where;
        $Res = $this->_getCURD($param)->count();
        return $Res ? true : false;
    }


    /**
     * 统计数目
     * User: 木
     * Date: 2018/7/23 15:08
     * @param array $where
     * @param string $order
     * @return mixed
     */
    public function queryCount($where=array(), $order='') {
        $param['where'] = $where;
        $param['field'] = 'count(*) as count';
        $Res = $this->where($where)->count();
        return $Res;
    }

    /**
     * 修改一行
     * User: 木
     * Date: 2018/7/23 15:08
     * @param string $where
     * @param array $data
     * @return bool
     */
    public function querySave($where='', $data=array()) {
        if(empty($where)) return $this->setError('where条件不能为空 Model->querySave');
        $where = $this->_ParseWhere($where);
        // 是否使用主键修改
        if(count($where) == 1 && array_keys($where)[0] == $this->getPk()) {
            $Res = $this->where($where)->save($data);
        }else {
            $_pk = $this->getPk();
            $pkValue = $this->where($where)->getField($_pk);
            if(empty($pkValue)) return $this->setError('没有找到有效的数据 Model->querySave');
            $Res = $this->where(array($_pk=>$pkValue))->save($data);
        }
        return $Res ? true : false;
    }

    /**
     * 修改多行
     * User: 木
     * Date: 2018/7/23 15:08
     * @param string $where
     * @param array $data
     * @return bool
     */
    public function querySaveAll($where='', $data=array()) {
        if(empty($where)) return $this->setError('where条件不能为空 Model->querySaveAll');
        $Res = $this->where($where)->save($data);
        return $Res ? true : false;
    }

    /**
     * 删一行
     * User: 木
     * Date: 2018/7/23 9:30
     * @param string $where
     * @return bool
     */
    final public function queryDelete($where='') {
        if(empty($where)) return $this->setError('where条件不能为空 Model->queryDelete');
        $where = $this->_ParseWhere($where);
        // 是否使用主键删除
        if(count($where) == 1 && array_keys($where)[0] == $this->getPk()) {
            $Res = $this->where($where)->delete();
        }else {
            $pk = $this->where($where)->getField($this->getPk());
            if(empty($pk)) return $this->setError('没有找到有效的数据 Model->queryDelete');
            $Res = $this->where(array($this->getPk()=>$pk))->delete();
        }
        return $Res ? true : false;
    }

    /**
     * 删多行
     * User: 木
     * Date: 2018/7/23 9:31
     * @param string $where
     * @return bool
     */
    final public function queryDeleteAll($where='') {
        if(empty($where)) return $this->setError('where条件不能为空 Model->queryDelete');
        $Res = $this->where($where)->delete();
        return $Res ? true : false;
    }

    /**
     * 解析where条件
     * User: 木
     * Date: 2018/7/23 9:13
     * @param $where
     * @return array|int|string
     */
    private function _ParseWhere($where) {
        // where传数字当做主键处理
        if(is_numeric($where)) {
            $where = array(
                $this->getPk() => $where
            );
        }
        return $where;
    }

    /**
     * 设置报错信息
     * User: 木
     * Date: 2018/7/23 9:31
     * @param string $e
     * @return bool
     */
    private function SetError($e='') {
        $this->_error = $e;
        return false;
    }

    final private function _getCURD($param = array(), $Page = null) {
        $model  = $this;
        //表别名 字符串
        !empty($param['alias'])         ? $model = $model->alias($param['alias']) : '';
        //去掉重复记录 true/false
        !empty($param['distinct'])      ? $model = $model->distinct($param['distinct']) : '';
        //指定当前数据表 字符串/数组 可用于多表操作  'think_user user,think_role role'  array('think_user'=>'user','think_role'=>'role')
        !empty($param['table'])         ? $model = $model->table($param['table']) : '';
        //查询字段  两个参数  字符串/数组 ,true/false  true代表过滤
        !empty($param['field'])         ? $model = $model->field($param['field'],$param['field_filter']) : '';
        //查询条件 数组
        !empty($param['where'])         ? $model = $model->where($param['where']) : '';
        //左、右、并 连接查询  统一使用数组 array('','')
        !empty($param['join'])          ? $model = $model->join($param['join']) : '';
        //归并 两个参数 合并两个或多个 SELECT 语句的结果集。 统一使用数组array('SELECT name FROM think_user_1','SELECT name FROM think_user_2')  array,true/false true代表unionall
        !empty($param['union'])         ? $model = $model->union($param['union'],$param['union_all']) : '';
        //排序 字符串
        !empty($param['order'])         ? $model = $model->order($param['order']) : '';
        //分页
        !empty($param['page_size']) && !($Page == null)  ? $model = $model->limit($Page->firstRow,$Page->listRows) : '';
        //分组查询 字符串 支持多字段分组  'name,score'
        !empty($param['group'])         ? $model = $model->group($param['group']) : '';
        //和group共用  查询条件  字符串
        !empty($param['having'])        ? $model = $model->having($param['having']) : '';
        //只返回SQL语句不执行 true/false  默认false,  为true时只返回sql语句
        $param['fetchSql']              ? $model = $model->fetchSql($param['fetchSql']) : '';

        return $model;
    }

    /**
     * @param int $total 总数
     * @param int $page_size 每页记录数
     * @param array $parameter 拼接参数
     * @return \Think\Page 分页对象
     */
    protected function getPage($total = 0, $page_size = 0, $parameter = array()) {
        $Page = new \Think\Page($total, $page_size, $parameter);
        //分页样式配置
        if($total>$page_size) {
            $Page->setConfig('theme','%UP_PAGE% %FIRST% %LINK_PAGE% %END% %DOWN_PAGE% %HEADER%');
        }
        return $Page;
    }

    /**
     * where分析
     * @access protected
     * @param mixed $where
     * @return string
     */
    protected function parseWhere($where) {
        $whereStr = '';
        if(is_string($where)) {
            // 直接使用字符串条件
            $whereStr = $where;
        }else{ // 使用数组表达式
            $operate  = isset($where['_logic'])?strtoupper($where['_logic']):'';
            if(in_array($operate,array('AND','OR','XOR'))){
                // 定义逻辑运算规则 例如 OR XOR AND NOT
                $operate    =   ' '.$operate.' ';
                unset($where['_logic']);
            }else{
                // 默认进行 AND 运算
                $operate    =   ' AND ';
            }
            foreach ($where as $key=>$val){
                if(is_numeric($key)){
                    $key  = '_complex';
                }
                if(0===strpos($key,'_')) {
                    // 解析特殊条件表达式
                    $whereStr   .= $this->parseThinkWhere($key,$val);
                }else{
                    // 查询字段的安全过滤
                    // if(!preg_match('/^[A-Z_\|\&\-.a-z0-9\(\)\,]+$/',trim($key))){
                    //     E(L('_EXPRESS_ERROR_').':'.$key);
                    // }
                    // 多条件支持
                    $multi  = is_array($val) &&  isset($val['_multi']);
                    $key    = trim($key);
                    if(strpos($key,'|')) { // 支持 name|title|nickname 方式定义查询字段
                        $array =  explode('|',$key);
                        $str   =  array();
                        foreach ($array as $m=>$k){
                            $v =  $multi?$val[$m]:$val;
                            $str[]   = $this->parseWhereItem($this->parseKey($k),$v);
                        }
                        $whereStr .= '( '.implode(' OR ',$str).' )';
                    }elseif(strpos($key,'&')){
                        $array =  explode('&',$key);
                        $str   =  array();
                        foreach ($array as $m=>$k){
                            $v =  $multi?$val[$m]:$val;
                            $str[]   = '('.$this->parseWhereItem($this->parseKey($k),$v).')';
                        }
                        $whereStr .= '( '.implode(' AND ',$str).' )';
                    }else{
                        $whereStr .= $this->parseWhereItem($this->parseKey($key),$val);
                    }
                }
                $whereStr .= $operate;
            }
            $whereStr = substr($whereStr,0,-strlen($operate));
        }
        return empty($whereStr)?'':' WHERE '.$whereStr;
    }


}
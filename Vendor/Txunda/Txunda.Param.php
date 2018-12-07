<?php
/**
 * Created by PhpStorm.
 * User: 木公子
 * Date: 2018/7/21
 * Time: 14:37
 */

class Param
{
    private $e; // 错误信息
    private $allowParamType = array('string', 'int', 'array', 'notnull', 'phone', 'email'); // 允许参数类型

    // 检查参数方法
    public function checkParam($fields=array()) {
        // 需要检查参数数量
        $field_list = is_array($fields) ? false : $this->ArrayLevel($fields) == 2 ? true : (isset($fields[1]) ? $this->checkParamType($fields[1]) : false);
        // 符合条件字段
        $_fields = array();
        $fields = $field_list ? $fields : array($fields);
        foreach($fields as $field) {
            $rule = $this->parseRule($field);
            $value = $this->checkRule($rule);
            if($value === false) {
                return false;
            }else {
                $_fields[$rule['key']] = $value;
            }
        }
        return $field_list ? $_fields : (isset($rule['key']) ? $_fields[$rule['key']] : '');
    }

    /**
     * 参数检查规则处理
     * User: 木
     * Date: 2018/7/25 14:33
     * @param $data
     * @return array|false
     */
    private function parseRule($data) {
        if(is_string($data)) {
            $temp = explode('|', strtolower($data));
        }else if(is_array($data)) {
            $temp = $data;
        }else {
            return $this->setError('parseRule 参数错误');
        }
        if(isset($temp[1])) {
            $temp[1] = $this->_ParseRule($temp[1]);
        }
        $rule = array(
            'key' => $temp[0],
            'rule' => isset($temp[1]) && in_array($temp[1]['type'], $this->allowParamType) ? $temp[1] : array('type'=>'notnull'),
            'info' => isset($temp[2]) ? $temp[2] : ''
        );

        return $rule;
    }

    /**
     * 参数检查规则解析
     * User: 木
     * Date: 2018/7/13 19:14
     * @param string $condition
     * @return array
     */
    private function _ParseRule($condition='') {
        $condition = strtolower($condition);
        $symbolList = array('=', '<', '>', '<=', '>=', '<>', '#');
        foreach ($symbolList as $item) {
            if(strpos($condition, $item)) {
                $rule = explode($item, $condition);
                $symbol = $item;
            }
        }
        $_rule = array(
            'type' => isset($rule[0]) ? $rule[0] : $condition,
            'condition' => isset($symbol) ? $symbol : null,
            'value' => isset($rule[1]) ? $rule[1] : null
        );
        return $_rule;
    }

    /**
     * 判断规则传参方式
     * User: 木
     * Date: 2018/7/25 15:07
     * @param $str
     * @return bool
     */
    private function checkParamType($str) {
        if(in_array($str, $this->allowParamType)) {
            return false;
        }
        $is_have = false;
        foreach($this->allowParamType as $item) {
            if(strpos($str, $item) !== false) {
                $is_have = true;
                break;
            }
        }
        return $is_have ? true : strpos($str, '|') === false ? true : false;
    }
    private function checkRule($rule) {
        $error = !empty($rule['info']) ? $rule['info'] : '';
        $data = $rule['rule']['type'] == 'file' ? $_FILES : $_REQUEST;
        $value = isset($data[$rule['key']]) ? $data[$rule['key']] : null;
        // 参数不能为null
        if($value === null) return $this->setError($error ? $error : $rule['key'].'参数不存在或不符合条件');
        if($rule['rule']['type'] == 'notnull') {
            return $value;
        }else if($rule['rule']['type'] == 'string') {
            if(!is_string($value)) return $this->setError($error ? $error : $rule['key'].'参数不是一个字符串');
            $res = $this->checkValue($rule['key'], $value, $rule['rule']);
            return $res === true ? $value : $this->setError($error ? $error : $res[1]);
        }else if($rule['rule']['type'] == 'int') {
            if(!is_numeric($value)) return $this->setError($error ? $error : $rule['key'].'参数不是一个数字');
            $res = $this->checkValue($rule['key'], $value, $rule['rule']);
            return $res === true ? $value : $this->setError($error ? $error : $res[1]);
        }else if($rule['rule']['type'] == 'array') {
            if(!is_array($value)) return $this->setError($error ? $error : $rule['key'].'参数不是一个数组');
            $res = $this->checkValue($rule['key'], $value, $rule['rule']);
            return $res === true ? $value : $this->setError($error ? $error : $res[1]);
        }else if($rule['rule']['type'] == 'phone') {
            if(!is_numeric($value) || !preg_match('#^1[0-9]{10}$#', $value)) return $this->setError($error ? $error : $rule['key'].'参数不是正确的手机号');
            $res = $this->checkValue($rule['key'], $value, $rule['rule']);
            return $res === true ? $value : $this->setError($error ? $error : $res[1]);
        }else if($rule['rule']['type'] == 'email') {
            if(!preg_match('/^\\w+([-_.]?\\w+)*@\\w+([\\.-]?\\w+)*(\\.\\w{2,6})+$/', $value)) return $this->setError($error ? $error : $rule['key'].'参数不是正确的邮箱');
            $res = $this->checkValue($rule['key'], $value, $rule['rule']);
            return $res === true ? $value : $this->setError($error ? $error : $res[1]);
        }
        return $value;
    }

    private function checkValue($key='', $value='', $rule=array()) {
        $condition = isset($rule['condition']) ? $rule['condition'] : null;
        $match = isset($rule['value']) ? $rule['value'] : null;
        // 判断长度
        if($condition != null && $match != null) {
            if($condition == '=' && strlen($value) != $match) return array(false, $key.'长度为'.$match);
            if($condition == '<' && strlen($value) >= $match) return array(false, $key.'长度小于'.$match);
            if($condition == '<=' && strlen($value) > $match) return array(false, $key.'长度不超过'.$match);
            if($condition == '>' && strlen($value) <= $match) return array(false, $key.'长度大于'.$match);
            if($condition == '>=' && strlen($value) < $match) return array(false, $key.'长度至少为'.$match);
            if($condition == '<>' && strlen($value) == $match) return array(false, $key.'长度不等于'.$match);
            if($condition == '#') {
                if(!function_exists($match)) return array(false, $match.'函数不存在');
                DUMP(call_user_func($match, $value));
                return call_user_func($match, $value) === true ? true : array(false, '验证失败');
            }
        }
        return true;
    }

    /**
     * 获取数组层数
     * User: 木
     * Date: 2018/7/16 11:14
     * @param array $arr
     * @return int
     */
    private function ArrayLevel($arr=array()) {
        $level = 0;
        if(!is_array($arr)) return $level;
        foreach($arr as $item) {
            $level += 1;
            $level += $this->ArrayLevel($item);
            break;
        }
        return $level;
    }

    /**
     * 设置错误信息
     * User: 木
     * Date: 2018/7/16 11:14
     * @param string $e
     * @return false
     */
    public function setError($e='') {
        $this->e = $e;
        return false;
    }

    /**
     * 返回报错信息
     * User: 木
     * Date: 2018/7/25 16:25
     * @return mixed
     */
    public function getError() {
        return $this->e;
    }



}
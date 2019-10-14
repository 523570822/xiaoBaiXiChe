<?php

namespace Api\Controller;
/**
 * Created by PhpStorm.
 * User: Txunda
 * Date: 2018/7/6
 * Time: 13:06
 */
class CouponController extends BaseController
{
    /**
     *现金抵扣券
     * 参数:token status page
     **/
    public function Coupon ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('status' , 'int' , '请选择卡片状态');//0未使用 1已使用 2已过期
        $this->checkParam ($rule);
        if ( $request['status'] == 0 ) {
            $where = array (
                'is_use' => 0 ,
                'end_time'=> array('gt',time ())
            );
        }
        if ( $request['status'] == 1 ) {
            $where = array (
                'is_use' => 1
            );
        }
        if ( $request['status'] == 2 ) {
            $where = array (
                'is_use' => 0 ,
                'end_time'=> array('lt',time ())
            );
        }
        $date = D ('CouponBind')->where (array ('m_id' => $m_id))->where ($where)->field ('id,code_id,end_time,money')->page ($request['page'] , '10')->select ();
//        echo D('CouponBind')->_sql();exit;
        foreach ($date as $k=>$v){
            $date[$k]['title']='白洗车代金券';
            $date[$k]['remarks']='请在有效期内使用。';
            $date[$k]['status']=$request['status'];
        }

        if ( !$date ) {
            $message = $request['page'] == 1 ? '暂无消息' : '无更多消息';
            $this->apiResponse ('1' , $message);
        }
        $this->apiResponse (1 , '查询成功' , $date);
    }

    /**
     *生成代金券兑换活码
     * 参数:
     **/
    public function couponCode ()
    {
        $request = $_REQUEST;
        $rule = array (
            array ('title' , 'string' , '批次名称'),
            array ('price' , 'int' , '批次价格'),
            array ('nums' , 'int' , '批次数量'),
            array ('code_length' , 'int' , '批次长度'),
            array ('prefix' , 'string' , '批次前缀'),
//            array ('remark' , 'string' , '批次备注'),
            array ('start_time' , 'int' , '开始时间'),
            array ('end_time' , 'int' , '结束时间'),
            );
        $this->checkParam ($rule);
        $date['title']=$request['title'];
        $date['num']=$request['nums'];
        $date['price']=$request['price'];
        $date['remark']=$request['remark'];
        $date['create_time']=time ();
        $date['start_time']=$request['start_time'];
        $date['end_time']=$request['end_time'];
        $batch=D ('Batch')->add ($date);
        $code=$this->generateCode($request['nums'],'',$request['code_length'],$request['prefix']);
        foreach ($code as $k => $v) {
            $one= $code[$k];
            $data = [['exchange'=>$one,'is_activation'=>0,'create_time'=>$date['start_time'],'end_time'=>$date['end_time'],'b_id'=>$batch['id']]];
            M ('RedeemCode')->addAll($data);
        }
        $this->apiResponse (1,'添加成功',count ($one));
    }

    /**
     * 生成兑换活码
     * @param int $nums 生成多少个兑换活码
     * @param array $exist_array 排除指定数组中的兑换活码
     * @param int $code_length 生成兑换活码的长度
     * @param int $prefix 生成指定前缀
     * @return array                 返回兑换活码数组
     */
    public function generateCode ($nums='' , $exist_array = '' , $code_length = '' , $prefix = '')
    {
        $characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnpqrstuvwxyz";
        $promotion_codes = array ();//这个数组用来接收生成的兑换活码
        for ( $j = 0; $j < $nums; $j++ ) {
            $code = '';
            for ( $i = 0; $i < $code_length; $i++ ) {
                $code .= $characters[mt_rand (0 , strlen ($characters) - 1)];
            }
            //如果生成的4位随机数不再我们定义的$promotion_codes数组里面
            if ( !in_array ($code , $promotion_codes) ) {
                if ( is_array ($exist_array) ) {
                    if ( !in_array ($code , $exist_array) ) {//排除已经使用的兑换活码
                        $promotion_codes[$j] = $prefix . $code; //将生成的新兑换活码赋值给promotion_codes数组
                    } else {
                        $j--;
                    }
                } else {
                    $promotion_codes[$j] = $prefix . $code;//将兑换活码赋值给数组
                }
            } else {
                $j--;
            }
        }
        return $promotion_codes;
    }

    /**
     * 兑换代金券
    **/
    public function useCode ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('exchange' , 'string' , '请输入兑换码');
        $this->checkParam ($rule);
        if( !$request['exchange'] ){
            $this->apiResponse ('0' , '请输入兑换码');
        }
        $have_code = D ('RedeemCode')->where (array ('exchange' => $request['exchange'] , 'is_activation' => 0))->find ();
        $batch = D ('Batch')->where (array ('id' => $have_code['b_id']))->find ();
        if ( $have_code ) {
            $date['is_activation'] = 1;
            $JH = D ('RedeemCode')->where (array ('exchange' => $request['exchange'] , 'is_activation' => 0))->save ($date);
            $where['m_id'] = $m_id;
            $where['is_bind'] = 1;
            $where['type'] = 2;
            $where['create_time'] = time ();
            $where['end_time'] = $batch['end_time'];
            $where['comes'] = $batch['title'];
            $where['money'] = $batch['price'];
            $where['code_id'] = $have_code['id'];
            $BD = D ('CouponBind')->add ($where);
            if ( $JH && $BD ) {
                $this->apiResponse ('1' , '兑换成功');
            } else {
                $this->apiResponse ('0', '兑换失败');
            }
        }else{
            $this->apiResponse ('0' , '请输入正确的兑换码');
        }
    }
}
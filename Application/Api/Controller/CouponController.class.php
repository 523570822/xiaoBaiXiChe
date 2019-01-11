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
     * 参数:status //卡券状态 1未使用/正常 2已使用 3已过期
     **/
    public function Coupon ()
    {
        $m_id = $this->checkToken ();
        $this->errorTokenMsg ($m_id);
        $request = $_REQUEST;
        $rule = array ('status' , 'int' , '请选择卡片状态');
        $this->checkParam ($rule);
        $list = D ('VipCard')
            ->where (array ('db_vip_card.m_id' => $m_id , 'db_vip_card.c_type' => 2 , array ('db_vip_card.status' => $request['status'] , 'k_status' => 1)))
            ->join ("db_wash_card ON db_vip_card.card_id = db_wash_card.id")
            ->join ("LEFT JOIN db_vip_card_bind ON db_vip_card.activation_code = db_vip_card_bind.exchange")
            ->field ('db_vip_card.id,db_vip_card.end_time,db_vip_card.status,db_vip_card.activation_code,db_wash_card.name,db_wash_card.card_price, db_vip_card_bind.id as is_bind')
            ->select ();

        foreach ( $list as $key => $value ) {
            $list[$key]['is_bind'] = (empty($value['is_bind'])) ? 0 : 1;
        }
        $this->apiResponse (1 , '查询成功' , $list);
    }

    /**
     * 生成兑换活码
     * @param int $nums 生成多少个兑换活码
     * @param array $exist_array 排除指定数组中的兑换活码
     * @param int $code_length 生成兑换活码的长度
     * @param int $prefix 生成指定前缀
     * @return array                 返回兑换活码数组
     */
    public function generateCode ($nums='10' , $exist_array = '' , $code_length = 6 , $prefix = 'DHQ')
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
//        return $promotion_codes;
        foreach ($promotion_codes as $k => $v) {
            $one= $promotion_codes[$k];
            $data = [['exchange'=>$one,'is_activation'=>1,'creator_time'=>time (),'end_time'=>time ()]];
            M ('RedeemCode')->addAll($data);
        }
        $this->apiResponse (1,'添加成功',count ($one));
    }
}
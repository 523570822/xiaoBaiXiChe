<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/01/03
 * Time: 02:24
 */

namespace Api\Controller;
use Think\Controller;

/**
 * 加盟商支付控制器
 * Class PayController
 * @package Api\Controller
 */
class PayController extends BaseController
{

    /**
     *支付构造方法
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/03 02:26
     */
    public function _initialize(){
        parent::_initialize();
    }

    /**
     *提现页面
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/03 02:27
     */
    public function withdraw(){
        $post = checkAppData('token','token');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
        $member = $this->getAgentInfo($post['token']);
//        $alipay = M('Withdraw')->where(array('member_id'=>$member['id']))->find();

        if(!empty($member)){
            $this->apiResponse('1','成功',$member['balance']);
        }
    }

    /**
     *提现类型
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/03 14:26
     */
    public function bankType(){
        $bank = M('BankType')->where(array('status'=>1))->field('bank_name,bank_pic')->select();
        foreach ($bank as $k=>$v){
            $pic['bank_pic'] = $v['bank_pic'];
            $pic_path = getPicPath($pic['bank_pic']);
            $bank[$k]['bank_pic_path'] = $pic_path;
        }
        if(!empty($bank)){
            $this->apiResponse('1','成功',$bank);
        }else{
            $this->apiResponse('0','暂无提现类型');
        }
    }

    /**
     *添加银行卡
     *user:jiaming.wang  459681469@qq.com
     *Date:2019/01/03 15:52
     */
    public function addBankCard(){
        $post = checkAppData('token,card_name,card_code,ID_card,phone,card_id','token-持卡人姓名-持卡人卡号-身份证号-手机号-卡类型');
//        $post['token'] = 'b7c6f0307448306e8c840ec6fc322cb4';
//        $post['card_name'] = '王子';
//        $post['card_code'] = '621669020750127784585';
//        $post['ID_card'] = '587456988541235187';
//        $post['phone'] = '18635359874';
//        $post['card_id'] = 1;

        $agent = $this->getAgentInfo($post['token']);
        $data = array(
            'agent_id' =>$agent['id'],
            'card_name' =>$post['card_name'],
            'card_code' =>$post['card_code'],
            'ID_card' =>$post['ID_card'],
            'phone' =>$post['phone'],
            'card_id' =>$post['card_id'],
        );
        $card = M('BankCard')->where(array('agent_id'=>$agent['id'],'card_code'=>$post['card_code']))->find();
        if(empty($card)){
            $add = M('BankCard')->add($data);
            $this->apiResponse('1','成功');
        }else{
            $this->apiResponse('0','此卡号已绑定');
        }
    }
}
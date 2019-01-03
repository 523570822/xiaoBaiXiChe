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
}
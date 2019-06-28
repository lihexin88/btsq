<?php
namespace app\api\Controller;
use app\common\controller\ApiBase;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;

/**
 * 财务管理、提现页面功能
 *
 * @remark 财务管理页面信息（financePage）、提现申请页面信息（withdrawalsPage）、提现申请（withdrawalsApply）
 */



class Withdrawals extends ApiBase
{
    public function _initialize() 
    {
        parent::_initialize();
    }

    /**
     * 财务管理页面信息
     * @param string @uid [用户ID]
     */
    public function financePage()
    {
        $id = trim(input('uid'));
        if(!$id) {
            $r = $this->rtn(-1,lang("parameter"));
        }else{
            if(false == ($data = model('User')->financePage($id))){
                $r = $this->rtn(-1,lang("error"));
            }else{
               $r = $this->rtn(0,lang("success"),$data); 
            } 
        }
        return json($r);
    }


    /**
     * 提现申请页面信息
     * @param string @uid [用户ID]
     */
    public function withdrawalsPage()
    {
        $id = trim(input('uid'));
        if(!$id) {
            $r = $this->rtn(-1,lang("parameter"));
        }else{
            $map['id'] = $id;
            $userdata = model('User')->infodata($map);
            $config = model('Common/config')->getConfig();
            $data['usd'] = $userdata['usd'];
            $data['withdrawals_min'] = $config['WITHDRAWALS_MIN'];
            $data['withdrawals_max'] = $config['WITHDRAWALS_MAX'];
            $data['difference'] = $config['EXCHANGE_RATE'] - $config['WITHDRAWALS_RATE'];
            $data['withdrawals_rate'] = $config['WITHDRAWALS_RATE'];
            $data['service_charge'] = $config['AMOUNT_OF_CASH'] * 100;
            $r = $this->rtn(0,lang("success"),$data); 
        }
        return json($r);

    }

    /**
     * 提现申请
     * @param string @uid [用户ID]
     * @param string @amount [提现金额]
     * @param string @payment_password [支付密码]
     */
    public function withdrawalsApply()
    {
        $id = trim(input('uid'));
        $amount = trim(input('amount'));
        $payment_password = trim(input('payment_password'));
        if(!$id) {
            $r=$this->rtn(-1,lang("parameter"));
        }else if(!$amount) {
            $r =  $this->rtn(-1,lang("cont_empty"));
        } elseif(!$payment_password) {
            $r =  $this->rtn(-1,lang("cont_empty"));
        }else{
            $return = model("User")->withdrawalsApply($id,$amount,$payment_password);
            if($return['status'] === 0) {
                $r = $this->rtn(-1,$return['info']);
            }else{
                $r = $this->rtn(0,lang("success"));
            }
     
        }
        return json($r);
    }

    /**
     * 提现记录
     * @param string @uid [用户ID]
     * @param string @p [页数]
     */
    public function withdrawalsList()
    {
        $id = trim(input('uid'));
        $p = input('p') ? input('p'): 1;
        if(!$id) {
            $r = $this->rtn(-1,lang("parameter"));
        }else{
            if(false == ($data = model('Withdrawals')->withdrawalsList($id,$p))){
                $r = $this->rtn(-1,lang("error"));
            }else{
               $r = $this->rtn(0,lang("success"),$data); 
            } 
        }
        return json($r);
    }
}
<?php
namespace app\api\Controller;
use app\common\controller\ApiBase;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;

/**
 * 抢币页面功能
 *
 * @remark 抢币列表页面信息（sellPage）、抢币页面信息（buyPage）、提现申请（withdrawalsApply）
 */



class Sell extends ApiBase
{
    public function _initialize() 
    {
        parent::_initialize();
    }

    /**
     * 抢币列表页面信息
     * @param string @sort [挂卖单价]
     * @param string @sort [挂卖时间]
     */
    public function sellListPage()
    {
        $map['sell_status'] = 1;
        $p = input('p') ? input('p'): 1;
        $sort = input('sort');
        if(!$sort){
            $sort = 0;
        }
        if(false == ($data = model('Sell')->sellPage($sort,$map,$p))){
            $r = $this->rtn(-1,lang("null"));
        }else{
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
            $r = $this->rtn(-1,lang("parameter"));
        }else if(!$amount) {
            $r = $this->rtn(-1,lang("present_has"));
        }else if(!$payment_password) {
            $r = $this->rtn(-1,lang('paymentpwd_has'));
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
     * 抢币页面信息
     * @param string @sellid [列表ID]
     */
    public function buyPage()
    {
        $id = trim(input('sellid'));
        if(!$id) {
            $r = $this->rtn(-1,lang("parameter"));
        }else{
            $map['id'] = $id;
            if(false == ($selldata = model('Sell')->infodata($map))){
                $r = $this->rtn(-1,lang("null"));
            }else{
                $userdata = model('User')->infodata(array('id'=>$selldata['seller_id']));
                $selldata['sellername'] = $userdata['username'];
                $selldata['alipay_accout'] = $userdata['alipay_accout'];
                $selldata['credit_level'] = $userdata['credit_level'];
                $r = $this->rtn(0,lang("success"),$selldata);   
            }
        }
        return json($r);
    }


    /**
     * 抢币功能
     * @param string @uid [用户ID]
     * @param string @sellid [列表ID]
     * @param string @number [购买数量]
     */
    public function grabCoin()
    {
        $id = trim(input('sellid'));
        $uid = trim(input('uid'));
        $number = abs(trim(input('number')));
        if(!$id) {
            $r = $this->rtn(-1,lang("parameter"));
        }else if(!$uid) {
            $r = $this->rtn(-1,lang("parameter"));
        }else if(!$number){
            $r = $this->rtn(-1,lang('number_has'));
        }else{
            $map['id'] = $id;
            if(false == ($selldata = model('Sell')->infodata($map))){
                $r = $this->rtn(-1,lang('null'));
            }else{
                if($uid == $selldata['seller_id']){
                    $r = $this->rtn(-1,lang('own_currency'));
                }else if($selldata['sell_number'] < $number){
                    $r = $this->rtn(-1,lang('excess_quantity'));
                }else{
                    $return = model("Sell")->grabCoin($id,$uid,$number);
                    if($return['status'] === 0) {
                        $r = $this->rtn(-1,$return['info']);
                    }else{
                        $r = $this->rtn(0,lang('success'));   
                    }                  
                }
            }
        }
        return json($r);
    }

    /**
     * 挂卖页面信息
     * @param string @uid [用户ID]
     */
    public function sellPage()
    {
        $id = trim(input('uid'));
        if(!$id) {
            $r = $this->rtn(-1,lang("parameter"));
        }else{
            if(false == ($data = model('User')->infodata(array('id'=>$id)))){
                $r = $this->rtn(-1,lang("error"));
            }else{
                $config = model('Common/config')->getConfig();
                $return['id'] = $data['id'];
                $return['username'] = $data['username'];
                $return['price'] = $config['DFS_EXCHANGE_RATE'];
                $return['credit_level'] = $data['credit_level'];
                $return['alipay_accout'] = $data['alipay_accout'];
                $return['dfs'] = $data['dfs'];
                $r = $this->rtn(0,lang("success"),$return); 
            } 
        }
        return json($r);
    }

    /**
     * 挂卖功能
     * @param string @uid [用户ID]
     * @param string @number [挂卖数量]
     */
    public function sell()
    {
        $id = trim(input('uid'));
        $number = abs(trim(input('number')));
        if(!$id) {
            $r = $this->rtn(-1,lang("parameter"));
        }else if(!$number){
            $r = $this->rtn(-1,lang('cont_empty'));
        }else{
            $return = model("Sell")->sell($id,$number);
            if($return['status'] === 0) {
                $r = $this->rtn(-1,$return['info']);
            }else{
                $r = $this->rtn(0,lang("success"));
            }
        }
        return json($r);
    }
}
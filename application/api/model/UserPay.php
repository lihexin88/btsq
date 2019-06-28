<?php

namespace app\api\model;

use think\Model;
use think\Db;
use think\Request;
use app\api\model\UserConfig;

class UserPay extends Model
{
    /**
     * 保存用户支付信息
     * @param $user
     * @param null $array
     * @return bool
     * @throws \think\exception\DbException
     */
    public function save_pays($user, $array = null)
    {
        $user_pay = $this->get(['uid' => $user['id']]);
        if ($user_pay) {

        } else {
            $user_pay = new $this;
        }
        foreach ($array as $k => $v) {
            $user_pay->$k = $v;
        }
        if (!$user_pay->save()) {
            return false;
        }
        return true;
    }

    /**
     * 去支付信息
     * @param $datas
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pay_info($datas){
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

        if(!$data['seller_id']){
            return ['code' => 0,'msg' => '未获取卖家信息'];
        }

        if(!$data['order_id']){
            return ['code' => 0,'msg' => '未获取订单信息'];
        }

//        // 获取用户语言状态
//        $user_config = UserConfig::config($userinfo);
//        // 根据用户设置的语言显示相应的内容
//        switch($user_config['language']){
//            case 1:
//                $UserPay = Db::name('user_pay') -> alias('p') -> join('user_bank b','p.uid=b.uid AND b.default=1') -> field('p.alipay_account,p.alipay_name,p.wechat_nick,b.bid,b.open_name,b.bank_card,b.bank_add') -> where('p.uid',$data['seller_id']) -> find();
//                $Bank = new Bank();
//                $UserPay['bank_text'] = $Bank -> get_bank_name_by_id($UserPay['bid']);
//                if(!$UserPay){
//                    return ['code' => 0];
//                }
//                break;
//            case 2:case 3:
//                $UserPay = $this -> where('uid',$data['seller_id']) -> field('alipay_account,alipay_name,wechat_nick,paypal') -> find();
//                if(!$UserPay){
//                    return ['code' => 0];
//                }
//                break;
//        }
        // 用户所有支付方式
        //$UserPay = Db::name('user_pay') -> alias('p') -> join('user_bank b','p.uid=b.uid AND b.default=1','left') -> field('p.alipay_account,p.alipay_name,p.wechat_nick,p.paypal,b.bid,b.open_name,b.bank_card,b.bank_add') -> where('p.uid',$data['seller_id']) -> find();
        $pay = Db::name('user_pay')-> field('alipay_account,alipay_name,wechat_nick,wechat_img,paypal') -> where('uid',$data['seller_id']) -> find();
        $bank = Db::name('user_bank')-> field('bid,open_name,bank_card,bank_add') -> where(['uid'=>$data['seller_id'],'default'=>1]) -> find();
        $UserPay = array_merge((array)$pay,(array)$bank);
        if($UserPay['wechat_img']){
            $request = Request::instance();
            $UserPay['wechat_img'] = $request->domain().$UserPay['wechat_img'];
//            $UserPay['wechat_img'] = $request->domain()."/btsq/public".$UserPay['wechat_img'];
        }
        $Bank = new Bank();
        $UserPay['bank_text'] = $Bank -> get_bank_name_by_id($bank['bid']);

        $return['list'] = $UserPay;
        $return['order_id'] = $data['order_id'];
        return ['code' => 1,'data' => $return];
    }


}
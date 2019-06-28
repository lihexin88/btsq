<?php
namespace app\api\controller;

use app\api\model\Config;
use app\api\model\Order;
use app\api\model\Trade;
use app\api\model\UserCoin;
use app\api\model\User;
use app\api\model\UserMessage;

use app\api\model\UserMoney;
use think\Controller;
use think\Exception;
use think\Db;


class Index extends controller {

    public function index() {
        echo config('WEB_SITE_NAME').'项目API接口目录';
    }


	/**
	 * 关于我们
	 * @return false|string
	 */
    public function about_us()
    {
		$about_us = Config::about_us();
		return rtn(1,lang('os_success'),$about_us);
    }




    /** 计划任务开始 **/
    /**
     * 自动取消未支付过期的订单(每3分钟执行一次)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pass_do_pay(){
        $Order = new Order();

        $pay_time = Db::name('config') -> where('key','LAST_PAY_TIME') -> value('value');
        $set_time = $pay_time*60*60;   // 设置的过期支付时间
        $time = time();                 // 当前时间

        $order_where['order_status'] = 1;
        $pass_time = time() - $set_time;
        $order_where['addtime'] = ['elt',$pass_time];
        $order = $Order -> get_order_list_by_where($order_where);
        if($order){
            $order = $order -> toArray();
            foreach($order as $k => $v){
                $this->pass_pay_active($v);
            }
        }
    }

    public function pass_pay_active($v)
    {
        $Order = new Order();
        $Trade = new Trade();
        $User = new User();
        $UserMessage = new UserMessage();
        Db::startTrans();
        try{
            // 返还卖家挂单数量
            $Trade_result = $Trade -> back_trade_number($v['trade_sell_id'],$v['order_number']);
            if($Trade_result['code'] === 0){
                throw new Exception('返还卖家挂单数量失败');
            }
            // 修改订单状态
            $Order_result = $Order -> cancel_order($v['id']);
            if($Order_result['code'] === 0){
                throw new Exception('修改订单状态失败');
            }
            // 执行惩罚
            $User_result = $User -> punishment($v['buyer_id']);
            if($User_result['code'] === 0){
                throw new Exception('执行处罚失败');
            }
            // 取消买家所有未匹配的挂单
            $cancel_trade_result = $Trade -> cancel_trades($v['buyer_id']);
            if($cancel_trade_result['code'] === 0){
                throw new Exception('取消买家未匹配挂单失败');
            }
            Db::commit();
        }catch(\Exception $e){
            Db::rollback();
            echo $e -> getMessage();
        }
    }

    /**
     * 自动确认未确认过期的订单(每3分钟执行一次)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pass_do_done(){
        $Order = new Order();
        $Trade = new Trade();
        $User = new User();
        $UserCoin = new UserCoin();
        $UserMessage = new UserMessage();

        $done_time = Db::name('config') -> where('key','LAST_DONE_TIME') -> value('value');
        $set_time = $done_time*60*60;           // 设置的过期支付时间
        $time = time();                             // 当前时间

        $order_where['order_status'] = 2;
        $pass_time = time() - $set_time;
        $order_where['pay_time'] = ['elt',$pass_time];
        $order = $Order -> get_order_list_by_where($order_where);
        if($order){
            $order = $order -> toArray();
            foreach($order as $k => $v){
                Db::startTrans();
                try{
                    $UserCoin = new UserCoin();

                    $sell_result = $UserCoin->inc_user_coin($v['buyer_id'], 1, $v['order_number'], $v['price'], 2);
                    if ($sell_result['code'] === 0) {
                        return ['code' => 0, 'msg' => $sell_result['msg']];
                    }

                    // 修改订单信息
                    $order_where['id'] = $v['id'];
                    $order_mod['order_status'] = 3;
                    $order_mod['done_time'] = time();
                    $order_result = $Order -> mod_order($order_where,$order_mod);
                    if($order_result['code'] === 0){
                        throw new Exception($order_result['msg']);
                    }
                    // 执行惩罚
                    $User_result = $User -> punishment($v['seller_id']);
                    if($User_result['code'] === 0){
                        throw new Exception('执行处罚失败');
                    }
                    // 取消卖家所有未匹配的挂单
                    $cancel_trade_result = $Trade -> cancel_trades($v['seller_id']);
                    if($cancel_trade_result['code'] === 0){
                        throw new Exception('取消卖家未匹配挂单失败');
                    }

                    Db::commit();
                    echo '成功';
                }catch(\Exception $e){
                    Db::rollback();
                    echo $e -> getMessage();
                }
                
            }
        }
    }
    /** 计划任务结束 **/
}


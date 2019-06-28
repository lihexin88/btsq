<?php

namespace app\api\model;

use think\Exception;
use think\Model;
use think\Session;
use think\Db;

class UserCoinProfit extends Model
{
    /**
     * 修改"用户币价值表"中的信息
     * @param $uid
     * @param $cur_id
     * @param $amount
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dec_user_coin_profit($uid,$cur_id,$amount){
        $coin_where['uid'] = $uid;
        $coin_where['cur_id'] = $cur_id;
        $coin_where['status'] = 1;
        $coin_where['amount'] = ['neq',0];
        $coin_list = $this -> where($coin_where) -> order('id ASC') -> select();
        $number = $amount;
        $total = 0;
        foreach($coin_list as $k => $v){
            if($number <= $v['amount']){    // 当交易的价格小于等于某一条记录的价格
                $num = $v['amount'] - $number;
                if($num != 0){  // 记录数量大于当前交易数量
                    $mod['amount'] = $num;
                    $mod['total'] = $num * $v['price'];
                    $mod['update_time'] = time();
                }else{  // 记录数量等于交易数量
                    $mod['amount'] = 0;
                    $mod['total'] = 0.000;
                    $mod['update_time'] = time();
                    $mod['status'] = 0;
                }
                if(false === $this -> where('id',$v['id']) -> update($mod)){
                    throw new Exception("修改用户币价值失败");
                }
                $total = $total + ($num * $v['price']);
                break;
            }else{  // 当交易的价格大于某一条记录的价格
                $number -= $v['amount'];
                $mod['amount'] = 0;
                $mod['total'] = 0.000;
                $mod['update_time'] = time();
                $mod['status'] = 0;
                if(false === $this -> where('id',$v['id']) -> update($mod)){
                    throw new Exception("修改用户币价值失败");
                }
                $total = $total + ($v['amount'] * $v['price']);
                unset($mod['status']);
            }
        }
        $user_money = db('user_money_profit')->where('uid',$uid)->find();
        $update['total'] = $user_money['total']-$total;
        $update['amount'] = $user_money['amount']-$amount;
        db('user_money_profit')->where('uid',$uid)->update($update);
    }
}

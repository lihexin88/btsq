<?php

namespace app\api\model;

use think\Model;
use think\Session;
use think\Db;

class UserMoney extends Model
{
    /**
     * 钱包地址
     * @param $user
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wallet_list($user)
    {
        return  self::alias('m')
                ->join('currency c','m.cur_id = c.id')
                ->where('m.uid',$user['id'])
                ->field('m.*,c.name,c.icon')
                ->select();
    }

    /**
     * 增加用户金额
     * @param $user
     * @param $money
     * @throws \think\exception\DbException
     */
    public function add_user_money($user, $money)
    {
        $user_money = $this::get(['uid' => $user['uid']]);
        if (!$user_money) {
            $user_money = new UserMoney();
            $user_money->total = 0;
            $user_money->uid = $user['uid'];
        }
        $user_money->total += $money;
        $user_money->force()->save();
        return true;
    }

    /**
     * 通过用户UID返还用户相应的数量
     * @param $uid
     * @param $number
     * @return array
     * @throws \think\Exception
     */
    public function get_back_user_money($trade_info){
        $coin['uid'] = $trade_info['uid'];
        $coin['amount'] = $trade_info['number'];
        $coin['price'] = $trade_info['price'];
        $coin['total'] = $coin['amount']*$coin['price'];
        $coin['create_time'] = time();
        $coin['update_time'] = time();
        $coin['type'] = 10;
        $coin['all_amount'] = $coin['amount'];
        db('user_coin_profit')->insert($coin);
        $user_money_profit = db('user_money_profit')->where('uid',$trade_info['uid'])->find();
        $money['uid'] = $trade_info['uid'];
        $money['total'] = $user_money_profit['total']+$coin['total'];
        $money['update_time'] = time();
        $money['amount'] = $user_money_profit['amount']+$coin['amount'];
        db('user_money_profit')->where('uid',$trade_info['uid'])->update($money);
    }

    /**
     * 通过用户ID和币种ID获取用户某一币种数量
     * @param $money_where
     * @return mixed
     */
    public function get_user_total($money_where){
        $total = $this -> where($money_where) -> value('amount');
        return $total;
    }

    /**
     * 通过用户ID和币种ID获取用户某一币种总量
     * @param $money_where
     * @return mixed
     */
    public function get_money_total($money_where){
        $total = $this -> where($money_where) -> value('total');
        return $total;
    }
}

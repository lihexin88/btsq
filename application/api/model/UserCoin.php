<?php

namespace app\api\model;

use think\Exception;
use think\Model;
use think\Session;
use think\Db;

class UserCoin extends Model
{
    /**
     *钱包地址
     */
    static public function wallet_list($user, $post)
    {
        // Db::listen(
        //     function($sql){pre($sql);}
        // );
        switch ($post['type']) {
            case '1':
                $type = array('in', '1,2,3,4,5');
                $status = array('in', '1,2,3');
                break;
            case '2':
                $type = array('in', '2,4,5');
                $status = 2;
                break;
            case '3':
                $type = array('in', '1,3');
                $status = 2;
                break;
            case '4':
                $type = array('in', '1,2,3,4,5');
                $status = 3;
                break;
        }
        $map = [
            'c.uid' => $user['id'],
            'c.cur_id' => $post['id'],
            'c.type' => $type,
            'c.status' => $status
        ];
        return self::alias('c')
            ->join('currency y', 'c.cur_id = y.id')
            ->where($map)
            ->field('c.*,y.name')
            ->select();
    }

    //该币种总数量
    public function transfer_info($map)
    {
        return self::where($map)->sum('amount');
    }

    /**
     * 币种详情
     * @param string $id [币种ID]
     * @param string $type [列表类型：1转入，2转出，6失败]
     */
    public function curDetails($map, $p, $page_size, $name)
    {
        $data = [];
        $list = db('Recharge')->where($map)->order('create_time desc')->page($p, $page_size)->select();
        foreach ($list as $k => $v) {
            $data[$k]['id'] = $v['id'];
            $data[$k]['address'] = jiami($v['to_address'], 5, 8);
            $data[$k]['time'] = date('Y-m-d H:i:s', $v['create_time']);
            if ($v['recharge_type'] == 1) {
                $data[$k]['price'] = '+' . $v['number'] . $name;
            } else {
                $data[$k]['price'] = '-' . $v['number'] . $name;
            }
            if ($v['recharge_status'] == 2) {
                $data[$k]['type'] = 6;
            } else {
                $data[$k]['type'] = $v['recharge_type'];
            }
        }
        return $data;

    }

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
    public function dec_user_coin($uid, $cur_id, $amount)
    {
        $coin_where['uid'] = $uid;
        $coin_where['cur_id'] = $cur_id;
        $coin_where['status'] = 1;
        $coin_list = $this->where($coin_where)->order('id ASC')->select();
        $number = $amount;
        foreach ($coin_list as $k => $v) {
            if ($number <= $v['amount']) {    // 当交易的价格小于等于某一条记录的价格
                $num = $v['amount'] - $number;
                if ($num != 0) {  // 记录数量大于当前交易数量
                    $mod['amount'] = $num;
                    $mod['total'] = $num * $v['price'];
                    $mod['update_time'] = time();
                } else {  // 记录数量等于交易数量
                    $mod['amount'] = 0;
                    $mod['total'] = 0.000;
                    $mod['update_time'] = time();
                    $mod['status'] = 0;
                }
                if (false === $this->where('id', $v['id'])->update($mod)) {
                    throw new Exception("修改用户币价值失败");
                }
                return true;
            } else {  // 当交易的价格大于某一条记录的价格
                $number -= $v['amount'];
                $mod['amount'] = 0;
                $mod['total'] = 0.000;
                $mod['update_time'] = time();
                $mod['status'] = 0;
                if (false === $this->where('id', $v['id'])->update($mod)) {
                    throw new Exception("修改用户币价值失败");
                }
                unset($mod['status']);
            }
        }
        throw new Exception("虚拟币不足");
    }

    // 对应UserMining.php中的调用
    public function dec_user_coin2($uid, $cur_id, $amount)
    {
        $coin_where['uid'] = $uid;
        $coin_where['cur_id'] = $cur_id;
        $coin_where['status'] = 1;
        $coin_where['amount'] = ['neq', 0];
        $coin_list = $this->where($coin_where)->order('id ASC')->select();
        return $coin_list;
    }


    public function dec_user_coin3($uid, $cur_id, $amount)
    {
        $coin_where['uid'] = $uid;
        $coin_where['cur_id'] = $cur_id;
        $coin_where['status'] = 1;
        $coin_list = db('user_coin_profit')->where($coin_where)->order('id ASC')->select();
        $number = $amount;
        $result = '';
        foreach ($coin_list as $k => $v) {
            if ($number <= $v['amount']) {    // 当交易的价格小于等于某一条记录的价格
                $num = $v['amount'] - $number;
                if ($num != 0) {  // 记录数量大于当前交易数量
                    $mod['amount'] = $num;
                    $mod['total'] = $num * $v['price'];
                    $mod['update_time'] = time();
                } else {  // 记录数量等于交易数量
                    $mod['amount'] = 0;
                    $mod['total'] = 0.000;
                    $mod['update_time'] = time();
                    $mod['status'] = 0;
                }
                $result[$v['id']] = $number;
                if (false === db('user_coin_profit')->where('id', $v['id'])->update($mod)) {
                    throw new Exception("修改用户币价值失败");
                }

            } else {  // 当交易的价格大于某一条记录的价格
                $number -= $v['amount'];
                $mod['amount'] = 0;
                $mod['total'] = 0.000;
                $mod['update_time'] = time();
                $mod['status'] = 0;
                $result[$v['id']] = $v['amount'];
                if (false === db('user_coin_profit')->where('id', $v['id'])->update($mod)) {
                    throw new Exception("修改用户币价值失败");
                }
                unset($mod['status']);

            }
        }
        return json_encode($result);

    }

    /**
     * 修改"用户币价值表"中的信息
     * @param $uid
     * @param $cur_id
     * @param $amount
     * @param $price
     * @param $type
     * @return array
     */
    public function inc_user_coin($uid, $cur_id, $amount, $price, $type)
    {
        $in_coin['uid'] = $uid;
        $in_coin['cur_id'] = $cur_id;
        $in_coin['amount'] = $amount;
        $in_coin['price'] = $price;
        $in_coin['total'] = $amount * $price;
        $in_coin['status'] = 1;
        $in_coin['all_amount'] = $amount;
        $in_coin['create_time'] = time();
        if ($type === 2) {
            $in_coin['type'] = 3;   // 买入
        } else {
            $in_coin['type'] = 4;   // 卖出
        }
        if (false === $this->insert($in_coin)) {
            return ['code' => 0, 'msg' => '记录用户币价值信息失败'];
        }
        return ['code' => 1];
    }

    /**
     * 主账户资产列表
     * @param $datas
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_finance_list($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

        if ($data['page']) {
            $page_start = $data['page'] * 20 - 20;
        } else {
            $page_start = 0;
        }
        $page_end = 20;

        $coin_where['uid'] = $userinfo['id'];
        $coin_where['cur_id'] = 1;
        $coin_where['type'] = ['in', '3,6'];
        $list = db('user_coin')->where($coin_where)->field('id,price,total,amount,create_time,type')->select(); // limit($page_start,$page_end) ->
        if (sizeof($list) != 0) {
            foreach ($list as $k => $v) {
                $list[$k]['amount_text'] = '' . number_format($v['amount'], 5);    // 数量
                $list[$k]['total'] = number_format($v['amount'] * $v['price'], 2);
                //$list[$k]['cny_total'] = $list[$k]['total'] * config('USDT_RMB');  // cny总额
                $list[$k]['type_text'] = $v['type'] == 3 ? '投资额增加' : '转入';
                $list[$k]['price'] = number_format($v['price'], 2);
                unset($v['all_amount']);
            }
        }
        $return['list'] = $list;
        $return['usd'] = sprintf('%.2f', db('user_money')->where('uid', $userinfo['id'])->sum('total'));
        return $return;
        $minings = db('user_mining')->where('uid', $userinfo['id'])->field('amount,spend,create_time')->select();
//        $minings = db('mining_log')->where('uid', $userinfo['id'])->select();
        $number = count($list);
        foreach ($minings as $k => $v) {
            $list[$number]['id'] = $v['id'];
            $list[$number]['amount_text'] = '-' . number_format($v['number'], 5);
            $list[$number]['price'] = number_format($v['price'], 2);
            $list[$number]['total'] = number_format($v['number'] * $v['price'], 2);
            $list[$number]['create_time'] = $v['create_time'];
            $list[$number]['type_text'] = '新增矿池';
            $number += 1;
        }
        $create_time = array_column($list, 'create_time');
        $id = array_column($list, 'id');
        array_multisort($create_time, SORT_DESC, $id, SORT_DESC, $list);
        $return['list'] = $list;
        $return['usd'] = sprintf('%.2f', db('user_money')->where('uid', $userinfo['id'])->sum('total'));
        return $return;
    }

    /**
     * 主账户资产详情
     * @param $datas
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_finance_detail($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

        if (!$data['id']) {
            return ['code' => 0, 'msg' => '未获取该条详情信息'];
        }

        $info = $this->where('id', $data['id'])->field('id,price,total,create_time,all_amount')->find();
        $info['amount_text'] = '+' . sprintf('%.2f', $info['all_amount']);    // 数量
        $info['cny_total'] = $info['total'] * config('USDT_RMB');  // cny总额
        unset($info['all_amount']);
        return ['code' => 1, 'data' => $info];
    }

    /**
     * 收益账户资产
     * @param $datas
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function revenue_finance($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

        if ($data['page']) {
            $page_start = $data['page'] * 20 - 20;
        } else {
            $page_start = 0;
        }
        $page_end = 20;

        $coin_where['uid'] = $userinfo['id'];
        $coin_where['cur_id'] = 1;
        $coin_where['type'] = ['in', '5,6,8,9,11'];
        $record = db('user_coin_profit')->where($coin_where)->field('id,create_time,all_amount,type')->order("create_time")->select();
        $list = [];
        if (sizeof($record) != 0) {
            foreach ($record as $k => $v) {
                $record[$k]['time'] = date('Y-m-d H:i', $v['create_time']);
                if (in_array($v['type'], [5, 8, 9, 11])) {
                    $kk = $v['type'] . date('Y-m-d H:i', $v['create_time']);
                    $list[$kk]['create_time'] = $v['create_time'];
                    $list[$kk]['amount_text'] += $v['all_amount'];
                    if ($v['type'] == 5) {
                        $list[$kk]['type_text'] = '活跃值收益';
                        $time1 = strtotime(date('Y-m-d', $v['create_time'])) + 46800;
                        if ($v['create_time'] < $time1) {
                            $list[$kk]['create_time'] = $time1 - 86400;
                        } else {
                            $list[$kk]['create_time'] = $time1;
                        }

                    } elseif ($v['type'] == 8) {
                        $list[$kk]['type_text'] = '收益';
                    } elseif ($v['type'] == 9) {
                        $list[$kk]['type_text'] = '收益';
                    } elseif ($v['type'] == 11) {
                        $list[$kk]['type_text'] = '收益';
                    }
                } else {
                    $list[$k]['amount_text'] = $v['all_amount'];    // 数量
                    $list[$k]['type_text'] = '后台充值';
                    $list[$k]['create_time'] = $v['create_time'];
                }
            }
            foreach ($list as $k => $v) {
                $list[$k]['amount_text'] = '+' . number_format($v['amount_text'], 5);    // 数量
            }
        } else {
            $list = array();
            $usd = 0.00;
        }
        $list = array_values($list);
        $order = db('order')->where(['seller_id' => $userinfo['id'], 'order_status' => 3])->field('order_number,done_time')->order("time")->select();
        $number = count($list);
        foreach ($order as $k => $v) {
            $list[$number]['amount_text'] = '-' . number_format($v['order_number'], 5);
            $list[$number]['create_time'] = $v['done_time'];
            $list[$number]['type_text'] = '卖出';
            $number += 1;
        }
        $recharge = db('recharge')->where(['uid' => $userinfo['id'], 'recharge_type' => 2, 'recharge_status' => 1])->field('number,create_time')->order("create_time")->select();
        $number = count($list);
        foreach ($recharge as $k => $v) {
            $list[$number]['amount_text'] = '-' . number_format($v['number'], 5);
            $list[$number]['type_text'] = '转出';
            $number += 1;
        }
        $create_time = array_column($list, 'create_time');
//        array_multisort($create_time, SORT_DESC, $list);
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        $return['list'] = $list;
        $return['baud'] = number_format(db('user_money_profit')->where('uid', $userinfo['id'])->sum('amount'), 5);
        $return['usd'] = number_format(db('user_money_profit')->where('uid', $userinfo['id'])->sum('total'), 2);
        return $return;
    }
}

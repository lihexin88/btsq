<?php
/**
 * Created by 李.
 * User: Administrator
 * Date: 2018/12/13
 * Time: 10:31
 */

namespace app\api\controller;

use app\common\controller\Base;
use app\api\model\AutomationLimit;
use app\api\model\AutoWithdraw;
use app\api\model\Config;
use app\api\model\Language;
use app\api\model\User as UserModel;
use app\api\model\UserContribution;
use app\api\model\UserMining;
use think\Controller;
use think\Db;
use think\Request;

class DailyPlan extends Base
{

    /**
     * 一键全部每日任务
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function run_all()
    {
        /*社区贡献*/
        $this->daily_compute();

        //报废币回收记录
        $this->mining_scrap();

        /*矿机日收益*/
        $this->daily_mining();

        /*自动提取收益*/
        $this->auto_withdraw_mining();

        /*美元汇率*/
        $this->exchange_rate();

        /*最佳买/卖价格增加1%*/
        $this->increase();

        /*全球分红*/
        $this->global_income();

        /*下次任务启动时间*/
        db('config')->where('key', 'TIMING_TASK')->update(['value' => time() + 86400]);

        /*每日报表*/
        $this->report_form();
    }


    /**
     * 日审核用户vip等级
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_level()
    {
        $users = UserModel::all();
        foreach ($users as $k => $v) {
            UserModel::daily_user_level($v);
        }
        return rtn(1, "success");

    }


    /**
     * 日常更新用户贡献值
     * @throws \think\exception\DbException
     */
    public function daily_compute()
    {

        $UserMining = new UserMining();
        /*评估用户列表*/
        $user_common = db('user')->select();
        $user = db('user')->field('id,parent_id,level')->select();
        $user2 = db('user')->where('level', 'egt', 2)->field('id,parent_id')->select();//VIP2以上的用户
        /*获取用户伞下总业绩（贡献值）*/
        $map = [];
        $achieve_step = UserMining::get_achieve_step($user, $user2, $map, 25);
        /*获取用户一周伞下总业绩（贡献值）*/
        $start_time = time() - 604800;
        $end_time = time();
        $map['create_time'] = ['between', "$start_time,$end_time"];
        $insert_step = UserMining::get_achieve_step($user, $user2, $map, 45);
        //VIP等级、直推贡献值
        $inv_score = UserMining::get_inv_step($user);
        foreach ($inv_score as $k => $v) {
            if (array_key_exists($k, $achieve_step)) {
                $achieves = $achieve_step[$k];
            } else {
                $achieves = 0;
            }
            if (array_key_exists($k, $insert_step)) {
                $inserts = $insert_step[$k];
            } else {
                $inserts = 0;
            }
            $inv_score[$k] = $v + $achieves + $inserts;
            $insert['contribution'] = 50 + ($v + $achieves + $inserts) * 0.5;
            $insert['vip'] = $v * 0.5;
            $insert['recommend'] = $v * 0.5;
            $insert['total_mining'] = $achieves ? $achieves * 0.5 : 0;
            $insert['insert_mining'] = $inserts ? $inserts * 0.5 : 0;
            $insert['update_time'] = time();
            if (db('user_contribution')->where('uid', $k)->find()) {
                db('user_contribution')->where('uid', $k)->update($insert);
            } else {
                $insert['create_time'] = time();
                $insert['uid'] = $k;
                db('user_contribution')->insert($insert);
            }
            db('contribution_his')->insert(['uid' => $k, 'contribution' => $insert['contribution'], 'create_time' => time(), 'update_time' => time()]);
        }
        return rtn(1, "success");
    }


    //币记录

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function mining_scrap()
    {
        $list = db('mining_extracted')->where('status', 0)->group('uid')->column('uid');
        foreach ($list as $k => $v) {
            $insert['time'] = time();
            $insert['uid'] = $v;
            $insert['settlement_income'] = db('mining_extracted')->where('uid', $v)->where('status', 0)->sum('settlement_income');
            $insert['unextracted_income'] = db('mining_extracted')->where('uid', $v)->where('status', 0)->sum('unextracted_income');
            $insert['extracted_income'] = db('mining_extracted')->where('uid', $v)->where('status', 0)->sum('extracted_income');
            db('mining_extracted_today')->insert($insert);
        }
        db('mining_extracted')->where('status', 0)->update(['state' => 1, 'status' => 1]);
    }

    /**
     * 矿机收益
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function daily_mining()
    {
        $UserMining = new UserMining();

        /*用户的矿*/
        $profit_status = db('user_config')->where('profit_status', 0)->column('uid');
        $user_mining_wherep['uid'] = ['not in', $profit_status];
        $user_mining_wherep['status'] = 1;
        //$user_mining_wherep['reword'] = ['eq',0];
        $user_mining_wherep['mining_status'] = 1;
        $user_mining = db('user_mining')->where($user_mining_wherep)->select();

        //用户贡献值
        $user_contribution = db('user_contribution')->field('uid,contribution')->order('contribution asc,id asc')->select();
        $all_mining = db('user_mining')->field('id,uid,spend')->order('spend asc,id asc')->select();
        //矿池投资额最大值、最小值
        $mining_data['max'] = db('user_mining')->max('amount');
        $mining_data['min'] = db('user_mining')->min('amount');
        //pre($user_contribution);exit;
        foreach ($user_mining as $k => $user) {
            $UserMining->mining_reword($user, $user_contribution, $mining_data, $all_mining);
        }
        $sum = db('user_mining')->where(['status' => 1, 'mining_status' => 1])->sum('power');
        db('user_mining')->where('1=1')->update(['net_power' => $sum]);
    }


    /**
     * 自动提取用户矿机收益
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function auto_withdraw_mining()
    {
        $AuthWithdraw = new AutoWithdraw();
        $UserMining = new UserMining();

        /*获取开通自动提取矿机收益的用户*/
        $auto_withdraw = db('auto_withdraw')->where('status', 1)->select();
        foreach ($auto_withdraw as $k => $v) {
            $user_mining = Mining::get_mining($v);
            foreach ($user_mining as $kk => $single_mining) {
                $UserMining->mining_withdraw($single_mining, 2);
            }
        }
    }

    /**
     * 报表
     * @return false|string
     */
    public function report_form()
    {
        $today = strtotime(date("Y-m-d"), time()) + 46800;
        $yesterday = strtotime(date("Y-m-d"), time()) - 39600;
        //昨日数据
        $where['create_time'] = ['between', "$yesterday,$today"];
        //昨日新增用户
        $insert_data['new_member'] = db('user')->where($where)->count();
        //昨日新增业绩
        $insert_data['new_achievement'] = db('user_mining')->where($where)->sum('amount');
        //昨日结算矿池收益
        $extracted_where['time'] = $where['create_time'];
        $insert_data['new_profit'] = db('mining_extracted')->where($extracted_where)->sum('settlement_income');
        //昨日新增下级返利收益
        $insert_data['new_child_profit'] = db('money_flow')->where($where)->sum('number');
        //昨日新增交易量
        $profit_where['order_status'] = 3;
        $profit_where['done_time'] = ['between', "$yesterday,$today"];
        $insert_data['new_vol'] = db('order')->where($profit_where)->sum('order_number');
        $insert_data['time'] = time();
        $insert_data['price'] = $this->average_price($profit_where);

        $recharge_where['recharge_status'] = 1;
        $recharge_where['create_time'] = ['between', "$yesterday,$today"];
        $insert_data['turn'] = db('recharge')->where($recharge_where)->sum('number');
        //转到外部钱包量
        $recharge_where['transfer_type'] = 2;
        $recharge_where['recharge_type'] = 2;

        $insert_data['turn_out'] = db('recharge')->where($recharge_where)->sum('number');

        //从外部钱包转入量
        $recharge_where['recharge_type'] = 1;
        $insert_data['turn_into'] = db('recharge')->where($recharge_where)->sum('number');
        db('report_form')->insert($insert_data);
        return rtn(1, lang('success'));
    }

    public function user_report()
    {
        $users = db('user')->field('id,parent_id')->select();
        $insert = [];
        foreach ($users as $k => $v) {
            $insert_data = [];
            $today = strtotime(date("Y-m-d"), time()) + 46800;
            $yesterday = strtotime(date("Y-m-d"), time()) - 39600;
            //昨日数据
            $where['create_time'] = ['between', "$yesterday,$today"];
            $ids = GetTeamMember($users, $v['id']);
            $ids[] = $v['id'];
            $where['uid'] = ['in', $ids];
            //昨日新增业绩
            $insert_data['new_achievement'] = db('user_mining')->where($where)->sum('amount');
            //昨日结算矿池收益
            $extracted_where['time'] = $where['create_time'];
            $extracted_where['uid'] = $where['uid'];
            $insert_data['new_profit'] = db('mining_extracted')->where($extracted_where)->sum('settlement_income');
            //昨日新增下级返利收益
            $insert_data['new_child_profit'] = db('money_flow')->where($where)->sum('number');
            //昨日新增交易量
            $profit_where['order_status'] = 3;
            $profit_where['done_time'] = ['between', "$yesterday,$today"];
            $profit_where['seller_id|buyer_id'] = ['in', $ids];
            $insert_data['new_vol'] = db('order')->where($profit_where)->sum('order_number');
            $insert_data['time'] = time();
            $insert_data['price'] = $this->average_price($profit_where);

            $recharge_where['recharge_status'] = 1;
            $recharge_where['create_time'] = ['between', "$yesterday,$today"];
            $recharge_where['uid'] = ['in', $ids];
            $insert_data['turn'] = db('recharge')->where($recharge_where)->sum('number');
            //转到外部钱包量
            $recharge_where['recharge_type'] = 2;

            $insert_data['turn_out'] = db('recharge')->where($recharge_where)->sum('number');

            //从外部钱包转入量
            $recharge_where['recharge_type'] = 1;
            $insert_data['turn_into'] = db('recharge')->where($recharge_where)->sum('number');
            $insert_data['uid'] = $v['id'];
            $insert[] = $insert_data;
        }
        db('user_report')->insertAll($insert);
        return rtn(1, lang('success'));
    }

    /**
     * @param $where
     * @return float|int
     */
    public function average_price($where)
    {
        unset($where['seller_id|buyer_id']);
        $total = db('order')->where($where)->sum('order_number*price');
        $number = db('order')->where($where)->sum('order_number');
        $average_price = 0;
        if ($total) {
            $average_price = $total / $number;
        }
        return $average_price;
    }

    /**
     * 汇率
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function exchange_rate()
    {
        $url = 'http://web.juhe.cn:8080/finance/exchange/rmbquot?type=1&bank=&key=7df910d3ef0a89a922cab13af95eedc1';
        $get_data = file_get_contents($url);
        $data = (array)json_decode($get_data);
        $data = (array)$data['result'][0];
        $price = $data['美元']->fBuyPri / 100;
        db('config')->where('key', 'USDT_RMB')->update(['value' => $price]);
    }

    public function increase()
    {
        $value = config('BEST_BUY_PRICE') + config('BEST_BUY_PRICE') * (config('PERCENT') * 0.01);
        Db::name('config')->where('key', 'BEST_BUY_PRICE')->update(['value' => $value]);
    }

    public function global_income()
    {
        $start = strtotime(date("Y-m-d", strtotime("last month +1 day")));
        $end = $start + 86400;
        $map['six_level_time'] = ['between', "$start,$end"];
        $map['level'] = 6;
        $list = db('user')->where($map)->field('id,level')->select();
        $all_user = db('user')->field('id,level,parent_id')->order('id asc')->select();
        if ($list) {
            foreach ($list as $k => $v) {
                $global = db('global')->where('uid', $v['id'])->find();
                if ($global) {
                    if ($global['number'] != 0) {
                        model('UserMining')->add_money($v['id'], $global['number'], 1, 3);
                        db('global')->where('uid', $v['id'])->update(['number' => 0]);
                        db('user')->where('id', $v['id'])->update(['six_level_time' => strtotime(date("Y-m-d", strtotime("+1 day")))]);
                    }
                }
            }
        }
    }
}
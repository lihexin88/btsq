<?php
/**
 * Created by 李.
 * User: Administrator
 * Date: 2018/12/13
 * Time: 14:51
 */

namespace app\api\model;

use think\Db;
use think\Exception;
use think\Model;
use app\api\model\User as UserModel;
use app\api\model\UserMessage;

class UserMining extends Model
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }


    /**
     * 用户矿机总投入量
     * @param $user
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_mining($user_common, $where = null)
    {
        $user_mining = $this->where(['uid' => $user_common['id']])->where($where)->field('sum(amount) as amount')->select();
        return $user_mining;
    }


    /**
     * 查询用户周新增矿机投入量,
     * @param $user_common
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_weekily_mining($user_common,$map)
    {
        $now_smtp = time();
        $where['create_time'] = [
            'between', [($now_smtp - 7 * 24 * 60 * 60), $now_smtp]
        ];
        foreach ($user_common as $k => $v) {
            $user_week_mining[$k] = self::user_mining($v, $where)->toArray()[0];
        }
        $max = max($user_week_mining);
        $new_step = $max['amount'] / 45;
        return $new_step;
    }


    /**
     * 获取用户伞下总业绩（贡献值）
     * @param $user_account
     * @return float|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function get_achieve_step($user,$user2,$map,$number)
    {
        $total_achieve = [];
        $max = 0;
        foreach ($user2 as $k => $v) {
            $total_achieve[$k]['id'] = $v['id'];
            $uids=GetTeamMember($user ,$v['id']);
            if($uids){
                $map['uid'] = ['in',$uids];
                $achieve = db('user_mining')->where($map)->sum('amount');
                $total_achieve[$k]['achieve'] = $achieve?$achieve:0;
            }else{
                $total_achieve[$k]['achieve'] = 0;
            }
            if($max < $total_achieve[$k]['achieve']){
                $max = $total_achieve[$k]['achieve'];
            }
        }
        $achieve_step = $max / $number;
        $return = [];
        foreach ($total_achieve as $k => $v) {
            if($achieve_step == 0){
                $return[$v['id']] = 0;
            }else{
                $return[$v['id']] = $number-floor(($max-$v['achieve'])/$achieve_step);
            }
        }
        return $return;
    }

    /**
     * 直推贡献值
     */
    static public function get_inv_step($user)
    {
        foreach ($user as $k => $v) {
            $inv_socre = 0;
            $inv_number = db('user')->where('parent_id',$v['id'])->count();
            if($inv_number == 0){
                $inv_socre = 0;
            }elseif ($inv_number == 1) {
                $inv_socre = 0;
            }elseif ($inv_number == 2 || $inv_number == 3) {
                $inv_socre = 2;
            }elseif ($inv_number == 4 || $inv_number == 5) {
                $inv_socre = 3;
            }elseif ($inv_number == 6 || $inv_number == 7) {
                $inv_socre = 4;
            }elseif ($inv_number == 8 || $inv_number == 9) {
                $inv_socre = 5;
            }elseif ($inv_number == 10 || $inv_number == 11) {
                $inv_socre = 6;
            }elseif ($inv_number == 12 || $inv_number == 13) {
                $inv_socre = 7;
            }elseif ($inv_number == 14 || $inv_number == 15) {
                $inv_socre = 8;
            }elseif ($inv_number == 16 || $inv_number == 17) {
                $inv_socre = 9;
            }elseif ($inv_number == 18 || $inv_number == 19) {
                $inv_socre = 10;
            }else{
                $inv_socre = 10;
            }

            if($v['level'] == 2){
                $inv_socre += 2;
            }elseif ($v['level'] == 3) {
                $inv_socre += 4;
            }elseif ($v['level'] == 4) {
                $inv_socre += 6;
            }elseif ($v['level'] == 5) {
                $inv_socre += 8;
            }elseif ($v['level'] == 6) {
                $inv_socre += 10;
            }
            $return[$v['id']] = $inv_socre;
        }
        return $return;
    }

    /**
     * 统计用户矿机
     * @param $user
     * @return int|string
     */
    static public function mining_list($user)
    {
        $count = self::where(['uid' => $user['id']])->order('create_time desc')->select();
        return $count;
    }


    /**
     * 获取用户的最后一条矿机记录
     * @param $user
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function last_mining($user)
    {
        $last_time = self::where(['uid' => $user['id']])->max('create_time');
        $last = self::where(['create_time' => $last_time, 'uid' => $user['id']])->find();
        return $last;
    }


    /**
     * 添加矿机
     * @param $post 矿机数组
     * @param $user 用户
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_usermining($post, $user)
    {
        /*增加用户矿机*/
        $usermining = $this;
        $usermining->uid = $user['id'];
        $usermining->amount = $post['mining_number'];
//        随机订单号
        $usermining->number = generateOrderNumber();

//        计算用户投资额
        $total_invest = db('user_money')->where('uid',$user['id'])->sum('invest');
        $usermining->spend_money_left = $total_invest;
        $usermining->spend = 0;
        /*减少用户投资额*/
        if ($total_invest < $post['mining_number']) {
            throw new Exception("可用投资额不足");
        }
        $UserMoney = new UserMoney();
        $UserMoney->where(['uid'=>$user['id']])->setDec('invest',$post['mining_number']);
        model('User')->daily_user_level($user,$post['mining_number']);
        $user['level'] = db('user')->where('id',$user['id'])->value('level');
        $usermining->user_level = $user['level'];
        $user_coin = model('UserCoin')->dec_user_coin2($user['id'], 1,$post['mining_number']);
        $left = $post['mining_number'];
        $mining_log['uid'] = $user['id'];
        $mining_log['create_time'] = time();
        foreach ($user_coin as $k => $v) {
            $v_left = $v['price'] * $v['amount'];
            if ($v_left < $left) {
                $left -= $v['total'];
                $usermining->spend += $v['total']/$v['price'];
                $user_coin_update['amount'] = 0;
                $user_coin_update['total'] = 0;
                db('user_coin')->where('id',$v['id'])->update($user_coin_update);

                $mining_log['coin_id'] = $v['id'];
                $mining_log['number'] = $v['amount'];
                $mining_log['price'] = $v['price'];
                db('mining_log')->insert($mining_log);
            } else {
                $v['total'] -= $left;
                $user_coin_update['amount'] = $v['total'] / $v['price'];
                $user_coin_update['total'] = $v['total'];
                db('user_coin')->where('id',$v['id'])->update($user_coin_update);
                $usermining->spend += $left/$v['price'];
                $mining_log['coin_id'] = $v['id'];
                $mining_log['number'] = $left/$v['price'];
                $mining_log['price'] = $v['price'];
                db('mining_log')->insert($mining_log);
                break;
            }
        }
        if($user['id'] == 28){
            $user['level'] == 0;
        }
        if($user['level'] == 0){
            $usermining->all_reword = $usermining->spend * 2;
        }else{
            $usermining->all_reword = $usermining->spend * (db('vip_details')->where('vid',$user['level'])->value('max_mining_save'));
        }
        // 添加 矿机算力
        $user_mining['uid'] = $user['id'];
        $user_mining['amount'] = $post['mining_number'];
        $user_mining['spend'] = $usermining->spend;
        $user_mining['user_level'] = $user['level'];
        $user_contribution = db('user_contribution')->column('uid,contribution');
        $mining_data['max'] = db('user_mining')->max('amount');
        $mining_data['min'] = db('user_mining')->min('amount');
        $all_mining = db('user_mining')->field('id,uid,spend')->order('spend asc,id asc')->select();
        $mining_reword = $this->mining_power($user_mining,$user_contribution,$mining_data,$all_mining);
        $usermining->power = $mining_reword['power'];
        // 添加 全网算力
        $mining_where['status'] = 1;
        $mining_where['mining_status'] = 1;
        $net_power = $this -> where($mining_where) -> sum('power');
        $usermining->net_power = $net_power+$mining_reword['power'];
        // 修改所有矿机的全网算力
//        $this ->where('1=1')-> update(['net_power' => $usermining->net_power]);
        if (!$usermining->save()) {
            throw new Exception("添加失败");
        }


        /*向上反馈开矿投资额*/
        RewordToParent::reword_to_parent($user,$post['mining_number']);
        return true;

    }

    /**
     * 计算挖矿收益
     * @param $reword_config 收益配置
     * @param $user_mining 用户矿机
     * @return bool
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function mining_reword($user_mining,$user_contribution,$mining_data,$all_mining)
    {
        $user = db('user')->where('id',$user_mining['uid'])->find();
        //矿机贡献值偏差、矿池投资额偏差、矿池的日算力
        $mining_reword = $this->mining_power($user_mining,$user_contribution,$mining_data,$all_mining);
        /*获取vip等级矿机收益存储量*/
        $all_mining = $user_mining['total_reword'] + $mining_reword['power'];
        if ($all_mining > $user_mining['all_reword']) {
            $user_mining_update['reword'] = $user_mining['all_reword']-$user_mining['total_reword'];
            $user_mining_update['total_reword'] = $user_mining['all_reword'];
            $user_mining_update['mining_status'] = 0;//矿机报废

            // 记录到"通知中心"
            $user = db('user')->where('id',$user_mining['uid'])->find();
            $UserMessage = new UserMessage();
            $info['title'] = '矿池报废';
            $info['first_content'] = '您在';
            $info['second_content'] = '报废的矿池投资额为：';
            $info['order'] = $user_mining['amount']?$user_mining['amount']:0;
            $info['msg'] = ",请联系管理员及时删除该矿机";
            $data = [
                'user_info' => $user,
                'data' => $info,
            ];
            $message_result = $UserMessage -> create_user_message($data);
            if($message_result['code'] === 0){
                return rtn(0, Language::lang($message_result['msg'], $this->userInfo));
            }
        }else{
            $user_mining_update['reword'] = $mining_reword['power'];
            $user_mining_update['total_reword'] = $user_mining['total_reword']+$mining_reword['power'];
        }
        $user_mining_update['power'] = $mining_reword['power'];
        /*该用户保存*/

        db('user_mining')->where('id',$user_mining['id'])->update($user_mining_update);
        $mining_extracted['uid'] = $user_mining['uid'];
        $mining_extracted['mining_id'] = $user_mining['id'];
        $mining_extracted['settlement_income'] = $mining_reword['power'];
        $mining_extracted['unextracted_income'] = $mining_reword['power'];
        $price = db('order')->where('order_status',3)->order('id desc')->value('price');
        $mining_extracted['price'] = $price?$price:config('INITIAL_PRICE');
        $mining_extracted['extracted_income'] = 0;
        $mining_extracted['contribution_deviation'] = $mining_reword['mining_contribution'];
        $mining_extracted['investment_deviation'] = $mining_reword['money_contribution'];
        $mining_extracted['time'] = time();
        db('mining_extracted')->insert($mining_extracted);

        /*直推反馈收益*/
        $reword_config = Config::feedback_reword();

        /*向上推15代*/
        $times = 1;
        $user_level = db('user')->where('id',$user_mining['uid'])->value('level');
        $this->parent_reword($user_mining['uid'], $mining_reword['power'], $times, $reword_config,$user_mining['uid'],$user_level,0,1);
        return true;
    }
    
    //算力

    /**
     * @param $user_mining
     * @param $contributions
     * @param $mining_data
     * @param $all_mining
     * @return mixed
     */
    public function mining_power($user_mining, $contributions, $mining_data, $all_mining)
    {
        if(array_key_exists($user_mining['uid'], $contributions)){
            $user_contribution = $contributions[$user_mining['uid']];
        }else{
            $user_contribution = 50;
        }



        /*平均收益*/
        $avg_reward = config('MINING_REWORD') / 100;

        //矿机贡献值偏差
        // $max = max($contributions);
        // $achieve_step = ($max-50)/2 / config('MINING_REWORD_RANG');
        // $return['mining_contribution'] = floor(100+config('MINING_REWORD_RANG')-($max-$user_contribution)/$achieve_step)/100;

        $score = array_search($user_mining['uid'], array_column($contributions, 'uid'))+1;
        $return['mining_contribution'] = $this->computation_bias(count($contributions),$score);
        //投资额偏差
        // $step = ($mining_data['max']-$mining_data['min'])/2 / config('INVESTMENT_RANG');
        // if($step){
        //     $return['money_contribution'] = floor(100+config('INVESTMENT_RANG')-($mining_data['max']-$user_mining['amount'])/$step)/100;
        // }else{
        //     $return['money_contribution'] = 1;
        // }
        $score = array_search($user_mining['id'], array_column($all_mining, 'id'))+1;
        $return['money_contribution'] =  $this->computation_bias(count($all_mining),$score);
        //用户算力
        $return['power'] = $avg_reward * $user_mining['spend'] * $return['mining_contribution'] * $return['money_contribution'];

        /*普通用户收益减半*/
        if ($user_mining['amount'] < 100) {
            $return['power'] = $avg_reward * $user_mining['spend'] /2;
        }
        return $return;  
    }

    public function computation_bias($all,$position)
    {   
        $each = floor($all/21);//多少人一份
        $position_101 = $all - $each*10+1;//第一个101分人的位置
        $position_99 = $each*10;//最后一个99分人的位置
        $score = 100;
        if($position > $position_101){
            $score = 101+floor(($position - $position_101)/$each);
        }
        if($position <=$position_99){
            $score = 99-floor(($position_99 - $position)/$each);
        }
        return $score/100;
    }

    /**
     * 收益向上递归
     * @param $user
     * @param $mining_reword
     * @param $times
     * @param $reword_config
     * @throws Exception
     * @throws \think\exception\DbException
     */
    private function parent_reword($user, $mining_reword, $times, $reword_config,$child_id,$user_level,$rate,$global)
    {
        /*获取父级id*/
        $pid = User::get(['id' => $user]);
        $pid = $pid['parent_id'];
        /*不存在就跳出递归*/
        if (!$pid) {
            return;
        }
        $level = db('user')->where('id',$pid)->value('level');
        //分享收益
        $status = 1;
        $user_count_where['parent_id'] = $pid;
        $user_count_where['level'] = ['neq',0];
        $user_count = db('user')->where($user_count_where)->count();
        if($user_count<$times){
            $status = 0;
        }
        if($level<2){
            //vip2以下没有分享收益
            $status = 0;
        }
        if($times > 15){
            //15代以上没有分享收益
            $status = 0;
        }

        if($status == 1){
            $feedback_reword = null;
            /*根据代数进行计算用户的反馈收益*/
            if ($times < 2) {
                $feedback_reword = $mining_reword * $reword_config['one'];
            }
            if ($times > 1 && $times < 6) {
                $feedback_reword = $mining_reword * $reword_config['two'];
            }
            if ($times > 5 && $times < 11) {
                $feedback_reword = $mining_reword * $reword_config['six'];
            }
            if ($times > 10) {
                $feedback_reword = $mining_reword * $reword_config['eleven'];
            }
            $this->add_money($pid,$feedback_reword,$child_id,1);
        }
        //无限代收益
        if($level > 2){
            if($level == 3){
                $level_rate = 0.06;
            }elseif ($level == 4) {
                $level_rate = 0.09;
            }else{
                $level_rate = 0.12;
            }
            if(($level_rate-$rate)>0){
                $feedback_reword = $mining_reword * ($level_rate-$rate);
                $this->add_money($pid,$feedback_reword,$child_id,2);
                $user_level = $level;
                $rate = $level_rate;
            }
            $gt_status = 1;
        }

        //全球分红
        if($level>4){
            if($global == 1){
                $global = $global+1;
            }elseif ($global == 2) {
                if($level == 6){
                   $global_data = db('global')->where('uid',$pid)->find();
                    $number = $mining_reword * config('GLOBAL_GAINS')/100;
                    if($global_data){
                        db('global')->where('uid',$pid)->setInc('number',$number);
                    }else{
                        $data['uid'] = $pid;
                        $data['number'] = $number;
                        db('global')->insert($data);
                    }
                    $global_record['uid'] = $pid;
                    $global_record['trader_id'] = $child_id;
                    $global_record['number'] = $number;
                    $global_record['create_time'] = time();
                    db('global_record')->insert($global_record);
                    $global = $global+1;
                }
                
            }
        }

        /*递归向上返*/
        $times++;
        $this->parent_reword($pid, $mining_reword, $times, $reword_config,$child_id,$user_level,$rate,$global);
    }

    public function add_money($pid,$feedback_reword,$child_id,$type)
    {
         /*增加流水记录*/
        $flow_user['uid'] = $pid;
        $MoneyFlow = new MoneyFlow();
        $MoneyFlow->add_flow($flow_user, $feedback_reword,$child_id,$type);

        /*增加用户资金*/
        $user_coin_insert['uid'] = $pid;
        $user_coin_insert['amount'] = $feedback_reword;
        $user_coin_insert['price'] = db('order')->where('order_status',3)->order('id desc')->value('price');
        $user_coin_insert['total'] = $feedback_reword*$user_coin_insert['price'];
        $user_coin_insert['create_time'] = time();
        $user_coin_insert['update_time'] = time();
        $user_coin_insert['type'] = 8;//分享收益
        if ($type == 2) {
            $user_coin_insert['type'] = 9;//无限代收益
        }elseif ($type == 3) {
            $user_coin_insert['type'] = 11;//全球分红
        }
        $user_coin_insert['all_amount'] = $feedback_reword;
        db('user_coin_profit')->insert($user_coin_insert);
         //用户增加资金
        $user_money = db('user_money_profit')->where('uid',$pid)->find();
        $user_money_update['total'] = $user_money['total'] + $user_coin_insert['total'];
        $user_money_update['update_time'] = time();
        $user_money_update['amount'] = $user_money['amount'] + $user_coin_insert['amount'];
        $user_money_update['invest'] = $user_money['invest'] + $user_coin_insert['total'];
        db('user_money_profit')->where('uid',$pid)->update($user_money_update);
    }

    /**
     * 提取收益
     * @param $mining
     * @param $type  类型 1手动提取 2自动提取
     * @return bool
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function mining_withdraw($mining,$type)
    {

        /*用户增加资金记录*/
        $insert_data['uid'] = $mining['uid'];
        $insert_data['cur_id'] = 1;
        $today = strtotime(date("Y-m-d"),time())+46800;
        $yesterday = strtotime(date("Y-m-d"),time())-39600;
        $order_where['cur_id'] = 1;
        $order_where['order_status'] = 3;
        $order_where['done_time'] = ['between',"$yesterday,$today"];
        $insert_data['price'] = db('order')->where($order_where)->avg('price');
        if(!$insert_data['price']){
            $insert_data['price'] = db('order')->where(['order_status'=>3,'cur_id'=>1])->order('done_time desc')->value('price');
        }
        $insert_data['amount'] = $mining['reword'];
        $insert_data['total'] = $mining['reword']*$insert_data['price'];
        $insert_data['create_time'] = time();
        $insert_data['update_time'] = time();
        $insert_data['type'] = 5;
        $insert_data['all_amount'] = $insert_data['amount'];
        db('user_coin_profit')->insert($insert_data);
        //用户增加资金
        $user_money = db('user_money_profit')->where('uid',$mining['uid'])->find();
        $user_money_update['total'] = $user_money['total'] + $insert_data['total'];
        $user_money_update['update_time'] = time();
        $user_money_update['amount'] = $user_money['amount'] + $insert_data['amount'];
        $user_money_update['invest'] = $user_money['invest'] + $insert_data['total'];
        db('user_money_profit')->where('uid',$mining['uid'])->update($user_money_update);
        $fee = $this->automatic_withdrawal_fee($type,$mining['uid']);
        //扣除自动提取的费用
        $this->deduct_money($mining['uid'],$fee);
        //用户币记录
        $mining_extracted_where['uid'] = $mining['uid'];
        $mining_extracted_where['mining_id'] = $mining['id'];
        $mining_extracted_where['status'] = 0;
        $mining_extracted_info = db('mining_extracted')->where($mining_extracted_where)->find();
        if($mining_extracted_info){
            $mining_extracted_update['unextracted_income'] = 0;
            $mining_extracted_update['extracted_income'] = $mining_extracted_info['settlement_income'];
            $mining_extracted_update['price'] = $insert_data['price'];
            $mining_extracted_update['status'] = 1;
            db('mining_extracted')->where('id',$mining_extracted_info['id'])->update($mining_extracted_update);
        }
        /*矿机收益重置*/
        $mining_update['reword'] = 0;
        $mining_update['update_time'] = time();
        if(!db('user_mining')->where('id',$mining['id'])->update($mining_update)){
            throw new Exception("保存失败");
        }
        // /*直推反馈收益*/
        // $reword_config = Config::feedback_reword();

        // /*向上推15代*/
        // $times = 1;
        // $user_level = db('user')->where('id',$mining['uid'])->value('level');
        // $this->parent_reword($mining['uid'], $insert_data['amount'], $times, $reword_config,$mining['uid'],$user_level,0);
        return true;
    }

    public function deduct_money($uid,$money)
    {
        $where['uid'] = $uid;
        $where['total'] = ['neq',0];
        $user_coin = db('user_coin_profit')->where($where)->order('id asc')->select();
        $left = $money;
        $all_amount = 0;
        foreach ($user_coin as $k => $v) {
            $v_left = $v['price'] * $v['amount'];
            if ($v_left < $left) {
                $left -= $v['total'];
                $update['amount'] = 0;
                $update['total'] = 0;
                $all_amount += $v['amount'];
            } else {
                $v['total'] -= $left;
                $update['amount'] = number_format($v['total'] / $v['price'], 3);
                $update['total'] = $v['total'];
                $all_amount += $left/$v['price'];
                break;
            }
            db('user_coin_profit')->where('id',$v['id'])->update($update);
            $money_info = db('user_money_profit')->where('uid',$uid)->find();
            $money_update['total'] = $money_info['total']-$money;
            $money_update['amount'] = $money_info['amount']-$all_amount;
            db('user_money_profit')->where('uid',$uid)->update($money_update);
        }
        return true;
    }

    public function automatic_withdrawal_fee($type,$uid)
    {
        $money = 0;
        if($type == 2){
            $level = db('user')->where('id',$uid)->value('level');
            if($level == 0 || $level == 1){
                $money = 0.2;
            }elseif ($level == 3) {
                $money = 0.15;
            }else{
                $start_time = db('auto_withdraw')->where('uid',$uid)->value('start_time');
                if($start_time){
                    if(date('Y-m-d') == date('Y-m-d',$start_time)){
                        $money = 3;
                        db('auto_withdraw')->where('uid',$uid)->update(['start_time'=>$start_time+86400]);
                    }
                }else{
                    $money = 3;
                    db('auto_withdraw')->where('uid',$uid)->update(['start_time'=>$start_time+86400]);
                }
            }
        }
        return $money;
    }

    /**
     * 一键开启关闭全部矿机
     * @param $user
     * @return bool
     * @throws \think\exception\DbException
     */
    public function switch_all($user)
    {
        $user_mining = self::get(['uid'=>$user['id'],'status'=>1]);
        $user_minings = self::where(['uid'=>$user['id']])->update(['status'=>$user_mining?0:1]);
        return true;
    }




    /**
     * 获取用户新增
     * @param $user 用户--对象
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function add_mining_weekily($user)
    {
        /*获取当前时间到一周前*/
        $now_time = time();
        $last_week = $now_time - 7 * 24 * 60 * 60;

        /*获取用户新增矿机投入量*/
        $add_where['create_time'] = [
            'between',[$last_week,$now_time]
        ];
        $user_mining = self::where(['uid'=>$user['id']])
            ->where($add_where)
            ->field('sum(amount) as amount')
            ->select();
        return $user_mining;
    }

}
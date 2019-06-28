<?php

namespace app\api\model;

use app\api\controller\Transaction;
use think\Exception;
use think\Lang;
use think\Model;
use think\Db;
use app\api\model\User as UserModel;
use app\api\model\Currency;
use app\api\model\UserMessage;
use think\Request;

class Trade extends Model
{

    /**
     * 返还挂卖用户相应数量
     * @param $id
     * @param $number
     * @return array
     * @throws Exception
     */
    public function back_trade_number($id, $number)
    {
        if (false === $this->where('id', $id)->setInc('number', $number)) {
            return ['code' => 0];
        }
        $this->where('id', $id)->update(['trade_status' => 1]);
        return ['code' => 1];
    }

    /**
     * 获取挂单信息
     * @param $where
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_trade_info_by_where($where)
    {
        $trade = $this->where($where)->find();
        return $trade;
    }

    /**
     * 判断用户是否为连续惩罚
     * @param $uid
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_last_trade($uid)
    {
        $trade = $this->where('uid', $uid)->order('id DESC')->find();
        if ($trade['trade_status'] === 4 && $trade['matching'] === 1) {
            return 1;
        } else {
            return 2;
        }
    }

    /**
     * 取消某一用户的所有未匹配挂单
     * @param $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel_trades($uid)
    {
        $UserCoinProfit = new UserCoinProfit();

        $trade_where['uid'] = $uid;
        $trade_where['trade_status'] = 1;
        $trade_where['number'] = ['neq', 0];
        $trade_where['trade_type'] = 1;
        $trades = $this->where($trade_where)->select();
        foreach ($trades as $k => $v) {
            $money_result = model('UserMoney')->get_back_user_money($v);
            if ($money_result['code'] === 0) {
                throw new Exception($money_result['msg']);
            }
        }
        $where['uid'] = $uid;
        $where['trade_status'] = 1;
        $where['number'] = ['neq', 0];
        $this->where($where)->update(['trade_status' => 4]);
        return ['code' => 1];
    }

    /**
     * 交易首页信息
     * @param $datas
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_info($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];
        if (!$data['type']) {
            $data['type'] = 2;
        }
        if (!$data['cur_id']) {
            $data['cur_id'] = 1;
        }
        $Order = new Order();
        switch ($data['type']) {
            case 1: // 卖出
                // 最佳卖价
                if (config('BEST_BUY_PRICE')) {   // 原为:BEST_SELL_PRICE
                    $best_sell_num = config('BEST_BUY_PRICE');  // 原为:BEST_SELL_PRICE
                    $return['best_sell_num'] = number_format($best_sell_num, 2);
                } else {
                    $best_sell_num = $Order->best_num(1, $data['cur_id']);
                    $return['best_sell_num'] = number_format($best_sell_num, 2);
                }
                break;
            case 2: // 买入
                // 最佳买价
                if (config('BEST_BUY_PRICE')) {
                    $best_buy_num = config('BEST_BUY_PRICE');
                    $return['best_buy_num'] = number_format($best_buy_num, 2);
                } else {
                    $best_buy_num = $Order->best_num(2, $data['cur_id']);
                    $return['best_buy_num'] = number_format($best_buy_num, 2);
                    break;
                }
        }
        // 成交量
        $volume = $Order->get_volume();
        // 最新价
        $new_price_usd = $Order->new_price();
        $new_price_usd = $new_price_usd ? $new_price_usd : config('INITIAL_PRICE');
        $new_price_cny = $new_price_usd * config('USDT_RMB');
        // 24h涨跌 日涨跌 = ((今天最后一笔交易 - 昨天最后一笔交易)/昨天最后一笔记交易) * 100
//        $day_rise_fall = $Order->day_rise_fall();
        $CurMarket = new CurMarket();
        $day_rise_fall = $CurMarket->get_day_rise_fall($data['cur_id']);
        if (strstr($day_rise_fall, '-') === false) {
            $day_rise_fall_color = 'red';
        } else {
            $day_rise_fall_color = 'green';
        }

        $return['volume'] = number_format($volume, 5);    // 成效量
        $return['new_price_usd'] = number_format($new_price_usd, 2);  // 最新USDT价格
        $return['new_price_cny'] = number_format($new_price_cny, 2);  // 最新cny价格
//        $return['day_rise_fall'] = $day_rise_fall['day_rise_fall']; // 最新日涨跌
        $return['day_rise_fall'] = $day_rise_fall;
//        $return['day_rise_fall_color'] = $day_rise_fall['day_rise_fall_color']; // 日涨跌颜色
        $return['day_rise_fall_color'] = $day_rise_fall_color;
        return rtn(1, '', $return);
    }

    /**
     * 买入范围
     * @param $datas
     * @return string
     * @throws \think\exception\DbException
     */
    public function buy_range($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];
        // 买入范围
        $range['min'] = config('BEST_BUY_LOW');
        $range['max'] = config('BEST_BUY_HIGH');

        return $range;
    }

    /**
     * 买入总价
     * @return mixed
     */
    public function buy_total($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

        $total_usd = $data['number'] * $data['price'];
        $total_cny = $total_usd * config('USDT_RMB');

        $return = $total_usd . 'USD≈' . $total_cny . '¥';
        return $return;
    }

    /**
     * 最大可卖
     * @param $datas
     * @return mixed
     */
    public function max_sell_num($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];
        if (!$data['cur_id']) {
            $data['cur_id'] = 1;
        }
        if (!$data['cur_id']) {
            return ['code' => 0, 'msg' => '未获取交易币种信息'];
        }

        // 最大可卖
        $UserMoney = new UserMoney();
        $money_where['uid'] = $userinfo['id'];
        $money_where['cur_id'] = $data['cur_id'];
        $user_bdc = db('user_money_profit')->where($money_where)->value('amount');
        $user_bdc = $user_bdc / (1 + config('SELL_SERVICE_CHARGE') / 100);
        if (!$user_bdc) {
            $user_bdc = 0;
        }
        return $user_bdc;
    }

    /**
     * 最大可卖提示
     * @param $userinfo
     * @return string
     * @throws \think\exception\DbException
     */
    public function max_sell_tips($userinfo)
    {
        $vip_detail = VipDetails::get(['vid' => $userinfo['level']]);
        if (!$vip_detail) {
            $text = '按照您的用户等级每天可以最大卖出99美金,每天最多可以卖出1次';
        } else {
            $text = '按照您的用户等级每天可以最大卖出' . number_format($vip_detail['max_trade_price']) . '美金,每天最多可以卖出' . '次';
        }
        return $text;
    }

    /**
     * 卖出总价
     * @param $datas
     * @return mixed
     */
    public function sell_total($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

        $total_usd = $data['number'] * $data['price'];
        $total_cny = $total_usd * config('USDT_RMB');

        $return['total'] = $total_usd . 'USD≈' . $total_cny . '¥';
        if (config('SELL_SERVICE_CHARGE')) {
            $return['service_charge'] = $return['total'] * config('SELL_SERVICE_CHARGE') / 100;
        } else {
            $return['service_charge'] = $return['total'];
        }
        return $return;
    }

    /**
     * 获取用户财务信息
     * @param $datas
     * @return mixed
     */
    public function get_user_finance($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];
        if (!$data['type']) {
            $data['type'] = 2;
        }
        if (!$data['cur_id']) {
            $data['cur_id'] = 1;
        }
        if (!$data['cur_id']) {
            return ['code' => 0, 'msg' => '未获取交易币种信息'];
        }

        $UserMoney = new UserMoney();
        // usdt兑换cny比例
        $usdt_cny = config('USDT_RMB');
        // 用户USD资产
        $money_where['uid'] = $userinfo['id'];
        $money_where['cur_id'] = 2;
        $usdt = $UserMoney->get_user_total($money_where);
        if (!$usdt) {
            $usdt = 0;
        }
        // 用户其它币种资产
        $money_where['cur_id'] = $data['cur_id'];
        $other = $UserMoney->get_user_total($money_where);
        if (!$other) {
            $other = 0;
        }
        // 矿工费比例
        switch ($data['type']) {
            case 1:
                $service_charge = config('SELL_SERVICE_CHARGE');
                break;
            case 2:
                $service_charge = config('BUY_SERVICE_CHARGE');
                break;
        }
        $service_charge = config('SELL_SERVICE_CHARGE') / 100;
        $return['usdt_cny'] = number_format($usdt_cny, 2);
        $return['usdt'] = number_format($usdt, 2);
        $return['other'] = $other;
        $return['service_charge'] = $service_charge;
        return $return;
    }

    /**
     * 通过用户ID获取用户今天交易的次数
     * @param $uid
     * @return int|string
     */
    public function get_user_traded($uid)
    {
        $trade_where['uid'] = $uid;
        $trade_where['trade_type'] = 1;
        $user_traded = $this->where($trade_where)->whereTime('start_time', 'today')->count();
        return $user_traded;
    }

    /**
     * 交易(挂买&挂卖)
     * @param $datas
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function do_trade($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];
        if (!$userinfo['phone']) {
            return rtn(0, Language::lang('请先绑定手机', $userinfo));
        }
        // 判断用户当前交易的 最大次数 和 每次交易的最大数额
        if ($userinfo['level'] != 0) {
            $vip_rights_detail = VipDetails::get(['vid' => $userinfo['level']]);
            // 用户每次交易的数额
            switch ($data['type']) {
                case 1: // 出售
                    // 用户当天交易的次数
                    $user_traded = $this->get_user_traded($userinfo['id']);
                    if ($user_traded >= $vip_rights_detail['p2p']/*如果交易次数超出*/) {
                        $words_first = "当前vip";
                        $words_second = "每天最多可交易";
                        $words_third = "次";
                        return rtn(
                            0,
                            Language::lang($words_first, $userinfo)
                            . $userinfo['level']
                            . Language::lang($words_second)
                            . $vip_rights_detail['p2p']
                            . Language::lang($words_third)
                        );
                    }
                    $max_price = $data['price'] * $data['number'];  // 转换为USDT
                    if ($max_price > $vip_rights_detail['max_trade_price']) {
                        $price_text = "当前vip" . $userinfo['level'] . "单笔出售数量换算为USD,已超过" . number_format($vip_rights_detail['max_trade_price']) . 'USD';
                        return rtn(0, Language::lang($price_text, $userinfo));
                    }
                    $pay = Db::name('user_pay')->field('alipay_account,alipay_name,wechat_nick,wechat_img,paypal')->where('uid', $userinfo['id'])->find();
                    $bank = Db::name('user_bank')->field('bid,open_name,bank_card,bank_add')->where(['uid' => $userinfo['id'], 'default' => 1])->find();
                    if (!$pay['alipay_account'] && !$pay['wechat_img'] && !$pay['paypal'] && !$bank['bank_card']) {
                        return rtn(0, Language::lang('请先设置收款信息', $userinfo));
                    }
                    break;
                case 2: // 求购
                    $max_price = $data['price'] * $data['number'];
                    if ($max_price < config('BEST_BUY_LOW')) {
                        $price_text = "单笔不能少于" . config('BEST_BUY_LOW') . "USD";
                        return rtn(0, Language::lang($price_text, $userinfo));
                    }
                    if ($max_price > config('BEST_BUY_HIGH')) {
                        $price_text = "单笔不能超过" . config('BEST_BUY_HIGH') . "USD";
                        return rtn(0, Language::lang($price_text, $userinfo));
                    }
                    // if ($max_price > $vip_rights_detail['max_trade_price']) {
                    //     $price_text = "当前vip" . $userinfo['level'] . "单笔最多可交易" . number_format($vip_rights_detail['max_trade_price']) . 'USD';
                    //     return rtn(0, Language::lang($price_text, $userinfo));
                    // }
                    break;
            }
        } else {
            // 用户每次交易的数额
            switch ($data['type']) {
                case 1: // 出售
                    $user_traded = $this->where(['uid' => $userinfo['id'], 'trade_type' => 1])->whereTime('start_time', 'week')->count();
                    if ($user_traded >= 1) {
                        $number_text = "当前vip" . $userinfo['level'] . "每周最多可交易1次";
                        return rtn(0, Language::lang($number_text, $userinfo));
                    }
                    $max_price = $data['price'] * $data['number'];  // 转换为USDT
                    if ($max_price > 100) {
                        $price_text = "当前vip" . $userinfo['level'] . "单笔最多可交易100USD";
                        return rtn(0, Language::lang($price_text, $userinfo));
                    }
                    break;
                case 2: // 求购
                    $max_price = $data['price'] * $data['number'];
                    if ($max_price < config('BEST_BUY_LOW')) {
                        $price_text = "单笔不能少于" . config('BEST_BUY_LOW') . "USD";
                        return rtn(0, Language::lang($price_text, $userinfo));
                    }
                    if ($max_price > config('BEST_BUY_HIGH')) {
                        $price_text = "单笔不能超过" . config('BEST_BUY_HIGH') . "USD";
                        return rtn(0, Language::lang($price_text, $userinfo));
                    }
                    break;
            }
        }

        // 判断可执行交易值不为空
        if (!$data['price'] || !$data['number'] || !$data['pwd']) {
            return rtn(0, Language::lang("不能为空", $userinfo));
        } else {
            if ($data['price'] <= 0 || $data['number'] <= 0) {
                return rtn(0, Language::lang("数值不能小于0", $userinfo));
            }
            $data['price'] = sprintf('%.4f', $data['price']);
            $data['number'] = sprintf('%.2f', $data['number']);

//            验证支付密码
            if ($userinfo['payment_password'] != encrypt(trim($data['pwd']))) {
                return rtn(0, Language::lang("支付密码不正确", $userinfo));
            }
            // 在交易表中插入数据
            if ($data['type'] == 1) {    // 卖 条件
                $map['uid'] = $userinfo['id'];
                $map['cur_id'] = $data['cur_id'];
                $cur_total = db('user_money_profit')->where($map)->value('amount');
                $all_number = $data['number'] * (1 + config('SELL_SERVICE_CHARGE') / 100);
                if ($cur_total < $all_number) {
                    return rtn(0, Language::lang("币数量不足", $userinfo));
                }
            }

            // 判断该用户是否可以交易
            $UserConfig = new UserConfig();
            $user_config = $UserConfig->config($userinfo);
            if ($user_config['transaction_status'] != 1) {
                return rtn(0, Language::lang("暂停交易", $userinfo));
            }
            // 判断用户是否有冻结交易时间
            if ($userinfo['punishment_time'] != 0) {
                $time = time();
                if ($userinfo['punishment_time'] >= $time) {
                    return rtn(0, Language::lang("冻结交易", $userinfo));
                }
            }

            // 获取币种的 价格
            $Currency = new Currency();
            $cur_info = $Currency->get_rise_fall($data['cur_id']);
            if ($cur_info['rise_fall']) {
                // 今日的开盘价
                $open_price = Db::name('trade')
                    ->where('cur_id', $data['cur_id'])
                    ->whereTime('start_time', 'today')
                    ->order('start_time ASC')
                    ->value('price');
                // 判断是否为今天第一笔挂单
                if ($open_price) {
                    $up = sprintf('%.4f', $open_price + $open_price * ($cur_info['rise_fall'] * 0.01));        // 交易涨幅
                    $down = sprintf('%.4f', $open_price - $open_price * ($cur_info['rise_fall'] * 0.01));        // 交易跌幅
                    if ($data['price'] > $up || $data['price'] < $down) {
                        $msg1 = Language::lang('单价应在', $userinfo);
                        $msg2 = Language::lang('和', $userinfo);
                        $msg3 = Language::lang('之间!', $userinfo);
                        return json_encode(array('code' => 0, 'msg' => $msg1 . $up . $msg2 . $down . $msg3, 'data' => array(),));
                    } else {
                        Db::startTrans();
                        try {
                            $this->do_trade_try($data, $userinfo);
                            Db::commit();
                            return rtn(1, Language::lang("成功", $userinfo));
                        } catch (\Exception $e) {
                            Db::rollback();
                            return rtn(0, Language::lang($e->getMessage(), $userinfo));
                        }
                    }
                } else {
                    Db::startTrans();
                    try {
                        $this->do_trade_try($data, $userinfo);
                        Db::commit();
                        return rtn(1, Language::lang("成功", $userinfo));
                    } catch (\Exception $e) {
                        Db::rollback();
                        return rtn(0, Language::lang($e->getMessage(), $userinfo));
                    }
                }
            } else {
                Db::startTrans();
                try {
                    $this->do_trade_try($data, $userinfo);
                    Db::commit();
                    return rtn(1, Language::lang("成功", $userinfo));
                } catch (\Exception $e) {
                    Db::rollback();
                    return rtn(0, Language::lang($e->getMessage(), $userinfo));
                }
            }
        }
    }

    /**
     * 执行交易
     * @param $data
     * @param $userinfo
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function do_trade_try($data, $userinfo)
    {
        // 执行挂单
        $insert_data['uid'] = $userinfo['id'];
        $insert_data['number'] = $data['number'];
        $insert_data['price'] = $data['price'];
        $insert_data['start_time'] = time();
        $insert_data['trade_type'] = $data['type'];
        $insert_data['cur_id'] = $data['cur_id'];
        $insert_data['all_number'] = $data['number'];
        $last_trade_id = $this->insertGetId($insert_data);
        $Transction = new Transaction();
        $step = $Transction->get_step();    // 获取用户插队百分比
        $position = $Transction->get_insert_position($data['type'], $step);
        Trade::insert_trade($position, $last_trade_id, $data['type']); // 新订单插队
        // 扣除用户相应的金额
        $UserCoinProfit = new UserCoinProfit();
        if ($data['type'] == 1) {    // 卖
            // 修改"用户币价值表"中的信息
            $all_number = $data['number'] * (1 + config('SELL_SERVICE_CHARGE') / 100);
            $UserCoinProfit->dec_user_coin_profit($userinfo['id'], $data['cur_id'], $all_number);
        }
        // 去掉 USDT 部分
//                    else {    // 买
//                        // 修改"用户币价值表"中的信息
//                        $number = $data['number'] * $data['price'];
//                        $UserCoin->dec_user_coin($userinfo['id'], 2, $number);
//                    }
        // 判断是否存在对应交易的挂单数据,如果存在则直接交易并扣除手续费
        if ($data['type'] == 1) {    // 卖
            $uid = $userinfo['id'];
            $sell = $this->suitable_trader_sell($last_trade_id, $uid, 2, $data['cur_id'], $data['price'], $data['number']);
//                        return rtn(0, '', $sell);
            if ($sell['code'] === 0) {
                throw new Exception($sell['msg']);
            }
        } else {    // 买
            $uid = $userinfo['id'];
            $buy = $this->suitable_trader_buy($last_trade_id, $uid, 1, $data['cur_id'], $data['price'], $data['number']);
//                        return rtn(0,'',$buy);
            if ($buy['code'] === 0) {
                throw new Exception($buy['msg']);
            }
        }

        // 插入币种行情统计表
        $Order = new Order();
        $cur_market = $Order->cur_market($data['cur_id']);
        if ($cur_market['code'] === 0) {
            throw new Exception($cur_market['msg']);
        }

        // 记录到"通知中心"
        $UserMessage = new UserMessage();
        $info['title'] = '交易挂单';
        $info['first_content'] = '您在';
        $info['second_content'] = '挂单成功,请及时查看。';
        $data = [
            'user_info' => $userinfo,
            'data' => $info,
        ];
        $message_result = $UserMessage->create_user_message($data);
        if ($message_result['code'] === 0) {
            throw new Exception($message_result['msg']);
        }
    }

    // 买

    /**
     * @param $last_trade_id
     * @param $uid
     * @param $trade_type
     * @param $cur_id
     * @param $price
     * @param $number
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function suitable_trader_buy($last_trade_id, $uid, $trade_type, $cur_id, $price, $number)
    {
        // 获取匹配到相应的数据
        $exist_where['trade_type'] = $trade_type;    // 1卖
        $exist_where['cur_id'] = $cur_id;    // 币种
        $exist_where['price'] = array('<=', $price);        // 单价
        $exist_where['trade_status'] = 1;    // 挂卖中
        $exist_where['number'] = array('neq', 0);
        $exist_where['uid'] = array('neq', $uid);    // 不等于自己
        $exist = Db::name('trade')->where($exist_where)->select();
        if ($exist) {
            // 根据插队条件对获取到的二维数组排序
            $sort = array(
                'direction' => 'SORT_ASC',        //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field' => 'next',            //排序字段
            );
            $arrSort = array();
            foreach ($exist as $k => $v) {
                foreach ($v as $k2 => $v2) {
                    $arrSort[$k2][$k] = $v2;
                }
            }
            if ($sort['direction']) {
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $exist);
            }

            // 将查询出来的数据进行匹配交易
            $num = $number;
            $time = 1;
            foreach ($exist as $k => $v) {
                if ($time > 10) {
                    return ['code' => 1];
                }
                // 插入订单条件
                $history_map = [];
                $history_map['buyer_id'] = $uid;
                $history_map['trade_buy_id'] = $last_trade_id;
                $history_map['seller_id'] = $v['uid'];
                $history_map['trade_sell_id'] = $v['id'];
                $history_map['order'] = generateOrderNumber();
                $history_map['order_number'] = $number;
                $history_map['price'] = $v['price'];
                $history_map['order_status'] = 1;
                $history_map['addtime'] = time();
                $history_map['trade_type'] = 2;
                $history_map['cur_id'] = $v['cur_id'];
                // 数量正好匹配
                if ($num == $v['number']) {
                    // 插入订单
                    $order_id = Db::name('order')->insertGetId($history_map);    // 历史交易人
                    $this->send_msg($history_map);
                    if (!$order_id) {
                        return ['code' => 0, 'msg' => '插入订单失败'];
                    }

                    // 修改交易表状态
                    $trade_mod['matching'] = 2;
                    $trade_mod['number'] = 0;
                    $trade_mod['trade_status'] = 5;
                    if (false === Db::name('trade')->where('id', 'in', [$v['id'], $last_trade_id])->update($trade_mod)) {    // 历史交易人
                        return ['code' => 0, 'msg' => '修改交易人失败'];
                    }

//                    删除卖家节点
                    self::delete_link($last_trade_id);
//                    删除买家
                    self::delete_link($v['id']);

                    return ['code' => 1];
                }
                // 实际购买大于挂单数量
                if ($num > $v['number']) {
                    // 插入订单
                    $order_id = Db::name('order')->insertGetId($history_map);    // 历史交易人
                    $this->send_msg($history_map);
                    if (!$order_id) {
                        return ['code' => 0, 'msg' => '插入订单失败'];
                    }

                    // 修改交易表状态
                    $trade_mod['matching'] = 2;
                    $trade_mod['number'] = 0;
                    $trade_mod['trade_status'] = 5;
                    if (false === Db::name('trade')->where('id', $v['id'])->update($trade_mod)) {    // 历史交易人
                        return ['code' => 0, 'msg' => '修改交易表状态失败'];
                    }
//                    删除挂卖单
                    self::delete_link($v['id']);

                    // 修改已交易的挂卖信息并将多出的部分再次挂卖
                    $mod_trade['number'] = $num - $v['number'];
                    $mod_trade['matching'] = 2;
                    if (false === Db::name('trade')->where('id', $last_trade_id)->update($mod_trade)) {
                        return ['code' => 0, 'msg' => '修改已交易的挂卖信息失败'];
                    }
                    $num = $num - $v['number'];
                    $time++;
                }
                // 实际购买小于挂单数量
                if ($num < $v['number']) {
                    // 插入订单
                    $order_id = Db::name('order')->insertGetId($history_map);    // 历史交易人
                    $this->send_msg($history_map);
                    if (!$order_id) {
                        return ['code' => 0, 'msg' => '插入订单失败'];
                    }

                    // 修改交易表状态
                    $trade_mod['matching'] = 2;
                    $trade_mod['number'] = 0;
                    $trade_mod['trade_status'] = 5;
                    if (false === Db::name('trade')->where('id', $last_trade_id)->update($trade_mod)) {    // 历史交易人
                        return ['code' => 0, 'msg' => '修改交易表状态失败'];
                    }
//                    删除挂买单
                    self::delete_link($last_trade_id);

                    // 修改已交易的挂卖信息并将多出的部分再次挂卖
                    $mod_trade['number'] = $v['number'] - $num;
                    $mod_trade['matching'] = 2;
                    if (false === Db::name('trade')->where('id', $v['id'])->update($mod_trade)) {
                        return ['code' => 0, 'msg' => '修改已交易的挂卖信息失败'];
                    }
                    $num = $v['number'] - $num;
                    $time++;
                }
            }
            return ['code' => 1];
        } else {
            return ['code' => 1];
        }
    }

    // 修改购买信息

    /**
     * @param $cur_id
     * @param $uid
     * @param $price
     * @param $exist_price
     * @param $exist_number
     * @param $exist_uid
     * @return array
     * @throws Exception
     */
    protected function mod_buy_info($cur_id, $uid, $price, $exist_price, $exist_number, $exist_uid)
    {
        $UserCoin = new UserCoin();
        $User = new User();

        // 判断是否需要返回给买家的差价
        $back_num = sprintf('%.4f', ($price - $exist_price) * $exist_number);
//        $back_result = $UserCoin->inc_user_coin($uid, 2, $back_num, $price, 2);
//        if ($back_result['code'] === 0) {
//            return ['code' => 0, 'msg' => $back_result['msg']];
//        }
        $add_back_result = $User->record_total_invest($uid, $back_num);
        if ($add_back_result['code'] === 0) {
            return ['code' => 0, 'msg' => $add_back_result['msg']];
        }

        // 计算手续费 修改用户交易金额
        if (config('BUY_SERVICE_CHARGE')) {
            $buy_num = sprintf('%.4f', $exist_number - ($exist_number * config('BUY_SERVICE_CHARGE')));
        } else {
            $buy_num = $exist_number;
        }
        $buy_result = $UserCoin->inc_user_coin($exist_uid, $cur_id, $buy_num, $price, 2);
        if ($buy_result['code'] === 0) {
            return ['code' => 0, 'msg' => $buy_result['msg']];
        }
        $add_buy_result = $User->record_total_invest($exist_uid, $buy_num);
        if ($add_buy_result['code'] === 0) {
            return ['code' => 0, 'msg' => $add_buy_result['msg']];
        }

        // 计算手续费 修改卖家交易金额
//        $service_charge = sprintf('%.4f', $exist_price * $exist_number * config('BUY_SERVICE_CHARGE'));
//        $sell_num = sprintf('%.4f', $exist_price * $exist_number - $service_charge);
//        $sell_result = $UserCoin->inc_user_coin($uid, 2, $sell_num, $price, 2);
//        if ($sell_result['code'] === 0) {
//            return ['code' => 0, 'msg' => $sell_result['msg']];
//        }
    }

    // 卖

    /**
     * @param $last_trade_id
     * @param $uid
     * @param $trade_type
     * @param $cur_id
     * @param $price
     * @param $number
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function suitable_trader_sell($last_trade_id, $uid, $trade_type, $cur_id, $price, $number)
    {
        $exist_where['trade_type'] = $trade_type;    // 2买
        $exist_where['cur_id'] = $cur_id;    // 币种
        // $exist_where['price'] = array('>=',$price);		// 单价
        $exist_where['trade_status'] = 1;    // 挂卖中
        $exist_where['number'] = array('neq', 0);
        $exist_where['uid'] = array('neq', $uid);    // 不等于自己
        $exist = Db::name('trade')->where($exist_where)->where('price >=' . $price)->select();
        if ($exist) {
            // 根据插队条件对获取到的二维数组排序
            $sort = array(
                'direction' => 'SORT_DESC',        // 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field' => 'next',            // 排序字段
            );
            $arrSort = array();
            foreach ($exist as $k => $v) {
                foreach ($v as $k2 => $v2) {
                    $arrSort[$k2][$k] = $v2;
                }
            }
            if ($sort['direction']) {
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $exist);
            }

            // 将查询出来的数据进行匹配交易
            $num = $number;
            foreach ($exist as $k => $v) {
                // 插入订单条件
                $history_map['buyer_id'] = $v['uid'];
                $history_map['trade_buy_id'] = $v['id'];
                $history_map['seller_id'] = $uid;
                $history_map['trade_sell_id'] = $last_trade_id;
                $history_map['order'] = generateOrderNumber();
                $history_map['order_number'] = $v['number'];
                $history_map['price'] = $v['price'];
                $history_map['order_status'] = 1;
                $history_map['addtime'] = time();
                $history_map['done_time'] = time();
                $history_map['trade_type'] = 1;
                $history_map['cur_id'] = $v['cur_id'];

                // 数量正好匹配
                if ($num == $v['number']) {
                    // 插入订单
                    $order_id = Db::name('order')->insertGetId($history_map);    // 历史交易人
                    $this->send_msg($history_map);
                    if (!$order_id) {
                        return ['code' => 0, 'msg' => '插入订单失败'];
                    }

                    // 修改交易表状态
                    $trade_mod['matching'] = 2;
                    $trade_mod['number'] = 0;
                    $trade_mod['trade_status'] = 5;
                    if (false === Db::name('trade')->where('id', 'in', [$v['id'], $last_trade_id])->update($trade_mod)) {    // 历史交易人
                        return ['code' => 0, 'msg' => '修改交易人失败'];
                    }

//                    删除卖家节点
                    self::delete_link($last_trade_id);
//                    删除买家
                    self::delete_link($v['id']);
                    return ['code' => 1];
                }

                // 实际出售小于挂单数量
                if ($num < $v['number']) {
                    // 插入订单
                    $history_map['order_number'] = $num;
                    $order_id = Db::name('order')->insertGetId($history_map);    // 当前交易人
                    $this->send_msg($history_map);
                    if (!$order_id) {
                        return ['code' => 0, 'msg' => '插入订单失败'];
                    }

                    // 修改交易表状态
                    $trade_mod['matching'] = 2;
                    $trade_mod['number'] = 0;
                    $trade_mod['trade_status'] = 5;
                    if (false === Db::name('trade')->where('id', $last_trade_id)->update($trade_mod)) {    // 当前交易人
                        return ['code' => 0, 'msg' => '修改交易表状态失败'];
                    }

                    // 修改已交易的挂卖信息并将多出的部分再次挂卖
                    $mod_trade['number'] = $v['number'] - $num;
                    $mod_trade['matching'] = 2;
                    if (false === Db::name('trade')->where('id', $v['id'])->update($mod_trade)) {
                        return ['code' => 0, 'msg' => '修改挂卖信息失败'];
                    }

//                    删除卖家节点
                    self::delete_link($last_trade_id);

                    return ['code' => 1];
                }
                // 实际出售大于挂单数量
                if ($num > $v['number']) {
                    // 插入订单
                    $order_id = Db::name('order')->insertGetId($history_map);    // 历史交易人
                    $this->send_msg($history_map);
                    if (!$order_id) {
                        return ['code' => 0, 'msg' => '插入订单失败'];
                    }

                    // 修改交易表状态
                    $trade_mod['matching'] = 2;
                    $trade_mod['number'] = 0;
                    $trade_mod['trade_status'] = 5;
                    if (false === Db::name('trade')->where('id', $v['id'])->update($trade_mod)) {    // 历史交易人
                        return ['code' => 0, 'msg' => '修改交易表状态失败'];
                    }

                    // 修改已交易的挂卖信息并将多出的部分再次挂卖
                    $mod_trade['number'] = $num - $v['number'];
                    $mod_trade['matching'] = 2;
                    $num -= $v['number'];
                    if (false === Db::name('trade')->where('id', $last_trade_id)->update($mod_trade)) {
                        return ['code' => 0, 'msg' => '修改已交易的挂卖信息失败'];
                    }
                    self::delete_link($v['id']);
                }
            }
            return ['code' => 1];
        } else {
            return ['code' => 1];
        }
    }

    public function send_msg($users)
    {
        $user = db('user')->where('id', $users['buyer_id'])->find();
        $info['title'] = '匹配成功';
        $info['first_content'] = '您在';
        $info['second_content'] = '生成一个新的订单';
        $data = [
            'user_info' => $user,
            'data' => $info,
        ];
        $UserMessage = new UserMessage();
        $UserMessage->create_user_message($data);

        $user = db('user')->where('id', $users['seller_id'])->find();
        $data = [
            'user_info' => $user,
            'data' => $info,
        ];
        $UserMessage->create_user_message($data);
    }

    // 修改出售信息

    /**
     * @param $cur_id
     * @param $uid
     * @param $price
     * @param $exist_price
     * @param $exist_number
     * @param $exist_uid
     * @return array
     */
    protected function mod_sell_info($cur_id, $uid, $price, $exist_price, $exist_number, $exist_uid)
    {
        $UserCoin = new UserCoin();
        // 计算手续费 修改买家交易金额
        if (config('SELL_SERVICE_CHARGE')) {
            $buy_num = sprintf('%.4f', $exist_number - ($exist_number * config('SELL_SERVICE_CHARGE') / 100));
        } else {
            $buy_num = 0;
        }
        $buy_result = $UserCoin->inc_user_coin($exist_uid, $cur_id, $exist_number, $price, 2);
        if ($buy_result['code'] === 0) {
            return ['code' => 0, 'msg' => $buy_result['msg']];
        }

        // 去掉 USDT 部分
//        // 计算手续费 修改卖家交易金额
//        $sell_num = sprintf('%.4f', $exist_price * $exist_number * (1 - config('SELL_SERVICE_CHARGE')));
//        $sell_result = $UserCoin->inc_user_coin($uid, 2, $sell_num, $price, 1);
//        if ($sell_result['code'] === 0) {
//            return ['code' => 0, 'msg' => $sell_result['msg']];
//        }
    }

    /**
     * 获取交易列表
     * @param $datas
     * @return false|string
     */
    public function trade_list($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

        if (!$data['cur_id']) {
            $data['cur_id'] = 1;
        }

        if (!$data['type']) {
            $data['type'] = 1;
        }

        if ($data['page']) {
            $page_start = $data['page'] * 20 - 20;
        } else {
            $page_start = 0;
        }
        $page_end = 20;

        $trade_where['uid'] = $userinfo['id'];
        switch ($data['type']) {
            case 1: // 出售(挂单中)
                $trade_where['trade_type'] = 1;
                $trade_where['trade_status'] = 1;
                $trade_where['number'] = ['neq', 0];
                $result = $this->get_trade_list_ing($trade_where, $page_start, $page_end, $data['cur_id']);
                break;
            case 2: // 求购(挂单中)
                $trade_where['trade_type'] = 2;
                $trade_where['trade_status'] = 1;
                $trade_where['number'] = ['neq', 0];
                $result = $this->get_trade_list_ing($trade_where, $page_start, $page_end, $data['cur_id']);
                break;
            case 3: // 出售(卖出进度)
                $Order = new Order();
                $order_where['seller_id'] = $userinfo['id'];
                $order_where['order_status'] = array('neq', 4);
                $result = $Order->get_trade_order_list(3, $order_where, $page_start, $page_end);
                break;
            case 4: // 求购(买入进度)
                $Order = new Order();
                $order_where['buyer_id'] = $userinfo['id'];
                $order_where['order_status'] = array('neq', 4);
                $result = $Order->get_trade_order_list(4, $order_where, $page_start, $page_end);
                break;
        }
        return rtn('1', null, $result);
    }

    // 获取挂单中的列表

    /**
     * @param $trade_where
     * @param $page_start
     * @param $page_end
     * @param $cur_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_trade_list_ing($trade_where, $page_start, $page_end, $cur_id)
    {
        $list = $this->where($trade_where)->field('id,start_time,price,number')->limit($page_start, $page_end)->order('id desc')->select();
        foreach ($list as $k => $v) {
            // 开始日期
            $list[$k]['start_date'] = date('Y-m-d H:i:s', $v['start_time']);
            $list[$k]['number'] = number_format($v['number'], 5);
            $list[$k]['price'] = number_format($v['price'], 2);
            unset($v['start_time']);
        }
        // 币种名称
        $Currency = new Currency();
        $cur_text = $Currency->get_name_by_id($cur_id);

        $return['list'] = $list;
        $return['cur_text'] = $cur_text;
        return $return;
    }

    /**
     * 获取单笔挂单详情
     * @param $datas
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function trade_detail($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

        if (!$data['trade_id']) {
            return rtn(0, Language::lang('未获取挂单信息', $userinfo));
        }

        // 当前挂单信息
        $trade = $this->where('id', $data['trade_id'])->field('id,uid,number,price,trade_status,trade_type')->find();
        if (!$trade) {
            return rtn(0, Language::lang('非挂单信息', $userinfo));
        }
        if ($trade['uid'] != $userinfo['id']) {
            return rtn(0, Language::lang('非本人挂单信息', $userinfo));
        }
        switch ($trade['trade_status']) {
            case 1:
                $trade['trade_status_text'] = '挂卖中';
                break;
            case 2:
                $trade['trade_status_text'] = '已付款';
                break;
            case 3:
                $trade['trade_status_text'] = '交易完成';
                break;
            case 4:
                $trade['trade_status_text'] = '挂卖撤销';
                break;
        }
//        if($trade['trade_type'] === null){
//            $trade['payment_method_text'] = '未支付';
//        }else{
//            switch($trade['trade_type']){
//                case 1:
//                    $trade['payment_method_text'] = '银行卡';
//                    break;
//                case 2:
//                    $trade['payment_method_text'] = '微信';
//                    break;
//                case 3:
//                    $trade['payment_method_text'] = '支付宝';
//                    break;
//                case 4:
//                    $trade['payment_method_text'] = 'PayPal';
//                    break;
//            }
//        }
        return $trade;
    }

    /**
     * 撤消挂单
     * @param $datas
     * @return UserMoney|array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel_trade($datas)
    {
        $data = $datas['data'];
        $userinfo = $data['user_info'];

        if (!$data['type']) {
            $data['type'] = 1;
        }

        if (!$data['trade_id']) {
            return rtn(0, Language::lang('未获取挂单信息', $userinfo));
        }

        // 获取挂单信息
        $trade_info = $this->where('id', $data['trade_id'])->find();
        if (!($trade_info['trade_status'] == 1)) {
            return rtn(0, Language::lang("订单状态不正确", $userinfo));
        }
        $UserMoney = new UserMoney();
        switch ($trade_info['trade_type']) {
            case 1: // 出售
                Db::startTrans();
                try {
                    // 返还用户相应的数量
                    $money_result = $UserMoney->get_back_user_money($trade_info);
                    if ($money_result['code'] === 0) {
                        throw new Exception($money_result['msg']);
                    }
                    // 修改挂单状态

                    if (false === $this->where('id', $data['trade_id'])->update(['trade_status' => 4])) {
                        throw new Exception('修改撤消信息失败');
                    }
//                    删除节点
                    self::delete_link($data['trade_id']);
                    Db::commit();
                    return rtn(1, Language::lang('撤消成功', $userinfo));
                } catch (\Exception $e) {
                    Db::rollback();
                    return rtn(0, Language::lang($e->getMessage(), $userinfo));
                }
                break;
            case 2: // 求购
                Db::startTrans();
                try {
                    // 修改挂单状态
                    if (false === $this->where('id', $data['trade_id'])->update(['trade_status' => 4])) {
                        throw new Exception('修改撤消信息失败');
                    }
                    self::delete_link($data['trade_id']);
                    Db::commit();
                    return rtn(1, Language::lang('撤消成功', $userinfo));
                } catch (\Exception $e) {
                    Db::rollback();
                    return rtn(0, Language::lang($e->getMessage(), $userinfo));
                }
                break;
        }
    }

    /**
     * 查看挂单完成详情
     * @param $datas
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view_details($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];
        if (!$data['trade_id']) {
            return rtn(0, Language::lang('未获取挂单信息', $userinfo));
        }
        // 获取挂单信息
        $trade = db('trade')->where('id', $data['trade_id'])->find();
//        dump($trade);exit;
        if (!$trade) {
            return rtn(0, Language::lang('获取挂单信息错误', $userinfo));
        }
        $return['trade_type_text'] = $trade['trade_type'] == 1 ? '卖出' : '买入';
        $return['trade_type_text_color'] = $trade['trade_type'] == 1 ? 'green' : 'red';
        if ($trade['seller_id'] == $userinfo['id']) {
            $return['trade_type_text_color'] = 'green';
        } elseif ($trade['buyer_id'] == $userinfo['id']) {
            $return['trade_type_text'] = '买入';
            $return['trade_type_text_color'] = 'red';
        }
        $return['order'] = $trade['order'] ? $trade['order'] : "--";
        $return['number'] = number_format($trade['all_number'], 3) . ' ' . '个';
        $return['price'] = number_format($trade['price'], 2);
        if ($trade['done_time']) {
            $return['done_date'] = date('Y-m-d H:i:s', $trade['done_time']);
        } else {
            $return['done_date'] = '-';
        }
        $Dict = new Dict();
        $dict_where['type'] = 'order_status';
        $dict_where['value'] = $trade['order_status'];
        $return['order_status_text'] = $Dict->get_dict_key($dict_where);
        $usdt = number_format($trade['all_number'] * $trade['price'], 2) . ' $ ';
        $cny = number_format($usdt * config('USDT_RMB'), 2) . ' ￥ ';
        $return['total'] = $usdt . ' ≈ ' . $cny;
        if ($trade['voucher']) {
            $request = Request::instance();
            $return['voucher'] = $request->domain() . $trade['voucher'];
//            $return['voucher'] = $request->domain() . "/btsq/public" . $trade['voucher'];
        } else {
            $return['voucher'] = '';
        }
        $return['seller_phone'] = db('user')->where('id', $trade['seller_id'])->value('phone');
        $return['buyer_phone'] = db('user')->where('id', $trade['buyer_id'])->value('phone');
        $return['addtime'] = $trade['addtime'] ? date('Y-m-d H:i:s', $trade['addtime']) : '暂无';
        $return['pay_time'] = $trade['pay_time'] ? date('Y-m-d H:i:s', $trade['pay_time']) : '暂无';
        $return['done_time'] = $trade['start_time'] ? date('Y年m月d日 H:i:s', $trade['start_time']) : '暂无';
        return rtn(1, '', $return);
    }

    /**
     * 通过挂单ID获取挂单状态
     * @param $trade_id
     * @return mixed
     */
    public function get_trade_type($trade_id)
    {
        $trade_type = $this->where('id', $trade_id)->value('trade_type');
        return $trade_type;
    }


    /**
     * 递归获取链表
     * @param $now_trade
     * @return string
     * @throws \think\exception\DbException
     */
    static public function get_link_list($now_trade)
    {
        echo $now_trade['id'] . " => ";
        $next = self::get(['id' => $now_trade['next']]);
        if ($next['next']) {
            echo $next['id'] . "<br />";
            return self::get_link_list($next);
        } else {
            echo "null" . "<br />";
            return "结束";
        }
    }

    /**
     * 获取链表下一条
     * @param $this_trade
     * @return Trade|bool|null
     * @throws \think\exception\DbException
     */
    static public function get_next($this_trade, $position)
    {
        if ($position > 0) {
            if ($this_trade['next']) {
                $next = self::get(['id' => $this_trade['next']]);
                $position--;
                return self::get_next($next, $position);
            }
            return $this_trade;
        }
        return $this_trade;
    }

    /**
     * 新订单插队
     * @param $position 插入的位置4-5之间，就传4，
     * @param $this_trade 插入的数据的id
     * @return bool
     * @throws Exception
     * @throws \think\exception\DbException
     */
    static public function insert_trade($position, $this_trade_id, $type)
    {
        /*查询上一个*/
        $last_trade = self::get(['id' => $position['id'], 'trade_type' => $type]);
        /*当前插入的*/
        $this_trade = self::get(['id' => $this_trade_id, 'trade_type' => $type]);
        if (!$this_trade['id']) {
            throw new Exception("插入的订单不存在");
        }
        /*原下一个*/
        $next_trade = null;
        if ($last_trade['next']) {
            $next_trade = self::get(['id' => $last_trade['next'], 'trade_type' => $type]);
        }

        /*更新上一个的尾节点*/
        $last_trade->next = $this_trade['id'];
        /*链接当前节点*/
        $this_trade->next = $next_trade['id'] ? $next_trade['id'] : null;
        if (!($last_trade->force(true)->save() && $this_trade->force(true)->save())) {
            throw new Exception("系统繁忙，请稍候再试");
        }
        return true;
    }

    /**
     * 删除当前节点
     * @param $position 当前节点的id
     * @return bool
     * @throws Exception
     * @throws \think\exception\DbException
     */
    static public function delete_link($position)
    {
        /*上一个*/
        $last_trade = self::get(['next' => $position]);

        /*当前*/
        $this_trade = self::get(['id' => $position]);

        /*下一个*/
        $next_trade = self::get(['id' => $this_trade['next']]);


        $last_trade->next = $next_trade['id'] ? $next_trade['id'] : null;
        $this_trade->next = null;
        try {
            $this_trade->save();
            $last_trade->save();
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 执行支付
     * @param $datas
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function do_pay($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];
        if (!$data['order_id']) {
            return ['code' => 0, 'msg' => Language::lang('未获取订单信息', $userinfo)];
        }
        if (!$data['payment_method']) {
            return ['code' => 0, 'msg' => Language::lang('请选择支付方式', $userinfo)];
        }
        if (!$data['voucher']) {
            return ['code' => 0, 'msg' => Language::lang('请上传支付截图', $userinfo)];
        }

        // 获取订单信息
        $Order = new Order();
        $order_info = $Order->get_order_info_by_id($data['order_id']);
//        return ['code' => 0,'msg' => $order_info];

        Db::startTrans();
        try {
            switch ($order_info['trade_type']) {
                case 1: // 订单状态为 卖
                    // 判断修改买家状态
                    $trade_buy = $this->where('id', $order_info['trade_buy_id'])->find();
                    if ($trade_buy['number'] == 0) {
                        if (false === $this->where('id', $order_info['trade_buy_id'])->update(['trade_status' => 3])) {
                            throw new Exception('修改买家挂单失败');
                        }
                    }
                    // 判断修改卖家状态
                    $trade_sell = $this->where('id', $order_info['trade_sell_id'])->find();
                    if ($trade_sell['number'] == 0 || $order_info['order_number'] == $trade_sell['number']) {
                        if (false === $this->where('id', $order_info['trade_buy_id'])->update(['trade_status' => 2])) {
                            throw new Exception('修改买家挂单失败');
                        }
                    }

                    // 修改卖家状态
                    if (false === $this->where('id', $order_info['trade_sell_id'])->update(['trade_status' => 3])) {
                        throw new Exception('修改卖家挂单失败');
                    }
                    break;

                case 2: // 订单状态为 买
                    // 判断修改卖家状态
                    $trade_sell = $this->where('id', $order_info['trade_sell_id'])->find();
                    if ($trade_sell['number'] == 0) {
                        if (false === $this->where('id', $order_info['trade_sell_id'])->update(['trade_status' => 3])) {
                            throw new Exception('修改卖家挂单失败');
                        }
                    }
                    // 判断修改买家状态
                    $trade_buy = $this->where('id', $order_info['trade_buy_id'])->find();
                    if ($trade_buy['number'] == 0 || $order_info['order_number'] == $trade_sell['number']) {
                        if (false === $this->where('id', $order_info['trade_buy_id'])->update(['trade_status' => 2])) {
                            throw new Exception('修改买家挂单失败');
                        }
                    }

                    // 修改买家状态
                    if (false === $this->where('id', $order_info['trade_buy_id'])->update(['trade_status' => 3])) {
                        throw new Exception('修改买家挂单失败');
                    }
                    break;
            }

            // 修改订单信息
            $order_where['id'] = $data['order_id'];
            $order_mod['order_status'] = 2;
            $order_mod['pay_time'] = time();
            $order_mod['payment_method'] = $data['payment_method'];
            $order_mod['voucher'] = $data['voucher'];
            $order_result = $Order->mod_order($order_where, $order_mod);
            if ($order_result['code'] === 0) {
                throw new Exception($order_result['msg']);
            }

            Db::commit();
            return ['code' => 1, 'msg' => '成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 执行已收款
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function do_done($datas)
    {
        $data = $datas['data'];
        if (!$data['order_id']) {
            return ['code' => 0, 'msg' => '未获取订单信息'];
        }

        // 获取订单信息
        $Order = new Order();
        $order_info = $Order->get_order_info_by_id($data['order_id']);
        Db::startTrans();
        try {
//            增加购买者的币
            $time = time();
            $UserCoin = new UserCoin();
            $sell_result = $UserCoin->inc_user_coin($order_info['buyer_id'], 1, $order_info['order_number'], $order_info['price'], 2);
            if ($sell_result['code'] === 0) {
                return ['code' => 0, 'msg' => $sell_result['msg']];
            }
//           释放出售者的币
            $number_left = $order_info['order_number'];
            $seller_coin = $UserCoin->where(['uid' => $order_info['seller_id']])->select();
            foreach ($seller_coin as $k => $v) {
                if ($number_left > 0) {
                    if ($number_left > $v['amount']) {
                        $number_left -= $v['amount'];
                        $v['amount'] = 0;
                        $v->save();
                    } else {
                        $v['amount'] -= $number_left;
                        $v->save();
                        break;
                    }
                } else {
                    break;
                }
            }

            // 修改订单信息
            $order_where['id'] = $data['order_id'];
            $order_mod['order_status'] = 3;
            $order_mod['done_time'] = time();
            $order_result = $Order->mod_order($order_where, $order_mod);
            if ($order_result['code'] === 0) {
                throw new Exception($order_result['msg']);
            }

            Db::commit();
            return ['code' => 1, 'msg' => '成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }


}



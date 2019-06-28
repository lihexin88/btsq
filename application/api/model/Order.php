<?php

namespace app\api\model;

use think\Model;
use think\Session;
use think\Db;
use think\Exception;
use think\Lang;
use app\api\model\Language;
use think\Request;

class Order extends Model
{

    /**
     * 获取订单信息
     * @param $order_where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_order_list_by_where($order_where)
    {
        $order_list = $this->where($order_where)->select();
        return $order_list;
    }

    /**
     * 设置订单为已取消
     * @param $id
     * @return array
     */
    public function cancel_order($id)
    {
        if (false === $this->where('id', $id)->update(['order_status' => 4])) {
            return ['code' => 0];
        }
        return ['code' => 1];
    }

    /**
     * 获取最新成交价
     * @return mixed
     */
    public function get_transaction_price()
    {
        $order_where['order_status'] = 3;
        $order_where['cur_id'] = 1;
        $transaction_price = $this->where($order_where)->value('price');
        return $transaction_price;
    }

    /**
     * 获取成交量
     * @return float|int
     */
    public function get_volume()
    {
        $order_where['cur_id'] = 1;
        $order_number = $this->where($order_where)->whereTime('done_time', 'today')->sum('order_number');
        return $order_number;
    }

    /**
     * 获取最新价
     * @return mixed
     */
    public function new_price()
    {
        $order_where['cur_id'] = 1;
        $order_where['order_status'] = 3;
        $new_price = $this->where($order_where)->order('done_time DESC')->value('price');
        return $new_price;
    }

    /**
     * 获取日涨跌
     * @return mixed
     */
    public function day_rise_fall()
    {
        $order_where['cur_id'] = 1;
        $today_last_price = $this->where($order_where)->whereTime('done_time', 'today')->order('done_time DESC')->value('price');
        if (!$today_last_price) {
            $today_last_price = $this->where($order_where)->whereTime('done_time', 'yesterday')->order('done_time DESC')->value('price');
        }
        $yesterday_last_price = $this->where($order_where)->whereTime('done_time', 'yesterday')->order('done_time DESC')->value('price');
        if ($today_last_price && $yesterday_last_price) {
            // ((今天最后成交价-昨天最后成交价)/昨天最后成交价)*100
            $day_rise_fall = sprintf('%.2f', (($today_last_price - $yesterday_last_price) / $yesterday_last_price) * 100);
            // 判断日涨跌样式
            if (strstr($day_rise_fall, '-') === false) {
                $day_rise_fall_color = 'red';
            } else {
                $day_rise_fall_color = 'green';
                $day_rise_fall = '+' . $day_rise_fall;
            }
        } else {
            $day_rise_fall = 0;
            $day_rise_fall_color = 'red';
        }

        $return['day_rise_fall'] = $day_rise_fall . '%';
        $return['day_rise_fall_color'] = $day_rise_fall_color;
        return $return;
    }

    /**
     * 最佳买价/卖价
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function best_num($type, $cur_id)
    {
        $buy_where['trade_type'] = $type;
        $buy_where['cur_id'] = $cur_id;
        $group_order = $this->where($buy_where)->field('order_number,price')->group('price')->select();
        if (sizeof($group_order->toArray()) != 0) {
            foreach ($group_order as $k => $v) {
                $order = $this->field('order_number,price')->select();
                $group_order[$k]['all_number'] = 0;
                foreach ($order as $key => $value) {
                    if ($v['price'] === $value['price']) {
                        $group_order[$k]['all_number'] += $value['order_number'];
                    }
                }
            }
            $group = $group_order->toArray();
            // $group 是二维数组
            $sort = array(
                'direction' => 'SORT_DESC',        // 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field' => 'all_number',       // 排序字段
            );
            $arrSort = array();
            foreach ($group as $k => $v) {
                foreach ($v as $k2 => $v2) {
                    $arrSort[$k2][$k] = $v2;
                }
            }
            if ($sort['direction']) {
                array_multisort($arrSort[$sort['field']], constant($sort['direction']), $group);
            }
            $price = sprintf('%.2f', $group[0]['price']);
            return $price;   // 去掉".' USD'"
        } else {
            return '0.00'; // 去掉" USD"
        }
    }


    /**
     * 交易记录
     * @param $datas
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function order_list($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

        $r_msg = array();
        if (!$data['cur_id']) {
            $data['cur_id'] = 1;
        }
        if (!$data['cur_id']) {
            $r_msg['code'] = 0;
            $r_msg['msg'] = "未获取交易币种信息";
            return $r_msg;
        }

        if ($data['page']) {
            $page_start = $data['page'] * 20 - 20;
        } else {
            $page_start = 0;
        }
        $page_end = 20;
        $order_where['cur_id'] = $data['cur_id'];
        //$order_where['order_status'] = array('neq',4);
        $order_list = $this->where('buyer_id|seller_id', $userinfo['id'])->where($order_where)->limit($page_start, $page_end)->field('id,order,buyer_id,trade_buy_id,seller_id,trade_sell_id,order_status')->order('id desc')->select();
        foreach ($order_list as $k => $v) {
            $Trade = new Trade();
            $Dict = new Dict();
            if ($userinfo['id'] === $v['buyer_id']) {    // 买家
                $trade_type = $Trade->get_trade_type($v['trade_buy_id']);
                $dict_where['type'] = 'trade_type';
                $dict_where['value'] = $trade_type;
                $order_list[$k]['trade_type_text'] = $Dict->get_dict_key($dict_where);
                switch ($trade_type) {
                    case 1:
                        $order_list[$k]['trade_type_text_color'] = 'green';
                        break;
                    case 2:
                        $order_list[$k]['trade_type_text_color'] = 'red';
                        break;
                }
            }
            if ($userinfo['id'] === $v['seller_id']) {   // 卖家
                $trade_type = $Trade->get_trade_type($v['trade_sell_id']);
                $dict_where['type'] = 'trade_type';
                $dict_where['value'] = $trade_type;
                $order_list[$k]['trade_type_text'] = $Dict->get_dict_key($dict_where);
                switch ($trade_type) {
                    case 1:
                        $order_list[$k]['trade_type_text_color'] = 'green';
                        break;
                    case 2:
                        $order_list[$k]['trade_type_text_color'] = 'red';
                        break;
                }

            }
            $Dict = new Dict();
            $dict_where['type'] = 'order_status';
            $dict_where['value'] = $v['order_status'];
            $order_list[$k]['order_status_text'] = $Dict->get_dict_key($dict_where);
        }
        return ['code' => 1, 'data' => $order_list];
    }

    /**
     * 通过订单ID获取订单信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_order_info_by_id($id)
    {
        $info = $this->where('id', $id)->find();
        return $info;
    }


    public function get_order_info_by_trade_buy($trade_id, $uid)
    {
        $order_where['trade_buy_id'] = $trade_id;
        $order_where['buyer_id'] = $uid;
        $info = $this->where($order_where)->find();
        return $info;
    }

    /**
     * 修改订单信息
     * @param $order_where
     * @param $order_mod
     * @return array
     */
    public function mod_order($order_where, $order_mod)
    {
        if (false === $this->where($order_where)->update($order_mod)) {
            return ['code' => 0, 'msg' => '修改订单信息失败'];
        } else {
            return ['code' => 1];
        }
    }


    public function latest_deal($id)
    {
        $list = db('order')->where('cur_id', $id)->order('done_time desc')->field('done_time,price,order_number')->limit(20)->select();
        foreach ($list as $k => $v) {
            $list[$k]['price'] = number_format($v['price'], 2);
            $list[$k]['order_number'] = number_format($v['order_number'], 5);
            $list[$k]['done_time'] = date('Y-m-d H:i:s', $v['done_time']);
        }
        return $list;
    }

    // 插入/修改币种行情统计表
    public function cur_market($cur_id)
    {
        // order 表查询条件
        $order_map['cur_id'] = $cur_id;
        $order_map['order_status'] = 3;

        // 获取最新价
        $price_new = $this->where($order_map)->whereTime('done_time', 'today')->order('done_time DESC')->value('price');    // 最新价
        if (!$price_new) {
            $price_new = $this->where($order_map)->whereTime('done_time', 'yesterday')->order('done_time DESC')->value('price');    // 最新价
        }

        // 获取最高价
        $max_price = $this->where($order_map)->whereTime('done_time', 'today')->max('price');
        if (!$max_price) {
            $max_price = $this->where($order_map)->whereTime('done_time', 'yesterday')->max('price');
        }

        // 获取最低价
        $min_price = $this->where($order_map)->whereTime('done_time', 'today')->min('price');
        if (!$min_price) {
            $min_price = $this->where($order_map)->whereTime('done_time', 'yesterday')->min('price');
        }

        // 日成交量
        $volume = $this->where($order_map)->whereTime('done_time', 'today')->sum('order_number');
        if (!$volume) {
            $volume = $this->where($order_map)->whereTime('done_time', 'yesterday')->sum('order_number');
        }

        // 获取日涨跌
        $today_last_price = $this->where($order_map)->whereTime('done_time', 'today')->order('done_time DESC')->value('price');    // 今天最后成交价格
        if (!$today_last_price) {
            $today_last_price = $this->where($order_map)->whereTime('done_time', 'yesterday')->order('done_time DESC')->value('price');
        }
        $yesterday_last_price = $this->where($order_map)->whereTime('done_time', 'yesterday')->order('done_time DESC')->value('price');    // 昨天最后成交价格
        if ($today_last_price && $yesterday_last_price) {
            // ((今天最后成交价-昨天最后成交价)/昨天最后成交价)*100
            $day_rise_fall = sprintf('%.2f', (($today_last_price - $yesterday_last_price) / $yesterday_last_price) * 100);
        } else {
            $day_rise_fall = 0;
        }

        // 获取当天的开盘价(查询当天的 order 表第一条交易记录的价格)
        $today_open_price = $this->where($order_map)->whereTime('done_time', 'today')->order('done_time ASC')->value('price');
        if ($today_open_price) {
            $data['open_price'] = $today_open_price;
        } else {
            $data['open_price'] = 0;
        }

        // 24小时成交量
        $twenty_four_volume = $this->where($order_map)->whereTime('done_time', 'today')->sum('order_number');

        // 一分钟内没有交易
        $last_two = $this->where($order_map)->whereTime('done_time', 'today')->field('done_time')->order('done_time ASC')->limit(2)->select();
        if (($last_two[0]['done_time'] - $last_two[0]['done_time']) > 60) {
            $status = 0;
        } else {
            $status = 1;
        }

        // 判断插入或修改
        $CurMarket = new CurMarket();
        $exist = $CurMarket->is_exist($cur_id);
        if ($exist === true) {
            // 修改 币种行情统计表
            $mod['price_new'] = $price_new;
            $mod['max_price'] = $max_price;
            $mod['min_price'] = $min_price;
            $mod['volume'] = $volume;
            $mod['day_rise_fall'] = $day_rise_fall;
            $mod['open_price'] = $today_open_price;
            $mod['create_time'] = time();
            $mod['twenty_four_volume'] = $twenty_four_volume;
            $mod['status'] = $status;
            if (false === Db::name('cur_market')->where('cur_id', $cur_id)->update($mod)) {
                return ['code' => 0, 'msg' => '修改币种行情统计表失败'];
            } else {
                return ['code' => 1];
            }
        } else {
            // 插入 币种行情统计表
            $in_market['cur_id'] = $cur_id;
            $in_market['price_new'] = $price_new;
            $in_market['max_price'] = $max_price;
            $in_market['min_price'] = $min_price;
            $in_market['volume'] = $volume;
            $in_market['day_rise_fall'] = $day_rise_fall;
            $in_market['open_price'] = $today_open_price;
            $in_market['create_time'] = time();
            $in_market['twenty_four_volume'] = $twenty_four_volume;
            $in_market['status'] = $status;
            if (false === Db::name('cur_market')->insert($in_market)) {
                return ['code' => 0, 'msg' => '添加币种行情统计表失败'];
            } else {
                return ['code' => 1];
            }
        }
    }

    // 获取 买入/卖出 进度
    public function get_trade_order_list($type, $order_where, $page_start, $page_end)
    {
        $request = Request::instance();
        $Trade = new Trade();
        // 买入/卖出 状态
        switch ($type) {
            case 3: // 卖出
                $list = $this->where($order_where)->field('id,seller_id,trade_sell_id')->limit($page_start, $page_end)->order('id desc')->select();
                if ($list) {
                    $list = $list->toArray();
                    foreach ($list as $k => $v) {
                        // 订单信息
                        $order_info = $this->get_order_info_by_id($v['id']);
                        // 订单ID
                        $list[$k]['order_id'] = $order_info['id'];
                        // 订单编号
                        $list[$k]['order'] = $order_info['order'];
                        // 订单价格
                        $list[$k]['total_usdt'] = number_format($order_info['order_number'] * $order_info['price'], 2);
                        $list[$k]['total_cny'] = number_format($list[$k]['total_usdt'] * config('USDT_RMB'), 2);
                        $list[$k]['total_usdt'] = number_format($order_info['order_number'], 5);
                        if ($order_info['voucher']) {
                            $list[$k]['voucher'] = $request->domain() . $order_info['voucher'];
//                            $list[$k]['voucher'] = $request->domain() . "/btsq/public" . $order_info['voucher'];
                        } else {
                            $list[$k]['voucher'] = '';
                        }
                        // 剩余交易时间
                        $time = time(); // 当前时间
                        $interval = 60 * 60 * config('LAST_DONE_TIME');
                        $left_time = $order_info['pay_time'] + $interval - $time;
                        $day = floor($left_time / 86400); // 天数
                        $hour = floor(($left_time - 86400 * $day) / 3600);  // 小时
                        $minute = floor(($left_time - 86400 * $day - 3600 * $hour) / 60); // 分钟
                        $second = floor(($left_time - 86400 * $day - 3600 * $hour - 60 * $minute) / 1); // 秒
                        // 订单状态
                        switch ($order_info['order_status']) {
                            case 1:
                                $list[$k]['operation_type'] = $order_info['order_status'];
                                $list[$k]['operation_text'] = '等待付款';
                                $list[$k]['left_time'] = config('LAST_DONE_TIME') . ':00:00';
                                $list[$k]['left_times'] = $order_info['addtime'] + $interval;;
                                break;
                            case 2:
                                $list[$k]['operation_type'] = $order_info['order_status'];
                                $list[$k]['operation_text'] = '确认收款';
                                $list[$k]['left_time'] = $hour . ':' . $minute . ':' . $second;
                                if ($order_info['time']) {
                                    $list[$k]['left_times'] = $order_info['time'] + $interval;
                                } else {
                                    $list[$k]['left_times'] = $order_info['pay_time'] + $interval;
                                }

                                break;
                            case 3:
                                $list[$k]['operation_type'] = $order_info['order_status'];
                                $list[$k]['operation_text'] = '已确认';
                                $list[$k]['left_time'] = '-';
                                $list[$k]['left_times'] = '-';
                                break;
                            case 5:
                                $list[$k]['operation_type'] = $order_info['order_status'];
                                $list[$k]['operation_text'] = '举报审核中';
                                $list[$k]['left_time'] = '-';
                                $list[$k]['left_times'] = '-';
                                break;
                        }
                    }
                }
                break;
            case 4: // 买入
                $list = $this->where($order_where)->field('id,buyer_id,trade_buy_id')->limit($page_start, $page_end)->order('id desc')->select();
                if ($list) {
                    $list = $list->toArray();
                    foreach ($list as $k => $v) {
                        // 订单信息
                        $order_info = $this->get_order_info_by_id($v['id']);
                        // 卖家ID
                        $list[$k]['seller_id'] = $order_info['seller_id'];
                        // 订单ID
                        $list[$k]['order_id'] = $order_info['id'];
                        // 订单编号
                        $list[$k]['order'] = $order_info['order'];
                        // 订单价格
                        $list[$k]['total_usdt'] = number_format($order_info['order_number'] * $order_info['price'], 2);
                        $list[$k]['total_cny'] = number_format($list[$k]['total_usdt'] * config('USDT_RMB'), 2);
                        $list[$k]['total_usdt'] = number_format($order_info['order_number'], 5);
                        // 剩余交易时间
                        $time = time(); // 当前时间
                        $interval = 60 * 60 * config('LAST_PAY_TIME');
                        $left_time = $order_info['addtime'] + $interval - $time;
                        $day = floor($left_time / 86400); // 天数
                        $hour = floor(($left_time - 86400 * $day) / 3600);  // 小时
                        $minute = floor(($left_time - 86400 * $day - 3600 * $hour) / 60); // 分钟
                        $second = floor(($left_time - 86400 * $day - 3600 * $hour - 60 * $minute) / 1); // 秒
                        // 订单状态
                        switch ($order_info['order_status']) {
                            case 1:
                                $list[$k]['operation_type'] = $order_info['order_status'];
                                $list[$k]['operation_text'] = '去支付';
                                if ($left_time <= 0) {
                                    $list[$k]['left_time'] = '-';
                                    $list[$k]['left_times'] = $order_info['addtime'] + $interval;
                                } else {
                                    $list[$k]['left_time'] = '-';
                                    $list[$k]['left_times'] = $order_info['addtime'] + $interval;
                                }
                                break;
                            case 2:
                                $list[$k]['operation_type'] = $order_info['order_status'];
                                $list[$k]['operation_text'] = '等待确认';
                                $list[$k]['left_time'] = '-';
                                if ($order_info['time']) {
                                    $list[$k]['left_times'] = $order_info['time'] + $interval;
                                } else {
                                    $list[$k]['left_times'] = $order_info['pay_time'] + $interval;
                                }
                                break;
                            case 3:
                                $list[$k]['operation_type'] = $order_info['order_status'];
                                $list[$k]['operation_text'] = '已确认';
                                $list[$k]['left_time'] = '-';
                                $list[$k]['left_times'] = '-';
                                break;
                            case 5:
                                $list[$k]['operation_type'] = $order_info['order_status'];
                                $list[$k]['operation_text'] = '举报审核中';
                                $list[$k]['left_time'] = '-';
                                $list[$k]['left_times'] = '-';
                                break;
                        }
                    }
                }
                break;
        }

        // 币种名称
        $Currency = new Currency();
        $cur_usdt = $Currency->get_name_by_id(2);
        $cur_cny = 'CNY';

        $return['list'] = $list;
        $return['cur_usdt'] = $cur_usdt;
        $return['cur_cny'] = $cur_cny;
        return $return;
    }
}

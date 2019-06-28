<?php

namespace app\api\controller;


use think\Controller;
use app\common\controller\ApiBase;
use app\api\model\Language;
use app\api\model\Order;
use app\api\model\UserCoin;
use app\api\model\UserMoney;
use think\Request;
use think\Session;

class Wallet extends ApiBase
{
    /**
     * ·µ»ØÒøÐÐÐÅÏ¢
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $wallet_list = UserMoney::wallet_list($this->userInfo);
        $Order = new Order;
        $bdc = $Order->order('id desc')->value('price');
        $total_usd = null;
        foreach ($wallet_list as $k => $v) {
            $list['wallet'][$k] = $v;
            $list['wallet'][$k]['usdt'] = $bdc ? $bdc : 0;
            $list['wallet'][$k]['usdt_number'] = $list['wallet'][$k]['usdt'] * $wallet_list[0]['total'];
            $list['wallet'][$k]['usdt'] = 1;
            $list['wallet'][$k]['usdt_number'] = 1 * $wallet_list['wallet'][$k]['total'];
            $total_usd += $list['wallet'][$k]['usdt_number'];
        }
        $total_rmb = $total_usd * 6.8798;
        $list['data'] = [
            'total_usd' => $total_usd,
            'total_rmb' => $total_rmb
        ];
        if ($list) {
            return rtn(1, Language::lang('获得信息', $this->userInfo), $list);
        } else {
            return rtn(0, Language::lang('暂无信息', $this->userInfo));
        }
    }

    //钱包币种 单个 详情
    public function details()
    {
        $post = input('post.');
        if ($post['id'] && $post['type']) {
            $wallet = UserCoin::wallet_list($this->userInfo, $post);
            if ($wallet) {
                return rtn(1, Language::lang('获得信息', $this->userInfo), $wallet);
            } else {
                return rtn(0, Language::lang('暂无信息', $this->userInfo));
            }
        } else {
            return rtn(0, Language::lang('参数不足', $this->userInfo));
        }

    }

    //转账信息
    public function transfer_info()
    {
        $post = input('post.');
        if ($post['id']) {
            $map['uid'] = $this->userInfo['id'];
            $map['cur_id'] = $post['id'];
            $transfer_info['amount'] = UserCoin::transfer_info($map);
            $transfer_info['fee'] = config('MINER_FEE');
            return rtn(1, Language::lang('获得信息', $this->userInfo), $transfer_info);
        } else {
            return rtn(0, Language::lang('参数不足', $this->userInfo), $wallet_list);
        }
    }

    /**
     * 币种详情
     * @param string $id [币种ID]
     * @param string $type [列表类型：1转入，2转出，6失败]
     */
    public function curDetails()
    {
        $request = Request::instance();
        $p = input('p') ? input('p') : 1;
        $page_size = input('page_size') ? input('page_size') : 20;
        $post = input('post.');
        if ($post['id']) {
            $map = [];
            $map['recharge_type'] = input("coin_type") == 1 ? 1 : 2;
            if (input('wallet_type') == 1) {
                $user_money = 'user_money_profit';
                $map['money_type'] = 2;
            } else {
                $user_money = 'user_money';
                $map['money_type'] = 1;
            }
            $user_info = $this->userInfo;
            $user_money = db($user_money)->alias('a')->join('currency b', 'a.cur_id = b.id')->where('uid', $user_info['id'])->field('b.id,a.amount,a.total,b.name,b.icon')->find();
            $result['icon'] = $request->domain() ."/btsq/public". $user_money['icon'];
//            $result['icon'] = $request->domain()."/btsq/public".$user_money['icon'];
            $result['name'] = $user_money['name'];
            $result['number'] = number_format($user_money['amount'], 5);
            $new_price = db('order')->where('order_status', 3)->order('done_time desc')->value('price');
            $result['usdt'] = number_format($user_money['total'], 2);
            $result['cny'] = number_format($result['usdt'] * config('USDT_RMB'), 2);

            $map['cur_id'] = $post['id'];
            $map['uid'] = $user_info['id'];
            if (input('type')) {
                $map['recharge_status'] = input('type');
            }
            $data = model('UserCoin')->curDetails($map, $p, $page_size, $user_money['name']);
            if (!$data) {
                $result['list'] = '';
                return rtn(1, lang("success"), $result);
            } else {
                $result['list'] = $data;
                return rtn(1, lang("success"), $result);
            }
        } else {
            return rtn(0, lang("parameter"));
        }

    }

    //转账信息
    public function transferDetails()
    {
        $request = Request::instance();
        $post = input('post.');
        if ($post['id']) {
            $info = db('Recharge')->where('id', $post['id'])->find();
            if ($info['recharge_status'] == 2) {
                $statusText = '失败';
                $result['status'] = 6;
            } else {
                $statusText = '成功';
                $result['status'] = $info['recharge_type'];
            }
            if ($info['recharge_type'] == 1) {
                $typeText = '转入';
            } else {
                $typeText = '转出';
            }

            $result['statusText'] = $typeText . $statusText;
            $result['time'] = date('Y-m-d H:i:s', $info['create_time']);
            $result['money'] = $info['number'];
            $result['miner_fee'] = $info['fee'];
            $result['receipt_address'] = $info['to_address'];
            $result['payment_address'] = $info['from_address'];
            $result['remarks'] = $info['remake'];
            $result['order_number'] = $info['transaction_number'];
            $result['block'] = $info['block'];
            $result['img'] = $request->domain() . $info['qr_code'];
//            $result['img'] = $request->domain()."/btsq/public"."/btsq/public".$info['qr_code'];
            return rtn(1, lang("success"), $result);
        } else {
            return rtn(0, lang("parameter"));
        }
    }

    /**
     * 转账页面信息
     * @param string $id [币种ID]
     */
    public function transferPage()
    {
        $id = input('id');
        if (!$id) {
            return rtn(0, lang("parameter"));
        } else {
            $map['cur_id'] = $id;
            $map['uid'] = $this->userInfo['id'];
            $result['balance'] = db('user_money_profit')->where($map)->value('amount');
            $result['id'] = $id;
            $result['name'] = db('currency')->where('id', $id)->value('name');
            $result['fee'] = config('MINER_FEE') . '%';
            $result['phone'] = $this->userInfo['phone'];
            $result['email'] = $this->userInfo['email'];
            $email_verify = db('user_config')->where('uid', $this->userInfo['id'])->value('transfer');
            $result['email_verify'] = $email_verify ? $email_verify : 0;
            return rtn(1, lang("success"), $result);
        }
    }

    /**
     * 转账发送手机短信
     */
    public function transferPhone()
    {
        $phone = $this->userInfo['phone'];
        if ($phone) {
            $code = generate_code(6);
            Session::set('authcode', ['code' => $code]);
            Session::set('transfer', ['phone' => 0, 'email' => 0]);
            return rtn(1, lang('success'), $code);
        } else {
            return rtn(0, lang("nobinding_phone"));
        }
    }

    /**
     * 验证手机验证码
     * @param string $code [验证码]
     */
    public function checkPhone()
    {
        $code = input('code');
        if ($code) {
            if ($code == session('authcode.code')) {
                session('transfer.phone', 1);
                Session::delete('authcode');
                return rtn(1, lang('success'));
            } else {
                return rtn(0, lang('phone_error'));
            }
        } else {
            return rtn(0, lang("not_null"));
        }
    }

    /**
     * 转账发送邮箱短信
     */
    public function transferEmail()
    {
        $email = $this->userInfo['email'];
        $code = generate_code(6);
        Session::set('authcode', ['code' => $code]);
        return rtn(1, lang('success'), $code);
    }

    /**
     * 验证邮箱验证码
     * @param string $code [验证码]
     */
    public function checkEmail()
    {
        $code = input('code');
        if ($code) {
            if ($code == session('authcode.code')) {
                session('transfer.email', 1);
                Session::delete('authcode');
                return rtn(1, lang('success'));
            } else {
                return rtn(0, lang('email_error'));
            }
        } else {
            return rtn(0, lang("not_null"));
        }
    }

    /**
     * 转账操作
     * @param string $to_address [地址]
     * @param string $number [钱数]
     * @param string $remake [备注]
     * @param string $id [币种ID]
     */
    public function transferActive()
    {
        $data = input('post.');
        if (!$data['to_address'] || !$data['number'] || !$data['id']) {
            return rtn(0, lang("not_null"));
        } else {
            $user_config = db('user_config')->where('uid', $this->userInfo['id'])->find();
            if ($user_config['transfer_status'] != 1) {
                return rtn(0, lang("no_transfer"));
            }

            if (session('transfer.phone') != 1) {
                return rtn(0, lang("code_error"));
            }
            if ($user_config['transfer'] == 1) {
                if (session('transfer.email') != 1) {
                    return rtn(0, lang("code_error"));
                }
            }
            $fee = config('MINER_FEE') * $data['number'];
            $all_number = $data['number'] * (1 + config('MINER_FEE') / 100);
            $user_money = db('user_money_profit')->where(['uid' => $this->userInfo['id'], 'cur_id' => 1])->value('amount');
            if ($user_money < $all_number) {
                return rtn(0, lang("not_numebr"));
            } else {

                //发送方减去币
                $coin_result = model('UserCoin')->dec_user_coin3($this->userInfo['id'], 1, $all_number);

                $user_wallet = db('user_money')->where(['address' => $data['to_address'], 'cur_id' => $data['id']])->find();
                if (!$user_wallet) {
                    //外部转账
                    $recharge_data['transfer_type'] = 2;
                    if ($data['number'] < config('TRANSFER_LIMIT_OUT')) {
                        $recharge_data['recharge_status'] = 1;
                    } else {

                    }
                } else {
                    $trader_config = db('user_config')->where('uid', $user_wallet['uid'])->find();

                    if ($trader_config['transfer_status'] != 1) {
                        return rtn(0, lang("no_transfer"));
                    }

                    if ($data['number'] < config('TRANSFER_LIMIT')) {
                        //接收方增加币
                        $map['uid'] = $trader_info['uid'];
                        $map['cur_id'] = 1;
                        $map['amount'] = $data['number'];
                        $map['price'] = db('order')->where('cur_id', 1)->order('id desc')->value('price');
                        $map['total'] = $map['amount'] * $map['price'];
                        $map['create_time'] = time();
                        $map['update_time'] = time();
                        $map['type'] = 1;
                        $map['all_amount'] = $data['number'];
                        db('user_coin')->insert($map);
                        $recharge_data['recharge_status'] = 1;
                    }
                }

                // 记录(转出)
                $recharge_data['cur_id'] = $data['id'];
                $recharge_data['uid'] = $this->userInfo['id'];
                $recharge_data['number'] = $data['number'];
                $recharge_data['fee'] = $fee;
                $recharge_data['from_address'] = db('user_money')->where(['uid' => $this->userInfo['id'], 'cur_id' => $data['id']])->value('address');
                $recharge_data['to_address'] = $data['to_address'];
                $recharge_data['remake'] = $data['remake'];
                $recharge_data['transaction_number'] = get_hash();
                $recharge_data['block'] = generate_code();
                $recharge_data['qr_code'] = '';
                $recharge_data['create_time'] = time();
                $recharge_data['recharge_type'] = 2;
                $recharge_data['all_number'] = $all_number;
                $recharge_data['transfer_code'] = $coin_result;
                db('recharge')->insert($recharge_data);

                return rtn(1, lang("success"));
//                }else{
//                    return rtn(0,lang("no_wallet"));
//                }
            }
        }
    }

    /**
     * 提交收款申请
     * @return false|string
     */
    public function into_transfer()
    {
        $data = input('post.');

        if (!$data['to_address'] || !$data['number']) {
            return rtn(0, lang("not_null"));
        }

        // 记录(转入)
        $into_transfer['cur_id'] = 1;
        $into_transfer['uid'] = $this->userInfo['id'];
        $into_transfer['number'] = $data['number'];
        $into_transfer['from_address'] = db('user_money')->where(['uid' => $this->userInfo['id'], 'cur_id' => 1])->value('address');
        $into_transfer['to_address'] = $data['to_address'];
        $into_transfer['remake'] = $data['remake'];
        $into_transfer['transaction_number'] = get_hash();
        $into_transfer['block'] = generate_code();
        $into_transfer['qr_code'] = '';
        $into_transfer['create_time'] = time();
        $into_transfer['recharge_type'] = 1;
        $into_transfer['recharge_status'] = 0;
        $into_transfer['all_number'] = $data['number'];

        if (false === db('recharge')->insert($into_transfer)) {
            return rtn(0, '提交申请失败');
        } else {
            return rtn(1, lang("success"));
        }
    }

    /**
     * 收款
     */
    public function receivablesPage()
    {
        if (!input('post.id')) {
            return rtn(0, lang("parameter"));
        } else {
            $map['uid'] = $this->userInfo['id'];
            $map['cur_id'] = input('id');
            $data = db('user_money')->where($map)->value('address');
            return rtn(1, lang("success"), $data);
        }
    }
}
<?php
return [
     'login_state'                                           => '登录过期',
     'is_phone'                                              => '手机号不存在',
     'no_wallet'                                              => '钱包不存在',
     'phone_notown'                                          => '原手机号错误',
     'nobinding_phone'                                       => '没有绑定手机号',
     'msg_error'                                             => '原信息错误',
     'success'                                               => '成功',
     'error'                                                 => '失败',
     'pwd_diffent'                                           => '两次密码不一致',
     'pwd_type'                                              => '登录密码为长度8~20位的字母数字',
     'pay_pwd_type'                                          => '支付密码为6位数字',
     'phone_diffent'                                         => '输入手机与发送手机不符',
     'phone_error'                                           => '手机验证码错误',
     'email_error'                                           => '邮箱验证码错误',
     'memorizing_words_error'                                => '助记词错误',
     'code_error'                                            => '验证码错误',
     'secret_key_error'                                      => '请输入正确的密钥',
     'not_null'                                              => '不能为空',
     'parameter'                                             => '参数不正确',
     'null'                                                  => '暂无数据',
     'pay_confirm'                                           => '已确认付款',
     'rec_confirm'                                           => '已确认收款',
     'present_has'                                           => '请填写提现金额',
     'paymentpwd_has'                                        => '请填写支付密码',
     'number_has'                                            => '请填写购买个数',
     'own_currency'                                          => '不能购买自己的货币',
     'excess_quantity'                                       => '数量超出',
     'cont_empty'                                            => '不能为空',
     'idcard_error'                                          => '身份证错误',
     'pwd_same'                                              => '新旧密码不能重复',
     'account_exist'                                         => '账号不存在',
     'invitation_code_exist'                                 => '邀请码不存在',
     'account_disable'                                       => '帐号被禁用',
     'incorrect_password'                                    => '密码不正确',
     'execution'                                             => '您不能执行该操作',
     'sale_end'                                              => '该挂卖已结束',
     'already_presented'                                     =>  '本月已提现',
     'no_presented'                                          =>  '本月未提现',
     'account_registered'                                    => '账号已注册',
     'application_not_pass'                                  => '上次申请还不通过',
     'bring_more'                                            => '今日已提现超过上限',
     'cash_more'                                             => '提现金额需大于',
     'not_password'                                          => '支付密码不正确',
     'not_numebr'                                            => '可用数量不足',
     //挖矿
     'ore_mining'                                            => '今日已提取',
     'no_profit'                                             => '暂无收益',
     'no_contribution'                                       => '暂无贡献',
     //用户配置
     'no_transfer'                                            => '禁止转账',
     'transfer_relationship'                                 => '直推关系才能转账',

//游戏竞猜中文返回值
	 'gus_recharged'                                         => '竞猜充值成功',
	 'recharge_failed'                                       => '竞猜充值失败',
	 'low_blance'                                            => '余额不足',
	 'os_error'                                              => '操作失败',
	 'os_success'                                            => '操作成功',
	 'number_error'                                          => '数量错误',
	 'guess_account_success'                                 => '获取账户信息成功',
	 'guess_account_error'                                   => '获取用户信息失败',
	 'wrong_team'                                            => '投注期号锁定',
	 'wrong_dir'                                             => '投注方向锁定',
	 'updated'                                               => '已更新',
	 'inserted'                                              => '已提交',
	 'wrong_secret'                                          => '私钥不正确',
    // 交易开始
    'buy'                       =>  '买入',
    'sell'                      =>  '卖出',
    'not_cur'                   =>  '未获取币种信息!',
    'not_area'                  =>  '未获取交易区信息!',
    'not_cur_area'              =>  '未获取交易对信息!',
    'not_trade_type'            =>  '未获取交易类型!',
    'input_price'               =>  '请输入单价!',
    'input_number'              =>  '请输入数量!',
    'sub_order'                 =>  '提交订单失败!',
    'service_failed'            =>  '扣除手续费失败!',
    'add_num_failed'            =>  '增加数量失败!',
    'mod_trade_failed'          =>  '修改挂单失败!',
    'in_trade_failed'           =>  '插入挂单失败!',
    // 交易结束
    'code_sent'                                             => '验证码已发送',
	 'password_changed'                                      => '密码修改成功',
	 'not_game_time'                                         => '不在游戏时间内',
	 'fail_cancel'                                           => '撤销失败',
	 'info_cant_find'                                        => '查询信息不存在',
	 'number_max'                                            => '本期剩余下注：',
	 'logout'                                                => '已退出登录',
     'red'                                                   => '红方',
     'blue'                                                  => '蓝方',
     'lotteryed'                                             => '已开奖',
     'not_lottery'                                           => '未开奖',
    'win'                                                    => '胜',
    'not_win'                                                => '未中奖',
    'cant_found_info'                                        => '暂无用户认证信息',
    'passed'                                                 => '认证通过',
    'not_auth'                                               => '未处理认证',
    'auth_rejected'                                          => '已拒绝认证',
    'selling'                                                => '挂卖中',
    'tradeing'                                               => '交易中',
    'traded'                                                 => '交易完成',
    'canceled'                                               => '挂卖撤销',
    'bank_card'                                              =>'银行卡',
    'weixin'                                                 =>'微信',
    'zhifubao'                                               =>'支付宝',
    'zidongtiqu1'                                               =>'每天会扣除0.2美金，确认开启自动提取？',
    'zidongtiqu2'                                               =>'每天会扣除0.15美金，确认开启自动提取？',
    'zidongtiqu3'                                               =>'每月会扣除3美金，确认开启自动提取？',



];
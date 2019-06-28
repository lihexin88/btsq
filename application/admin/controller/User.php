<?php

namespace app\admin\controller;

use app\admin\model\UserAuth;
use app\common\controller\AdminBase;
use think\Request;
use think\Db;


class User extends Admin
{

    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {
        $map = [];
        $keywords = input('get.keywords') ? input('get.keywords') : null;
        if ($keywords) {
            $map['a.email'] = array('like', '%' . trim($keywords) . '%');
        }
        if (is_numeric(input('get.status'))) {
            $map['a.status'] = input('get.status');
        }
        if (is_numeric(input('get.time'))) {
            $map['a.login_time'] = ['elt', time() - input('get.time') * 86400];
        }
        $start_number = input('start') ? input('start') : 0;
        $end_number = input('end') ? input('end') : 99999999999;
        $where['amount'] = ['between', "$start_number,$end_number"];
        $ids = db('user_money')->where($where)->column('uid');
        $map['a.id'] = ['in', $ids];
        $start_time = input('start_time') ? strtotime(input('start_time')) + 46800 : 0;
        $end_time = input('end_time') ? strtotime(input('end_time')) + 46800 : 99999999999;
        $map['a.create_time'] = ['between', "$start_time,$end_time"];
        $this->assign("info", model('User')->infoList($map, $p));
        $this->assign("state", model("Common/Dict")->showList('common_state'));//状态
        return $this->fetch();
    }

    public function user_money()
    {
        $id = input('id');
        $list = db('user_money')->alias('a')->join('currency b', 'a.cur_id = b.id')->where('a.uid', $id)->where('a.cur_id', 1)->select();
        return $list;
    }

    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {
        if (Request::instance()->isPost()) {
            $data['email'] = input('email');
            $data['phone'] = input('phone');
            if (input('password')) {
                $data['password'] = encrypt(input('password'));
            }
            if (input('payment_password')) {
                $data['payment_password'] = encrypt(input('payment_password'));
            }
            db('user')->where('id', input('id'))->update($data);
            return array('status' => 1, 'info' => '成功');
        }
        $this->assign("info", model('User')->infodata(array('id' => $id)));
        $this->assign('pagename', '修改用户');
        return $this->fetch('add');
    }

    /**
     * 修改等级
     * @param  string $id ID
     */
    public function edit_level()
    {
        if (Request::instance()->isPost()) {
            $data['level'] = input('level');
            if ($data['level'] == 2) {
                $data['two_level_time'] = time();
            }
            if ($data['level'] == 6) {
                $data['six_level_time'] = time();
            }
            $data['level_time'] = time();
            db('user')->where('id', input('id'))->update($data);
            return array('status' => 1, 'info' => '成功');
        }
    }

    /**
     * 充值扣费
     */
    public function rechargegcu()
    {
        if (Request::instance()->isPost()) {
            $data = input('post.');
            $number = $data['status'] == 6 ? $data['number'] : '-' . $data['number'];
            $insert_data['uid'] = $data['uid'];
            $insert_data['cur_id'] = $data['cur_type'];
            $insert_data['amount'] = $number;
            $price = db('order')->where('cur_id', $data['cur_type'])->order('id desc')->value('price');
            $insert_data['price'] = $price ? $price : 0;
            $insert_data['total'] = $insert_data['amount'] * $insert_data['price'];
            $insert_data['create_time'] = time();
            $insert_data['update_time'] = time();
            $insert_data['type'] = $data['status'];
            $insert_data['all_amount'] = $number;
            if ($data['wallet_type'] == 1) {
                db('user_coin')->insert($insert_data);
            } else {
                db('user_coin_profit')->insert($insert_data);
                $money_info = db('user_money_profit')->where('uid', $data['uid'])->find();
                $money_update['total'] = $money_info['total'] + $insert_data['total'];
                $money_update['update_time'] = time();
                $money_update['amount'] = $money_info['amount'] + $number;
                $money_update['invest'] = $money_info['invest'] + $insert_data['total'];
                db('user_money_profit')->where('uid', $data['uid'])->update($money_update);
            }

            return json(['status' => 1, 'info' => '成功', 'url' => url('index')]);
        }
        $this->assign('id', input('id'));
        $this->assign("cur_list", db('currency')->select());
        $this->assign('pagename', '充值扣费');
        return $this->fetch();
    }

    public function userinfo()
    {
        $id = input('id');
        $user = db('user')->where('id', $id)->field('id,parent_id,email')->select();
        foreach ($user as $k => $v) {
            $data[$k]['id'] = $v['id'];
            $data[$k]['name'] = $v['email'];
            if (db('user')->where('parent_id', $v['id'])->find()) {
                $data[$k]['icon'] = '/upload/group.png';
            } else {
                $data[$k]['icon'] = '/upload/person.png';
            }
        }
        return json($data);
    }

    public function childinfo()
    {
        $id = trim(input('id'));
        $user = db('user')->where('parent_id', $id)->field('id,parent_id,email')->select();
        foreach ($user as $k => $v) {
            $data[$k]['id'] = $v['id'];
            $data[$k]['name'] = $v['email'];
            if (db('user')->where('parent_id', $v['id'])->find()) {
                $data[$k]['icon'] = '/upload/group.png';
            } else {
                $data[$k]['icon'] = '/upload/person.png';
            }
        }
        return json($data);
    }

    public function relationship()
    {
        $id = input('id');
        $keywords = input('keywords') ? input('keywords') : null;
        if ($keywords) {
            $id = db('user')->where('email', $keywords)->value('id');
        }
        if (Request::instance()->isPost()) {
            if ($id) {
                $all_user = db('user')->select();
                return $this->old_child_msg($id, $all_user);
            } else {
                return ['name' => '暂无', 'info' => [['title' => '暂无', 'value' => '暂无']]];
            }
        } else {
            $this->assign('pagename', '组织结构图');
            return $this->fetch();
        }
    }

    /**
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function old_child_msg($id)
    {
        $result = [];
        $user = db('user')->where('id', $id)->field('id,parent_id,email')->find();
        $result['name'] = $user['email'];
        $result['info'][] = ['title' => '直推人数', 'value' => db('user')->where('parent_id', $id)->count()];
        $order_where['buyer_id'] = $id;
        $order_where['order_status'] = 3;
        $result['info'][] = ['title' => '总业绩', 'value' => db('order')->where($order_where)->sum('order_number * price')];
        $result['info'][] = ['title' => '新增业绩', 'value' => db('order')->where($order_where)->whereTime('done_time', 'today')->sum('order_number * price')];
        $child = db('user')->where('parent_id', $id)->field('id,parent_id,email')->select();
        if ($child) {
            foreach ($child as $k => $v) {
                $result['children'][] = $this->old_child_msg($v['id']);
            }
        }
        return $result;
    }


    /**
     * @param $id
     * @param $all_user
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function childMsg($id, $all_user)
    {
        $result = [];
        $user = db('user')->where('id', $id)->field('id,parent_id,email,level')->find();
        $result['name'] = $user['email'];
        $one_clock = strtotime(date('Y-m-d')) + 46800;
        if (time() > $one_clock) {
            $start_time = $one_clock - 1;
            $end_time = $one_clock + 86399;
            $map['time'] = ['between', "$start_time,$end_time"];
        } else {
            $start_time = $one_clock - 86401;
            $end_time = $one_clock - 1;
            $map['time'] = ['between', "$start_time,$end_time"];
        }
        $map['uid'] = $user['id'];
        $settlement_income = db('mining_extracted')->where($map)->sum('settlement_income');
        $result['info'][] = ['title' => 'ID', 'value' => $user['id']];
        $result['info'][] = ['title' => '等级', 'value' => $user['level']];
        $result['info'][] = ['title' => '今日挖矿收益', 'value' => round($settlement_income, 5)];
        $result['info'][] = ['title' => '直推人数', 'value' => db('user')->where('parent_id', $id)->count()];
        $order_where['buyer_id'] = $id;
        $ids = GetTeamMember($all_user, $id);
        $ids[] = $id;
        $where['uid'] = ['in', $ids];
        $result['info'][] = ['title' => '总业绩', 'value' => db('user_mining')->where($where)->sum('amount')];
        $result['info'][] = ['title' => '新增业绩', 'value' => db('user_mining')->where($where)->whereTime('create_time', 'today')->sum('amount')];
        $child = db('user')->where('parent_id', $id)->field('id,parent_id,email')->select();
        if ($child) {
            foreach ($child as $k => $v) {
                $result['children'][] = $this->childMsg($v['id'], $all_user);
            }
        }
        return $result;
    }

    public function user_config($p = 1)
    {
        if (Request::instance()->isPost()) {
            $id = input('id');
            $state = input('state');
            $user_state = db('user_config')->where('id', $id)->value($state);
            $user_update = $user_state == 1 ? 0 : 1;
            db('user_config')->where('id', $id)->update([$state => $user_update]);
            return array('status' => 1, 'info' => '成功');
        } else {
            $map = [];
            $keywords = input('get.keywords') ? input('get.keywords') : null;
            if ($keywords) {
                $map['b.email'] = array('like', '%' . trim($keywords) . '%');
            }
            $this->assign("info", model('User')->configList($map, $p));
            $this->assign('pagename', '用户配置');
            return $this->fetch();
        }

    }

    /**
     * 列表
     * @param  integer $p 页码
     */
    public function recharge_record($p = 1)
    {
        $map['a.type'] = ['in', '6,7'];
        $keywords = input('get.keywords') ? input('get.keywords') : null;
        if ($keywords) {
            $map['b.email'] = array('like', '%' . trim($keywords) . '%');
        }
        if (is_numeric(input('get.type'))) {
            $map['a.type'] = input('get.type');
        }
        $start_time = input('start') ? strtotime(input('start')) + 46800 : 0;
        $end_time = input('end') ? strtotime(input('end')) + 46800 : 99999999999;
        $map['a.create_time'] = ['between', "$start_time,$end_time"];
        $this->assign("info", model('User')->recharge_record($map, $p));
        $this->assign('pagename', '后台充值记录');
        return $this->fetch();
    }

    public function edit_status()
    {
        if (Request::instance()->isPost()) {
            db('user')->where('id', input('id'))->update(['status' => input('status')]);
            return array('status' => 1, 'info' => '成功');
        }
    }
}
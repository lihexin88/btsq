<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;

class MoneyFlow extends Base
{
    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量


    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p  页码
     * @return array      返回列表
     */
    public function infoList($map, $p)
    {

        $request= Request::instance();
        $users = db('money_flow')->where($map)->order('create_time desc')->select();
        $list = [];
        // foreach ($users as $k => $v) {
        //     $list[$k]['create_time'] = $v['create_time'];
        //     $list[$k]['user_name'] = db('user')->where('id',$v['uid'])->value('email');
        //     $map['uid'] = $v['uid'];
        //     $map['type'] = 1;
        //     $list[$k]['share'] = db('money_flow')->where($map)->sum('number');
        //     $map['type'] = 2;
        //     $list[$k]['agent'] = db('money_flow')->where($map)->sum('number');
        // }
        foreach($users as $k => $v){
            $kk = $v['uid'].date('Y-m-d H',$v['create_time']);
            $list[$kk]['create_time'] =$v['create_time'];
            $list[$kk]['user_name'] =db('user')->where('id',$v['uid'])->value('email');
            if($v['type'] == 1){
                $list[$kk]['share'] += round($v['number'],5); 
            }elseif($v['type'] == 2){
                $list[$kk]['agent'] += round($v['number'],5); 
            }else{
                $list[$kk]['global_income'] += round($v['number'],5); 
            }
            $list[$kk]['global_income'] = $list[$kk]['global_income']?$list[$kk]['global_income']:0; 
        }
        $return['count'] = count($list);
        $i = ($p-1) * self::PAGE_LIMIT;
        $list = array_slice($list,$i,self::PAGE_LIMIT);
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $map['type'] = 1;
        $return['all_share'] = db('money_flow') ->where($map) -> sum('number');
        $map['type'] = 2;
        $return['all_agent'] = db('money_flow') ->where($map) -> sum('number');
        $map['type'] = 3;
        $return['all_global_income'] = db('money_flow') ->where($map) -> sum('number');
        return $return;
    }

    public function infoList2($map, $p)
    {

        $request= Request::instance();
        $users = db('money_flow')->where($map)->order('create_time desc')->select();
        $moneyFlowTypeArr = model('Common/Dict')->showKey('money_flow_type');
        $list = [];
        $page_number = 0;
        foreach($users as $k => $v){
            $kk = $v['trader_id'].date('Y-m-d H',$v['create_time']);
            $list[$kk]['create_time'] =$v['create_time'];
            $list[$kk]['user_name'] =db('user')->where('id',$v['uid'])->value('email');
            $list[$kk]['t_name'] =db('user')->where('id',$v['trader_id'])->value('email');
            $list[$kk]['number'] += round($v['number'],5); 
            $list[$kk]['money_flow_type'] = $moneyFlowTypeArr[$v['type']];
            $page_number += $v['number'];
        }
        $return['count'] = count($list);
        $i = ($p-1) * self::PAGE_LIMIT;
        $list = array_slice($list,$i,self::PAGE_LIMIT);
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $return['page_number'] = round($page_number,5);
        $return['all_number'] = round(db('money_flow') ->where($map) -> sum('number'),5);
        return $return;
    }
    // public function infoList2($map, $p, $order)
    // {

    //     $request= Request::instance();
    //     $list = db('money_flow')->where($map)->order($order)->page($p, self::PAGE_LIMIT)->select();
    //     $moneyFlowTypeArr = model('Common/Dict')->showKey('money_flow_type');
    //     $page_number = 0;
    //     foreach ($list as $k => $v) {
    //         $list[$k]['user_name'] = db('user')->where('id',$v['uid'])->value('email');
    //         $list[$k]['t_name'] = db('user')->where('id',$v['trader_id'])->value('email');
    //         $list[$k]['money_flow_type'] = $moneyFlowTypeArr[$v['type']];
    //         $page_number += $v['number'];
    //     }
    //     $return['count'] = $this->where($map)->count();
    //     $return['list'] = $list;
    //     $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
    //     $return['page_number'] = $page_number;
    //     $return['all_number'] = db('money_flow') ->where($map) -> sum('number');
    //     return $return;
    // }

    public function global_record($map, $p, $order)
    {

        $request= Request::instance();
        $users = db('global_record')->where($map)->order($order)->select();
        $list = [];
        $page_number = 0;
        foreach($users as $k => $v){
            $kk = $v['trader_id'].date('Y-m-d H',$v['create_time']);
            $list[$kk]['create_time'] =$v['create_time'];
            $list[$kk]['user_name'] =db('user')->where('id',$v['uid'])->value('email');
            $list[$kk]['t_name'] =db('user')->where('id',$v['trader_id'])->value('email');
            $list[$kk]['number'] += round($v['number'],5); 
            $list[$kk]['money_flow_type'] = '全球分红';
            $page_number += $v['number'];
        }
        $return['count'] = count($list);
        $i = ($p-1) * self::PAGE_LIMIT;
        $list = array_slice($list,$i,self::PAGE_LIMIT);
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $return['page_number'] = round($page_number,5);
        $return['all_number'] = round(db('global_record') ->where($map) -> sum('number'),5);
        return $return;
    }
    // public function global_record($map, $p, $order)
    // {

    //     $request= Request::instance();
    //     $list = db('global_record')->where($map)->order($order)->page($p, self::PAGE_LIMIT)->select();
    //     $page_number = 0;
    //     foreach ($list as $k => $v) {
    //         $list[$k]['user_name'] = db('user')->where('id',$v['uid'])->value('email');
    //         $list[$k]['t_name'] = db('user')->where('id',$v['trader_id'])->value('email');
    //         $list[$k]['money_flow_type'] = '全球分红';
    //         $page_number += $v['number'];
    //     }
    //     $return['count'] = db('global_record')->where($map)->count();
    //     $return['list'] = $list;
    //     $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
    //     $return['page_number'] = $page_number;
    //     $return['all_number'] = db('global_record') ->where($map) -> sum('number');
    //     return $return;
    // }

    public function recharge($map, $p)
    {
        $request= Request::instance();
        $list = db('recharge')->where($map)->order('id desc')->page($p, self::PAGE_LIMIT)->select();
        $rechargeTypeArr = model('Common/Dict')->showKey('recharge_type');
        $rechargeStatusArr = model('Common/Dict')->showKey('recharge_status');
        $page_in_number = 0;
        $page_out_number = 0;
        foreach ($list as $k => $v) {
            $list[$k]['user_name'] = db('user')->where('id',$v['uid'])->value('email');
            $list[$k]['cur_name'] = db('currency')->where('id',$v['cur_id'])->value('name');
            $list[$k]['recharge_type'] = $rechargeTypeArr[$v['recharge_type']];
            $list[$k]['recharge_status_text'] = $rechargeStatusArr[$v['recharge_status']];
            $list[$k]['transfer_type_text'] = $v['transfer_type'] == 1?'内部转账':'外部转账';
            switch($v['recharge_type']){
                case 1:
                    if($v['recharge_status'] === 1){
                        $page_in_number += $v['number'];
                    }
                    break;
                case 2:
                    if($v['recharge_status'] === 1){
                        $page_out_number += $v['number'];
                    }
                    break;
            }
        }
        $return['count'] = db('recharge')->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $return['page_in_number'] = $page_in_number;
        $return['page_out_number'] = $page_out_number;
        $in_where['recharge_type'] = 1;
        $in_where['recharge_status'] = 1;
        $return['all_in_number'] = db('recharge') -> where($in_where) -> sum('number');
        $out_where['recharge_type'] = 1;
        $out_where['recharge_status'] = 1;
        $return['all_out_number'] = db('recharge') -> where($out_where) -> sum('number');
        return $return;
    }

    public function report_form($map, $p,$order)
    {

        $request= Request::instance();
        $list = db('report_form')->where($map)->order($order)->page($p, self::PAGE_LIMIT)->select();
        foreach ($list as $k => $v) {
            $list[$k]['time'] = date('Y-m-d',$v['time']);
            $list[$k]['strat_time'] = date('Y-m-d',$v['time']-86400);
        }
        $return['count'] = db('report_form')->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        return $return;
    }

    public function report_form_all($map)
    {
        if(array_key_exists('time',$map)){
            $map['create_time'] = $map['time'];
            unset($map['time']);
            $order_where = $map;
            $order_where['done_time'] = $order_where['create_time'];
            unset($order_where['create_time']);
        }
        $return['member'] = db('user')->where($map)->count();
        $return['achievement'] = db('user_mining')->where($map)->sum('amount');
        $return['profit'] = db('user_coin_profit')->where($map)->where('type',5)->sum('all_amount');
        $return['child_profit'] = db('money_flow')->where($map)->sum('number');
        $return['vol'] = db('order')->where($order_where)->where('order_status',3)->sum('order_number');
        $recharge_where['recharge_status'] = 1;
        $return['turn'] = db('recharge')->where($map)->where($recharge_where)->sum('number');
        $recharge_where['transfer_type'] = 2;
        $recharge_where['recharge_type'] = 2;
        $return['turn_out'] = db('recharge')->where($map)->where($recharge_where)->sum('number');
        $recharge_where['recharge_type'] = 1;
        $return['turn_into'] = db('recharge')->where($map)->where($recharge_where)->sum('number');
        return $return;
    }

    public function user_report($map, $p,$order)
    {

        $request= Request::instance();
        $list = db('user_report')->where($map)->order($order)->page($p, self::PAGE_LIMIT)->select();
        foreach ($list as $k => $v) {
            $list[$k]['time'] = date('Y-m-d',$v['time']);
            $list[$k]['strat_time'] = date('Y-m-d',$v['time']-86400);
            $list[$k]['user_email'] = db('user')->where('id',$v['uid'])->value('email');
        }
        $return['count'] = db('user_report')->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $return['achievement'] = db('user_report')->where($map)->sum('new_achievement');
        $return['profit'] = db('user_report')->where($map)->sum('new_profit');
        $return['child_profit'] = db('user_report')->where($map)->sum('new_child_profit');
        $return['vol'] = db('user_report')->where($map)->sum('new_vol');
        $return['turn'] = db('user_report')->where($map)->sum('turn');
        $return['turn_out'] = db('user_report')->where($map)->sum('turn_out');
        $return['turn_into'] = db('user_report')->where($map)->sum('turn_into');
        return $return;
    }

    /**
     * 新增/修改
     * @param  array $data 传入信息
     */
    public function saveInfo($data)
    {    
        if(array_key_exists('id',$data)){
            $id = $data['id'];
            if(!empty($id)){
                $where = true;
            }else{
                $where = false;
            }
        }else{
            $where = false;
        }    
     
        $Order = new Order;
        $result = $Order->allowField(true)->isUpdate($where)->save($data);
        if(false === $result){
            return ['status'=>0,'info'=>$AuthGroup->getError()];
        }else{
            return array('status' => 1, 'info' => '保存成功', 'url' => url('index'));
        }
    }

    public function person($id)
    {
        $orderinfo = $this->infodata(array('id'=>$id));
        $buyerinfo = model('User')->infodata(array('id'=>$orderinfo['buyer_id']));
        $buyerinfo['type'] = '买家';
        $sellerinfo = model('User')->infodata(array('id'=>$orderinfo['seller_id']));
        $sellerinfo['type'] = '卖家';
        $list[] = $buyerinfo;
        $list[] = $sellerinfo;
        return $list;
    }



    public function cancel($data)
    {
        $id = $data['id'];
        if(false == ($orderinfo = $this->infodata(array('id'=>$id)))){
            return ['status'=>0,'info'=>'没有该订单'];
        }else{
            if($orderinfo['order_status'] == 2 || $orderinfo['order_status'] == 3){
                return ['status'=>0,'info'=>'该订单当前状态，不能撤单'];
            }else{
                $where['id'] = $orderinfo['seller_id'];
                $userinfo = model('User')->infodata($where);
                $where['dfs'] = $userinfo['dfs'] + $orderinfo['order_amount'];
                $user_result = model('User')->saveInfo($where);
                return ['status'=>1,'info'=>'撤单成功'];
            }
        }
    }

    /**
     * 改变状态
     * @param  array $data 传入数组
     */
    public function changeState($data)
    {
        if ($this->where(array('id'=>$data['id']))->update(array('order_status'=>$data['order_status']))) {
            return array('status' => 1, 'info' => '更改状态成功');
        } else {
            return array('status' => 0, 'info' => '更改状态失败');
        }
    }

    /**
     * 删除
     * @param  string $id ID
     */
    public function deleteInfo($id)
    {
        if($this->where(array('id'=>$id))->delete()){
            return ['status'=>1,'info'=>'删除成功'];
        }else{
            return ['status'=>0,'info'=>'删除失败,请重试'];
        }
    }

        /**
     * 根据查询条件获取信息
     * @param string $map [查询条件]
     * @return mixed
     */
    public function infodata($map){
        $list = $this->where($map)->find();
        if(!is_null($list)){
            return $list->toArray();
        }
        return false;
    }

}
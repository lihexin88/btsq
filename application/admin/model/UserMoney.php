<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;
use think\Exception;
class UserMoney extends Base
{

    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量


    /**
     * 获取列表
     * @param $map
     * @param $p
     * @return mixed
     * @throws \think\exception\DbException
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     */
    public function infoList($map, $p,$order)
    {
        $Currency = new Currency();
        $request= Request::instance();
        $list = db('user_money')->where($map)->order($order)->page($p, self::PAGE_LIMIT)->select();
        $page_sum_total = 0;
        foreach ($list as $k => $v) {
            $list[$k]['email'] = db('user')->where('id',$v['uid'])->value('email');
            $list[$k]['create_date'] = date('Y-m-d H:i:s',$v['create_time']);
            $list[$k]['cur_text'] = $Currency -> get_cur_text($v['cur_id']);
            $list[$k]['total'] = number_format($v['total'],5);
            $page_sum_total += $v['total'];
        }
        $return['count'] = $this->where($map)->count();
        $return['list'] = $list;

        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
	    $return['page_sum_total'] = number_format($page_sum_total,5);
        $return['sum_total'] = number_format(db('user_money') -> sum('total'),5);
        return $return;
    }

    public function recharge($data)
    {
        $recharge_info = db('recharge')->where('id',$data['id'])->find();
        if($recharge_info['recharge_type'] === 1){
            if($recharge_info['recharge_status'] != 0 || $recharge_info['transfer_type'] == 1){
                return ['status'=>0,'info'=>'不能这样操作！'];
            }
        }
        if($data['recharge_status'] == 1){
            if($recharge_info['recharge_type'] == 1){
                //转入
                $to_address = db('user_money')->where('address',$recharge_info['to_address'])->find();
                if($to_address){
                    //接收方增加币
                    $map['uid'] = $to_address['uid'];
                    $map['cur_id'] = 1;
                    $map['amount'] = $recharge_info['number']-$recharge_info['fee'];
                    $map['price'] = db('order')->where('cur_id',1)->order('id desc')->value('price');
                    $map['total'] = $map['amount']*$map['price'];
                    $map['create_time'] = time();
                    $map['update_time'] = time();
                    $map['type'] = 1;
                    $map['all_amount'] = $map['number'];
                    db('user_coin')->insert($map);
                }else{
                    return ['status'=>0,'info'=>'失败,没有该账户!'];
                }
            }
            db('recharge')->where('id',$data['id'])->update(['recharge_status'=>1]);
        }else{
            db('recharge')->where('id',$data['id'])->update(['recharge_status'=>2]);
            if($recharge_info['recharge_type'] == 2){
                $transfer_code = json_decode($recharge_info['transfer_code']);
                foreach($transfer_code as $k => $v){
                    $user_coin_profit = db('user_coin_profit') -> where('id',$k) -> find();
                    db('user_coin_profit') -> where('id',$k) -> setInc('amount',$v);    // 返加用户"该币剩余数量"
                    // 返加用户"总额"
                    $total = $user_coin_profit['price'] * $v;
                    db('user_coin_profit') -> where('id',$k) -> setInc('total',$total);
                }
            }
        }
        return ['status'=>1,'info'=>'成功'];
    }
}
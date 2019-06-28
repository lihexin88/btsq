<?php
/**
 * Created by ��.
 * ����Դ�ڻ��
 * hello world�����֮Դ
 * Date: 2018/12/17
 * Time: 17:28
 */

namespace app\api\model;


use think\Model;
use think\Db;

class MoneyFlow extends Model
{
    /**
     * 增加资金流水记录
     * @param $user
     * @param $money
     */
     public function add_flow($user, $money,$child_id,$type)
    {
        /*增加资金流水记录*/
        $map['uid'] = $user['uid'];
        $map['number'] = $money;
        $map['trader_id'] = $child_id;
        $map['create_time'] = time();
        $map['update_time'] = time();
        $map['type'] = $type;
        db('money_flow')->insert($map);
    }
}
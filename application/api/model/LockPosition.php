<?php

namespace app\api\model;

use think\Model;
use think\Validate;

class LockPosition extends Model
{

    /**
     * 添加锁仓信息
        $uid 用户ID
        $number 数量
        $time 锁仓持续时间
     */
    public function implement($uid,$number,$time)
    {
        $ins['uid'] = $uid;
        $ins['lock_time'] = time();
        $ins['number'] = $number;
        $ins['expiry_time'] = time()+$time*24*60*60;
        $inss = $this->insert($ins);
        if($inss){
            $times = time()+$time*24*60*60;//到期时间存进来
        }else{
            $times = false;
        }
        return $times;
    }
}

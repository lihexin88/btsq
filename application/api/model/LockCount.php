<?php

namespace app\api\model;

use think\Model;
use think\Validate;

class LockCount extends Model
{

    /**
     * 添加锁仓总信息
        $uid 用户ID
        $time 最新锁仓到期时间
     */
    public function lock_log($uid,$time,$number)
    {
        $list = $this->where('uid',$uid)->find();
        $ins['uid'] = $uid;
        $ins['time'] = $time;
        if($list){//如果存在 修改
            $ins['count_number'] = $number+$list['count_number'];
            $list = $this->where('uid',$uid)->update($ins);
        }else{//否则添加
            $ins['count_number'] = $number;
            $list = $this->insert($ins);
        }
        return $list;
    }
    //查询用户信息
    public function data($uid)
    {
        return $this->where('uid',$uid)->find();
    }
    //执行提现 用户ID 提现数量 锁仓到期时间
    public function extract($uid,$number)
    {
        return $this->where('uid',$uid)->setDec('count_number',$number);//总数量自减
    }
}

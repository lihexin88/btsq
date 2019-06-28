<?php

namespace app\api\model;

use think\Model;
use think\Validate;

class Method extends Model
{

    /**
     * 添加锁仓总信息
        $address 提笔地址
        $number 数量
        $id 用户ID
        $cur_id 币种ID
     */
    public function inserts($address,$number,$id,$cur_id,$fee,$type)
    {
        $ins['uid'] = $id;
        $ins['cur_id'] = $cur_id;
        $ins['method_type'] = $type;
      	if($type == 2){
        	 $ins['money'] = $number*(1+$fee);
          	 $ins['service_charge'] = $number*$fee;//数量*手续费
        }else{
        	 $ins['money'] = $number;
          	 $ins['service_charge'] = 0;//数量*手续费
        }
        $ins['create_time'] = time();
        $ins['review'] = 1;
        $ins['address'] = $address;
        return $this->insert($ins);
    }
    //提现记录 1充值 2提现
    public function lists($uid,$cur_id,$p,$type){
        $map=[
                'uid'=>$uid,
                'cur_id'=>$cur_id,
                'method_type'=>$type,
             ];
        $list['data'] = $this->where($map)->order('create_time desc')->page($p,8)->field('address,create_time,money')->select();
        $count = $this->where($map)->count();
        $list['page'] = ceil($count/8);
        return $list;
    }
}

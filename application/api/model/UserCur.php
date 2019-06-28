<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 11:42
 */

namespace app\api\model;


use think\Exception;
use think\Model;

class UserCur extends Model
{
	/**
	 * 用户币种账户存取方法
	 * @param $direction 方向 1，存入 -1，取出
	 * @param $cur_id 币种类型
	 * @param $number 存取数量
	 * @param $user 用户信息
	 * @return array 返回值
	 * @throws Exception
	 * @throws \think\exception\DbException
	 */
	public function update_user_cur($direction,$cur_id,$number,$user)
	{
		$fee = Config::game_fee();
		$UserCur = $this->get(['cur_id'=>$cur_id,'uid'=>$user['id']]);
		if($direction>0){
			if($UserCur['number'] < $number * ( 1 + $fee )){
				throw new Exception('low_blance');
			}
			$UserCur['number'] -= $number * (1+ $fee );
		}else{
			$UserCur['number']+=$number;
		}

		if(!$this->where(['cur_id'=>$cur_id,'uid'=>$user['id']])->update(['number'=>$UserCur['number']])){
			throw new Exception('recharge_failed');
		}
		return ['code'=>1,'msg'=>'gus_recharged'];

	}
  
  	/**
     * 锁仓提现
     	$uid 用户ID
        $ratio 手续费比例
     	$number 提现数量
        cur_id 1是LBCC
     */
    public function operate($uid,$ratio,$number)
    {
        $num = $number*(1-$ratio);//数量*(1-比例(单位:%))
        return $this->where('uid',$uid)->where('cur_id',1)->setInc('number',$num);//自增
    }
  //币种信息
    public function usdt_list($uid)
    {
        return 	db('user_cur')->alias('r')
          		->join('currency y','r.cur_id = y.id')
          		->where('r.uid',$uid)
          		->field('r.id,r.number,r.cur_id,y.name')
          		->select();
    }
}
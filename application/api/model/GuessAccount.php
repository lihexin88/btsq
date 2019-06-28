<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/22
 * Time: 16:29
 */

namespace app\api\model;


use think\Exception;
use think\Model;

class GuessAccount extends Model
{
	/**
	 * @param $password 支付密码
	 * @param $number 交易数量
	 * @param $user 用户信息
	 * @return array
	 * @throws Exception
	 * @throws \think\exception\DbException
	 */
	public function exchange($direction,$password,$number,$user)
	{
//		获取游戏手续费
		$fee = Config::game_fee();
//		验证用户交易密码
		if($user['payment_password'] != encrypt($password)){
			throw new Exception('not_password');
		}
//		获取用户信息
		$guessaccount = $this->get(['uid'=>$user['id']]);

		if($guessaccount){
			if($direction < 0){
				if($guessaccount->blance < $number*(1+$fee)){
					throw new Exception('low_blance');
				}
				$guessaccount->blance -= $number*(1+$fee);
				if(!$guessaccount->save()){
					throw new Exception('low_blance');
				}
			}else{
				$guessaccount->blance += $number;
				if(!$guessaccount->save()){
					throw new Exception('os_error');
				}
			}
		}else{
			$guessaccount['uid'] = $user['id'];
			$guessaccount['blance'] = $direction<0?0:$number;
			if($direction>0){
				if(!$this->save($guessaccount)){
					throw new Exception('os_error');
				}
			}else{
				throw new Exception('low_blance');
			}
		}

	}

	/**
	 * 押注扣取押注费
	 * @param $user 用户信息
	 * @param $number 押注数量
	 * @throws Exception
	 * @throws \think\exception\DbException
	 */
	public function bet_fee($user,$number)
	{
		$GuessAccount = $this->get(['uid'=>$user['id']]);
		if($GuessAccount['blance'] < $number){
			throw new Exception('low_blance');
		}
		$GuessAccount['blance'] -= $number;
		if(!$GuessAccount->save()){
			throw new Exception('os_error');
		}
	}

	/**
	 * 获取用户的账户信息
	 * @param $user 用户信息
	 * @return array
	 * @throws \think\exception\DbException
	 */
	static public function get_guess_account($user)
	{
		$guess_account = self::alias('g')
			->join('user u','u.id = g.uid')
			->field('u.account,g.*')
			->where(['uid'=>$user['id']])->find();
		if(!$guess_account){
			return ['code'=>-1];
		}else{
			return ['code'=>1,'data'=>$guess_account];
		}
	}

	/**
	 * 中奖增加用户lbcc
	 */
	public function right_income($uid,$number)
	{
//		获取游戏手续费
		$fee = Config::game_fee();
//      获取当前获奖用户
		$right_income = self::get(['uid'=>$uid]);
		$right_income['blance']+=$number*(2-$fee);
		$user['id'] = $uid;

		if(!$right_income->save())
		{
			throw new Exception('os_error');
		}
//		增加资金流水记录
		$right_income = new GuessOrder();
		$right_income->create_order(2,$number * (2-$fee),$user);
		return true;
	}


}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 10:47
 */

namespace app\api\model;


use think\Exception;
use think\Model;

class GuessOrder extends Model
{
	/**
	 * @param $direction 方向
	 * @param $number 数额
	 * @param $user 用户信息
	 * @param $type 类型 -1、充值 1、提现 2、中奖 3、下注
	 * @return array
	 * @throws Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function create_order($direction,$number,$user)
	{
		$fee = Config::game_fee();
		$GuessOrder['uid'] = $user['id'];
		$GuessOrder['direction'] = $direction;
		if($direction < 0){
			$number *= 1+$fee;
		}
		$GuessOrder['number'] = $number;
		if(!$this->save($GuessOrder))
		{
			throw new Exception('recharge_failed');
		}
		return ['code'=>1,'msg'=>'gus_recharged'];
	}


	/**
	 * 获取用户竞猜账户信息
	 * @param null $where
	 * @return mixed
	 * @throws \think\exception\DbException
	 */
	static public function get_order($where = null)
	{
		$pagesize = 15;
		$config = [];
		if($where['direction']){
			$query['direction']=$where['direction'];
		}
		if($where['u.account']){
			$query['keywords']=$where['u.account'];
		}
		$config = [
			'query'=>$query
		];
		$order = self::
			where($where)
			->alias('r')
			->join('user u','u.id = r.uid')
			->field('u.account,r.*')
			->order('r.update_time desc')
			->paginate($pagesize,false,$config);
		$r['data'] = $order;
		$r['page'] = $order->render();
		$r['count'] = self::
			where($where)
			->alias('r')
			->join('user u','u.id = r.uid')->count();
		return $r;
	}

	/**
	 * 获取参与游戏中奖记录的最新10条
	 * @return \think\Paginator
	 * @throws \think\exception\DbException
	 */
	static public function bingo_user()
	{
		$pagesize = 10;
		$recode = self::alias('r')
			->join('user u','u.id = r.uid')
			->where(['direction'=>2])
			->field('u.account,r.number,r.update_time')
			->order('r.update_time desc')
			->paginate($pagesize);
		return $recode;
	}
}
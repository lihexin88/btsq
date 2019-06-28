<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 15:32
 */

namespace app\api\model;


use think\Model;

class BonusList extends Model
{
	/*
		uid    用户
		bonus  数量
		status 1STO红利 2链接红利 3高管佣金 4当日交易佣金
		tid    返佣人ID
	*/
	static public function add_log($uid,$bonus,$type,$tid)
	{
		
		if($bonus != 0){
			$ins['uid'] = $uid;
			$ins['tid'] = $tid;
			$ins['bonus'] = $bonus;
			$ins['time'] = time();
			$ins['type'] = $type;
			db('bonus_list')->insert($ins);
		}
		return true;
	}

	/**
	 * 邀请返佣
	 * @param $user 用户信息
	 * @return false|\PDOStatement|string|\think\Collection
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function reword($user)
	{
		$reword = $this->alias('b')
			->join('user u' ,'u.id = b.uid')
			->where(['b.uid'=>$user['id']])
			->field('b.bonus,b.type,b.time')
			->select();
		foreach ($reword as $k=>$v){
            $reword[$k]['time'] = date("Y-m-d H:i:s",$v['time']);
		}
		return $reword;
	}
}
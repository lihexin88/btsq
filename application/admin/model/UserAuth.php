<?php
/**---------------------------|
 * Created by  '李'
 * User: Administrator
 * Date: 2018/11/28
 * Time: 15:24
 **---------------------------|
 */

namespace app\admin\model;


use think\Model;

class UserAuth extends Model
{
	/**
	 * 获取全部用户认证信息
	 * @return false|Model[]|\think\Paginator
	 * @throws \think\exception\DbException
	 */
	static public function all()
	{
		$pagesize = 15;
		$all = self::paginate($pagesize);
		return $all;
	}

	/**
	 * @param $id 认证id
	 * @param $type 操作类型 3 通过 4 拒绝
	 * @return bool|false|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function authenticate($id,$type)
	{
		$user_auth = $this->where(['id'=>$id])->find();
		if(!$user_auth){
			return rtn(-1,'未找到用户信息');
		}
		$user_auth->status = $type==3?1:2;
		if(!$user_auth->save()){
			return rtn(-1,'审核失败');
		}
		return true;
	}
}
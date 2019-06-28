<?php
/**---------------------------|
 * Created by  '李'
 * User: Administrator
 * Date: 2018/11/29
 * Time: 17:01
 **---------------------------|
 */

namespace app\admin\model;


use think\Exception;
use think\Model;

class GuessInfo extends Model
{
	/**
	 * 获取游戏公告等信息
	 * @return GuessInfo[]|false
	 * @throws \think\exception\DbException
	 */
	static public function get_info()
	{
		return self::all();
	}

	/**
	 * 修改游戏公告信息
	 * @param $data 修改的参数
	 * @throws Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function edit_info($data)
	{

		$result = false;
		foreach ($data as $k=>$v){
			$each = $this->where(['key'=>$k])->find();
			$each->value = $v;
			if($each->save()){
				$result = true;
			}
		}
		return $result;
	}
}
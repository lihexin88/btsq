<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/24
 * Time: 9:32
 */

namespace app\api\model;


use think\Exception;
use think\Model;
use think\Db;

class GuessConfig extends Model
{
	/**
	 * 获取当前期号
	 * @return mixed
	 * @throws \think\exception\DbException
	 */
	static public function ThisTeam()
	{
		$team = self::get(['status'=>1]);
		return $team['id'];
	}

	/**
	 * 开奖、生成下一期信息
	 */
	static public function lottery()
	{
//      获取本期期号
		$this_team = self::get(['status'=>1]);

//		统计总投注数
		$this_team_recode = GuessRecode::get_this_recode($this_team);
		$red = 0;
		$blue = 0;
		$team_count = 0;
		$dir = null;
		foreach ($this_team_recode as $k=>$v){
			if($v['dir'] == 0){
				$red+=$v['number'];
			}else{
				$blue+=$v['number'];
			}
		}
		if($red == $blue){
			$dir = ceil(rand(0,9))%2;
		}else{
			$dir = $red>$blue?1:0;
		}
//		更新竞猜记录表
		GuessRecode::lottery($this_team,$dir);

//      中奖的用户增加lbcc
		$this_team_recode = GuessRecode::get_this_recode($this_team);
		$right_account = new GuessAccount();
		foreach ($this_team_recode as $k=>$v){
			if($v['right'] == 1){
				$right_account->right_income($v['uid'],$v['number']);
			}
		}

//		更新当前期号，并生成下一期信息
		if(!self::where(['id'=>$this_team['id']])->update(['status'=>0,'update_time'=>time(),'chip_money'=>$red+$blue,'red_money'=>$red,'blue_money'=>$blue,'right'=>$dir])){
			throw new Exception('os_error');
		}
		$new_team = new self();
		$new_team['status'] = 1;
		if(!$new_team->save()){
			throw new Exception('os_error');
		}
		return true;
	}

	/**
	 * 获取系统开奖记录
	 * @param $where 条件
	 * @return mixed
	 * @throws \think\exception\DbException
	 */
	static public function get_recode($where = null)
	{
		$pagesize = 8;
		$query = null;
		if($where['id']){
				$query=[
					'id'=>$where['id']
				];
		}
		$configs = [
			'query'=>$query
		];
		$recode = self::where($where)->where(['status'=>0])->order('create_time desc')->paginate($pagesize,false,$configs);
		foreach ($recode as $k=>$v){
            $recode[$k]['status'] = lang($v['status'] == 0?"lotteryed":"not_lottery");
            $recode[$k]['color'] = $v['right'] == 0?"0":"1";
            $recode[$k]['right'] = lang($v['right'] == 0?"red":"blue").lang("win");
        }
		$r['data'] = $recode;
		$r['page'] = $recode->render();
		$r['count'] = self::where($where)->count();
		return $r;
	}


    /**
     * 获取本期剩余时间
     * @return GuessConfig|null
     * @throws \think\exception\DbException
     */
	static public function team_time()
    {
        return self::get(['status'=>1]);
    }
}
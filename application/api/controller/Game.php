<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 9:50
 */

namespace app\api\controller;


use app\admin\model\GuessInfo;
use app\api\model\Config;
use app\api\model\GuessOrder;
use app\api\model\GuessRecode;
use think\Controller;
use app\api\model\GuessConfig;
use think\Db;


class Game extends Controller
{
	/**
	 * 开奖、生成下一期信息
	 * @return false|string
	 */
	public function lottery()
	{
		$access = PlanTaskAccessLimit::planTaskAccesslimit();
		if($access['code'] !=1){
			return rtn(-1,$access['msg']);
		}
//		开奖时间提前1分、推后3分，确保当前全部开奖
		$now = time();
		$start_time = strtotime(date("Y-m-d",time())) + 9 *60 *60 - 1 * 60;
		$end_time = strtotime(date("Y-m-d",time())) + 21 *60 *60 + 3 * 60;
		if(($now<$start_time) || ($now > $end_time)){
			return rtn(-1,'not_game_time');
		}

		Db::startTrans();
		try{
			GuessConfig::lottery();
			Db::commit();
			return rtn(1,'team changed  ->  time : ',date("Y-m-d H:i:s",time()));
		}catch (\Exception $e){
			Db::rollback();
			return rtn(-1,$e->getMessage());
		}
	}

	/**
	 * 获取当天所有用户获奖信息
	 * @return false|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function get_history()
	{
		$GuessRecode = GuessRecode::get_daily_recode();
		$GuessRecode = $GuessRecode;
		$Guess = null;
		foreach ($GuessRecode as $k=>$v){
		    $Guess[$k]['account'] = substr($v['account'],0,3)."****".substr($v['account'],-4);
        }
		return rtn(1,lang('os_success'),$Guess);
	}

	/**
	 * 获取游戏公告信息
	 * @return false|string
	 * @throws \think\exception\DbException
	 */
	public function get_info()
	{
		$guess_info = GuessInfo::get_info();
		return rtn(1,lang('获取成功'),$guess_info);
	}

	/**
	 * 获取系统开奖记录
	 * @return false|string
	 * @throws \think\exception\DbException
	 */
	public function config_recode()
	{
		return rtn(1,'os_success',GuessConfig::get_recode());
	}


	/**
	 * 获取游戏手续费
	 * @return false|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function game_fee()
	{
		return rtn(1,lang('os_success'),Config::game_fee());
	}


	/**
	 * 获取参与游戏且中奖的用户信息
	 * @return false|string
	 * @throws \think\exception\DbException
	 */
	public function get_join_er()
	{
		$join_er = GuessOrder::bingo_user();
		return rtn(1,lang('os_success'),$join_er);
	}

    /**
     * 前端接收剩余时间
     * @return false|string
     */
	public function get_team_time()
    {
        $team_time = GuessConfig::team_time();
        $now_time = time();
        $today_start = strtotime(date("Y-m-d",time())) + 9 * 60 * 60;
        $time_left = 0;
        if($now_time < $today_start){
            $time_left = $today_start - $now_time;
        }else{
            $time_left = strtotime($team_time['create_time']) + 3 * 60 - $now_time;
        }
        return rtn(1,lang('os_success'),$time_left);
    }

}
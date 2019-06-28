<?php
/**---------------------------|
 * Created by  '李'
 * User: Administrator
 * Date: 2018/11/28
 * Time: 17:40
 **---------------------------|
 */

namespace app\admin\controller;

use app\admin\model\GuessInfo;
use app\api\model\GuessAccount;
use app\api\model\GuessConfig;
use app\api\model\GuessOrder;
use app\api\model\GuessRecode;
use app\common\controller\AdminBase;
use think\Controller;
use think\Db;

class Game extends AdminBase
{
	/**
	 * 获取中奖状态
	 * @return mixed
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function index()
	{
		$where = null;
		$where['keywords'] = $_GET['keywords']!=null?$_GET['keywords']:null;
		$where['status'] = $_GET['status']!=null?$_GET['status']:null;
		$recode = null;
		$Recode = new GuessRecode();
		$recode = $Recode->get_all($where);
		$this->assign('page',$recode['page']);
		$this->assign('all_recode',$recode['data']);
		$this->assign('count',$recode['count']);
		return $this->fetch();
	}

	/**
	 * 获取系统开奖记录
	 * @return mixed
	 * @throws \think\exception\DbException
	 */
	public function config_recode()
	{
		$where = null;
		if($_GET['keywords']){
			$where['id'] = $_GET['keywords'];
		}
		$recode = GuessConfig::get_recode($where);
		$this->assign('all_recode',$recode['data']);
		$this->assign('page',$recode['page']);
		$this->assign('count',$recode['count']);
		return $this->fetch();
	}

	/**
	 * 游戏公告信息
	 * @return mixed
	 * @throws \think\exception\DbException
	 */
	public function game_info()
	{
		$guess_info = GuessInfo::get_info();
		$this->assign('list',$guess_info);
		return $this->fetch();
	}

	/**
	 * 修改游戏公告信息
	 * @return false|string
	 */
	public function edit_info()
	{
		if(!$_POST){
			return rtn(-1,lang('os_error'));
		}
		$data = null;
		foreach ($_POST as $k=>$v){
			$data[$k] = $v;
		}
		$GameInfo = new GuessInfo();
		$result = $GameInfo->edit_info($data);
		if($result){
			return rtn(1 ,'更新成功');
		}else{
			return rtn(-1,'未更新任何字段');
		}
	}

	/**
	 * 用户精彩账户信息
	 * @return mixed
	 * @throws \think\exception\DbException
	 */
 	public function account_recode()
	{
		$where = null;
		if($_GET['keywords']){
			$where['u.account'] = $_GET['keywords'];
		}
		if($_GET['direction']){
			$where['direction'] = $_GET['direction'];
		}
		$order = GuessOrder::get_order($where);
//		exit;
	    $this->assign('direction',$_GET['direction']);
		$this->assign('all_recode',$order['data']);
		$this->assign('page',$order['page']);
		$this->assign('count',$order['count']);
		return $this->fetch();
	}

	/**
	 * 后台删除用户竞猜账户资金流水记录
	 * @param id 资金流水id
	 * @return false|string
	 */
	public function del_recode(){
		$Order = new GuessOrder();
		if($Order->where(['id'=>$_POST['id']])->delete()){
			return rtn(1,'os_success');
		}
		return rtn(-1,'os_error');
	}


}
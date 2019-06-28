<?php
namespace app\api\Controller;
use app\common\controller\ApiBase;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;


class Upgrade extends ApiBase
{
  	private $User;
  	private $Currency;
    public function __construct(\think\Request $request = null) 
    {
        parent::__construct($request);
      	$this->User = new \app\api\model\User();
      	$this->Currency = new \app\api\model\Currency();
      	$this->UserCur = new \app\api\model\UserCur();
    }
    /**
		升级
    **/
	public function index($user)
	{
		//因为$user 是 升级后传过来的 所以VIP人数默认为1
		$sum = 1;
		$parent  = $this->User->where('id',$user['parent_id'])->find();//父级信息
		$count  = $this->User->where('parent_id',$user['parent_id'])->count();//统计直推人数
		$level_count = $this->User->where('parent_id',$user['parent_id'])->where('level',1)->count();//VIP等级为1
		if($count>=10 && $level_count>=3 && $parent['level'] == 1){//直推10人并且直推里有三人是VIP并且本人等级是VIP 才可以升级成 铜冠
			$sum = 0;
			$achievement = $this->achievement($parent,$sum);//业绩和
			if($achievement >= 150000){
				$this->User->where('id',$parent['id'])->setInc('level',1);//自增1 升级为2
				db('user')->where('id',$parent['id'])->update(['level_time'=>time()]);
				$people = $this->crown($parent);//冠级
				if($people['sum'] >= 100 && $people['count'] >= 3){
					$this->User->where('id',$people['parent']['id'])->setInc('level',1);//自增1 升级为3
					db('user')->where('id',$people['parent']['id'])->update(['level_time'=>time()]);
					$father = $this->crown($people['parent']);//冠级
					if($father['sum'] >= 330 && $father['count'] >= 3){
						$this->User->where('id',$father['parent']['id'])->setInc('level',1);//自增1 升级为4
						db('user')->where('id',$father['parent']['id'])->update(['level_time'=>time()]);
						$top = $this->crown($father['parent']);//冠级
						if($top['sum'] >= 1000 && $top['count'] >= 3){
							$this->User->where('id',$top['parent']['id'])->setInc('level',1);//自增1 升级为5
							db('user')->where('id',$top['parent']['id'])->update(['level_time'=>time()]);
						}
					}
				}
			}
		}
	}
	public function crown($user)
	{
		$parent  = $this->User->where('id',$user['parent_id'])->find();//父级信息
		$child = $this->User->where('parent_id',$parent['id'])->select();//下级信息
		$list['count'] = $this->User->where('parent_id',$parent['id'])->where('level',2)->count();//下级信息
		$list['parent'] = $parent;
		$sum = 0;//下级人数
		foreach ($child as $k1 => $v1) {
			$sum += $this->count($v1);//下级人数和
			$two = $this->User->where('parent_id',$v1['id'])->select();//下级信息
			foreach ($two as $k2 => $v2) {
				$sum += $this->count($v2);//下级人数和
				$three = $this->User->where('parent_id',$v2['id'])->select();//下级信息
				foreach ($three as $k3 => $v3) {
					$sum += $this->count($v3);//下级人数和
					$four = $this->User->where('parent_id',$v3['id'])->select();//下级信息
					foreach ($four as $k4 => $v4) {
						$sum += $this->count($v4);//下级人数和
						$five = $this->User->where('parent_id',$v4['id'])->select();//下级信息
						foreach ($five as $k5 => $v5) {
							$sum += $this->count($v5);//下级人数和
							$six = $this->User->where('parent_id',$v5['id'])->select();//下级信息
							foreach ($six as $k6 => $v6) {
								$sum += $this->count($v6);//下级人数和
								$seven = $this->User->where('parent_id',$v6['id'])->select();//下级信息
								foreach ($six as $k7 => $v7) {
									$sum += $this->count($v7);//下级人数和
									$eight = $this->User->where('parent_id',$v7['id'])->select();//下级信息
									foreach ($eight as $k8 => $v8) {
										$sum += $this->count($v8);//下级人数和
										$nine = $this->User->where('parent_id',$v8['id'])->select();//下级信息
										foreach ($nine as $k9 => $v9) {
											$sum += $this->count($v9);//下级人数和
											$ten = $this->User->where('parent_id',$v9['id'])->select();//下级信息
											foreach ($ten as $k10 => $v10) {
												$sum += $this->count($v10);//下级人数和
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		$list['sum'] = $sum;
		return $list;
	}
	//本人数据 查询下级所有业绩和 下一级
	public function achievement($user,$sum)
	{
		$child = $this->User->where('parent_id',$user['id'])->select();//下级信息
		foreach ($child as $k1 => $v1) {
			$sum += $this->currency($v1);//个人资产和
			$two = $this->User->where('parent_id',$v1['id'])->select();//下级信息
			foreach ($two as $k2 => $v2) {
				$sum += $this->currency($v2);//个人资产和
				$three = $this->User->where('parent_id',$v2['id'])->select();//下级信息
				foreach ($three as $k3 => $v3) {
					$sum += $this->currency($v3);//个人资产和
					$four = $this->User->where('parent_id',$v3['id'])->select();//下级信息
					foreach ($four as $k4 => $v4) {
						$sum += $this->currency($v4);//个人资产和
						$five = $this->User->where('parent_id',$v4['id'])->select();//下级信息
						foreach ($five as $k5 => $v5) {
							$sum += $this->currency($v5);//个人资产和
							$six = $this->User->where('parent_id',$v5['id'])->select();//下级信息
							foreach ($six as $k6 => $v6) {
								$sum += $this->currency($v6);//个人资产和
								$seven = $this->User->where('parent_id',$v6['id'])->select();//下级信息
								foreach ($six as $k7 => $v7) {
									$sum += $this->currency($v7);//个人资产和
									$eight = $this->User->where('parent_id',$v7['id'])->select();//下级信息
									foreach ($eight as $k8 => $v8) {
										$sum += $this->currency($v8);//个人资产和
										$nine = $this->User->where('parent_id',$v8['id'])->select();//下级信息
										foreach ($nine as $k9 => $v9) {
											$sum += $this->currency($v9);//个人资产和
											$ten = $this->User->where('parent_id',$v9['id'])->select();//下级信息
											foreach ($ten as $k10 => $v10) {
												$sum += $this->currency($v10);//个人资产和
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $sum;
	}
	//数据和
	public function currency($child)
	{
		$list = $this->UserCur->where('uid',$child['uid'])->select();
		foreach ($list as $k => $v) {
			$name = $this->Currency->where('id',$v['cur_id'])->value('name');
			if($name = 'LBCC'){
				$price = 2;
			}else{
				$price = api_currency($name);//调用公共方法
			}
			$sum += $price*$v['number'];
		}
		return $sum;
	}
	//人数和
	public function count($child)
	{
		return $this->User->where('parent_id',$child['id'])->count();
	}
}
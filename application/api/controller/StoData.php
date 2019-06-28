<?php
namespace app\api\Controller;
use app\common\controller\ApiBase;
use app\api\controller\Upgrade;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;


class StoData extends ApiBase
{
	private $StoData;
	private $UserCur;
  	private $User;
    public function __construct(\think\Request $request = null) 
    {
        parent::__construct($request);
        $this->StoData = new \app\api\model\StoData();
        $this->UserCur = new \app\api\model\UserCur();
      	$this->User = new \app\api\model\User();
    }

    /**
		sto 通证列表
		id 用户id
    **/
	public function sto_list()
	{
		$res = $this->userInfo;//用户信息
		$list = $this->StoData->alias('s')->join('currency y','y.id = s.cur_id')->where('uid',$res['id'])->field('s.uid,s.cur_id,s.status,y.name,y.icon')->select();
		foreach ($list as $key => $value) {
			$list[$key]['icon'] = config('WEBSITE').$value['icon'];
		}
		$r = rtn(1,'',$list);
		return $r;
	}
	//单个STO信息
	public function cur_list()
	{
		$res = $this->userInfo;//用户信息
		$cur_id = trim(input('cur_id'));//币种ID
		$list = $this->StoData->where('uid',$res['id'])->where('cur_id',$cur_id)->field('id,uid,cur_id,number,bonus')->find();
		$list['name'] = db('currency')->where('id',$cur_id)->value('name');
		$r = rtn(1,'',$list);
		return $r;
	}
	/**
	 * STO提现
	 	pwd    密码
	 	cur_id 币种ID
	 * @remark 
	*/
	public function sto_withdraw()
	{
		// $pwd = trim(input('pwd'));//密码
		$cur_id = trim(input('cur_id'));//币种ID
		$res = $this->userInfo;//用户信息
		// if(encrypt($pwd) !== $res['payment_password']){//支付密码
		// 	$r = rtn(0,lang('not_password'));
		// }else
			$list = $this->StoData->data($res['id'],$cur_id);//查询信息
			if(time()>=$list['time']){
				$ratio = config('FEE_RATIO_YES')/100;//锁仓时间已到的提现手续费比例 整数除以100是百分数
			}else{
				$ratio = config('FEE_RATIO_NO')/100;//锁仓时间未到的提现手续费比例 整数除以100是百分数
			}
			$this->UserCur->operate($res['id'],$ratio,$list['number']);//锁仓提现进虚拟币资产表
			$this->StoData->edit($res['id'],$cur_id);//修改数据
			$r = rtn(1,lang('success'));
     	return $r;
	}

	/**
	 * STO
	 *	number 数量
	 	pwd    密码
	 	cur_id 币种
	 * @remark 
	 */
	public function sto_lock()
	{
		$number = trim(input('number'));//数量
		$pwd = trim(input('pwd'));//密码
		$cur_id = trim(input('cur_id'));//币种
		$res = $this->userInfo;//用户信息
		$map['uid'] = $res['id'];//用户ID
		$map['cur_id'] = $cur_id;//币种ID
		$user_number = db('user_cur')->where($map)->value('number');//用户可用虚拟币数量
		if(encrypt($pwd) !== $res['payment_password']){
			$r = rtn(0,lang('not_password'));
		}elseif($number > $user_number){
			$r = rtn(0,lang('not_numebr'));
		}else{
			$time = config('LOCK_TIME');//设置时间
			$num = $this->StoData->where($map)->value('number');//STO数量
          	$timess = time()+$time*24*60*60;
			$times =$this->StoData->where($map)->value('create');//STO数量
          	$numbers = $num + $number;//sto库存数量+最新锁仓数量
			$situation = $this->StoData->implement($map,$numbers,$timess,$times);
          	//$situation = 1;
			if($situation){
              	$lock_num = config('LOCK_NUMBER');//设置升级数量
              	if($numbers >= $lock_num && $res['level'] == 0 && time()>=$times){
              		//如果sto数量 大于等于 设置的升级VIP数量 那么进行升级VIP
                	$this->User->where('id',$res['id'])->setInc('level',1);//本人从非VIP升级成VIP
                  	$upgrade = new Upgrade();
                	$upgrade_data = $upgrade->index($res);
                }
              	$r = rtn(1,lang('success'));
			}else{
				$r = rtn(0,lang('error'));
			}
		}
        return $r;
	}
}
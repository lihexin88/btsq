<?php
namespace app\api\Controller;
use app\common\controller\ApiBase;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;


class LockPosition extends ApiBase
{
	private $LockPosition;
	private $LockCount;
	private $UserCur;
    public function __construct(\think\Request $request = null) 
    {
        parent::__construct($request);
        $this->LockPosition = new \app\api\model\LockPosition();
        $this->LockCount = new \app\api\model\LockCount();
        $this->UserCur = new \app\api\model\UserCur();
    }
/**
 * 锁仓
 *	number 数量
 	pwd    密码
 * @remark 
 */
	public function lock_position()
	{
		$number = trim(input('number'));
		$res = $this->userInfo;//用户信息
		$map['uid'] = $res['id'];//用户ID
		$map['cur_id'] = 1;//LBCC ID 1
		$user_number = db('user_cur')->where($map)->value('number');//用户可用虚拟币数量
		if($number > $user_number){
			$r = rtn(0,lang('not_numebr'));
		}else{
			$time = config('LOCK_TIME');
			$situation = $this->LockPosition->implement($res['id'],$number,$time);
			if($situation){
				//到期时间发过去
				$this->LockCount->lock_log($res['id'],$situation,$number);
				$r = rtn(1,lang('success'));
			}else{
				$r = rtn(0,lang('error'));
			}
		}
        return $r;
	}
	//锁仓信息
	public function lock_position_data()
	{
		$res = $this->userInfo;//用户信息
		$situation = $this->LockCount->data($res['id']);//用户总记录
		$data = $this->LockPosition->where('uid',$res['id'])->select();//参与锁仓信息
		foreach ($data as $k => $v) {
			$data[$k]['lock_time'] = date('Y-m-d H:i:s',$v['lock_time']);
			$data[$k]['expiry_time'] = date('Y-m-d H:i:s',$v['expiry_time']);
		}
		if($situation || !isset($data)){
			$list['total'] = $situation;
			$list['data'] = $data;
			$r = rtn(1,lang('success'),$list);
		}else{
			$r = rtn(0,lang('null'));
		}
		return $r;
	}
	/**
	 * 锁仓提现
	 *	number 数量
	 	pwd    密码
	 * @remark 
	*/
	public function lock_withdraw()
	{
		$number = trim(input('number'));
		$pwd = trim(input('pwd'));
		$res = $this->userInfo;//用户信息
		if(encrypt($pwd) !== $res['payment_password']){
			$r = rtn(0,lang('not_password'));
		}elseif(!$number){
			$r = rtn(0,lang('error'));
		}else{
			$list = $this->LockCount->data($res['id']);//查询信息
			if($list['count_number'] < $number){//总数量小于提现数量
				$r = rtn(0,lang('not_numebr'));
			}else{
				$time = $this->LockCount->where('uid',$res['id'])->value('time');//最新时间
				$situation = $this->LockCount->extract($res['id'],$number);//执行提现
				if($situation){
					if(time()>=$time){
						$ratio = config('FEE_RATIO_YES')/100;//锁仓时间已到的提现手续费比例 整数除以100是百分数
					}else{
						$ratio = config('FEE_RATIO_NO')/100;//锁仓时间未到的提现手续费比例 整数除以100是百分数
					}
					$this->UserCur->operate($res['id'],$ratio,$number);//锁仓提现进虚拟币资产表
					$r = rtn(1,lang('success'));
				}else{
					$r = rtn(0,lang('error'));
				}
			}
		}
     	return $r;
	}
}
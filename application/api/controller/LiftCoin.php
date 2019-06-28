<?php
namespace app\api\controller;
use app\common\controller\ApiBase;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;

/**
 * 提币
 *
 * @remark 
 */
class LiftCoin extends ApiBase
{
    private $UserCur;
    private $Method;
    private $UserAddress;
    private $LockCount;
    public function __construct(\think\Request $request = null) 
    {
        parent::__construct($request);
        $this->UserCur = new \app\api\model\UserCur();
        $this->Method = new \app\api\model\Method();
        $this->UserAddress = new \app\api\model\UserAddress();
        $this->LockCount = new \app\api\model\LockCount();
    }
    //矿工手续费
    public function fee()
    {
        $fee = config('MINER_FEE')*100;
        $r = rtn(1,'',$fee);
        return $r;
    }
    //执行提现
    public function index()
    {
        $res = $this->userInfo;//用户信息
        $address = trim(input('address'));//提币地址
        $cur_id = trim(input('cur_id'));//币种ID
        $number = trim(input('number'));//数量
        $phone = trim(input('phone'));//手机号
        $yzm = trim(input('yzm'));//验证码
        $pwd = trim(input('pwd'));//支付密码
        $code = session('authcode.code');//session存的手机验证码
        $map['uid'] = $res['id'];
        $map['cur_id'] = $cur_id;
        $user_number = $this->UserCur->where($map)->value('number');//查询用户改币种的数量
        if(!$address){
            $r = rtn(0,lang('not_null'));
        }elseif(!$cur_id){
            $r = rtn(0,lang('not_null'));
        }elseif(!$number){
            $r = rtn(0,lang('not_null'));
        }elseif(!$phone){
            $r = rtn(0,lang('not_null'));
        }elseif(!$yzm){
            $r = rtn(0,lang('not_null'));
        }elseif($yzm != $code){
            $r = rtn(0,lang('code_error'));
        }elseif(encrypt($pwd) != $res['payment_password']){
            $r = rtn(0,lang('not_password'));
        }elseif($user_number < $number){
            $r = rtn(0,lang('excess_quantity'));
        }else{
            $fee = config('MINER_FEE');//提笔手续费
            $look_count = $this->LockCount -> where('uid',$res['id']) -> field('count_number,time') -> find();
            $time = time();
            if($look_count['count_number'] >= 5000 && $time > $look_count['time']){
                $service = $fee * config('FIVE_THOUSAND');
            }else if($look_count['count_number'] >= 10000 && $time > $look_count['time']){
                $service = $fee * config('TEM_THOUSAND');
            }else{
                $service = $fee;
            }
            $res = $this->Method->inserts($address,$number,$res['id'],$cur_id,$service,2);
            if($res){
                $r = rtn(1,lang('success'));
            }else{
                $r = rtn(0,lang('error'));
            }
        }
        return $r;
    }
    //绑定提币地址
    public function address()
    {
        $res = $this->userInfo;//用户信息
        $cur_id = trim(input('cur_id'));
        $ps = trim(input('ps'));//备注
        $address = trim(input('address'));
        $pwd = trim(input('pwd'));
        if(!$address){
            $r = rtn(0,lang('not_null'));
        }elseif(!$cur_id){
            $r = rtn(0,lang('not_null'));
        }elseif(!$ps){
            $r = rtn(0,lang('not_null'));
        }elseif(encrypt($pwd) != $res['payment_password']){
            $r = rtn(0,lang('not_password'));
        }else{
            $res = $this->UserAddress->inserts($address,$res['id'],$cur_id,$ps);
            if($res){
                $r = rtn(1,lang('success'));
            }else{
                $r = rtn(0,lang('error'));
            }
        }
        return $r;
    }
    //提现记录
    public function listss()
    {
        $res = $this->userInfo;//用户信息
        $cur_id = trim(input('cur_id'));//币种ID
        $p = trim(input('p'));//页码
        if(!$cur_id){
            $r = rtn(0,lang('not_null'));
        }else{
            $res = $this->Method->lists($res['id'],$cur_id,$p,2);
            if($res['page']){
                $r = rtn(1,lang('success'),$res);
            }else{
                $r = rtn(0,lang('null'));
            }
        }
        return $r;
    }
}
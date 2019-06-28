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
class Recharge extends ApiBase
{
    private $UserCur;
    private $Method;
    private $ExternalAddress;
    private $Currency;
    public function __construct(\think\Request $request = null) 
    {
        parent::__construct($request);
        $this->UserCur = new \app\api\model\UserCur();
        $this->Method = new \app\api\model\Method();
        $this->ExternalAddress = new \app\api\model\ExternalAddress();
        $this->Currency = new \app\api\model\Currency();
    }
    //绑定外部充值地址
    public function index()
    {
        $res = $this->userInfo;//用户信息
        $cur_id = trim(input('cur_id'));
        $ps = trim(input('ps'));
        $address = trim(input('address'));
        $pwd = trim(input('pwd'));
        if(!$cur_id || !$ps || !$address || !$pwd){
            $r = rtn(0,lang('not_null'));
        }elseif(encrypt($pwd) !== $res['payment_password']){
            $r = rtn(0,lang('not_password'));
        }else{
            $res = $this->ExternalAddress->inserts($res['id'],$cur_id,$ps,$address);
            if($res){
                $r = rtn(1,lang('success'),$address);
            }else{
                $r = rtn(0,lang('error'));
            }
        }
        return $r;
    }
    //充值记录
    public function listss()
    {
        $res = $this->userInfo;//用户信息
        $cur_id = trim(input('cur_id'));//币种ID
        $p = trim(input('p'));//页码
        if(!$cur_id){
            $r = rtn(0,'not_null');
        }else{
            $res = $this->Method->lists($res['id'],$cur_id,$p,1);
            if($res['page']){
                $r = rtn(1,lang('success'),$res);
            }else{
                $r = rtn(0,lang('null'));
            }
        }
        return $r;
    }
    //本地钱包地址
    public function address(){
        $res = $this->userInfo;//用户信息
        $cur_id = trim(input('cur_id'));//本地钱包地址对应币种
        $list['cur'] = $this->Currency->where('id',$cur_id)->value('name');
        $list['address'] = config($list['cur'].'_ADDRESS');
        $list['external'] = $this->ExternalAddress->where(['uid'=>$res['id'],'cur_id'=>$cur_id])->value('address');
        if($list){
            $r = rtn(0,lang('success'),$list);
        }else{
            $r = rtn(0,lang('null'));
        }
        return $r;
    }
    //外地钱包地址
    public function out_address()
    {
        $res = $this->userInfo;//用户信息
        $cur_id = trim(input('cur_id'));//外部地址对应币种
        $list = $this->ExternalAddress->where(['uid'=>$res['id'],'cur_id'=>$cur_id])->field('id,address')->select();
        if($list){
            $r = rtn(0,lang('success'),$list);
        }else{
            $r = rtn(0,lang('error'));
        }
      return $r;
    }
    //充值
    public function recharge()
    {
        $res = $this->userInfo;//用户信息
        $cur_id = trim(input('cur_id'));//外部地址对应币种
        $number = trim(input('number'));//数量
        $address = trim(input('address'));//数量
        if(!$cur_id || !$number || !$address){//如果币种ID 充值数量 外部充值地址为空
            $r = rtn(0,lang('null'));//返回信息
        }else{
            $list = $this->Method->inserts($address,$number,$res['id'],$cur_id,0,1);
          	if($list){
            	 $r = rtn(0,lang('success'));
            }else{
            	 $r = rtn(0,lang('error'));
            }
        }
        return $r;
    }
}
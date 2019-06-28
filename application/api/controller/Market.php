<?php
namespace app\api\Controller;
use app\common\controller\ApiBase;
use app\api\controller\Lib;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;


class Market extends ApiBase
{
	private $Currency;
    public function __construct(\think\Request $request = null) 
    {
        parent::__construct($request);
        $this->Currency = new \app\api\model\Currency();
      	$this->Order = new \app\api\model\Order();
    }

    /**
		行情列表
    **/
	public function index()
	{
    $map['a.id'] = ['neq',2];
    $list = db('currency')->alias('a')->join('cur_market b','a.id = b.cur_id','left')->where($map)->field('a.id,a.name,b.twenty_four_volume,b.day_rise_fall')->select();
    // $list = db('cur_market')->alias('a')->join('currency b','a.cur_id=b.id','left')->field('b.id,b.name,a.price_new,a.twenty_four_volume,a.day_rise_fall')->order('b.id asc')->select();
    // foreach ($list as $k => $v) {                 
    //   $list[$k]['cny'] = $v['price_new'] * config('USDT_RMB');
    //   if($v['day_rise_fall']<0){
    //     $list[$k]['fall_type'] = 1;
    //     $list[$k]['day_rise_fall'] = ($v['day_rise_fall']*100).'%';
    //   }else{
    //     $list[$k]['fall_type'] = 2;
    //     $list[$k]['day_rise_fall'] = '+'.($v['day_rise_fall']*100).'%';
    //   }
    // }
    foreach ($list as $k => $v) {   
      $array = [];
      $price = db('order')->where('cur_id',$v['id'])->where('order_status',3)->order('done_time desc')->value('price');   
      $price = $price?$price:config('INITIAL_PRICE');
      $array[$k]['id'] = $v['id'];           
      $array[$k]['name'] = $v['name'];          
      $array[$k]['price_new'] = number_format($price,2);
      $array[$k]['twenty_four_volume'] = $v['twenty_four_volume'];           
      $array[$k]['cny'] =number_format($price * config('USDT_RMB'),2);
      if($v['day_rise_fall']<0){
        $array[$k]['fall_type'] = 1;
        $array[$k]['day_rise_fall'] = ($v['day_rise_fall']*100).'%';
      }else{
        $array[$k]['fall_type'] = 2;
        $array[$k]['day_rise_fall'] = '+'.($v['day_rise_fall']*100).'%';
      }
    }
    return rtn(1,lang("success"),$array);
	}

  /**
  *行情列表
  *@param ID 虚拟币ID 
  **/
	public function marketData(){
    $id = input('id');
    if(!$id){
      return rtn(0,lang("parameter"));
    }else{
      //币种行情
      $info = db('cur_market')->alias('a')->join('currency b','a.cur_id=b.id','left')->where('a.cur_id',$id)->field('b.id,b.name,a.price_new,a.twenty_four_volume,a.day_rise_fall,a.max_price as high,a.min_price as low')->order('b.id asc')->find();                
      $info['price_new'] = number_format($info['price_new'],2).'USD';
      if($info['day_rise_fall']<0){
        $info['fall_type'] = 1;
        $info['day_rise_fall'] = ($info['day_rise_fall']*100).'%';
      }else{
        $info['fall_type'] = 2;
        $info['day_rise_fall'] = '+'.($info['day_rise_fall']*100).'%';
      }
      $info['high'] = number_format($info['high'],2);
      $info['low'] = number_format($info['low'],2);
      $info['twenty_four_volume'] = number_format($info['twenty_four_volume'],5);
      $result['market'] = $info;
      //委托订单
      $result['buy_list'] = model('Entrust')->buy_five_gear($id);
      
      $result['sell_list'] = model('Entrust')->sell_five_gear($id);

      //最新成交
      $result['latest_deal'] = model('Order')->latest_deal($id);
    }

    return rtn(1,lang("success"),$result);
	}
}
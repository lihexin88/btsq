<?php
namespace app\api\controller;
use app\common\controller\ApiBase;
use think\Session;
use think\Request;
use think\Db;

/**
 * 交易页面功能
 *
 * @remark 
 */



class Data extends ApiBase
{
    public function _initialize() 
    {
        parent::_initialize();
    }

    /**
     * 交易对K线图
     * @param string @id [交易对ID]
     * @param string @time [时间段：1min, 5min, 15min, 30min, 60min, 1day]
     */
    public function kLine()
    {
        $id = trim(input('id'));
        $time = trim(input('time'));
        $lbcc_area = [1,2,3,4,5,6];
        $huobi_area = [7,8,9,10,13];
        $zb_area = [11];
        if(in_array($id,$lbcc_area)){
			if($time == '1min'){
                $times = 60;
            }elseif($time == '5min'){
                $times = 300;
            }elseif($time == '15min'){
                $times = 900;
            }elseif($time == '30min'){
                $times = 1800;
            }elseif($time == '60min'){
                $times = 3600;
            }else{
                $times = 86400;
            }
            for($i=0;$i<=200;$i++){
                $close = time()-$times*$i;
                $open = $close-$times;
                $arr = db('kline')->where('time','between',[$open,$close])->order('time DESC')->find();
                if($arr){
                    $list[$i] = $arr;
                }
            }
          	if($list){
            	foreach ($list as $key => $value) {
                    $data[$key][0] = date('Y-m-d H:i:s',$value['time']);
                    $data[$key][1] = $value['open_price'];
                    $data[$key][2] = $value['max_price'];
                    $data[$key][3] = $value['min_price'];
                    $data[$key][4] = $value['close_price'];
                    $data[$key][5] = $value['vol'];
                }
                return rtn(0,array_reverse($data));
            }else{
            	 return rtn(0,lang('null'));
            }
            
        }elseif (in_array($id,$huobi_area)) {
            $cur_name = db('currency_area')->alias('a')->join('currency b','a.cur_id = b.id')->where('a.id',$id)->value('name');
            $area_name = db('currency_area')->alias('a')->join('currency b','a.area_id = b.id')->where('a.id',$id)->value('name');
            $cur_area = strtolower($cur_name).strtolower($area_name);
            $Lib = new Lib();
            $huobi_kline = $Lib->get_history_kline($cur_area,$time,300);
            $data = [];
            foreach ($huobi_kline['data'] as $k => $v) {
                $data[$k][0] = date('Y-m-d H:i:s',$v['id']); 
                $data[$k][1] = $v['open']; 
                $data[$k][2] = $v['high']; 
                $data[$k][3] = $v['low']; 
                $data[$k][4] = $v['close']; 
                $data[$k][5] = $v['vol']; 
            }
            return rtn(0,array_reverse($data)); 
        }elseif (in_array($id,$zb_area)) {
            if($time == '60min'){
                $time = '1hour'; 
            }
            $cur_name = db('currency_area')->alias('a')->join('currency b','a.cur_id = b.id')->where('a.id',$id)->value('name');
            $area_name = db('currency_area')->alias('a')->join('currency b','a.area_id = b.id')->where('a.id',$id)->value('name');
            $cur_area = strtolower($cur_name).'_'.strtolower($area_name);
            $url = 'http://api.zb.cn/data/v1/kline?market='.$cur_area.'&type='.$time.'&size=300';
            $zb_data =  json_decode(file_get_contents($url), true);
            foreach ($zb_data['data'] as $k => $v) {
                $zb_data['data'][$k][0] = $v[0]/1000; 
            }
            return rtn(0,$zb_data['data']);
            
        }
    }

    /**
     * 获取聚合行情
     * @param string @id [交易对ID]
     */
    public function merged()
    {
        $id = trim(input('id'));
        $time = trim(input('time'));
        $lbcc_area = [1,2,3,4,5,6];
        $huobi_area = [7,8,9,10,13];
        $zb_area = [11];
        if(in_array($id,$lbcc_area)){

        }elseif (in_array($id,$huobi_area)) {
            $cur_name = db('currency_area')->alias('a')->join('currency b','a.cur_id = b.id')->where('a.id',$id)->value('name');
            $area_name = db('currency_area')->alias('a')->join('currency b','a.area_id = b.id')->where('a.id',$id)->value('name');
            $cur_area = strtolower($cur_name).strtolower($area_name);
            $Lib = new Lib();
            $huobi = $Lib->get_market_tickers();
            $huobi_data = $huobi['data'];
            foreach ($huobi_data as $k => $v) {
                if($v['symbol'] == $cur_area){
                    $return_data['price'] = $v['close'];
                    $return_data['vol'] = $v['vol'];
                    $return_data['high'] = $v['high'];
                    $return_data['low'] = $v['low'];
                    $increase = sprintf('%.2f',($v['close'] - $v['open'])/$v['open']*100);
                    if($Increase > 0){
                        $return_data['increase'] = '+'.$increase.'%';
                    }else{
                        $return_data['increase'] = $increase.'%';
                    }
                }
            }
            
            pre($return_data);exit;
            $data = [];
            return rtn(0,array_reverse($data)); 
        }elseif (in_array($id,$zb_area)) {
            if($time == '60min'){
                $time = '1hour'; 
            }
            $cur_name = db('currency_area')->alias('a')->join('currency b','a.cur_id = b.id')->where('a.id',$id)->value('name');
            $area_name = db('currency_area')->alias('a')->join('currency b','a.area_id = b.id')->where('a.id',$id)->value('name');
            $cur_area = strtolower($cur_name).'_'.strtolower($area_name);
            $url = 'http://api.zb.cn/data/v1/kline?market='.$cur_area.'&type='.$time.'&size=300';
            $zb_data =  json_decode(file_get_contents($url), true);
            foreach ($zb_data['data'] as $k => $v) {
                $zb_data['data'][$k][0] = $v[0]/1000; 
            }
            return rtn(0,$zb_data['data']); 
            
        }
    }
}
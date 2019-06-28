<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 15:16
 */

namespace app\api\model;

use think\Model;

class CurrencyArea extends Model
{

    public function get_area_name(){
        $list = $this -> field('area_id as id') -> group('area_id') -> select() -> toArray();
        foreach($list as $k => $v){
            $Currency = new Currency();
            $list[$k]['name'] = $Currency -> get_cur_text($v['id']);
        }
        return $list;
    }

    /**
     * model 获取交易区中某一交易对左右对应的币种ID
     */
    public function trade_pairs($id){
        $info = $this -> where('id',$id) -> field('cur_id,area_id') -> find();
        return $info;
    }

    /**
     * model 获取交易区列表名
     */
    public function area_list($user,$area_id){
        if(!$area_id){
            $area_id = 1;
        }
        $list = $this -> field('area_id') -> where('area_id',$area_id) -> group('area_id') -> select();
        foreach($list as $k => $v){
            $Currency = new Currency();
            // 获取分区名
            $list[$k]['area_text'] = $Currency -> where('id',$v['area_id']) -> value('name');
            // 获取分区下的交易对
            $list[$k]['area_cur'] = $this -> where('area_id',$area_id) -> select();
            foreach($list[$k]['area_cur'] as $area_k => $area_v){
                $left = $Currency -> get_cur_text($area_v['cur_id']);    // 获取交易对左边的名称
                $right = $Currency -> get_cur_text($area_v['area_id']);  // 获取交易对右边的名称
                $list[$k]['area_cur'][$area_k]['area_cur_text'] = $left.'/'.$right; // 组合交易对名称
                // 释放不需要的变量
                unset($list[$k]['area_cur'][$area_k]['cur_id']);
                unset($list[$k]['area_cur'][$area_k]['area_id']);
            }
        }
        if($list){
            return ['code' => 1,'data' => $list];
        }else{
            return ['code' => 0];
        }
    }

    /**
     * model 搜索交易对
     */
    public function search_area($user,$keywords){
        $Currency = new Currency();
        $ids = $Currency -> get_cur_ids($keywords);
        $cur_id_where['cur_id|area_id'] = array('in',$ids);
        $list = $this -> where($cur_id_where) -> select();
        foreach($list as $k => $v){
            $left = $Currency -> get_cur_text($v['cur_id']);    // 获取交易对左边的名称
            $right = $Currency -> get_cur_text($v['area_id']);  // 获取交易对右边的名称
            $list[$k]['area_cur_text'] = $left.'/'.$right; // 组合交易对名称
        }
        return ['code' => 1,'data' => $list];
    }

    /**
     * model 获取交易区下的交易币种信息
     */
    public function area_currency($data){
        if(!$data['area_id']){
            $data['area_id'] = 1;
        }
        if(!$data['currency_area_id']){
            $data['currency_area_id'] = $this -> where('area_id',$data['area_id']) -> order('id ASC') -> value('id');
        }
        // 交易对信息
        $area_where['area_id'] = $data['area_id'];
        $area_where['id'] = $data['currency_area_id'];
        $result = $this -> where($area_where) -> find();
        $Currency = new Currency();
        $result['cur_text'] = $Currency -> get_cur_text($result['cur_id']);
        $result['area_text'] = $Currency -> get_cur_text($result['area_id']);
        // 页面下方交易列表
        $Order = new Order();
        $order_where['order_status'] = 3;
        $order_where['cur_id'] = $result['cur_id'];
        $result['order'] = $Order -> where($order_where) -> field('trade_type,price,order_number,done_time') -> select();
        foreach($result['order'] as $k => $v){
            switch($v['trade_type']){
                case 1:
                    $result['order'][$k]['trade_type_text'] = '卖出';
                    $result['order'][$k]['trade_type_color'] = 'green';
                    break;
                case 2:
                    $result['order'][$k]['trade_type_text'] = '买入';
                    $result['order'][$k]['trade_type_color'] = 'red';
                    break;
            }
            $result['order'][$k]['done_date'] = date('Y-m-d H:i:s',$v['done_time']);
        }

        if($result){
            return ['code' => 1,'data' => $result];
        }else{
            return ['code' => 0];
        }
    }

}
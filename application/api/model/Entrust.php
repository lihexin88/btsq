<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/29
 * Time: 16:30
 */

namespace app\api\model;


use think\Model;

class Entrust extends Model
{

    public function buy_five_gear($id)
    {
        $data = [];
        $buy_array = [];
        //查询出所有的买记录
        $buy_list = db('trade')->where(['trade_type' => 2, 'trade_status' => 1,'cur_id' => $id])->order('price desc,start_time desc')->select();
        foreach ($buy_list as $k => $v) {
            $data[$v['price']][] = $v;
        }
        foreach ($data as $k => $v) {
            $number = 0;
            foreach ($v as $k1 => $v1) {
                $number += $v1['number'];
            }
            array_push($buy_array, ['price' => number_format($k,2), 'number' => $number]);
        }
        return $buy_array;
    }

    public function sell_five_gear($id)
    {
        $data = [];
        $sell_array = [];
        $buy_list = db('trade')->where(['trade_type' => 1, 'trade_status' => 1,'cur_id' => $id])->order('price asc,start_time desc')->select();
        foreach ($buy_list as $k => $v) {
            $data[$v['price']][] = $v;
        }
        foreach ($data as $k => $v) {
            $number = 0;
            foreach ($v as $k1 => $v1) {
                $number += $v1['number'];
            }
            array_push($sell_array, ['price' => number_format($k,2), 'number' => $number]);
        }
        return $sell_array;
    }

}
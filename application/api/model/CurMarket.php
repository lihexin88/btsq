<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/28
 * Time: 11:56
 */

namespace app\api\model;


use think\Model;

class CurMarket extends Model
{


    /**
     * 获取最新成交价
     * @param $cur_id 币种id
     * @return bool|mixed
     */
    static public function get_lastest_price($cur_id)
    {
        $lastest_price = self::where(['cur_id'=>$cur_id])->max('id')->find();
        if(!$lastest_price){
            $lastest_price = new self();
            $lastest_price->price_new = false;
        }
        return $lastest_price->price_new;
    }

    /**
     * 判断是否存在
     * @param $cur_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function is_exist($cur_id){
        $info = $this -> where('cur_id',$cur_id) -> find();
        if($info){
            $result = true;
        }else{
            $result = false;
        }
        return $result;
    }

    /**
     * 获取日涨跌
     * @param $cur_id
     * @return mixed
     */
    public function get_day_rise_fall($cur_id){
        $day_rise_fall = $this -> where('cur_id',$cur_id) -> value('day_rise_fall');
        return $day_rise_fall;
    }

}
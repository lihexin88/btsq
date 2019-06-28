<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/11
 * Time: 15:05
 */

namespace app\api\model;


use think\Model;

class Bank extends Model
{
    /**
     * 返回所有银行信息
     * @return Bank[]|false
     * @throws \think\exception\DbException
     */
    static public function all_bank()
    {
        $banks = self::all();
        return $banks;
    }

    /**
     * 获取银行名称
     * @param $id
     * @return mixed
     */
    public function get_bank_name_by_id($id){
        $bank_name = $this -> where('id',$id) -> value('bank_name');
        return $bank_name;
    }
}
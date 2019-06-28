<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/28
 * Time: 10:23
 */

namespace app\api\model;


use think\Model;

class VipDetails extends Model
{
    /**
     * 根据等级获取vip权益
     * @param $vip vip等级
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function details($vip)
    {
        $vip_rights = self::where(['vid'=>$vip])->find();
        return $vip_rights;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/28
 * Time: 16:12
 */

namespace app\api\model;


use think\Db;
use think\Exception;
use think\Model;

class RewordToParent extends Model
{
    static public $rolling_times = 1;

    /**
     * 向上反馈给父层投资额
     * @param $user 用户对象
     * @param $number 数量
     * @return bool
     * @throws \think\exception\DbException
     */
    static public function reword_to_parent($user, $number)
    {
        $parent_times = 10;
        $parent = User::get(['parent_id' => $user['id']]);
        if ($parent && self::$rolling_times <= $parent_times) {
            $parent->child_spend += $number;
            $re = $parent->force()->save();
            if (!$re) {
                throw new Exception("运行出错~请稍候再试");
            }
            /*无限代投入量记录*/
            $parent_times += self::$rolling_times + 1;
            return self::reword_to_parent($parent, $number);
        }
        return true;
    }
}
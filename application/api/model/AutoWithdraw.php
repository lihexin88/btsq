<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/22
 * Time: 14:51
 */

namespace app\api\model;


use think\Model;

class AutoWithdraw extends Model
{
    /**
     * 获取用户自动提配置
     * @param $user
     * @return AutoWithdraw|null
     * @throws \think\exception\DbException
     */
    static public function get_info($user)
    {
        $this_info = self::get(['uid'=>$user['id']]);
        if(!$this_info){
            $this_info = new self();
            $this_info->uid = $user['id'];
            $this_info->save();
            return self::get_info($user);
        }
        return $this_info;
    }
}
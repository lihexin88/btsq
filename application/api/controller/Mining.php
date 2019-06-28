<?php
/**
 * Created by 李.
 * 万物源于汇编
 * hello world乃万恶之源
 * Date: 2018/12/18
 * Time: 10:27
 */

namespace app\api\controller;


use app\api\model\UserMining;
use think\Controller;

class Mining extends Controller
{
    /**
     * 获取用户所有矿机
     * @param $user
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function get_mining($user)
    {
        if(!$user){
            return false;
        }
        $UserMining = UserMining::where(['uid'=>$user['uid']])->select();
        return $UserMining;
    }


}
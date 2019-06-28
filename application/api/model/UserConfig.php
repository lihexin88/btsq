<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/8
 * Time: 14:36
 */

namespace app\api\model;

use think\Model;

class UserConfig extends Model
{
    /**
     * 获取用户配置信息
     * @param $user
     * @return UserConfig|null
     * @throws \think\exception\DbException
     */
    static public function config($user)
    {
        $config = self::get(['uid'=>$user['id']]);
        if(!$config){
            $config = new self();
            $config->uid = $user['id'];
            $config->save();
            $config = self::config($user);
        }
        return $config;
    }


    /**
     * 修改用户配置
     * @param $user 用户对象
     * @param $array 修改的数组
     * @return bool|false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function change($user,$array)
    {
        $UserConfig = $this::get(['uid'=>$user['id']]);
        if($UserConfig){
            foreach ($array as $k=>$v){
                $UserConfig->$k = $v;
            }
            $UserConfig->save();
        }else{
            foreach ($array as $k=>$v){
                $user_config[$k] = $v;
            }
            $this->save($user_config);
        }
        return true;
    }


}
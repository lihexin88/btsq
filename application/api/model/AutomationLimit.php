<?php
/**
 * Created by 李.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 14:11
 */

namespace app\api\model;


use think\Model;

class AutomationLimit extends Model
{
    /**
     * 获取可访问ip列表
     * @return AutomationLimit[]|false
     * @throws \think\exception\DbException
     */
    static public function ip_lists()
    {
        $ip_list_t = self::all();
        $ip_list = null;
        foreach ($ip_list_t as $k => $v) {
            $ip_list[$k] = $v['ip'];
        }
        return $ip_list;
    }
}
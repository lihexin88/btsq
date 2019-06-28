<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 13:37
 */

namespace app\api\model;

use think\Model;

class Dict extends Model
{

    /**
     * 获取状态名称
     * @param $dict_where
     * @return mixed
     */
    public function get_dict_key($dict_where){
        $key = $this -> where($dict_where) -> value('key');
        return $key;
    }

}
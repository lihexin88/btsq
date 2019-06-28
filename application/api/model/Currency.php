<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/27
 * Time: 17:13
 */

namespace app\api\model;


use think\Model;

class Currency extends Model
{
    /**
     * 根据币种id获取币种名字
     * @param $id 币种id
     * @return mixed
     * @throws \think\exception\DbException
     */
    static public function get_name_by_id($id)
    {
        $name = self::get(['id' => $id]);
        return $name['name'];
    }

    /**
     * @param $id
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function get_cur_text($id)
    {
        return self::get_name_by_id($id);
    }

    //币种信息
    public function cur_list()
    {
        return $this->where('id', 'neq', 7)->select();
    }

    /**
     * 通过关键字母搜索币种名称
     * @param $keywords
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_cur_ids($keywords)
    {
        $array_ids = $this->where('name', 'LIKE', '%' . $keywords . '%')->field('id')->select();
        $ids = '';
        foreach ($array_ids as $k => $v) {
            $ids .= $v['id'] . ',';
        }
        $ids = trim($ids, ',');
        return $ids;
    }

    /**
     * 获取币种信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_rise_fall($id)
    {
        $rise_fall = $this->where('id', $id)->find();
        return $rise_fall;
    }
}


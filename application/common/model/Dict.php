<?php
namespace app\common\model;

use think\Model;
class Dict extends Model
{

    public function showList($type)
    {
        $list = $this->where(array('type' => $type, 'state' => 1))->field('key,value')->order('id asc')->select()->toArray();
        return $list;
    }

    /* 根据键名取键值 用于foreach */
    public function showKey($type)
    {
        $list = $this->where(array('type' => $type, 'state' => 1))->field('key,value')->order('id desc')->select()->toArray();
        foreach ((array)$list as $k => $v) {
            $return[$v['value']] = $v['key'];
        }
        return $return;
    }

    public function showValue($type)
    {
        $list = $this->where(array('type' => $type, 'state' => 1))->field('key,value')->order('id desc')->select()->toArray();
        foreach ((array)$list as $k => $v) {
            $return[$v['key']] = $v['value'];
        }
        return $return;
    }

}
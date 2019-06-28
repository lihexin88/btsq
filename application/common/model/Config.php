<?php
namespace app\common\model;
use think\Model;
class Config extends Model
{

    //获取全局配置
    public function getConfig()
    {
        $list = $this->field('key,value')->select();
        foreach ($list as $k => $v) {
            $listinfo[$v['key']] = $v['value'];
        }
        return $listinfo;
    }

}
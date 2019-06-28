<?php

namespace app\api\model;

use think\Model;
use think\Validate;

class Banner extends Model
{

    /**
     * 获取首页轮播图列表
     */
    public function homePageList()
    {
        $list = $this->where(array('state'=>1))->order('sort','desce')->select()->toArray();
        return $list;
    }

}

<?php

namespace app\api\model;

use think\Model;
use think\Validate;

class Notice extends Model
{

    /**
     * 获取首页公告列表
     */
    public function homePageList()
    {
        $list = $this->where(array('state'=>1))->order('create_time','ase')->limit(10)->select()->toArray();
        return $list;
    }

     /**
     * 公告列表
     * @param string $p [页数]
     */
    public function noticeListPage($map,$p)
    {
        $list = $this->where($map)->page($p, 10)->order('create_time desc')->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = date('Y-m-d',strtotime($v['create_time']));
            $list[$k]['update_time'] = date('Y-m-d',strtotime($v['update_time']));
        }
        $data['list'] = $list;
        $data['count'] = ceil(($this->where($map)->count())/10);
        return $data;
    }

     /**
     * 公告详情
     * @param string $id [公告ID]
     */
    public function noticeInfo($id)
    {
    	$map['state'] = 1;
    	$map['id'] = $id;
    	$list = $this->where($map)->find();
        return $list;
    }

}

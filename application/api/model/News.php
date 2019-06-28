<?php

namespace app\api\model;

use think\Model;
use think\Validate;

class News extends Model
{
    const PAGE_LIMIT = '20';//用户表分页限制

     /**
     * 公告列表
     * @param string $p [页数]
     */
    public function newsListPage($p,$page_size)
    {
        $data = [];
        $list = db('news')->order('create_time desc')->page($p,$page_size)->field('id,'.config('THINK_VAR').'title,'.'create_time')
            ->select();
        foreach ($list as $k => $v) {
            $data[$k]['id'] = $v['id'];
            $data[$k]['title'] = $v[config('THINK_VAR').'title'];
            $data[$k]['time'] = date('Y-m-d',$v['create_time']);
        }
        return $data;
    }

     /**
     * 公告详情
     * @param string $id [公告ID]
     */
    public function newsInfo($id)
    {
    	$map['id'] = $id;
    	$list = db('news')->where($map)->field('id,'.config('THINK_VAR').'title,'.config('THINK_VAR').'content,create_time')->find();
        $data['id'] = $list['id'];
        $data['title'] = $list[config('THINK_VAR').'title'];
        $data['content'] = $list[config('THINK_VAR').'content'];
        $data['time'] = date('Y-m-d H:i:s',$list['create_time']);
        return $data;
    }

}

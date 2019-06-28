<?php
namespace app\common\model;
use app\common\model\Base;
use think\Model;
class AuthRule extends Base
{
    const RULE_URL = 1;
    const RULE_MAIN = 2;

	/**
     * 权限菜单-超管
     */
    public function ruleMap()
    {
        $list = $this->where(['pid'=>0,'is_show'=>1])->field('id,name,title,icon')->order('sort desc,id asc')->select()->toArray();
        foreach ((array)$list as $k => $v) {
            $list[$k]['child'] = $this->where(['pid'=>$v['id'],'is_show'=>1])->field('id,name,title,icon')->order('sort desc,id asc')->select();
            $list[$k]['count'] = count($list[$k]['child']);
        }
        return $list;
    }

    /**
     * 权限菜单-非超管
     * @return [type] [description]
     */
    public function ruleMaps()
    {
        $group_id = session('group_id');
        $idArr = db('auth_group')->where('id',$group_id[0])->column('rules');
        $list = $this->where(['pid'=>0,'is_show'=>1,'id'=>['in',$idArr[0]]])->field('id,name,title,icon')->order('sort desc,id asc')->select()->toArray();
        foreach ((array)$list as $k => $v) {
            $list[$k]['child'] = $this->where(['pid'=>$v['id'],'is_show'=>1,'id'=>['in',$idArr[0]]])->field('id,name,title,icon')->order('sort desc,id asc')->select()->toArray();
            $list[$k]['count'] = count($list[$k]['child']);
        }
        return $list;
    }

    /**
     * 根据URL获取名称
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    public function ruleTitle($url)
    {
    	return $this->getFieldByName($url,'title');
    }
}
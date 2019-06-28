<?php
namespace  app\admin\model;
use think\Model;
use think\Request;
use think\db;
use think\Validate;
class ExternalAddress extends Model
{
    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量


    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p  页码
     * @return array      返回列表
     */
    public function external($map, $p)
    {
        $request= Request::instance();
        $list = $this->where($map)->order('id desc')->page($p, self::PAGE_LIMIT)->select()->toArray();
        foreach ($list as $k => $v) {
             $list[$k]['account'] = db('user')->where('id',$v['uid'])->value('account');
          	 $list[$k]['cur_name'] = db('currency')->where('id',$v['cur_id'])->value('name');
        }
        $return['count'] = $this->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        return $return;
    }

}
<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;
use think\Validate;
class StoData extends Base
{
    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量
    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p  页码
     * @return array      返回列表
     */
    public function infoLists($id, $p)
    {
        $request= Request::instance();
        $list = $this->where('uid',$id)->order('id desc')->page($p, self::PAGE_LIMIT)->select()->toArray();
        foreach ($list as $key => $value) {
            $list[$key]['time'] = $value['time'] ==0?'还未开启':date('Y-m-d H:i:s',$value['time']);
          	$list[$key]['cur_id'] = db('currency')->where('id',$value['cur_id'])->value('name');
          	$list[$key]['status'] = $value['time'] ==0?'未开启':'已开启';
        }
        $return['count'] = $this->where('uid',$id)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        return $return;
    }
}
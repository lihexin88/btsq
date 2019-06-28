<?php
namespace  app\admin\model;

use app\common\model\Base;
use think\Request;
use think\Db;
use think\Exception;

use app\admin\model\User;
use app\admin\model\Dict;

class BonusList extends Base
{

    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量


    /**
     * 获取列表
     * @param $map
     * @param $p
     * @return mixed
     * @throws \think\exception\DbException
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     */
    public function infoList($map, $p)
    {
        $request= Request::instance();
        $list = $this -> where($map) -> select();
        $count = $this -> count();
        $page = boot_page($count, self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        if($list){
            $list = $list -> toArray();
        }
        $User = new User();
        $Dict = new Dict();
        foreach ($list as $k => $v) {
            // 用户邮箱
            $list[$k]['user_email'] = $User -> get_user_email_by_id($v['uid']);
            // 返佣人邮箱
            $list[$k]['t_user_email'] = $User -> get_user_email_by_id($v['tid']);
            // 分红类型
            $dict_where['type'] = 'bonus_type';
            $dict_where['value'] = $v['type'];
            $list[$k]['type_text'] = $Dict -> get_dict_key_by_where($dict_where);
            // 格式化日期
            $list[$k]['date'] = date('Y-m-d H:i:s',$v['time']);
        }

        $return['count'] = $count;
        $return['list'] = $list;
        $return['page'] = $page;
        return $return;
    }

}
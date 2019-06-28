<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/14
 * Time: 17:13
 */

namespace app\admin\model;

use app\common\model\Base;
use think\Request;


class AdminMessage extends Base
{
    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量

    // 时间字段取出后的默认时间格式
    protected $dateFormat;

    /**
     * 记录发送短信记录
     * @param $type
     */
    public function record_admin_message($type){
        $in_message['aid'] = session('aid');
        $in_message['type'] = $type;
        $in_message['create_time'] = time();
        $this -> insert($in_message);
    }

    /**
     * 短信发送记录
     * @param $p
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function message_list($p){
        $message_list = $this -> page($p,self::PAGE_LIMIT) -> order('id DESC') -> select();
        foreach($message_list as $k => $v){
            $Admin = new Admin();
            $message_list[$k]['adminname'] = $Admin -> get_username_by_id($v['aid']);
        }
        $message_count = $this -> count();
        $request = Request::instance();
        $message_page = boot_page($message_count,self::PAGE_LIMIT,self::PAGE_SHOW,$p,$request->action());

        $return['log_list'] = $message_list;
        $return['log_count'] = $message_count;
        $return['log_page'] = $message_page;
        return $return;
    }
}
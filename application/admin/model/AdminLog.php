<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/14
 * Time: 11:32
 */

namespace app\admin\model;

use app\common\model\Base;
use app\common\controller\AdminBase;
use think\Request;


class AdminLog extends Base
{

    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量

    // 时间字段取出后的默认时间格式
    protected $dateFormat;

    /**
     * 记录登陆管理员IP信息
     * @param $aid
     */
    public function recordLogin($aid){
    	$request = Request::instance();
        $in_log['aid'] = $aid;
        $in_log['ip'] = $request->ip();
        $in_log['create_time'] = time();
        $this -> insert($in_log);
	
    }

    /**
     * 获取用户登陆列表
     * @param $p
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function log_list($p){
        $log_list = $this -> page($p,self::PAGE_LIMIT) -> order('id DESC') -> select();
        foreach($log_list as $k => $v){
            $Admin = new Admin();
            $log_list[$k]['adminname'] = $Admin -> get_username_by_id($v['aid']);
        }
        $log_count = $this -> count();
        $request = Request::instance();
        $log_page = boot_page($log_count,self::PAGE_LIMIT,self::PAGE_SHOW,$p,$request->action());

        $return['log_list'] = $log_list;
        $return['log_count'] = $log_count;
        $return['log_page'] = $log_page;
        return $return;
    }

}
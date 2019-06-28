<?php

namespace app\admin\model;

use app\common\model\Base;
use think\Request;
use think\db;
use think\Exception;

class Feedback extends Base
{
    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量


    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p 页码
     * @return array      返回列表
     */
    public function infoList($map, $p = 1)
    {
        $request = Request::instance();
        $list = db('feedback')->alias('a')->join('user b', 'a.uid = b.id', 'left')->join('feedback_type c', 'a.f_type = c.id', 'left')->where($map)->limit(($p - 1) * self::PAGE_LIMIT, self::PAGE_SHOW)->field('a.id,a.f_type,a.content,a.create_time,a.status,b.email,c.chs_name')->order('create_time desc')->select();
        foreach ($list as $k => $v) {
            $list[$k]['feedback_status'] = $v['status'] == 1 ? '已回复' : '未回复';
        }
        $return['count'] = db('feedback')->alias('a')->join('user b', 'a.uid = b.id', 'left')->join('feedback_type c', 'a.f_type = c.id', 'left')->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p, $request->action());
        return $return;
    }

    /**
     * 新增/修改
     * @param  array $data 传入信息
     */
    public function saveInfo($data)
    {
        if (array_key_exists('id', $data)) {
            $id = $data['id'];
            if (!empty($id)) {
                $where = true;
            } else {
                $where = false;
            }
        } else {
            $where = false;
        }
        $User = new User;
        $result = $User->allowField(true)->isUpdate($where)->save($data);
        if (false === $result) {
            // 验证失败 输出错误信息
            return ['status' => 0, 'info' => $User->getError()];
        } else {
            return array('status' => 1, 'info' => '保存成功', 'url' => url('index'));
        }
    }
}
<?php

namespace app\admin\model;

use app\common\model\Base;
use think\helper\Time;
use think\Request;
use think\db;

class Trade extends Base
{

    /**
     *添加时自动完成
     */
    protected $insert = array('start_time', 'sell_status' => 1);

    /**
     *更新时自动完成
     */
    protected $update = [];

    public function setStartTimeAttr()
    {
        return time();
    }

    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量

    /**
     * 返回订单列表
     * @param $trade_type
     * @return array
     */
    public function show_list($trade_type)
    {
        $list = [];
        $first = $trade_type == 1 ? config("FIRST_SELL_ID") : config("FIRST_BUY_ID");
        $list = $this->get_list_next($first, $list);
        return $list;
    }

    /**
     * 遍历订单列表
     * @param $next_id
     * @param $list
     * @return array
     * @throws \think\exception\DbException
     */
    protected function get_list_next($next_id, $list)
    {
        $item = $this->get(['id' => $next_id]);
        if ($item) {
            $list[] = $item;
            if ($item['next']) {
                return $this->get_list_next($item['next'], $list);
            }
        }
        return $list;
    }

    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p 页码
     * @return array      返回列表
     */
    public function infoList($map, $p)
    {
        $request = Request::instance();

        $list = db('trade')->where($map)->order('id desc')->page($p, self::PAGE_LIMIT)->select();
        $tradetextpeArr = model('Common/Dict')->showKey('trade_status');

        // pre($tradeTypeArr);
        foreach ($list as $k => $v) {
            $list[$k]['start_time'] = date("Y-m-d H:i:s", $v['start_time']);
            $list[$k]['trade_type'] = $v['trade_type'] == 1 ? '卖出' : '买入';
            $list[$k]['trade_status'] = $tradetextpeArr[$v['trade_status']];
            $list[$k]['user_name'] = db('user')->where('id', $v['uid'])->value('email');
            $list[$k]['cur_name'] = db('currency')->where('id', $v['cur_id'])->value('name');
        }
        // pre($list);exit;
        $return['count'] = $this->where($map)->count();
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
        $Sell = new Sell;
        $result = $Sell->allowField(true)->isUpdate($where)->save($data);
        if (false === $result) {
            return ['status' => 0, 'info' => $AuthGroup->getError()];
        } else {
            return array('status' => 1, 'info' => '保存成功', 'url' => url('index'));
        }
    }

    /**
     * 改变状态
     * @param  array $data 传入数组
     */
    public function changeState($data)
    {
        if ($this->where(array('id' => $data['id']))->update(array('sell_status' => $data['sell_status']))) {
            return array('status' => 1, 'info' => '更改状态成功');
        } else {
            return array('status' => 0, 'info' => '更改状态失败');
        }
    }


    /**
     * 删除
     * @param  string $id ID
     */
    public function deleteInfo($id)
    {
        if ($this->where(array('id' => $id))->delete()) {
            return ['status' => 1, 'info' => '删除成功'];
        } else {
            return ['status' => 0, 'info' => '删除失败,请重试'];
        }
    }

    /**
     * 根据查询条件获取信息
     * @param string $map [查询条件]
     * @return mixed
     */
    public function infodata($map)
    {
        $list = $this->where($map)->find();
        if (!is_null($list)) {
            return $list->toArray();
        }
        return false;
    }

}
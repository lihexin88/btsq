<?php

namespace app\admin\controller;

use app\api\controller\Transaction;
use app\common\controller\AdminBase;
use think\Exception;
use think\Request;
use think\Db;
use app\admin\model\Trade as TradeModel;

class Trade extends Admin
{
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {
        $map = [];
        $keywords = input('get.keywords') ? input('get.keywords') : null;
        if ($keywords) {
            $where['email'] = array('like', '%' . trim($keywords) . '%');
            $uids = db('user')->where($where)->column('id');
            $map['uid'] = ['in', $uids];
        }
        if (is_numeric(input('get.trade_type'))) {
            $map['trade_type'] = input('get.trade_type');
        }
        if (is_numeric(input('get.trade_status'))) {
            $map['trade_status'] = input('get.trade_status');
        } else {
            $map['trade_status'] = ['neq', 6];
        }
        if (is_numeric(input('get.money_type'))) {
            $map['cur_id'] = input('get.money_type');
        }
        $start_time = input('start_time') ? strtotime(input('start_time')) + 46800 : 0;
        $end_time = input('end_time') ? strtotime(input('end_time')) + 46800 : 99999999999;
        $map['start_time'] = ['between', "$start_time,$end_time"];

        $Trade = new TradeModel();
        $trade = model('Trade')->infoList($map, $p);
        $this->assign("info", $trade);
        $this->assign("trade_status", model("Common/Dict")->showList('trade_status'));//状态
        $this->assign("trade_type", model("Common/Dict")->showList('trade_type'));//卖出
        return $this->fetch();
    }

    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {
        if (Request::instance()->isPost()) {
            return json(model('Trade')->changeState(input('post.')));
        }
    }

    /**
     * 订单链表
     * @return mixed
     */
    public function trade_list()
    {
        $Trade = new TradeModel();
        $where['trade_type'] = 1;
        if ($_POST['trade_type'] && in_array($_POST['trade_type'], array(1, 2))) {
            $where['trade_type'] = $_POST['trade_type'];
        }
        $list = $Trade->show_list($where['trade_type']);
        $this->assign('list', $list);
        $this->assign('type', $where['trade_type']);
        return $this->fetch();
    }

    /**
     * 后台删除节点
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function delete_link()
    {
//        数据传递完整性
        if (!$_POST['id']) {
            return rtn(0, "信息不完整");
        }
//        数据获取完整性
        $this_node = \app\admin\model\Trade::get(['id' => $_POST['id']]);
        if (!$this_node['id']) {
            return rtn(0, "数据不存在");
        }
        try {
            Db::startTrans();

//            更新订单状态为7，管理员删除节点
            $this_node->trade_status = 7;
            if (!$this_node->force(true)->save()) {
                throw new Exception("删除失败");
            }
            \app\api\model\Trade::delete_link($_POST['id']);
            Db::commit();
            return rtn(1, "删除成功");
        } catch (\Exception $e) {
            Db::rollback();
            return rtn(0, $e->getMessage());
        }
    }
}
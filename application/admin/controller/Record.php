<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class Record extends Admin
{
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {
        $map = [];
        if (is_numeric(input('get.transaction_type'))) {
            $map['transaction_type'] = input('get.transaction_type');
        }
         if (is_numeric(input('get.pay_type'))){
            $map['pay_type'] = input('get.pay_type');
        }
        $this->assign("info", model('Record')->infoList($map, $p));
        $this->assign("pay_type", model("Common/Dict")->showList('pay_type'));//状态
        $this->assign("transaction_type", model("Common/Dict")->showList('transaction_type'));
        return $this->fetch();
    }

    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {
        if (Request::instance()->isPost()) {
            return json(model('Record')->changeState(input('post.')));
        }
    }
}
<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class Treasure extends Admin
{
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {
        $map = [];
        if (is_numeric(input('get.user_id'))) {
            $map['user_id'] = input('get.user_id');
        }
        if (is_numeric(input('get.treasure_type'))) {
            $map['treasure_type'] = input('get.treasure_type');
        }

         if (is_numeric(input('get.pay_type'))){
            $map['pay_type'] = input('get.pay_type');
        }
        $this->assign("info", model('Treasure')->infoList($map, $p));
        $this->assign("state", model("Common/Dict")->showList('treasure_type'));//状态
        $this->assign("userlist", model("User")->showList());
        return $this->fetch();
    }

    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {
        if (Request::instance()->isPost()) {
            return json(model('Treasure')->changeState(input('post.')));
        }
    }
}
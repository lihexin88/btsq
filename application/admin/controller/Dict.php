<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class Dict extends Admin
{

    public function index($p = 1)
    {
        $this->assign("info", model("Dict")->infoList(array(), $p));
        return $this->fetch();
    }

    public function add()
    {
        if (Request::instance()->isPost()) {
            return json(model('Dict')->saveInfo(input('post.')));
        }
        $info = ['type'=>null,'key'=>null,'value'=>null,'id'=>null,'state'=>1];
        $this->assign("info", $info);
        return $this->fetch();
    }

    public function edit($id)
    {
        $this->assign("info", model("Dict")->listInfo($id));
        return $this->fetch('add');
    }
}
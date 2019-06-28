<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;


class Bidders extends Admin
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
            $map['title'] = array('like', '%' . trim($keywords) . '%');
        }
        if (is_numeric(input('get.bidders_state'))) {
            $map['status'] = input('get.bidders_state');
        }
        $this->assign("info", model('Bidders')->infoList($map, $p));
        $this->assign("state", model("Common/Dict")->showList('bidders_state'));//状态
        return $this->fetch();
    }

    /**
     * 新增
     */
    public function add()
    {
        if(Request::instance()->isPost()){
            return json(model('Bidders')->saveInfo(input('post.')));
        }
        $this->assign('info',$info);
        $this->assign('pagename','竞拍管理');
        return $this->fetch();
    }
    public function edit($id)
    {
        if (Request::instance()->isPost()) {
            return json(model('Bidders')->changeState(input('post.')));
        }
    }
    /**
     * 删除信息
     * @param  string $id ID
     */
    public function delete()
    {
        if (Request::instance()->isPost()) {
            return json(model('Bidders')->deleteInfo(input('post.id')));
        }
    }

}
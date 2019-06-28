<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class Notice extends Admin
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
            $map['tittle'] = array('like', '%' . trim($keywords) . '%');
        }
        if (is_numeric(input('get.state'))) {
            $map['state'] = input('get.state');
        }
        $this->assign("info", model('Notice')->infoList($map, $p));
        $this->assign("state", model("Common/Dict")->showList('is_show'));//状态
        return $this->fetch();
    }

    /**
     * 新增
     */
    public function add()
    {
        if(Request::instance()->isPost()){
            return json(model('Notice')->saveInfo(input('post.')));
        }
        $info = ['id'=>null,'tittle'=>null,'content'=>null];
        $this->assign('info',$info);
        $this->assign('pagename','添加APP文档');
        return $this->fetch();
    }

    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {
        if (Request::instance()->isPost()) {
            return json(model('Notice')->changeState(input('post.')));
        }
        $this->assign("info", model('Notice')->infodata(array('id'=>$id)));
        $this->assign('pagename','添加APP文档修改');
        return $this->fetch('add');
    }

}
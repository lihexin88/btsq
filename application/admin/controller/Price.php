<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class Price extends Admin
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
            $map['accout'] = array('like', '%' . trim($keywords) . '%');
        }
        if (is_numeric(input('get.state'))) {
            $map['state'] = input('get.state');
        }
        $this->assign("info", model('Price')->infoList($map, $p));
        return $this->fetch();
    }

    /**
     * 新增
     */
    public function add()
    {
        if(Request::instance()->isPost()){
            return json(model('Price')->saveInfo(input('post.')));
        }
        $info = ['id'=>null,'username'=>null,'accout'=>null,'alipay_accout'=>null,'parent_id'=>null,'dfs'=>null,'usd'=>null,'tel'=>null,'identity'=>null];
        $this->assign('info',$info);
        $this->assign('pagename','添加用户');
        return $this->fetch();
    }

    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {
        if (Request::instance()->isPost()) {
            return json(model('Price')->changeState(input('post.')));
        }
        $this->assign("info", model('Price')->infodata(array('id'=>$id)));
        $this->assign('pagename','修改用户');
        return $this->fetch('add');
    }

    /**
     * 删除信息
     * @param  string $id ID
     */
    public function delete()
    {
        if (Request::instance()->isPost()) {
            return json(model('Price')->deleteInfo(input('post.id')));
        }
    }
}
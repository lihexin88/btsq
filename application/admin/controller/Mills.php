<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;


class Mills extends Admin
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
        if (is_numeric(input('get.status'))) {
            $map['status'] = input('get.status');
        }
        $this->assign("info", model('Mills')->infoList($map, $p));
        $this->assign("state", model("Common/Dict")->showList('common_state'));//状态
        return $this->fetch();
    }

    /**
     * 新增
     */
    public function add()
    {
        if(Request::instance()->isPost()){
            return json(model('Mills')->saveInfo(input('post.')));
        }
        
        $this->assign('info',$info);
        $this->assign('pagename','矿车管理');
        return $this->fetch();
    }
  
    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {
        if (Request::instance()->isPost()) {
            return json(model('Mills')->changeState(input('post.')));
        }
        $this->assign("info", model('Mills')->infodata(array('id'=>$id)));
        $this->assign('pagename','修改矿车');
        return $this->fetch('add');
    }
    

    /**
     * 删除信息
     * @param  string $id ID
     */
    public function delete()
    {
        if (Request::instance()->isPost()) {
            return json(model('Mills')->deleteInfo(input('post.id')));
        }
    }

}
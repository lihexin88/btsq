<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class News extends Admin
{
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {
            $start_time = input('start_time')?strtotime(input('start_time'))+46800:0;
        $end_time = input('end_time')?strtotime(input('end_time'))+46800:99999999999;
        $map['create_time'] = ['between',"$start_time,$end_time"];
        $this->assign("info", model('News')->infoList($map, $p));
        return $this->fetch();
    }

    /**
     * 新增
     */
    public function add()
    {
        if(Request::instance()->isPost()){
            return json(model('News')->saveInfo(input('post.')));
        }
        $info = ['id'=>null,'tittle'=>null,'content'=>null];
        $this->assign('info',$info);
        $this->assign('pagename','添加资讯');
        return $this->fetch();
    }

    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {

        if (Request::instance()->isPost()) {
            return json(model('News')->changeState(input('post.')));
        }
        $this->assign("info", model('News')->infodata(array('id'=>$id)));
        $this->assign('pagename','修改信息');
        return $this->fetch('add');
    }

    /**
     * 删除信息
     * @param  string $id ID
     */
    public function delete()
    {
        if (Request::instance()->isPost()) {
            return json(model('News')->deleteInfo(input('post.id')));
        }
    }
}
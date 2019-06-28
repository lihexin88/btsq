<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class 	Question extends Admin
{
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {
        $map = [];

           if (is_numeric(input('get.uid'))) {
            $map['uid'] = input('get.uid');
        }

        if (is_numeric(input('get.question_state'))) {
            $map['question_state'] = input('get.question_state');
        }

        $this->assign("info", model('Question')->infoList($map, $p));
        $this->assign("question_state", model("Common/Dict")->showList('question_state'));//状态
        $this->assign("userlist", model("User")->showList());
        return $this->fetch();
    }

    /**
     * 新增
     */
    public function add()
    {
        if(Request::instance()->isPost()){
            return json(model('Question')->saveInfo(input('post.')));
        }
        $info = ['id'=>null,'seller_id'=>null,'sell_number'=>null,'sell_amount'=>null,'tel'=>null];
        $this->assign('info',$info);
        $this->assign('pagename','添加挂卖');
        return $this->fetch();
    }

    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {
        $this->assign("info", model('Question')->infodata(array('id'=>$id)));
        $this->assign('pagename','修改用户');
        return $this->fetch('add');
    }
        /**
     * 回复
     * @param  string $id ID
     */
    public function reply($id)
    {
        
        if(Request::instance()->isPost()){ 
            $data = input('post.');
            $data['question_state'] = 1;
            return json(model('Question')->saveInfo($data));
        }
    
        $this->assign("info", model('Question')->infodata(array('id'=>$id)));

         
        $this->assign('id',$id);
        $this->assign('pagename','修改用户');
        return $this->fetch();
    }

    /**
     * 删除信息
     * @param  string $id ID
     */
    public function delete()
    {
        if (Request::instance()->isPost()) {
            return json(model('Question')->deleteInfo(input('post.id')));
        }
    }
}
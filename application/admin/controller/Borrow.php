<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class Borrow extends Admin
{
    /**
     * controller 借币列表
     */
    public function index($p = 1){
        $map = [];
    	// 搜索用户名
    	$keywords = trim(input('keywords')) ? trim(input('keywords')) : null;
    	if($keywords){
    		$user_map['username'] = array('like','%'.$keywords.'%');
    	}

    	// 搜索审核状态
    	$examine = input('examine');
    	if($examine){
    		$map['examine'] = $examine;
    	}

    	$this -> assign('examine',model('Common/Dict') -> showList('examine_type'));
        $this -> assign("borrow", model('Borrow') -> borrowList($p,$user_map,$map));
        $this -> assign('pagename','借币管理');
        return $this -> fetch();
    }

    /**
     * controller 借币审核
     */
    public function examine(){
    	if(Request::instance() -> isPost()){
    		return json(model('Borrow') -> doExamine(input('post.')));
    	}
    }
}
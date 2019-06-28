<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class External extends Admin
{
    /**
     * controller 借币列表
     */
    public function index($p = 1){
        $this -> assign("external", model('ExternalAddress') -> external($p,$user_map,$map));
        $this -> assign('pagename','外部地址');
        return $this -> fetch();
    }
}
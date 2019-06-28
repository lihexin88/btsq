<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class BonusList extends Admin
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
            $where['email'] = array('like', '%' . trim($keywords) . '%');
            $uids = db('user')->where($where)->column('id');
            $map['uid'] = ['in',$uids];
        }
        $tkeywords = input('get.tkeywords') ? input('get.tkeywords') : null;
        if ($tkeywords) {
            $twhere['email'] = array('like', '%' . trim($tkeywords) . '%');
            $tuids = db('user')->where($twhere)->column('id');
            $map['tid'] = ['in',$tuids];
        }
        if (is_numeric(input('get.bonus_type'))) {
            $map['type'] = input('get.bonus_type');
        }
        $this->assign("info", model('BonusList')->infoList($map, $p));
        $this->assign("bonus_type", model("Common/Dict")->showList('bonus_type'));
        return $this->fetch();
    }
}
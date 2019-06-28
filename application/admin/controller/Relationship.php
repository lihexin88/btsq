<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/5
 * Time: 10:46
 */

namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class Relationship extends Admin
{
    /**
     * 列表
     * @param int $p
     * @return mixed
     */
    public function index($p = 1)
    {        if (Request::instance()->isPost()) {
            $data = input('post.');
            $string = '';
            foreach ($data as $k => $v) {
                $string .= '&' . $k . '=' . $v;
            }
            $request = Request::instance();
            return $request->url() . '?' . $string;
        }else{
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
	            if(input('num')){
                $sort = input('num') == 1?'asc':'desc';
                $order = 'total+0 '.$sort;
            }else{
                $order = 'id desc';
            }
        $this->assign("info", model('UserMoney')->infoList($map, $p,$order));
        return $this->fetch();
    }}
}
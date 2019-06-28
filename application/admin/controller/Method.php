<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;
class Method extends AdminBase
{
	/**
     *充值|提现申请列表
     * @param inter $p 页码
     */
	public function index($p = 1)
	{
		$map = [];
		$keywords = input('get.keywords')?input('get.keywords') : null;
		if($keywords) {
			$map['address'] = array('like','%' . trim($keywords) . '%');
		}
		$this->assign('info',model('Method')->infoList($map,$p));
		$this->assign('user_type',session('user_type'));
		return $this->fetch();
	}
	//审核
	public function edit_status($id)
	{
		if (Request::instance()->isPost()) {
            return json(model('Method')->changeState(input('post.')));
        }
      	$this->assign('status',model('Method')->statusList($id));
        return $this->fetch('edit_status');
	}
}
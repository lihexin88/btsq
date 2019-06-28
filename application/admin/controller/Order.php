<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class Order extends Admin
{
    /**
     * 订单列表
     * @param int $p
     * @return mixed
     */
    public function index($p = 1)
    {
        $map = [];
        $keywords = input('get.keywords') ? input('get.keywords') : null;
        if ($keywords) {
            $map['order'] = array('like', '%' . trim($keywords) . '%');
        }
        if (input('get.email')) {
            $map['seller_id|buyer_id'] = db('user')->where('email',input('get.email'))->value('id');   
        }
        $user_report = input('get.user_report') ? input('get.user_report') : null;
        if ($user_report) {
            $id = db('user')->where('email',input('get.email'))->value('id');
            $users = db('user')->field('id,parent_id')->select();
            $ids = GetTeamMember($users,$id);
            $ids[] = $id; 
            $map['seller_id|buyer_id'] = ['in',$ids];
        }
        if (is_numeric(input('get.order_status'))) {
            $map['order_status'] = input('get.order_status');
        }

        $start_time = input('start_time')?strtotime(input('start_time'))+46800:0;
        $end_time = input('end_time')?strtotime(input('end_time'))+46800:99999999999;
        $map['done_time'] = ['between',"$start_time,$end_time"];
        $this->assign("order_status", model("Common/Dict")->showList('order_status'));//状态
        $this->assign("info", model('Order')->infoList($map, $p));
        $this->assign("trade_type", model("Common/Dict")->showList('trade_type'));//状态
        return $this->fetch();
    }

    /**
     * @return mixed|\think\response\Json
     */
    public function report_info()
    {
        if(Request::instance() -> isPost()){
            $data = input('post.');
            return json(model('Order')->report_active($data));
        }else{
            $this->assign('info',model('Order')->orderInfo(input('id')));
            $this->assign('pagename','举报审核');
            return $this->fetch();
        }
    }
}
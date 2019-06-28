<?php
namespace app\admin\controller;

use app\admin\model\UserAuth;
use app\common\controller\AdminBase;
use app\admin\model\BonusList;
use think\Request;
use think\Db;


class MoneyFlow extends Admin
{

    public function index($p = 1)
    {
        $map = [];
        $keywords = input('get.keywords') ? input('get.keywords') : null;
        if ($keywords) {
            $where['email'] = array('like', '%' . trim($keywords) . '%');
            $uids = db('user')->where($where)->column('id');
            $map['uid'] = ['in',$uids];
        }
        $user_report = input('get.user_report') ? input('get.user_report') : null;
        if ($user_report) {
            $id = db('user')->where('email',$keywords)->value('id');
            $users = db('user')->field('id,parent_id')->select();
            $ids = GetTeamMember($users,$id);
            $ids[] = $id; 
            $map['uid'] = ['in',$ids];
        }
        $start_time = input('start_time')?strtotime(input('start_time'))+46800:0;
        $end_time = input('end_time')?strtotime(input('end_time'))+46800:99999999999;
        $map['create_time'] = ['between',"$start_time,$end_time"];
        $this->assign("info", model('MoneyFlow')->infoList($map, $p));
        $this->assign("last_mouth", date("Y-m-d",strtotime("last month")));
        $this->assign('pagename','动态奖励列表');
        return $this->fetch();
    }
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index1($p = 1)
    {
        if (Request::instance()->isPost()) {
            $data = input('post.');
            $string = '';
            foreach ($data as $k => $v) {
                $string .= '&' . $k . '=' . $v;
            }
            $request = Request::instance();
            return $request->url() . '?' . $string;
        }else{
            $map['type'] = ['in','1,2'];
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
                $map['trader_id'] = ['in',$tuids];
            }
            if (is_numeric(input('get.type'))) {
                $map['type'] = input('get.type');
            }
            $start_time = input('start_time')?strtotime(input('start_time'))+46800:0;
            $end_time = input('end_time')?strtotime(input('end_time'))+46800:99999999999;
            $map['create_time'] = ['between',"$start_time,$end_time"];
            if(input('num')){
                $sort = input('num') == 1?'asc':'desc';
                $order = 'number+0 '.$sort;
            }else{
                $order = 'id desc';
            }
            $this->assign("info", model('MoneyFlow')->infoList2($map, $p, $order));
            $this->assign("money_flow_type", model("Common/Dict")->showList('money_flow_type'));//状态
            $this->assign('pagename','动态奖励列表');
            return $this->fetch();
        }
    }

    /**
     * 全球分红列表
     * @param  integer $p 页码
     */
    public function global_record($p = 1)
    {
        if (Request::instance()->isPost()) {
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
                $map['trader_id'] = ['in',$tuids];
            }
            $start_time = input('start_time')?strtotime(input('start_time'))+47400:0;
            $end_time = input('end_time')?strtotime(input('end_time'))+47400:99999999999;
            $map['create_time'] = ['between',"$start_time,$end_time"];
            if(input('num')){
                $sort = input('num') == 1?'asc':'desc';
                $order = 'number+0 '.$sort;
            }else{
                $order = 'create_time desc,trader_id desc';
            }
            $this->assign("info", model('MoneyFlow')->global_record($map, $p, $order));
            $this->assign('pagename','动态奖励列表');
            return $this->fetch();
        }
    }
    
    public function report_form($p = 1)
    {
        if (Request::instance()->isPost()) {
            $data = input('post.');
            $string = '';
            foreach ($data as $k => $v) {
                $string .= '&'.$k.'='.$v;
            }
            $request = Request::instance();
            return $request->url().'?'.$string;
        }else{
            $map = [];
            $start_time = input('start_time')?strtotime(input('start_time'))+47220:0;
            $end_time = input('end_time')?strtotime(input('end_time'))+47220:99999999999;
            $map['time'] = ['between',"$start_time,$end_time"];
            $order = 'id desc';
            if(input('nms')){
                $sort = input('nms') == 1?'asc':'desc';
                $order = 'new_member+0 '.$sort;
            }
            if(input('nyj')){
                $sort = input('nyj') == 1?'asc':'desc';
                $order = 'new_achievement+0 '.$sort;
            }
            if(input('nsy')){
                $sort = input('nsy') == 1?'asc':'desc';
                $order = 'new_profit+0 '.$sort;
            }
            if(input('nxjsy')){
                $sort = input('nxjsy') == 1?'asc':'desc';
                $order = 'new_child_profit+0 '.$sort;
            }
            if(input('njy')){
                $sort = input('njy') == 1?'asc':'desc';
                $order = 'new_vol+0 '.$sort;
            }
            if(input('time')){
                $sort = input('time') == 1?'asc':'desc';
                $order = 'time '.$sort;
            }
            if(input('to')){
                $sort = input('to') == 1?'asc':'desc';
                $order = 'turn_out+0 '.$sort;
            }
            if(input('ti')){
                $sort = input('ti') == 1?'asc':'desc';
                $order = 'turn_into+0 '.$sort;
            }
            $this->assign("info", model('MoneyFlow')->report_form($map, $p,$order));
            $this->assign("all_data", model('MoneyFlow')->report_form_all($map));
            $this->assign('pagename','每日报表');
            return $this->fetch();
        }
    }

    public function user_report($p = 1)
    {
        if (Request::instance()->isPost()) {
            $data = input('post.');
            $string = '';
            foreach ($data as $k => $v) {
                $string .= '&'.$k.'='.$v;
            }
            $request = Request::instance();
            return $request->url().'?'.$string;
        }else{
            $map = [];
            $email = input('get.email') ? input('get.email') : null;
            if ($email) {
                $where['email'] = array('like', '%' . trim($email) . '%');
                $ids = db('user')->where($where)->column('id');
                $map['uid'] = ['in',$ids];
            }
            $start_time = input('start_time')?strtotime(input('start_time'))+47220:0;
            $end_time = input('end_time')?strtotime(input('end_time'))+47220:99999999999;
            $map['time'] = ['between',"$start_time,$end_time"];
            $order = 'id desc';
            if(input('nyj')){
                $sort = input('nyj') == 1?'asc':'desc';
                $order = 'new_achievement+0 '.$sort;
            }
            if(input('nsy')){
                $sort = input('nsy') == 1?'asc':'desc';
                $order = 'new_profit+0 '.$sort;
            }
            if(input('nxjsy')){
                $sort = input('nxjsy') == 1?'asc':'desc';
                $order = 'new_child_profit+0 '.$sort;
            }
            if(input('njy')){
                $sort = input('njy') == 1?'asc':'desc';
                $order = 'new_vol+0 '.$sort;
            }
            if(input('time')){
                $sort = input('time') == 1?'asc':'desc';
                $order = 'time '.$sort;
            }
            if(input('to')){
                $sort = input('to') == 1?'asc':'desc';
                $order = 'turn_out+0 '.$sort;
            }
            if(input('ti')){
                $sort = input('ti') == 1?'asc':'desc';
                $order = 'turn_into+0 '.$sort;
            }
            $this->assign("info", model('MoneyFlow')->user_report($map, $p,$order));
            $this->assign('pagename','每日报表');
            return $this->fetch();
        }
    }

    public function user_money()
    {
        $id = input('id');
        $list = db('user_money')->alias('a')->join('currency b','a.cur_id = b.id')->where('a.uid',$id)->select();
        return $list;
    }

    public function recharge($p = 1)
    {
        if (Request::instance()->isPost()) {
            return model('UserMoney')->recharge(input('post.'));
        }else{
            $map = [];
            $keywords = input('get.keywords') ? input('get.keywords') : null;
            if ($keywords) {
                $where['email'] = array('like', '%' . trim($keywords) . '%');
                $uids = db('user')->where($where)->column('id');
                $map['uid'] = ['in',$uids];
            }
            if (is_numeric(input('get.transaction_number'))) {
                $map['transaction_number'] = input('get.transaction_number');
            }
            if (is_numeric(input('get.transfer_type'))) {
                $map['transfer_type'] = input('get.transfer_type');
            }
            if (input('get.from_address')) {
                $map['from_address'] = input('get.from_address');
            }
            if (input('get.to_address')) {
                $map['to_address'] = input('get.to_address');
            }
            if (is_numeric(input('get.recharge_type'))) {
                $map['recharge_type'] = input('get.recharge_type');
            }
            if (is_numeric(input('get.recharge_status'))) {
                $map['recharge_status'] = input('get.recharge_status');
            }
    	        $start_time = input('start_time')?strtotime(input('start_time'))+46800:0;
            $end_time = input('end_time')?strtotime(input('end_time'))+46800:99999999999;
            $map['create_time'] = ['between',"$start_time,$end_time"];
            $this->assign("info", model('MoneyFlow')->recharge($map, $p));
            $this->assign("recharge_type", model("Common/Dict")->showList('recharge_type'));//类型
            $this->assign("recharge_status", model("Common/Dict")->showList('recharge_status'));//状态
            $this->assign('pagename','转币记录');
            return $this->fetch();
        }
    }

    /**
     * 用户奖金列表
     * @param int $p
     * @return mixed
     * @throws Db\exception\DataNotFoundException
     * @throws Db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bonus($p = 1){
        $BonusList = new BonusList();
        $map = [];
        // 用户邮箱
        $keywords = input('get.keywords') ? input('get.keywords') : null;
        if($keywords){
            $user_where['email'] = array('like','%'.trim($keywords).'%');
            $uids = db('user') -> where($user_where) -> column('id');
            $map['uid'] = ['in',$uids];
        }
        // 返佣人邮箱
        $tkeywords = input('get.tkeywords') ? input('get.tkeywords') : null;
        if ($tkeywords) {
            $t_user_where['email'] = array('like', '%' . trim($tkeywords) . '%');
            $tuids = db('user') -> where($t_user_where) -> column('id');
            $map['tid'] = ['in',$tuids];
        }
        $start_time = input('start_time') ? strtotime(input('start_time')) + 46800 : 0;
        $end_time = input('end_time') ? strtotime(input('end_time')) + 46800 : 99999999999;
        $map['time'] = ['between',"$start_time,$end_time"];
        $this -> assign('list',$BonusList -> infoList($map,$p));
        $this -> assign('pagename','用户奖金列表');
        return $this -> fetch();
    }
}
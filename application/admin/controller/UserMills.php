<?php
namespace app\admin\controller;

use app\api\model\UserMining as UserMiningmodel;
use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class UserMills extends Admin
{
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {

        $map = [];
        $email = input('get.email') ? input('get.email') : null;
        if ($email) {
            $where['email'] = array('like', '%' . trim($email) . '%');
            $ids = db('user')->where($where)->column('id');
            $map['uid'] = ['in',$ids];
        }
        $user_report = input('get.user_report') ? input('get.user_report') : null;
        if ($user_report) {
            $id = db('user')->where('email',$email)->value('id');
            $users = db('user')->field('id,parent_id')->select();
            $ids = GetTeamMember($users,$id);
            $ids[] = $id; 
            $map['uid'] = ['in',$ids];
        }
        if (is_numeric(input('get.status'))) {
            $map['status'] = input('get.status');
        }
        if (is_numeric(input('get.mining_status'))) {
            $map['mining_status'] = input('get.mining_status');
        }
	    $start_time = input('start_time')?strtotime(input('start_time')):0;
        $end_time = input('end_time')?strtotime(input('end_time'))+84600:99999999999;
        $map['create_time'] = ['between',"$start_time,$end_time"];
        $this->assign("info", model('UserMills')->infoList($map, $p));
        $this->assign('pagename','用户矿池');
        return $this->fetch();
	
    }

    /**
     * 矿池收益列表
     * @param  integer $p 页码
     */
    public function mining_profit($p = 1)
    {    if (Request::instance()->isPost()) {
            $data = input('post.');
            $string = '';
            foreach ($data as $k => $v) {
                $string .= '&'.$k.'='.$v;
            }
            $request = Request::instance();
            return $request->url().'?'.$string;
        }else{
        $map['type'] = 5;
        $email = input('get.email') ? input('get.email') : null;
        if ($email) {
            $where['email'] = array('like', '%' . trim($email) . '%');
            $ids = db('user')->where($where)->column('id');
            $map['uid'] = ['in',$ids];
        }
        $start_time = input('start_time')?strtotime(input('start_time')):0;
        $end_time = input('end_time')?strtotime(input('end_time'))+86400:99999999999;
        $map['create_time'] = ['between',"$start_time,$end_time"];
        $order = 'create_time desc';
        $this->assign("info", model('UserMills')->mining_profit($map, $p,$order));
        $this->assign('pagename','矿池收益列表');
        return $this->fetch();}
    }

    /**
     * 结算出的挖矿收益
     * @param  integer $p 页码
     */
    public function mining_profit2($p = 1)
    {   
        $map = [];
        $email = input('get.email') ? input('get.email') : null;
        if ($email) {
            $where['email'] = array('like', '%' . trim($email) . '%');
            $ids = db('user')->where($where)->column('id');
            $map['uid'] = ['in',$ids];
        }
        $user_report = input('get.user_report') ? input('get.user_report') : null;
        if ($user_report) {
            $id = db('user')->where('email',$email)->value('id');
            $users = db('user')->field('id,parent_id')->select();
            $ids = GetTeamMember($users,$id);
            $ids[] = $id; 
            $map['uid'] = ['in',$ids];
        }
        $start_time = input('start_time')?strtotime(input('start_time'))+46810:0;
        $end_time = input('end_time')?strtotime(input('end_time'))+46810:99999999999;
        $map['time'] = ['between',"$start_time,$end_time"];
        $this->assign("info", model('UserMills')->mining_profit2($map, $p));
        $this->assign('pagename','用户静态收益');
        return $this->fetch();
    }

    /**
     * 用户矿池详情
     * @param  integer $p 页码
     */
    public function mining_profit3($p = 1)
    {   
        $map = [];
        $map['a.uid'] = input('get.uid');
        $start_time = input('time')?input('time')-86340:0;
        $end_time = input('time')?input('time')+60:99999999999;
        $map['a.time'] = ['between',"$start_time,$end_time"];
        $this->assign("info", model('UserMills')->mining_profit3($map, $p));
        $this->assign('pagename','用户矿池提取记录详情');
        return $this->fetch();
    }

    /**
     * 回收币页面
     * @param  integer $p 页码
     */
    public function mining_scrap($p = 1)
    {   
        $map = [];
        $email = input('get.email') ? input('get.email') : null;
        if ($email) {
            $where['email'] = array('like', '%' . trim($email) . '%');
            $ids = db('user')->where($where)->column('id');
            $map['uid'] = ['in',$ids];
        }
        $start_time = input('start_time')?strtotime(input('start_time')):0;
        $end_time = input('end_time')?strtotime(input('end_time'))+86400:99999999999;
        $map['create_time'] = ['between',"$start_time,$end_time"];
        $this->assign("info", model('UserMills')->mining_scrap($map, $p));
        $this->assign('pagename','报废币列表');
        return $this->fetch();
    }


    /**
     * 新增
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        if(Request::instance()->isPost()){
            $data = input('post.');
            $user = db('user')->where('id',input('uid'))->find();
            if($user['identity']){
                $data['mills_state'] = 1;
                $data['begin_time'] = time();
                $data['end_time'] = time()+config('BATTERY_CYCLE')*24*3600;
                
            }
            return json(model('UserMills')->saveInfo($data));
        }
        $info = ['id'=>null,'username'=>null,'mills_id'=>null,'alipay_accout'=>null,'parent_id'=>null,'dfs'=>null,'usd'=>null,'tel'=>null,'identity'=>null];
        $this->assign('info',$info);
        $this->assign("userList", model("User")->showList());
        $this->assign("millsList", model("Mills")->showList());
        $this->assign('pagename','赠送用户');
        return $this->fetch();
    }

    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {
        if (Request::instance()->isPost()) {
            return json(model('UserMills')->changeState(input('post.')));
        }
        $this->assign("info", model('UserMills')->infodata(array('id'=>$id)));
        $this->assign('pagename','修改用户');
        return $this->fetch('add');
    }

    /**
     * 删除信息
     * @param  string $id ID
     */
    public function delete()
    {
        if (Request::instance()->isPost()) {
            return json(model('UserMills')->deleteInfo(input('post.id')));
        }
    }

    /**
     * 删除用户矿机
     * @return false|string
     */
    public function delete_mining(){
        $UserMinig = new UserMiningmodel();
        if($UserMinig->where(['id'=>$_POST['id']])->delete()){
            return rtn(1,"已删除");
        }
            return rtn(0,"删除失败");


    }
}
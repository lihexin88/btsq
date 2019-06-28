<?php
namespace app\admin\controller;

use app\admin\model\UserAuth;
use app\common\controller\AdminBase;
use think\Request;
use think\Db;


class Feedback extends Admin
{
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {
        if($_GET['p']){
            $p = $_GET['p'];
        }
        $map = [];
        $keywords = input('get.keywords') ? input('get.keywords') : null;
        if ($keywords) {
            $where['email'] = array('like', '%' . trim($keywords) . '%');
            $uids = db('user')->where($where)->column('id');
            $map['uid'] = ['in',$uids];
        }
        if (is_numeric(input('get.f_type'))) {
            $map['f_type'] = input('get.f_type');
        }
        if (is_numeric(input('get.status'))) {
            $map['a.status'] = input('get.status');
        }
	 $start_time = input('start')?strtotime(input('start'))+46800:0;
        $end_time = input('end')?strtotime(input('end'))+46800:99999999999;
        $map['a.create_time'] = ['between',"$start_time,$end_time"];
        $this->assign("info", model('Feedback')->infoList($map, $p));
        $this->assign("f_type", db('feedback_type')->where('is_delete',0)->column('id,chs_name'));//状态
        return $this->fetch();
    }


    /**
     * 用户问题详情
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_feedback()
    {
        $id = input('id');
        $info = db('feedback')->where('id',$id)->find();

        if($info['f_type']){
            $info['f_type'] = db('feedback_type')->where(['feedback_type'=>$info['f_type']])->column('chs_name');
        }
        return $info;
    }


    /**
     * 回复
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function add_feedback()
    {
        $data = input('post.');
        db('feedback')->where('id',$data['id'])->update(['reply'=>$data['reply'],'status'=>1]);
        return ['status'=>1,'info'=>'成功'];
    }
}
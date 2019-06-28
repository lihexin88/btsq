<?php
namespace app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;
class Method extends Base
{
	const PAGE_LIMIT = '7';//用户表分页限制
	const PAGE_SHOW = '5';//用户表分页限制
	 /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p  页码
     * @return array      返回列表
     */
    public function infoList($map,$p)
    {

     	$request = Request::instance();
        //查询记录
        if(session('user_type') == 1){
               $map['uid'] = session('aid');
          }
     	$list = $this->where($map)->order('id asc')->page($p,self::PAGE_LIMIT)->select()->toArray();
          foreach ($list as $k => $v) {
            $list[$k]['name'] =  db('user_auth')->where('id',$v['uid'])->value('name');
            $list[$k]['cur_name'] = db('currency')->where('id',$v['cur_id'])->value('name');
                 }
     	$return['count'] = $this->where($map)->count();
     	$return['list'] = $list;
     	$return['page'] = boot_page($return['count'],self::PAGE_LIMIT,self::PAGE_SHOW,$p,$request->action());
     	return $return;
    }

    //取出要审核的申请
    public function statusList($id)
    {
        $res = $this->where('id',$id)->field('review,id')->find();
        return $res;
    }

   //审核状态
    public function changeState($data)
    { 
        $edit['review'] = $data['status'];
        $res = db('method')->where('id',$data['id'])->update($edit);
        if(false === $res){
            return ['status'=>0,'info'=>$AuthGroup->getError()];
        }else{
            $methodinfo = db('method')->where('id',$data['id'])->find();//充值提现
          	if($methodinfo['method_type'] ==1){
            	db('user_cur')->where('uid',$methodinfo['uid'])->where('cur_id',$methodinfo['cur_id'])->setInc('number',$methodinfo['money']);
            }else{
            	db('user_cur')->where('uid',$methodinfo['uid'])->where('cur_id',$methodinfo['cur_id'])->setDec('number',$methodinfo['money']);
            }
            return array('status' => 1, 'info' => '保存成功', 'url' => url('index'));
        }
    }
     /**
     * 新增/修改
     * @param  array $data 传入信息
     */
    public function saveInfo($data)
    {  
        $result = db('msg')->insert($data);
        if($result){
            return array('status' => 1, 'info' => '保存成功', 'url' =>url('index'));
        }else{
            return ['status'=>0,'info'=>$AuthGroup->getError()];
        }
    }
}
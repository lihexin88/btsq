<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/27
 * Time: 17:03
 */

namespace app\api\model;


use think\Model;
use think\Validate;

class StoData extends Model
{

    /**
     * 添加锁仓信息
        $map['uid'] 用户ID
        $map['cur_id'] 币种ID
        $number 数量
        $time 锁仓持续时间
        $num sto库存数量
     */
    public function implement($map,$number,$time,$create)
    {
        $total_number = $this->where($map)->value('total_number');
      	$create = $this->where($map)->value('create');
        $edit['number'] = $number;//sto库存数量+最新锁仓数量
        $edit['total_number'] = $number+$total_number;//sto库存数量+最新锁仓数量
      	if($create){
        	$edit['create'] = $create;
        }else{
         	$edit['create'] = time();
        }
        $edit['time'] = $time;//当前时间+45天
        $edit['status'] = 1;//状态为1
        return $this->where($map)->update($edit);
    }
    //查询信息
    public function data($id,$cur_id){
        $map['uid'] = $id;
        $map['cur_id'] = $cur_id;
        return $this->where($map)->find();
    }
    //提现
    public function edit($id,$curid)
    {
        $map['uid'] = $id;
        $map['cur_id'] = $curid;
        $edit['number'] = 0;//0数量
        $edit['time'] = time();//执行时间
        $edit['status'] = 0;//0关闭
        return $this->where($map)->update($edit);
    }

	/**
	 * 查询一个用户的所有sto
	 * @param $uid 用户uid
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
    public function get_data($uid)
    {
		$sto = $this->where(['uid'=>$uid])->select()->toArray();
		return $sto;
    }
}
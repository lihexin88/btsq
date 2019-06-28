<?php

namespace app\api\model;

use think\Model;
use think\Validate;

class Record extends Model
{
    // 关闭自动写入update_time字段
    protected $updateTime = false;

    /**
     * 获取首页交易记录列表
     */
    public function homePageList()
    {
        $list = $this->where(array('transaction_type'=>1))->order('create_time','desce')->limit(3)->select()->toArray();
        $transactionTypeArr = model('Common/Dict')->showKey('transaction_type');
        $userTypeArr = model('User')->showKey();
        foreach ($list as $k => $v) {
        	$list[$k]['transactionTextArr'] = $transactionTypeArr[$v['transaction_type']];
        	$list[$k]['userTextArr'] = $userTypeArr[$v['user_id']];
        	$list[$k]['traderTextArr'] = $userTypeArr[$v['trader_id']];
        }
        return $list;
    }

    /**
     * 昨日收益
     * @param int @id [用户ID]
     * @return mixed
     */
    public function yesterdayEarnings($id)
    {
        $map['status'] = 1;
        $map['user_id'] = $id;
        $map['transaction_type']  = array('in','3');
        $yesterdayEarnings = $this->where($map)->whereTime('create_time','y')->sum('money');
        return $yesterdayEarnings;
    }

    /**
     * 总收益
     * @param int @id [用户ID]
     * @return mixed
     */
    public function allEarnings($id)
    {
        $map['user_id'] = $id;
        $map['transaction_type']  = array('in','3');
        $allEarnings = $this->where($map)->sum('money');
        return $allEarnings;
    }

    /**
     * 交易记录页面信息
     * @param string $id [排序类型]
     * @param array  $map [查询条件]
     * @param string $p [页数]
     */
    public function recordListPage($id,$map,$p)
    {
        $map['trader_id'] = $id;
        $list = $this->where($map)->page($p, 10)->order('create_time desc')->select()->toArray();
        $transactionTypeArr = model('Dict')->showKey('transaction_type');
        foreach ($list as $k => $v) {
            $userinfo = model('User')->infodata(array('id'=>$v['user_id']));
            $list[$k]['name'] = $userinfo['username'];
            $list[$k]['transactionTypeText'] = $transactionTypeArr[$v['transaction_type']];
        }
        $data['list'] = $list;
        $data['count'] = ceil(($this->where($map)->count())/10);
        return $data;
    }

    /**
     * 获取昨日奖金
     * @param string @uid [用户ID]
     */
    public function ydayTeamBonus($id)
    {
        $map['trader_id'] = $id;
        $map['status'] = 1;
        $map['transaction_type'] = 4;
        $data = $this->where($map)->whereTime('create_time', 'y')->sum('money');
        return $data;

    }

    /**
     * 获取总入奖金
     * @param string @uid [用户ID]
     */
    public function allTeamBonus($id)
    {
        $map['trader_id'] = $id;
        $map['transaction_type'] = 4;
        return $this->where($map)->sum('money');
    }

    /**
     * 储存记录
     * @param array $map [数组]
     * @return mixed
     */
    public function add($map)
    {
        $result = $this->save($map);
        if(false === $result){
            return ['status'=>0,'info'=>lang('error')];
        }else{
            return ['status'=>1,'info'=>lang('success')];
        }
    }

    /**
     * 升级
     */
    public function upGrade($id)
    {
        $buyerinfo = model('User')->infodata(array('id'=>$id));   
        if($buyerinfo['credit_level'] < 5){
               $map['user_id'] = $buyerinfo['id'];
               $map['state'] = 0;
               $map['transaction_type'] = array('in','1,2');
               $record = $this->where($map)->select()->toArray();
               if($record){
                $id_array = '';
                foreach ($record as $k => $v) {
                    $id_array .= $v['id'].',';
                }
                $where['id'] = $id;
                $where['credit_level'] = $buyerinfo['credit_level'];
                $update_map['id'] = array('in',$id_array);
                $update_map['state'] = 1;
                if($buyerinfo['credit_level'] < 4){
                    if(count($record) >= config('UPGRADE_FOUR_STARS')){
                        $where['credit_level'] = $buyerinfo['credit_level'] + 1;
                        $this->saveInfo($update_map);
                    }
                }else if($buyerinfo['credit_level'] = 4){
                    if(count($record) >= config('UPGRADE_FIVE_STARS')){
                        $where['credit_level'] = $buyerinfo['credit_level'] + 1;
                        $this->saveInfo($update_map);
                    }
                }
                model('User')->saveInfo($where);
               }
 
        }
        return true;
    }


    /**
     * 添加更新数据
     * @param array $data [数据]
     * @return mixed
     */
    public function saveInfo($data)
    {
        if(array_key_exists('id',$data)){
            $where = true;
        }else{
            $where = false;
        }
        $result = $this->isUpdate($where)->data($data, true)->save();
        if ($result) {
            return ['status'=>1,'info'=>lang('success')];
        } else {
            return ['status'=>0,'info'=>lang('error')];
        }      
    }

}

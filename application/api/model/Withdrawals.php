<?php

namespace app\api\model;

use think\Model;

class Withdrawals extends Model
{
    protected $field = true;
    /**
    *添加时自动完成
    */
    protected $insert = array('withdrawals_status'=> 0);

    /**
    *更新时自动完成
    */
    protected $update = [];

    /**
     * 获取提现记录列表
     */
    public function withdrawalsList($id,$p)
    {
        $list = $this->where(array('uid'=>$id))->page($p, 10)->order('withdrawals_status','desce')->select()->toArray();
        $withdrawalsTypeArr = model('Common/Dict')->showKey('withdrawals_status');
        $userTypeArr = model('User')->showKey();
        foreach ($list as $k => $v) {
            $list[$k]['withdrawalsTextArr'] = $withdrawalsTypeArr[$v['withdrawals_status']];
            $list[$k]['userTextArr'] = $userTypeArr[$v['uid']];
        }
        $data['list'] = $list;
        $data['count'] = ceil(($this->where(array('uid'=>$id))->count())/10);
        return $data;
    }

    /**
     * 储存提现记录
     * @param string $data [储存数据]
     */
    public function withdrawalsApply($data)
    {
        $result = $this->save($data);
        if(false === $result){
            return ['status'=>0,'info'=>$User->getError()];
        }else{
            return ['status'=>1,'info'=>lang('success')];
        }
    }


    /**
     * 检测当月是否提现
     * @param string $map [当月时间段]
     */
    public function iswithdrawals($map)
    {   
        if($this->where($map)->find()){
            return ['status'=>1,'info'=>lang('already_presented')];
        }else{
            return ['status'=>0,'info'=>lang('no_presented')];
        }
    }
}

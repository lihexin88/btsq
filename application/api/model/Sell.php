<?php

namespace app\api\model;

use think\Model;
use think\Validate;

class Sell extends Model
{
    // 关闭自动写入时间戳
    protected $autoWriteTimestamp = false;

    /**
     * 获取首页挂卖记录列表
     */
    public function homePageList()
    {
        $list = $this->order('start_time','desc')->limit(3)->select()->toArray();
        $userTypeArr = model('User')->showKey();
        foreach ($list as $k => $v) {
            $list[$k]['money'] = $v['sell_number'];
        	$list[$k]['userTextArr'] = $userTypeArr[$v['seller_id']];
            $list[$k]['transactionTextArr'] = '挂卖';            
        }
        return $list;
    }


    /**
     * 抢币页面信息
     * @param string @sort [排序类型]
     */
    public function sellPage($sort,$map,$p)
    {
        switch ($sort) {
            case '0':
                $sort = 'id desc';
                break;
            case '1':
                $sort = 'sell_amount asc';
                break;
            case '2':
                $sort = 'sell_amount desc';
                break;
            case '3':
                $sort = 'start_time asc';
                break;
            case '4':
                $sort = 'start_time desc';
                break;
        }
        $list = $this->where($map)->order($sort)->page($p, 10)->select()->toArray();
        $userTypeArr = model('User')->showKey();
        foreach ($list as $k => $v) {
            $list[$k]['userTextArr'] = $userTypeArr[$v['seller_id']];
        }
        $data['list'] = $list;
        $data['count'] = ceil(($this->where($map)->count())/10);
        return $data;
    }

    /**
     * 抢币功能
     * @param string @id [数据ID]
     * @param string @uid [购买者ID]
     * @param string @number [购买数量]
     */
    public function grabCoin($id,$uid,$number)
    {
        $map['id'] = $id;
        $sellinfo = $this->infodata($map);
        if($sellinfo['sell_status'] == 2 || $sellinfo['sell_status'] == 3){
            return ['status'=>0,'info'=>lang('sale_end')];
        }
        $map['sell_number'] = $sellinfo['sell_number'] - $number;
        if($this->saveInfo($map)){
            $selldata = $this->infodata(array('id'=>$id));
            if($selldata['sell_number'] == 0){
                $where['id'] = $id;
                $where['sell_status'] = 2;
                $where['end_time'] = time();
                $this->saveInfo($where);
            }
            $result = model('Order')->grabCoin($id,$uid,$sellinfo['seller_id'],$number);
            return ['status'=>1,'info'=>lang('success')];
        }
        return ['status'=>0,'info'=>lang('error')];
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
        $result = $this->isUpdate($where)->save($data);
        if ($result) {
            return ['status'=>1,'info'=>lang('success')];
        } else {
            return ['status'=>0,'info'=>lang('error')];
        }      
    }

    /**
     * 挂卖功能
     * @param string $id [用户ID]
     * @param string $number [挂卖数量]
     * @return mixed
     */
    public function sell($id,$number){
        if(false == ($userdata = model('User')->infodata(array('id'=>$id)))){
            return ['status'=>0,'info'=>lang('null')];
        }else{
            if($userdata['dfs'] < $number){
                return ['status'=>0,'info'=>lang('excess_quantity')];
            }else{
                $where['id'] = $id;
                $where['dfs'] = $userdata['dfs'] - $number;
                if(model('User')->saveInfo($where)){
                    $config = model('Common/config')->getConfig();
                    $map['seller_id'] = $id;
                    $map['sell_number'] = $number;
                    $map['sell_amount'] = $config['DFS_EXCHANGE_RATE'];
                    $map['tel'] = $userdata['accout'];
                    $map['sell_status'] = 1;
                    $map['start_time'] = time();
                    if($this->saveInfo($map)){
                        return ['status'=>1,'info'=>lang('success')];
                    }else{
                        return ['status'=>0,'info'=>lang('error')];
                    }
                }else{
                    return ['status'=>0,'info'=>lang('error')];
                }
            }
        }
    }

    /**
     * 根据查询条件获取信息
     * @param string $map [查询条件]
     * @return mixed
     */
    public function infodata($map){
        $list = $this->where($map)->find();
        if(!is_null($list)){
            return $list->toArray();
        }
        return false;
    }

}

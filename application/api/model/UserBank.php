<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/12
 * Time: 10:15
 */

namespace app\api\model;


use think\Model;

class UserBank extends Model
{
    /**
     *
     * 添加银行卡
     * @param $bank_info
     * @param $user
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_bank($bank_info, $user){
        $where = [
            'bank_card'=>$bank_info['bank_card']
        ];
        $userbank = $this->where($where)->find();
        $r = null;
        if($userbank){
            return ['status'=>0,'info'=>'该银行卡已被使用'];
        }
        $userbank = $this;
        $userbank->uid = $user['id'];
        $userbank->bid = $bank_info['bid'];
        $userbank->bank_card = $bank_info['bank_card'];
        $userbank->open_name = $bank_info['name'];
        $userbank->bank_add = $bank_info['bank_add'];
        if(!$userbank->save()){
            return ['status'=>0,'info'=>'用户银行卡保存信息失败'];
        }
        return ['status'=>1,'info'=>'用户银行卡保存信息失败'];
    }
}
<?php
/**
 * Created by 李.
 * 万物源于汇编
 * hello world乃万恶之源
 * Date: 2018/12/18
 * Time: 14:38
 */

namespace app\api\model;


use think\Model;

class ContributionHis extends Model
{
    /**
     * 添加社区贡献值记录
     * @param $user
     * @param $contribution
     * @return bool
     */
    public function add_con_his($user, $contribution)
    {
        $Contribution = new self();
        $Contribution->uid = $user['id'];
        $Contribution->contribution = $contribution;
        $Contribution->save();
        return true;
    }
}
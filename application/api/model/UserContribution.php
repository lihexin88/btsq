<?php
/**
 * Created by ??.
 * User: Administrator
 * Date: 2018/12/13
 * Time: 10:41
 */

namespace app\api\model;

use think\Exception;
use think\Model;

use app\api\model\User as UserModel;

class UserContribution extends Model
{


    /**
     *
     * 更新用户社区贡献度，并写进历史记录
     * @param $user
     * @param $achieve_step
     * @param $insert_step
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function change_contribution($user, $achieve_step, $insert_step)
    {
        $UC = $this::get(['uid' => $user['id']]);
        if (!$UC) {
            $UC = $this;
            $UC->uid = $user['id'];
        }
        $count_bouns = null;
        $UserMining = new UserMining();

        /*获取vip等级*/
        $user_level = $user['level'];
        $weight = self::contribution_weight('vip', $user_level);
        $UC->vip = $weight['value'];
        $count_bouns += $weight['value'];

        /*获取直推人数*/
        $user_recom_number = UserModel::where(['parent_id' => $user['id']])->count();
        $weight = self::contribution_weight('dir_recommend', $user_recom_number > 10 ? 10 : $user_recom_number);
        $UC->recommend = $weight['value'];
        $count_bouns += $weight['value'];

        /*矿机总投入量*/
        $total_mining = $UserMining->user_mining($user)->toArray();
        $total_mining = $total_mining[0]['amount'];
        $bonus = ceil($total_mining / $achieve_step);
        $UC->total_mining = $bonus;
        $count_bouns += $bonus;

        /*矿机新增投入量*/
        $now_smtp = time();
        $where['create_time'] = [
            'between', [($now_smtp - 7 * 24 * 60 * 60), $now_smtp]
        ];
        $insert_achieve = $UserMining->user_mining($user, $where)->toArray();
        if($insert_step){
            $bonus = ceil($insert_achieve[0]['amount'] / $insert_step);
        }else{
            $bonus = 0;
        }
        $UC->insert_mining = $bonus;
        $count_bouns += $bonus;



        $UC->contribution = $count_bouns;
        $UC->update_time = time();
        if(!($UC->force()->save())){
            throw new Exception("失败");
        }

        /*添加贡献记录*/
        $ContributionHis = new ContributionHis();
        $ContributionHis->add_con_his($user,$count_bouns);


        return ['status'=>1];
    }

    /**
     * 获取vip等级奖励
     * @return ContributionWeight[]|false
     * @throws \think\exception\DbException
     */
    static public function contribution_weight($key, $join)
    {
        $all_weight = ContributionWeight::get(['key' => $key, 'join' => $join]);
        return $all_weight;
    }


    /**
     * 获取用户在当前社区贡献度排位中插队的百分比
     * @param $user
     * @return bool|float|int|string
     * @throws \think\exception\DbException
     */
    static public function get_position_percent($user)
    {
        /*插队区间*/
        $max_insert = config("MAX_INSERT") / 100;
        $min_insert = config("MIN_INSERT") / 100;

        $contributions = self::order('contribution asc')->select();
        /*贡献度人数*/
        $count_user = count($contributions);

        /*插队分度*/
        $per_step = ($max_insert - $min_insert) / ($count_user - 1);

        /*贡献度位置百分比*/
        $user_position = false;
        foreach ($contributions as $k => $v) {
            if ($v['uid'] == $user['id']) {
                $user_position = $k + 1;
                break;
            }
        }
        if ($user_position) {
            $user_position = $min_insert + $per_step * ($user_position - 1);
        }
        return 1 - $user_position;
    }


}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/19
 * Time: 10:07
 */

namespace app\api\controller;

use app\api\model\Language;
use app\api\model\UserConfig;
use app\api\model\VipRights as VipRightsModel;

use app\common\controller\ApiBase;

class VipRights extends ApiBase
{

    /**
     * 获取用户配置信息
     * @return UserConfig|null
     * @throws \think\exception\DbException
     */
    private function user_config()
    {
        $user_config = UserConfig::get(['uid'=>$this->userInfo['id']]);
        return $user_config;
    }

    /**
     * 获取vip权益信息
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function get_vip_rights()
    {
        $vip_rights = VipRightsModel::all();
        $user_config = $this->user_config();
        $return = null;
        foreach ($vip_rights as $k => $v){
            $return[$k]['vip'] = $v['vip'];
            if($user_config['language'] == 1){
                $return[$k]['rights'] = $v['chs'];
            }else if($user_config['language'] == 2){
                $return[$k]['rights'] = $v['cht'];
            }else{
                $return[$k]['rights'] = $v['en'];
            }
        }
        return rtn(1,Language::lang("成功",$this->userInfo),$return);
    }
}
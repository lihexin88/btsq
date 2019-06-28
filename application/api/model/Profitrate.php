<?php

namespace app\api\model;

use think\Model;
use think\Validate;

class Profitrate extends Model
{

    /**
     * 获取财务管理页面
     */
    public function financePage()
    {
        $list = $this->order('time','desce')->limit(6)->select()->toArray();
        foreach ($list as $k => $v) {
        	$list[$k]['time'] = date('y.m.d',$v['time']);    
        }
        $todayrate = model('Common/config')->getConfig();
        $todaydate = array('profit_rate'=>$todayrate['RATE_OF_RETURN'],'time'=> date('y.m.d',time()));
        $list[] = $todaydate;
        return $list;
    }

}

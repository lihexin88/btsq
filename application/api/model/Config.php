<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 15:32
 */

namespace app\api\model;


use think\Model;

class Config extends Model
{
    /**
     * 关于我们
     * @return null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function about_us()
    {
//		查询about_us 的信息
        $about_us_where['key'] = array('like', 'ABOUT_US%');
        $about_us = self::where($about_us_where)->select();

        $about_us_key = array('ABOUT_US_TEXT', 'ABOUT_US_QQ', 'ABOUT_US_TEL', 'ABOUT_US_WECHAT');
        $about_us_content = null;
        foreach ($about_us as $k => $v) {
            if (in_array($v['key'], $about_us_key)) {
//				循环赋值
                $about_us_content[$v['key']] = $v['value'];
            }
        }
        return $about_us_content;
    }

    /**
     * 游戏手续费
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function game_fee()
    {
        $fee = self::where(['key' => 'GUESS_BET_FEE'])->field('value')->find();
        return $fee['value'];
    }

    /**
     *
     * 下注范围
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function chip_range()
    {
        $min = null;
        $max = null;

        $min = self::where(['key' => 'GAME_MIN_CHIP'])->field('value')->find();
        $max = self::where(['key' => 'GAME_MAX_CHIP'])->field('value')->find();

        $chip['min'] = $min['value'];
        $chip['max'] = $max['value'];
        return $chip;
    }


    /**
     * 注册协议
     * @return Config|null
     * @throws \think\exception\DbException
     */
    static public function reg_protocol()
    {
        $reg_pro = self::get(['key' => 'REGISTER_PROTOCLO']);
        return $reg_pro;
    }

    /**
     * 获取矿机范围
     * @return mixed
     * @throws \think\exception\DbException
     */
    static public function mining_rang()
    {
        $min = null;
        $max = null;

        $min = self::get(['key' => "MINING_MIN"]);
        $max = self::get(['key' => "MINING_MAX"]);

        $rang['min'] = $min['value'];
        $rang['max'] = $max['value'];
        return $rang;
    }


    /**
     * 获取本月矿机收益配置
     * @return mixed
     * @throws \think\exception\DbException
     */
    static public function mining_reword()
    {
        $mining_reword = null;
        $mining_reword_rang = null;
        $mining_step = null;

        $mining_reword = self::get(['key'=>"MINING_REWORD"]);
        $mining_reword_rang = self::get(['key'=>"MINING_REWORD_RANG"]);
        $mining_step = self::get(['key'=>'MINIG_STEP']);

        $reword['reword'] = $mining_reword['value'];
        $reword['rang'] = $mining_reword_rang['value'];
        $reword['step'] = $mining_step['value'];
        return $reword;
    }

    /**
     * 获取矿机直推反馈收益
     * @return mixed
     * @throws \think\exception\DbException
     */
    static public function feedback_reword()
    {
        $one = null;
        $t_t_f = null;
        $s_t_t = null;
        $e_t_ft = null;

        $one = self::get(['key'=>"ONE"]);
        $t_t_f = self::get(['key'=>"T_T_F"]);
        $s_t_t = self::get(['key'=>"S_T_T"]);
        $e_t_ft = self::get(['key'=>"E_T_FT"]);

        $return['one'] = $one['value'] / 100;
        $return['two'] = $t_t_f['value'] / 100;
        $return['six'] = $s_t_t['value'] / 100;
        $return['eleven'] = $e_t_ft['value'] / 100;

        return $return;
    }



}
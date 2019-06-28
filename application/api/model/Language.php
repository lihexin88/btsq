<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/8
 * Time: 14:40
 */

namespace app\api\model;


use think\Db;
use think\Model;

class Language extends Model
{
    /**
     * 三语转换
     * @param $chs 中文语言
     * @param $user 用户对象
     * @return null
     * @throws \think\exception\DbException
     */
    static public function lang($chs,$user)
    {
        /*获取当前中文对应的语言包*/
        $lang = self::get(['chs'=>$chs]);
        $r = null;

        /*不存在的话插入新的语言包,并递归调用*/
        if(!$lang){
            $new_lang = new self();
            $new_lang->chs = $chs;
            $new_lang->cht = $chs."繁体";
            $new_lang->en = $chs."english";
            $new_lang->save();
            return self::lang($chs,$user);
        }
        $user_config = UserConfig::config($user);
        if($user_config['language'] == 3){
            $r = $lang['en'];
        }else if($user_config['language'] == 2){
            $r = $lang['cht'];
        }else{
            $r = $lang['chs'];
        }
        return $r;
    }


    /**
     * 获取数据表中的中英文数据
     * @param $chs 中文数据
     * @param $modelname 数据表的名字
     * @param $user 用户对象信息
     * @return null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function lang_change($chs, $modelname, $user)
    {
        $user_config = UserConfig::config($user);
        $lang = Db::name($modelname)->where(['chs'=>$chs])->find();
        $r = null;
        if($user_config['language'] == 3){
            $r = $lang['en'];
        }else if($user_config['language'] == 2){
            $r = $lang['cht'];
        }else{
            $r = $lang['chs'];
        }
        return $r;
    }


    /**
     * 未登录三语转换
     * @param $chs
     * @param $language_type
     * @return array
     * @throws \think\exception\DbException
     */
    static public function no_login($chs, $language_type)
    {
        if(!in_array($language_type,['1','2','3'])){
            return ['error'];
        }
        $lang = Language::get(['chs' => $chs]);
        if(!$lang){
            $new_lang = new self();
            $new_lang->chs = $chs;
            $new_lang->cht = $chs."繁体";
            $new_lang->en = $chs."english";
            $new_lang->save();
            return self::no_login($chs,$language_type);
        }
        if ($_POST['language'] == 1) {
            $r['res'] = $lang['chs'];
        } else if ($_POST['language'] == 2) {
            $r['res'] = $lang['cht'];
        } else {
            $r['res'] = $lang['en'];
        }
        return $r['res'];

    }

}
<?php
/**
 * api接口基类
 */
namespace app\common\controller;
use app\common\controller\Base;
use think\Db;
class ApiBase extends Base
{
    public $userInfo;
    public function _initialize()
    {
        parent::_initialize();
        if (cookie('think_var') == 'zh-cn') {
            config('THINK_VAR', 'chs_');
        } elseif(cookie('think_var') == 'zh-ct'){
            config('THINK_VAR', 'cht_');
        }else{
            config('THINK_VAR', 'en_');
        }
         $map['token'] = input('token');
         $map['time_out'] = ['egt',time()];
         $token_user = db('user')->where($map)->find();
         if(!$token_user){
             echo rtn(1,lang('login_state')); exit;
         }else{
             $data['time_out'] = strtotime("+2 days");
             db('user')->where('token',input('token'))->update($data);
             $this->userInfo = db('user')->where('id',$token_user['id'])->find();
         }
    }

    /**
     * 验证请求的参数完整性
     * @param $post post的请求
     * @param null $param 请求验证的参数
     * @return mixed
     */
    static public function check_post($posts,$param = null)
    {
        foreach ($param as $k=>$v){
            if(!array_key_exists($k,$posts)){
                return ['status'=>0,'param'=>$v];
            }
            if(!isset($posts[$k])){
                return ['status'=>0,'param'=>$v];
            }
        }
        return ['status'=>1,'param'=>null];
    }
}


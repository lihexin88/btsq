<?php
namespace app\api\Controller;
use app\common\controller\ApiBase;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;

/**
 * 转账页面功能
 *
 * @remark 转账页面信息接口（transferPage）、转账功能接口（transfer）、获取验证码并验证用户手机号（code）
 */



class Transfer extends ApiBase
{
    public function _initialize() 
    {
        parent::_initialize();
    }

    /**
     * 转账页面信息
     * @param string @uid [用户ID]
     */
    public function transferPage()
    {
        $id = trim(input('uid'));
        if(!$id) {
            $r = $this->rtn(-1,lang("parameter"));
        }else{
            if(false == ($data = model('User')->transferPage($id))){
                $r = $this->rtn(-1,lang("error"));
            }else{
                $r = $this->rtn(0,lang("success"),$data); 
            } 
        }
        return json($r);
    }

    /**
     * 转账功能
     * @param string @uid [用户ID]
     * @param string @tid [转入用户ID]
     * @param string @dfs [转入DFS]
     */
    public function transfer()
    {
        $id = trim(input('uid'));
        $trader= trim(input('trader'));
        $money = trim(input('dfs'));
        $accout = trim(input('phone'));
        $code = trim(input('code'));
        if(!$id) {
            $r = $this->rtn(-1,lang("parameter"));
        }else if(!$trader) {
            $r = $this->rtn(-1,lang('cont_empty'));
        }else if(!$money) {
            $r = $this->rtn(-1,lang('cont_empty'));
        }else{
            if(session('authcode.code')){
                if($code == session('authcode.code')){
                    if($accout == session('authcode.phone')){
                        $return = model('User')->transfer($id,$trader,$money);
                        if($return['status'] === 0) {
                            $r = $this->rtn(-1,$return['info']);
                        }else{
                            $r = $this->rtn(0,lang('success'),session('authcode.code'));  
                        }
                    }else{
                        $r = $this->rtn(-1,lang('phone_diffent'));
                    }
                }else{
                    $r = $this->rtn(-1,lang('phone_error'));
                };  
            }else{
                $r = $this->rtn(-1,lang('code_error'));
            }

        }
        Session::delete('authcode');
        return json($r);
    }

    /**
     * code 获取验证码并验证用户手机号
     * @param string @uid [用户ID]
     * @param string @phone [手机号]
     */
    public function code(){
        $map['id'] = trim(input('uid'));
        $map['accout'] = trim(input('phone'));
        $userinfo = model('User')->infodata($map);
        if(!$userinfo){
            $r = $this->rtn(-1,lang('is_phone'));
        }else{
            $code = generate_code(6);
            Session::set('authcode', ['code' => $code, 'phone' => $map['accout']]);
            $r = $this->rtn(0,lang('success'),session('authcode.code'));   
        }
        return json($r);
    }
}

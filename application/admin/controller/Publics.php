<?php
namespace app\admin\controller;

use app\common\controller\Base;
use app\admin\model\Admin;
use think\Request;
use Org\Util\Ueditor;

class Publics extends Base
{
    /**
     * 公共模块首页
     * @return 根据是否存在aid决定跳转路径
     */
    function index()
    {
        if (session('?aid') == false) {
            $this->redirect(url('publics/login'));
        } else {
            $this->redirect(url('index/index'));
        }
    }

    /**
     * controller 获取手机验证码
     */
    public function get_verify(){
        $Admin = new Admin();
        return json($Admin -> getVerify_login(input('post.')));
    }

    /**
     * 用户登录
     */
    function login()
    {
        if (Request::instance()->isPost()) {
            return model('Admin')->userLogin(input('post.'));
        } else {
            return $this->fetch();
        }
    }

    /**
     * 用户登出
     */
    function logout()
    {
        session('aid', null);
        session('username', null);
        session('group_id', null);
        session('[destroy]'); // 销毁session
        $this->redirect('Publics/login', '您已经安全退出！');
    }

    /**
     * 生成验证码
     */
    public function verify()
    {
        ob_end_clean();//清除缓存
        $Verify = new \Think\Verify();
        $Verify->length = 4;
        $Verify->useNoise = false;
        $Verify->useNoise = true;
        $Verify->codeSet = '0123456789';
        $Verify->entry(1);
    }

    //上传图片
     public function upload(){
        $type = trim(input('type'));
        if(!$type){
            $r = ['status'=>0,'info'=>'参数不正确'];;
        }else{
            // 获取表单上传文件 例如上传了001.jpg
            $file = request()->file('file');
            
            // 移动到框架应用根目录/public/uploads/ 目录下
            if($file){
                $info = $file->move(ROOT_PATH . 'public' . DS . 'upload/'.$type);
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    $link = '/upload/'.$type.'/'.$info->getSaveName();
                    $r = ['status'=>1,'info'=>'成功','msg'=>$link];
                }else{
                    // 上传失败获取错误信息
                    $r = ['status'=>0,'info'=>$file->getError()];;
                }
            }else{
                $r = ['status'=>0,'info'=>'未上传'];
            }
        }
        return json($r);
    }

}
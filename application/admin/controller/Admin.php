<?php
namespace app\admin\controller;

use app\admin\model\AdminMessage;
use app\common\controller\AdminBase;
use app\admin\model\AdminLog;
use think\Request;
use think\Db;

class Admin extends AdminBase
{
    /**
     * controller 管理员列表
     * @param int $p
     * @return mixed
     */
    public function index($p = 1){
        $map = [];
        $keywords = input('get.keywords')?input('get.keywords'):null;
        if($keywords){
            $map['username']  = array('like', '%'.trim($keywords).'%');
        }
        $this -> assign("list",model('Admin') -> adminList($p,$map));
        return $this -> fetch();
    }

    /**
     * 新增管理员
     */
    public function add_user()
    {
        if(Request::instance()->isPost()){
           $map = input('post.');
           if(empty($map['username'])){
              return json(array('status' => 0, 'info' => '请输入用户名'));
           }
           if(empty($map['phone'])){
              return json(array('status' => 0, 'info' => '请输入手机号'));
           }
           if(!array_key_exists('group_id',$map)) {
              return json(array('status' => 0, 'info' => '请选择用户组'));
           }else{
             $map['user_type'] = $map['group_id'];
             return json(model('Admin')->saveInfo($map));
           }
          
        }
        $info = ['id'=>null,'username'=>null,'description'=>null,'group_id'=>2,'user_type'=>1,'status'=>1];
        $this->assign("group_list", model("AuthGroup")->getUserGroup());
        $this->assign('info',$info);
        return $this->fetch();
    }
    /**
     * controller 重置管理员密码
     */
    public function edit_pwd($id){
        return json(model('Admin') -> resetPwd($id));
    }

    /**
     * controller 删除管理员
     */
    public function delete_admin($id){
        return json(model('Admin') -> deleteAdmin($id));
    }




    /**
     * controller 管理员组列表
     */
    public function group(){
        $this -> assign("list", model("AuthGroup") -> groupList());
        return $this -> fetch();
    }

    /**
     * controller 修改管理员组权限
     */
    public function change_group_status(){
        if(Request::instance() -> isPost()){
            return json(model('AuthGroup') -> changeStatus(input('post.')));
        }
    }

    /**
     * controller 删除管理员组
     */
    public function delete_group($id){
        return json(model('AuthGroup') -> deleteGroup($id));
    }

    /**
     * controller 添加管理员组
     */
    public function add_group(){
        $this -> assign('pagename','添加管理员组');

        if(Request::instance() -> isPost()){
            return json(model('AuthGroup') -> saveInfo(input('post.')));
        }

        return $this -> fetch();
    }

    /**
     * 修改管理员组信息
     * @param $id
     * @return mixed|\think\response\Json
     */
    public function edit_group($id){
        $this -> assign('pagename','修改管理员组');
        $this -> assign('info',model('AuthGroup') -> modGroup($id));

        if(Request::instance() -> isPost()){
            return json(model('AuthGroup') -> saveInfo(input('post.')));
        }

        return $this -> fetch('add_group');
    }

    /**
     * controller 管理员组授权
     * @param $id
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function group_auth($id){
        $this -> assign('pagename','管理员组授权');
        $this -> assign("list", model("AuthRule") -> ruleList());

        $request = Request::instance();
        if(Request::instance() -> isPost()){
            $data['id'] = input('post.id');
            $rules = $request -> post('rules/a');
            if($rules){
                sort($rules);
                $data['rules']  = implode( ',' , array_unique($rules));
                $result = db('AuthGroup') -> where('id',$data['id']) -> update(['rules' => $data['rules']]);
                if($result){
                    return json(['code' => 1,'msg' => '更新完成!']);
                }else{
                    return json(['code' => 0,'msg' => '更新失败,权限未变动!']);
                }
            }else{
                return json(['code' => 0,'msg' => '更新失败,未提交成功']);
            }
        }

        $this -> assign("info",model("AuthGroup") -> getGroupRules($id));

        return $this->fetch();
    }



    /**
     * controller 权限列表
     */
    public function rule(){
        $this -> assign("list", model("AuthRule") -> ruleList());
        $this -> assign('is_show',dict_list('is_show'));
        $this -> assign('status',dict_list('common_state'));
        return $this -> fetch();
    }

    /**
     * controlle 删除权限
     */
    public function delete($id){
        return json(model('AuthRule') -> deleteInfo($id));
    }

    /**
     * controller 新增权限
     */
    public function add(){
        $this -> assign('pagename','添加权限');
        $this -> assign('pidlist',model('AuthRule') -> pidList(0));	// 获取权限表中pid为0的字段
        $this -> assign('is_show',dict_list('is_show'));	// 获取dict表中是否显示

        if(Request::instance() -> isPost()){
            $map = input('post.');
            $map['module'] = 'admin';
            return json(model('AuthRule') -> saveInfo($map));
        }

        $this -> assign('info',['id'=>null,'title'=>null,'name'=>null,'pid'=>0,'condition'=>null,'is_show'=>1,'icon'=>null]);
        return $this -> fetch();
    }

    /**
     * controller 修改权限信息
     * @param $id
     * @return mixed|\think\response\Json
     */
    public function edit_rule($id){
        $this -> assign('pagename','修改权限');
        $this -> assign('pidlist',model('AuthRule') -> pidList(0));	// 获取权限表中pid为0的字段
        $this -> assign('is_show',dict_list('is_show'));	// 获取dict表中是否显示
        $this -> assign('info',model('AuthRule') -> infodata(array('id' => $id)));	// 获取指定权限信息

        if(Request::instance() -> isPost()){
            return json(model('AuthRule') -> saveInfo(input('post.')));
        }

        return $this -> fetch('add');
    }

    /**
     * 用户登陆记录列表
     * @param int $p
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function log($p = 1){
        $this -> assign('pagename','登录日志');

        $AdminLog = new AdminLog();
        $this -> assign('log_list',$AdminLog -> log_list($p));

        return $this -> fetch();
    }

    /**
     * 短信发送记录
     * @param int $p
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function message($p = 1){
        $this -> assign('pagename','短信发送记录');

        $AdminMessage = new AdminMessage();
        $this -> assign('message_list',$AdminMessage -> message_list($p));

        return $this -> fetch();
    }
}
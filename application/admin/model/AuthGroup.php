<?php
namespace  app\admin\model;
use think\Model;
use think\Request;
use think\db;
use think\Validate;

class AuthGroup extends Model
{
    protected $insert = ['module'=>'admin','type'=>1];

    /**
     * model 管理员组列表
     */
    public function groupList() {
        $list = $this -> where( array('module'=>'admin','type'=>'1') ) -> order('id asc') -> select() -> toArray();
        foreach ($list as $k => $v) {
            // 启用/禁用
            $where['type'] = 'common_state';
            $where['value'] = $v['status'];
            $list[$k]['statusTxt'] = Db::name('dict') -> where($where) -> value('key');
            switch($v['status']){
                case 1:
                    $list[$k]['status_btn'] = 'status_green';
                    break;
                case 2:
                    $list[$k]['status_btn'] = 'status_red';
                    break;
            }
        }
        return $list;
    }

    /**
     * model 获取管理员组信息
     */
    public function getGroup() {
        $list = $this -> where( array('status'=>1) ) -> field('id,title') -> order('id asc') -> select() -> toArray();
        return $list;
    }

    /**
     * model 获取登陆管理员组列表
     */
    public function getUserGroup() {
        if(session('group_id')){
            $list = $this -> where(['status'=>1,'id' => array('gt',session('group_id'))]) -> field('id,title') ->order('id desc')-> select() -> toArray();
            return $list;
        }else{
            return $this -> getGroup();
        }
    }

    /**
     * model 修改用户组状态
     */
    public function changeStatus($data){
        $id = $data['id'];
        if(!$id){
            return ['code' => 0,'msg' => '未获取用户组信息!'];
        }
        $status = $data['status'];
        if(!$status){
            return ['code' => 0,'msg' => '未获取用户组状态!'];
        }

        if($status == 1){
            $mod['status'] = 2;
        }else{
            $mod['status'] = 1;
        }

        $result = $this -> where('id',$id) -> update($mod);
        if($result){
            return ['code' => 1,'msg' => '修改成功!'];
        }else{
            return ['code' => 0,'msg' => '修改失败!'];
        }
    }

    /**
     * model 删除用户组
     */
    public function deleteGroup($id){
        if(!$id){
            return ['code' => 0,'msg' => '未获取用户组信息!'];
        }

        $del = $this -> where('id',$id) -> delete();
        if($del){
            return ['cdoe' => 1,'msg' => '删除成功!'];
        }else{
            return ['code' => 0,'msg' => '删除失败!'];
        }
    }

    /**
     * model 添加/修改用户组
     */
    public function saveInfo($data){
        $id = $data['id'];

        if(!empty($id)){
            $where = true;
        }else{
            $where = false;
        }

        $AuthGroup = new AuthGroup;
        $result = $AuthGroup -> allowField(true) -> isUpdate($where) -> save($data);

        if($result === false){
            return ['status' => 0,'info' => $AuthGroup -> getError()];
        }else{
            return array('status' => 1, 'info' => '保存成功', 'url' => url('group'));
        }
    }

    /**
     * model 修改显示用户组信息
     */
    public function modGroup($id){
        $info = $this -> where('id',$id) -> find();
        return $info;
    }

    /**
     * moodel 显示用户组权限信息
     */
    public function getGroupRules($id){
        $list = $this -> field('id,title,rules') -> where( array('id' => $id,'module'=>'admin'))->find();
        return $list;
    }

}
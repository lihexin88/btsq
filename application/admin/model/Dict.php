<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;

class Dict extends Base
{
    const PAGE_LIMIT = '20';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量

    /**
     * 获取分类名称
     * @param $where
     * @return mixed
     */
    public function get_dict_key_by_where($where){
        $where['state'] = 1;
        $result = $this -> where($where) -> value('key');
        return $result;
    }

    public function infoList($map, $p)
    {
        $request= Request::instance();
        $list = $this->where($map)->order('type asc,value asc')->page($p, self::PAGE_LIMIT)->select()->toArray();
        foreach ((array)$list as $k => $v) {
            $list[$k]['stateTxt'] = $v['state'] ? '启用' : '禁用';
        }
        $return['count'] = $this->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        return $return;
    }

    public function listInfo($id)
    {
        $info = $this->where(array('id' => $id))->find();
        return $info;
    }

    public function saveInfo($data)
    {
        if(array_key_exists('id',$data)){
            $id = $data['id'];
            if(!empty($id)){
                $where = true;
            }else{
                $where = false;
            }
        }else{
            $where = false;
        }
        $Dict = new Dict;
        $result = $Dict->allowField(true)->isUpdate($where)->save($data);
        if(false === $result){
            return ['status'=>0,'info'=>$Dict->getError()];
        }else{
            return array('status' => 1, 'info' => '保存成功', 'url' => url('index'));
        }
    }

    //数据的删除操作
    public function deleteInfo($id)
    {
        if ($this->where(array('id' => $id))->delete()) {
            return array('status' => 1, 'info' => '删除成功!', 'url' => U('index'));
        } else {
            return array('status' => 0, 'info' => '删除失败，数据未删除');
        }
    }

    //改变状态
    public function changeState($data)
    {
        $data['update_time'] = NOW_TIME;
        if ($this->save($data)) {
            return array('status' => 1, 'info' => '更改状态成功');
        } else {
            return array('status' => 0, 'info' => '更改状态失败');
        }
    }

    /* 内部检测 */
    public function checkKey($key)
    {
        $id = I('post.id');
        if ($id) {
            return true;
        } else {
            $info = $this->where(array('type' => I('post.type'), 'key' => $key))->find();
            if ($info) {
                return false;
            } else {
                return true;
            }
        }
    }

    /* 内部检测 */
    public function checkValue($value)
    {
        $id = I('post.id');
        if ($id) {
            return true;
        } else {
            $info = $this->where(array('type' => I('post.type'), 'value' => $value))->find();
            if ($info) {
                return false;
            } else {
                return true;
            }
        }
    }
}
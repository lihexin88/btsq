<?php
namespace  app\admin\model;
use think\Request;
use think\db;
use think\Validate;
use think\Model;
class AuthRule extends Model
{
    protected $autoWriteTimestamp = false;

    /**
     * model 权限列表
     */
    public function ruleList(){
        $list = $this -> where(['pid'=>0]) -> order('sort ASC,id ASC') -> select() -> toArray();
        foreach ($list as $k => $v) {
            // 查询一级权限中是否存在二维权限
            $child_count = $this -> where(['pid' => $v['id']]) -> count();
            if($child_count > 0){
                $list[$k]['have'] = 1;
            }else{
                $list[$k]['have'] = 0;
            }

            $list[$k]['child'] = $this -> where(['pid' => $v['id']]) -> order('sort ASC,id ASC') -> select();
        }
        return $list;
    }

    /**
     *  model 删除权限
     */
    public function deleteInfo($id){
        if(!$id){
            return ['code' => 0,'msg' => '未获取到要删除的权限信息!'];
        }

        $del = $this -> where(array('id' => $id)) -> delete();
        if($del){
            return ['code' => 1,'msg' => '删除成功!'];
        }else{
            return ['code' => 0,'msg' => '删除失败!'];
        }
    }

    /**
     * model 根据pid获取权限列表
     * @param  string $id PID
     */
    public function pidList($id){
        $list = $this -> where(['pid' => $id]) -> order('sort ASC') -> select();
        return $list;
    }

    /**
     * model 添加/修改权限
     * @param  array $data 传入数据
     */
    public function saveInfo($data) {

        if(array_key_exists('id',$data)){
            $id = $data['id'];
            if(!empty($id)){
                $where = true;
            }else{
                $where = false;
                // 判断规则(对应url)是否重复
                $exist = $this -> where('name',$data['name']) -> find();
                if($exist){
                    return ['status' => 0,'info' => '此"对应URL"已存在!'];
                }
            }
        }else{
            $where = false;
            // 判断规则(对应url)是否重复
            $exist = $this -> where('name',$data['name']) -> find();
            if($exist){
                return ['status' => 0,'info' => '此"对应URL"已存在!'];
            }
        }

        $AuthRule = new AuthRule;
        $result = $AuthRule -> allowField(true) -> isUpdate($where) -> save($data);
        if($result === false){
            return ['status' => 0,'info' => $AuthGroup -> getError()];
        }else{
            return array('status' => 1, 'info' => '保存成功', 'url' => url('index'));
        }
    }

    /**
     * model 根据查询条件获取信息
     * @param string $map [查询条件]
     * @return mixed
     */
    public function infodata($map){
        $list = $this->where($map)->find();
        if(!is_null($list)){
            return $list->toArray();
        }
        return false;
    }

}
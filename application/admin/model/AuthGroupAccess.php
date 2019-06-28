<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;
class AuthGroupAccess extends Base
{
    protected $autoWriteTimestamp = false;
    /**
     * 新增/修改用户
     * @param  array $data 传入信息
     */
    public function saveInfo($data)
    {                
        $info = $this->where(array('uid'=>$data['uid']))->find();
        if($info){
            $AuthGroupAccess = new AuthGroupAccess;
            $result = $AuthGroupAccess->where('uid', $data['uid'])
                ->update(['group_id' => $data['group_id']]);
        }else{
            $result = $this->isUpdate(false)->save($data);
        }
        if (false === $result) {
            return array('status' => 0, 'info' => '保存失败');
        } else {
            return array('status' => 1, 'info' => '保存成功');
        }     
    }
}
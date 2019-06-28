<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;
use think\Validate;
class Mills extends Base
{

    /**
    *添加时自动完成
    */
    protected $insert = array('invitation_code','credit_level'=> 3,'all_profit'=>0);

    /**
    *更新时自动完成
    */
    protected $update = [];

    /**
    *自动加密
    */
    public function setPasswordAttr($value)
    {
        return encrypt($value);
    }
    public function setPaymentPasswordAttr($value)
    {
        return encrypt($value);
    }

    /**
    *自动生成邀请码
    */
    public function setInvitationCodeAttr()
    {
        return generateOrderNumber();
    }
    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量


    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p  页码
     * @return array      返回列表
     */
    public function infoList($map, $p)
    {
        $request= Request::instance();
        $list = $this->where($map)->order('id desc')->page($p, self::PAGE_LIMIT)->select()->toArray();
        foreach ($list as $k => $v) {
            $userinfo = $this->infodata(array('id'=>$v['parent_id']));
            $list[$k]['parent_name'] = $userinfo['username'];
        }
        $return['count'] = $this->where($map)->count();
        $return['list'] = $list;
        
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        return $return;
    }

    /**
     * 新增/修改
     * @param  array $data 传入信息
     */
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
        $User = new Mills;
        $result = $User->allowField(true)->isUpdate($where)->save($data);
        if(false === $result){
            // 验证失败 输出错误信息
            return ['status'=>0,'info'=>$User->getError()];
        }else{
            return array('status' => 1, 'info' => '保存成功', 'url' => url('index'));
        }
    }

    /**
     * 改变状态
     * @param  array $data 传入数组
     */
    public function changeState($data)
    {
        if ($this->where(array('id'=>$data['id']))->update(array('state'=>$data['status']))) {
            return array('status' => 1, 'info' => '更改状态成功');
        } else {
            return array('status' => 0, 'info' => '更改状态失败');
        }
    }

    /**
     * 删除
     * @param  string $id ID
     */
    public function deleteInfo($id)
    {
        if($this->where(array('id'=>$id))->delete()){
            return ['status'=>1,'info'=>'删除成功'];
        }else{
            return ['status'=>0,'info'=>'删除失败,请重试'];
        }
    }

        /**
     * 根据查询条件获取信息
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

    /* 根据键名取键值 用于foreach */
    public function showKey()
    {
        $list = $this->field('id,name')->order('id desc')->select()->toArray();
        foreach ((array)$list as $k => $v) {
            $return[$v['id']] = $v['name'];
        }
        return $return;
    }

    public function showList()
    {
        $list = $this->field('id,name')->order('id asc')->select()->toArray();
        return $list;
    }

}
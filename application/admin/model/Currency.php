<?php
namespace app\admin\model;

use app\common\model\Base;
use think\Request;
use think\db;

class Currency extends Base
{
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

        $return['count'] = $this->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        return $return;
    }

    /**
     * 新增/修改
     * @param array $data 传入信息
     */
    public function saveInfo($data)
    {
    	if(array_key_exists('id', $data)){
    		$id = $data['id'];
    		if(!empty($id)){
    			$where = true;
    		}else{
    			$where = false;
    		}
    	}else{
    		$where = false;
    	}
    	$Currency = new Currency;
    	$result = $Currency->allowField(true)->isUpdate($where)->save($data);
    	if(false === $result){
            return ['status'=>0,'info'=>$AuthGroup->getError()];
        }else{
            return array('status' => 1, 'info' => '保存成功', 'url' => url('index'));
        }
    }

    public function changeState()
    {
        return 123;
    }
    /**
     * 根据查询条件获取信息
     */
    public function currList($id)
    {
    	$list = $this->where('id',$id)->find();
    	return $list;
    }

    /**
     * 获取所有币种信息
     * @return mixed
     * @throws \think\exception\DbException
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     */
    public function curList(){
        $list = $this -> select();
        return $list;
    }

    /**
     * 获取币种名称
     * @param $id
     * @return mixed
     */
    public function get_cur_text($id){
        $text = $this -> where('id',$id) -> value('name');
        return $text;
    }
}
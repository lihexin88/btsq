<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;

class Record extends Base
{

    /**
    *添加时自动完成
    */
    protected $insert = array('start_time','sell_status'=> 1);

    /**
    *更新时自动完成
    */
    protected $update = [];

    public function setStartTimeAttr()
    {
        return time();
    }
    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量


    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p  页码
     * @return array      返回列表
     */
    //1买入（DFS），2卖出（DFS），3DFS收益，4组织收益，5提现（USD），6转账（DFS）
    public function infoList( $p,$map)
    {
        $request= Request::instance();
        $list = $this->where($map)->order('id desc')->page($p, self::PAGE_LIMIT)->select()->toArray();
        $count = $this->where($map)->count();
        $page = boot_page($count, self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $userDataArr = model('User')->showKey();
        $payTypeArr = model('Common/Dict')->showKey('pay_type');
        foreach ($list as $k => $v) {
            $list[$k]['user_name'] = $userDataArr[$v['user_id']];
            if($v['trader_id']){
                $list[$k]['trader_name'] = $userDataArr[$v['trader_id']];
            }else{
                $list[$k]['trader_name'] = '暂无';
            }
            $list[$k]['transactionTextArr'] = $this -> get_transaction_text($v['transaction_type']);
            if($v['pay_type']){
                $list[$k]['money'] = $v['money'].$payTypeArr[$v['pay_type']];
            }else{
                $list[$k]['money'] = $v['money'].'GCU';
            }
            $list[$k]['payTypeArr'] =  $payTypeArr[$v['pay_type']];
        }
        $return['count'] = $count;
        $return['list'] = $list;
        $return['page'] = $page;
        return $return;
    }

    // 获取交易类型中文
    protected function get_transaction_text($type){
        $map['type'] = 'transaction_type';
        $map['value'] = $type;
        $map['state'] = 1;
        $result = Db::name('dict') -> where($map) -> value('key');
        return $result;
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
        $Sell = new Sell;
        $result = $Sell->allowField(true)->isUpdate($where)->save($data);
        if(false === $result){
            return ['status'=>0,'info'=>$AuthGroup->getError()];
        }else{
            return array('status' => 1, 'info' => '保存成功', 'url' => url('index'));
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

}
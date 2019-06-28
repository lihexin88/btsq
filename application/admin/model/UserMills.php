<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;
use think\Validate;
class UserMills extends Base
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
        $list = db('user_mining')->where($map)->order('uid desc')->page($p, self::PAGE_LIMIT)->select();
        $page_amount = 0;           // 本页总花费
        $page_total_reword = 0;     // 本页总收益
        foreach ($list as $k => $v) {
            $list[$k]['user_name'] = db('user')->where('id',$v['uid'])->value('email');
            $list[$k]['status'] = $v['status'] == 1 ?'开启':'关闭';
            $list[$k]['mining_status'] = $v['mining_status'] == 1 ?'未报废':'已报废';
            $page_amount += $v['amount'];
            $page_total_reword += $v['total_reword'];
            $a = $v['all_reword']-$v['total_reword'];
            if($v['spend'] <= 0){
                continue;
            }
            $list[$k]['shengyu'] = $a*$v['amount']/$v['spend'];
            $list[$k]['amount'] = number_format($v['amount'],2);
            $list[$k]['spend'] = number_format($v['spend'],5);
        }
        $return['count'] = db('user_mining')->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $return['page_amount'] = number_format($page_amount,2);
        $return['page_total_reword'] = number_format($page_total_reword,5);
        $return['all_amount'] = number_format(db('user_mining') -> where($map) -> sum('amount'),2);
        $return['all_total_reword'] = number_format(db('user_mining') -> where($map) -> sum('total_reword'),5);
        return $return;
    }

    public function mining_profit($map, $p,$order)
    {
        $request= Request::instance();
        $list = db('user_coin_profit')->where($map)->order($order)->page($p, self::PAGE_LIMIT)->select();
        $page_all_amount = 0;
        foreach ($list as $k => $v) {
            $list[$k]['user_name'] = db('user')->where('id',$v['uid'])->value('email');
            $list[$k]['all_amount'] = number_format($v['all_amount'],5);
            $list[$k]['price'] = number_format($v['price'],2);
            $page_all_amount += $v['all_amount'];
        }
        $return['count'] = db('user_coin_profit')->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $return['page_all_amount'] = number_format($page_all_amount,5);
        $return['all_amount'] = number_format(db('user_coin_profit') -> where($map) -> sum('all_amount'),5);
        return $return;
    }

    public function mining_profit2($map, $p)
    {
        $request= Request::instance();
        $record = db('mining_extracted')->where($map)->order('time desc')->select();
        if($record){
            foreach($record as $k => $v){
                $record[$k]['create_time'] = date('Y-m-d H:i',$v['time']);
                $kk = $v['uid'].date('Y-m-d H:i',$v['time']);
                $list[$kk]['time'] =$v['time'];
                $list[$kk]['uid'] =$v['uid'];
                $list[$kk]['settlement_income'] += $v['settlement_income'];
                $list[$kk]['unextracted_income'] += $v['unextracted_income'];
                $list[$kk]['extracted_income'] += $v['extracted_income'];  
                $list[$kk]['email'] = db('user')->where('id',$v['uid'])->value('email');  
            }
            $last_names = array_column($list,'time');
            array_multisort($last_names,SORT_DESC,$list);  
        }else{
            $list = [];
        }
        // $list = db('mining_extracted_today')->where($map)->order('time desc')->page($p, self::PAGE_LIMIT)->select();
        // foreach ($list as $k => $v) {
        //   //  $list[$k]['time'] = date('Y.m.d',$v['time']);
        //     $list[$k]['email'] = db('user')->where('id',$v['uid'])->value('email');
        //     $list[$k]['time'] = $v['time'];
        // }
        $return['count'] = count($list);
        $i = ($p-1)*10;
        $return['list'] = array_slice($list,$i,10);
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $return['settlement_income'] = db('mining_extracted_today')->where($map)->sum('settlement_income');
        $return['unextracted_income'] = db('mining_extracted_today')->where($map)->sum('unextracted_income');
        $return['extracted_income'] = db('mining_extracted_today')->where($map)->sum('extracted_income');
        return $return;
    }

    public function mining_profit3($map, $p)
    {
        $request= Request::instance();
        $list = db('mining_extracted')->alias('a')->join('user_mining b','a.mining_id = b.id')->where($map)->order('a.time desc')->page($p, self::PAGE_LIMIT)->field('a.id,a.uid,a.mining_id,a.settlement_income,a.unextracted_income,a.contribution_deviation,a.investment_deviation,a.extracted_income,a.time,a.price,a.state,b.spend,b.amount,b.user_level,b.all_reword,b.total_reword')->select();  
        foreach ($list as $k => $v) {
            $list[$k]['time'] = date('Y.m.d',$v['time']);
            $list[$k]['email'] = db('user')->where('id',$v['uid'])->value('email');
            $list[$k]['mining_status'] = '否';
            $list[$k]['unextracted'] = 0;
            $list[$k]['shengyu'] = ($v['all_reword']-$v['total_reword'])*$v['price'];
            if($v['state'] == 1){
                if($v['unextracted_income'] != 0){
                    $list[$k]['mining_status'] = '是';
                    $list[$k]['unextracted'] = $v['unextracted_income'];
                }
            }
        }
        
        $return['count'] = db('mining_extracted')->alias('a')->join('user_mining b','a.mining_id = b.id')->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        // $return['settlement_income'] = db('mining_extracted_today')->where($map)->sum('settlement_income');
        // $return['unextracted_income'] = db('mining_extracted_today')->where($map)->sum('unextracted_income');
        // $return['extracted_income'] = db('mining_extracted_today')->where($map)->sum('extracted_income');
        return $return;
    }

    public function mining_scrap($map, $p)
    {
        $request= Request::instance();
        $list = db('mining_scrap')->where($map)->order('id desc')->page($p, self::PAGE_LIMIT)->select();
        $page_all_amount = 0;
        foreach ($list as $k => $v) {
            $list[$k]['user_name'] = db('user')->where('id',$v['uid'])->value('email');
            $list[$k]['number'] = round($v['total']/$v['price'],2);
            $page_all_amount += $v['total'];
        }
        $return['count'] = db('mining_scrap')->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $return['page_all_amount'] = $page_all_amount;
        $return['all_amount'] = db('mining_scrap') -> where($map) -> sum('total');
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
        $User = new UserMills;
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
            return array('status' => 1,'info' => '更改状态成功');
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
        $list = $this->field('id,uid')->order('id desc')->select()->toArray();
        foreach ((array)$list as $k => $v) {
            $return[$v['id']] = $v['uid'];
        }
        return $return;
    }

    public function showList()
    {
        $list = $this->field('id,uid')->order('id asc')->select()->toArray();
        return $list;
    }

}
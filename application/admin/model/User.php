<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;
use think\Validate;
use think\Exception;
class User extends Base
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
     * 获取用户邮箱
     * @param $id
     * @return mixed
     */
    public function get_user_email_by_id($id){
        $email = $this -> where('id',$id) -> value('email');
        return $email;
    }

    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p  页码
     * @return array      返回列表
     */
    public function infoList($map, $p)
    {
        $request= Request::instance();
        $list = db('user')->alias('a')->join('auto_withdraw b','a.id=b.uid','LEFT')->where($map)->order('a.id desc')->page($p, self::PAGE_LIMIT)->field('a.id,a.email,a.level,a.level_time,a.two_level_time,a.parent_id,a.invitation_code,a.create_time,a.status,a.login_time,b.status as withdraw_status')->select();
        foreach ($list as $k => $v) {
            $parent_name = $this->where('id',$v['parent_id'])->value('email');
            $list[$k]['parent_name'] = $parent_name?$parent_name:'无';
            $list[$k]['withdraw_status'] = $v['withdraw_status'] == 1?'已开启':'未开启';
            $contribution = db('contribution_his')->where('uid',$v['id'])->order('update_time desc')->value('contribution');
            $list[$k]['contribution'] = $contribution?$contribution:0;
        }
        $return['count'] = db('user')->alias('a')->join('auto_withdraw b','a.id=b.uid','LEFT')->where($map)->count();
        $return['list'] = $list;
        
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        return $return;
    }

    public function recharge_record($map, $p)
    {
        $request= Request::instance();
        $list = db('user_coin')->alias('a')->join('user b','a.uid=b.id','LEFT')->where($map)->order('a.id desc')->page($p, self::PAGE_LIMIT)->field('a.id,a.all_amount,a.create_time,a.type,b.email')->select();
        $page_recharge = 0;
        $page_deduction = 0;
        foreach ($list as $k => $v) {
            $list[$k]['type'] = $v['type'] == 6?'充值':'扣除';
            $list[$k]['all_amount'] = number_format($v['all_amount'],5);
            switch($v['type']){
                case 6:
                    $page_recharge += $v['all_amount'];
                    break;
                case 7:
                    $page_deduction += $v['all_amount'];
                    break;
            }

        }
        $return['count'] = db('user_coin')->alias('a')->join('user b','a.uid=b.id','LEFT')->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        $return['page_recharge'] = number_format($page_recharge,5);
        $return['all_recharge'] = number_format(db('user_coin')->alias('a')->join('user b','a.uid=b.id','LEFT')->where($map)->where('a.type=6')-> sum('a.all_amount'),5);
        $return['page_deduction'] = number_format($page_deduction,5);
        $return['all_deduction'] = number_format(db('user_coin')->alias('a')->join('user b','a.uid=b.id','LEFT')->where($map)->where('a.type=7')-> sum('a.all_amount'),5);
        return $return;
    }

    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p  页码
     * @return array      返回列表
     */
    public function configList($map, $p)
    {
        $request= Request::instance();
        $list = db('user_config')->alias('a')->join('user b','a.uid=b.id','LEFT')->where($map)->order('a.id desc')->page($p, self::PAGE_LIMIT)->field('a.id,b.email,a.transfer_status,a.transaction_status,a.profit_status,a.account_status')->select();
        $return['count'] =  db('user_config')->alias('a')->join('user b','a.uid=b.id','LEFT')->where($map)->count();
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
        $User = new User;
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
        if ($this->where(array('id'=>$data['id']))->update(array('status'=>$data['status']))) {
            return array('status' => 1, 'info' => '更改状态成功');
        } else {
            return array('status' => 0, 'info' => '更改状态失败');
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
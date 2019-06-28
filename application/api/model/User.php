<?php

namespace app\api\model;

use think\Exception;
use think\Model;
use think\Session;
use think\Db;

class User extends Model
{
    /**
    *添加时自动完成
    */
    protected $insert = ['secret_key'];

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

    /**
    *自动加密
    */
    public function setPaymentPasswordAttr($value)
    {
        return encrypt($value);
    }

    /**
    *自动生成邀请码
    */
    public function setInvitationCodeAttr()
    {
        return make_coupon_card();
    }


    /**
    *自动生成秘钥
    */
    public function setSecretKeyAttr()
    {
        return get_hash();
    }

    /**
     * 获取用户信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_by_id($id){
        $user = $this -> where('id',$id) -> find();
        return $user;
    }

    /**
     * 执行惩罚
     * @param $id
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function punishment($id){
        $time = time();
        $week = 60*60*24*7;
        $month = 60*60*24*30;

        // 记录连续惩罚次数
        $Trade = new Trade();
        $is_continuity = $Trade -> get_user_last_trade($id);
        switch($is_continuity){
            case 1: // 是连续惩罚(追加记录)
                if(false === $this -> where('id',$id) -> setInc('continuity_punishment')){
                    throw new Exception('记录连续惩罚次数失败');
                }
                break;
            case 2: // 非连续惩罚(重新记数)
                if(false === $this -> where('id',$id) -> update(['continuity_punishment' => 1])){
                    throw new Exception('记录连续惩罚次数失败');
                }
                break;
        }

        // 记录交易超时惩罚结束时间
        $user = $this -> where('id',$id) -> find();
        $info['title'] = '交易超时';
        $info['first_content'] = '您在';
        switch($user['continuity_punishment']){
            case 1:
             // 记录到"通知中心"
            $info['second_content'] = '交易超时，两次交易将冻结一周';
            break;
            case 2:
                $mod_punishment['punishment_time'] = $time + $week;
                $info['second_content'] = '交易超时，三次交易将冻结一月';
                if(false === $this -> where('id',$id) -> update($mod_punishment)){
                    throw new Exception('记录交易超时惩罚结束时间失败');
                }
                break;
            case 3:
                $mod_punishment['punishment_time'] = $time + $month;
                $info['second_content'] = '交易超时，四次账号将被冻结';
                if(false === $this -> where('id',$id) -> update($mod_punishment)){
                    throw new Exception('记录交易超时惩罚结束时间失败');
                }
                break;
            case 4:
                $mod_punishment['status'] = 2;
                if(false === $this -> where('id',$id) -> update($mod_punishment)){
                    throw new Exception('记录交易超时惩罚结束时间失败');
                }
                break;
        }
        $data = [
            'user_info' => $user,
            'data' => $info,
        ];
        $UserMessage = new UserMessage();
        $UserMessage -> create_user_message($data);
        return ['code' => 1];
    }

    /**
     * 用户注册
     * @param  array $data 注册信息
     */
    public function userReg($data)
    {
        $data['level_time'] = time();
        $User = new User;
        if($this->where('email', $data['email'])->find()){
            return ['status'=>0,'info'=>lang('account_registered')];
        }
        $info = $this->where('invitation_code', $data['invitation_code'])->find();
        if($info){
            $data['parent_id'] = $info['id'];
        }else{
            return ['status'=>0,'info'=>lang('invitation_code_exist')];
        }
        // 调用当前模型对应的User验证器类进行数据验证
        $result = $User->allowField(true)->save($data);
        if(false === $result){
            return ['status'=>0,'info'=>$User->getError()];
        }else{
            $id = $User->id;
            $invitation_code = $User->invitation_code;
            $secret_key = $User->secret_key;
            model('Library')->qrcode($id,$invitation_code);
            return ['id'=>$id,'status'=>1,'info'=>$secret_key];
        }
    }


    /**
     * 用户登录
     * @param string @account [账号]
     * @param string @password [密码]
     * @param
     * @return mixed
     */
    public function login($email, $password,$language,$email_code = null)
    {
//      验证用户存在
        if(false == ($userinfo = $this->where(array('email'=>$email))->find()) ){
            return ['status'=>0,'info'=>'no_account'];
        }
//        验证用户禁用状态
        if($userinfo['status'] == 2){
            return ['status'=>0,'info'=>Language::lang('用户被禁止',$userinfo)];
        }
  //验证密码
        if(encrypt($password) != $userinfo['password']){
            if($userinfo['pwd_times'] == 4 || $userinfo['pwd_times'] == 9 || $userinfo['pwd_times'] == 14 || $userinfo['pwd_times'] == 19 || $userinfo['pwd_times'] == 24 ){//次数为4 9 14 19 24
                $time = time()+1800;//存时间
                $this->where('email',$email)->update(['time'=>$time]);//半小时
            }
            if($userinfo['count_times'] == 24){//次数为8
            	$time = time()+86400;//存时间
                $this->where('email',$email)->update(['time'=>$time]);//24小时
            }
            $this->where('email',$email)->setInc('pwd_times',1);//自增1
          	$this->where('email',$email)->setInc('count_times',1);//自增1
            return ['status'=>0,'info'=>Language::lang('密码不正确',$userinfo)];
        }
//        获取用户配置信息
        $user_config = UserConfig::config($userinfo);
        $user_config->language = $language;
        $user_config->save();
        if ($user_config['login_email'] == 1) {
            if (!\session('email_code')) {
                return ['status' => 2, 'info' => Language::lang('请输入邮箱验证码', $userinfo)];
            }
            if(!(\session('email_code.email') == $email && \session('email_code.code') == $email_code)){
                return ['status'=>2,'info'=>Language::lang('邮箱验证码不正确',$userinfo)];
            }
        }
//        产生token
        $data['token'] = createToken();
        $data['time_out'] = strtotime("+2 days");
        $data['login_time'] = time();
        $this->where('id',$userinfo['id'])->update($data);
//        更新用户配置
        $array =[
            'language'=>$language,
        ];
        $UserConfig = new UserConfig();
        if(!$UserConfig->change($userinfo,$array)){
            return ['status'=>0,'info'=>Language::lang("修改用户配置失败",$userinfo)];
        }
//      删除session
        /**
         * 待
         */
        $datas['token'] = $data['token'];
        $datas['language'] = $user_config['language'];

        return ['status' => 1, 'info' => $datas];
    }


    /**
     * 修改用户信息
     * @param $user 用户对象信息
     * @param null $array 返回的数组
     * @return bool|false|string
     * @throws \think\exception\DbException
     */
    public function change_user_info($user,$array = null)
    {
        $user_info = $this->get(['id'=>$user['id']]);
        foreach ($array as $k=>$v){
            $user_info[$k] = $v;
        }
        if(!($user_info->save())){
            return false;
        }
        return true;
    }


    /**
     * 日审核用户等级
     * @param $user 用户
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public function daily_user_level($user,$amount=0)
    {
        $vip_detials = VipDetails::order('vid')->select();
        $user_mining = UserMining::field('sum(amount) as amount')->where(['uid' => $user['id']])->select();
        $user_share = self::where(['parent_id' => $user['id']])->field('id')->select()->toArray();
        $user_share_mining = UserMining::whereIn('uid',$user_share )->field('count(amount) as amount')->select();
        $user_mining[0]->amount = $user_mining[0]->amount + $amount;
        switch ($user_mining[0]->amount) {
            /*平民*/
            case $user_mining[0]->amount < 100:
                self::user_grade($user, 0);
                break;

            /*vip1*/
            case $user_mining[0]->amount >= 100 && $user_mining[0]->amount <= 299:
                self::user_grade($user, 1);
                break;

            /*vip2*/
            case $user_mining[0]->amount >= 300:
                self::user_grade($user, 2);

            /*vip3*/
            case $user_mining[0]->amount >= 1000:
                if ($user_share_mining >= 10) {
                    $user_grade = VipDetails::get(['vid' => 3]);
                    if ($user['child_spend'] >= $user_grade->upgrade_min_spend) {
                        self::user_grade($user, 3);
                    }
                }

            /*vip4*/
            case $user_mining[0]->amount >= 3000:
                $user_share_vips = self::where(['parent_id' => $user['id'], 'level' => 3])->field('count(id) as num')->select();
                if ($user_share_mining > 10) {
                    if ($user_share_vips[0]->num > 3) {
                        $user_grade = VipDetails::get(['vid' => 4]);
                        if ($user['child_spend'] >= $user_grade->upgrade_min_spend) {
                            self::user_grade($user, 4);
                        }
                    }
                }

            /*vip5*/
            case $user_mining[0]->amount >= 10000:
                $user_share_vips = self::where(['parent_id' => $user['id'], 'level' => 4])->field('count(id) as num')->select();
                if ($user_share_mining > 10) {
                    if ($user_share_vips[0]->num > 3) {
                        $user_grade = VipDetails::get(['vid' => 5]);
                        if ($user['child_spend'] >= $user_grade->upgrade_min_spend) {
                            self::user_grade($user, 5);
                        }
                    }
                }

            /*vip6*/
            case $user_mining[0]->amount >= 30000:
                $user_share_vips = self::where(['parent_id' => $user['id'], 'level' => 5])->field('count(id) as num')
                    ->select();
                if ($user_share_mining > 10) {
                    if ($user_share_vips[0]->num > 3) {
                        $user_grade = VipDetails::get(['vid' => 6]);
                        if ($user['child_spend'] >= $user_grade->upgrade_min_spend) {
                            self::user_grade($user, 6);
                        }
                    }
                }
                break;
            default :
                self::user_grade($user, 0);
        }
        return true;
    }


    /**
     * @param $user 用户对象信息
     * @param $array 修改的用户数据
     * @return array 返回的状态信息等
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function change_user_account($user,$array)
    {
        $user_info = $this->where(['email'=>$user['email']])->find();
        if(!$user_info){
            return ['status'=>0,'info'=>'用户不存在'];
        }
        foreach ($array as $k=>$v){
            $user_info->$k = $v;
        }
        if(!$user_info->save()){
            return ['status'=>0,'info'=>'用户信息修改失败'];
        }
        return ['status'=>1,'info'=>'用户信息修改成功'];
    }



    /**
     * 根据ID获取团队信息
     * @param string $$$id [用户ID]
     * @return mixed
     */
    public function homePageList($id)
    {
        $count = 0;
        $info = $this->where(array('id'=>$id))->field('id,username,account,parent_id')->find()->toArray();
        $info['name'] = $info['username'].'_'.$info['account'];
        $child1 = $this->userinfo($info['id']);
        foreach ($child1 as $k => $v) {
            $child1[$k]['name'] = $v['username'].'_'.$v['account'];
            $child2 = $this->userinfo($v['id']);
            foreach ($child2 as $kk => $vv) {
                $child2[$kk]['name'] = $vv['username'].'_'.$vv['account'];
                $child3 = $this->userinfo($vv['id']);
                if($child3){
                    foreach ($child3 as $kkk => $vvv) {
                        $child3[$kkk]['name'] = $vvv['username'].'_'.$vvv['account'];
                    }
                    $child2[$kk]['children'] = $child3;
                    $count = $count+count($child3);
                }
            }
            if($child2){
              $child1[$k]['children'] = $child2;  
              $count = $count+count($child2);
            }
        }
        if($child1){
            $info['children'] = $child1; 
            $count = $count+count($child1);
        } 
        $info['allcount'] = $count+1;
        return $info;
    }

    /**
     * 根据ID获取团队z总金额
     * @param string $$$id [用户ID]
     * @return mixed
     */
    public function allMoney($id)
    {
        $money = 0;
        $info = $this->where(array('id'=>$id))->field('id,username,parent_id,dfs')->find()->toArray();
        $child1 = $this->userinfo($info['id']);
        foreach ($child1 as $k => $v) {
            $child2 = $this->userinfo($v['id']);
            foreach ($child2 as $kk => $vv) {
                $child3 = $this->userinfo($vv['id']);
                if($child3){
                    $child2[$kk]['children'] = $child3;
                        foreach ($child3 as $kkk => $vvv) {
                            $money = $money + $vvv['dfs'];
                        }
                }
                $money = $money + $vv['dfs'];
            }
            if($child2){
              $child1[$k]['children'] = $child2;  
            }
            $money = $money + $v['dfs'];
        }
        if($child1){
            $info['children'] = $child1; 
        } 
        $info['allcount'] = ($money + $info['dfs']) * config('DFS_EXCHANGE_RATE');
        return $info['allcount'];
    }

    /**
     * 获取财务管理页面信息
     * @param int @id [用户ID]
     * @return mixed
     */
    public function financePage($id)
    {
        $userinfo = $this->where(array('id'=>$id))->field('id,usd,dfs,all_profit')->find()->toArray();
        $userinfo['yesterday_earnings'] = model('Record')->yesterdayEarnings($id); 
        $userinfo['profitrate'] = model('Profitrate')->financePage(); 
        $userinfo['all_profit'] = model('Record')->allEarnings($id); 
        return $userinfo;
    }


    /**
     * 提现申请
     * @param string @uid [用户ID]
     * @param string @amount [提现金额]
     * @param string @payment_password [支付密码]
     */
    public function withdrawalsApply($id,$amount,$payment_password)
    {
        $config = model('Common/config')->getConfig();

        $withdrawalsinfo = db('withdrawals')->where(array('withdrawals_status'=> 0,'uid' => $id))->find();
        if($withdrawalsinfo){
            return ['status'=>0,'info'=>lang('application_not_pass')];
        }

        $start_time=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $end_time=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $withdrawals_map['create_time'] = array(array('egt', $start_time), array('elt', $end_time));
        $withdrawals_map['uid'] = $id;
        $withdrawals_sum = db('withdrawals')->where($withdrawals_map)->sum('amount');
        $withdrawals_money = $withdrawals_sum + $amount;
        if($config['WITHDRAWALS_MAX'] < $withdrawals_money){
            return ['status'=>0,'info'=>lang('bring_more')];
        }
        $userinfo = $this->infodata(array('id'=>$id));
        if(encrypt($payment_password) != $userinfo['payment_password']){
            return ['status'=>0,'info'=>lang('incorrect_password')];
        }
        if($amount < $config['WITHDRAWALS_MIN']){
            return ['status'=>0,'info'=>lang('cash_more').$config['WITHDRAWALS_MIN'].'$'];
        }
        if($userinfo['usd'] < $amount){
            return ['status'=>0,'info'=>lang('b')];
        }
        $month = getthemonth();
        $monthmap['uid'] = $id;
        $monthmap['create_time'] = array(array('egt', $month[0]), array('elt', $month[1])
            );
        $iswithdrawals = model('Withdrawals')->iswithdrawals($monthmap);
        if($iswithdrawals['status'] == 0){
            $feededuction = $amount;//本月未提现
            $map['service_charge'] = 0;
        }else{
            $feededuction = $amount * ($config['AMOUNT_OF_CASH'] + 1);//本月已提现
            $map['service_charge'] =$amount * $config['AMOUNT_OF_CASH'];
        }
        $usermap['usd'] = $userinfo['usd'] - $feededuction;
        if($usermap['usd'] < 0){
            return ['status'=>0,'info'=>lang('b')];
        }
        $map['uid'] = $id;
        $map['amount'] = $amount;
        $map['alipay_account'] = $userinfo['alipay_account'];
        $result = model('Withdrawals')->withdrawalsApply($map);
        if($result['status'] === 0) {
            $this->rtn(-1,$result['info']);
        }
        $fee = $this->where(array('id'=>$id))->update($usermap);
        if(false === $fee){
            return ['status'=>0,'info'=>$User->getError()];
        }else{
            return ['status'=>1,'info'=>lang('success')];
        }

    }

    /**
     * 获取转账页面信息
     * @param int @id [用户ID]
     * @return array
     */
    public function transferPage($id)
    {
        $userinfo = $this->where(array('id'=>$id))->field('id,usd,dfs,all_profit')->find()->toArray();
        return $userinfo;
    }

    /**
     * 转账功能
     * @param string $id [用户ID]
     * @param string $trader [转入用户ID]
     * @param string $money [转入DFS]
     * @return array
     */
    public function transfer($id,$trader,$money)
    {
        if(false == ($userinfo = $this->infodata(array('id'=>$id)))){
            return ['status'=>0,'info'=>lang('null')];
        }
        if($userinfo['account'] == $trader){
            return ['status'=>0,'info'=>lang('b')];
        }
        if($userinfo['dfs'] < $money){
            return ['status'=>0,'info'=>lang('c')];
        }
        if(false == ($traderinfo = $this->infodata(array('account'=>$trader)))){
            return ['status'=>0,'info'=>lang('d')];
        }
        $map['dfs'] = $userinfo['dfs'] - $money;
        if(false === $this->where(array('id'=>$id))->update($map)){
            return ['status'=>0,'info'=>lang('error')];
        }
        $trader_map['dfs'] = $traderinfo['dfs'] + $money;
        $this->where(array('account'=>$trader))->update($trader_map);
        $where['money'] = $money;
        $where['user_id'] = $id;
        $where['trader_id'] = $traderinfo['id'];
        $where['transaction_type'] = 6;
        model('Record')->add($where);
        return ['status'=>1,'info'=>lang('success')];
    }


    /**
     * 我的页面
     * @param string $id [用户ID]
     * @return array
     */
    public function userPage($id)
    {
        $infodata = $this->infodata(array('id'=>$id));
        return $infodata;
    }

    /**
     * 每天收益
     */
    public function dailyEarnings()
    {
        $list = $this->select()->toArray();
        db('Record')->where(array('status'=>1))->update(array('status'=>2));//上线删除，数据库也删除status字段 model record84行
        foreach ($list as $k => $v) {
            $earn = $v['dfs'] * config('DFS_EXCHANGE_RATE')* config('RATE_OF_RETURN');
            $map['usd'] = $v['usd']+$earn;
            $this->where(array('id'=>$v['id']))->update($map);
            if($earn != 0){
                $where['money'] = $earn;
                $where['user_id'] = $v['id'];
                $where['trader_id'] = $v['id'];
                $where['transaction_type'] = 3;
                model('Record')->saveInfo($where);
            }
            if($v['parent_id'] != 0){
                $list1 = $this->infodata(array('id'=>$v['parent_id']));
                $earn1 = $earn * config('FIRST_LEVEL');
                $map1['usd'] = $list1['usd']+$earn1;
                $this->where(array('id'=>$list1['id']))->update($map1);
                if($earn1 != 0){
                    $where1['money'] = $earn1;
                    $where1['user_id'] = $v['id'];
                    $where1['trader_id'] = $list1['id'];
                    $where1['transaction_type'] = 4;
                    model('Record')->saveInfo($where1);
                }
                if($list1['parent_id'] != 0){
                    $list2 = $this->infodata(array('id'=>$list1['parent_id']));
                    $earn2 = $earn * config('SECOND_LEVEL');
                    $map2['usd'] = $list2['usd']+$earn2;
                    $this->where(array('id'=>$list2['id']))->update($map2);
                    if($earn2 != 0){
                        $where2['money'] = $earn2;
                        $where2['user_id'] = $v['id'];
                        $where2['trader_id'] = $list2['id'];
                        $where2['transaction_type'] = 4;
                        model('Record')->saveInfo($where2);
                    }
                    if($list2['parent_id'] != 0){
                        $list3 = $this->infodata(array('id'=>$list2['parent_id']));
                        $earn3 = $earn * config('THIRD_LEVEL');
                        $map3['usd'] = $list3['usd']+$earn3;
                        $this->where(array('id'=>$list3['id']))->update($map3);
                        if($earn3 != 0){
                            $where3['money'] = $earn3;
                            $where3['user_id'] = $v['id'];
                            $where3['trader_id'] = $list3['id'];
                            $where3['transaction_type'] = 4;
                            model('Record')->saveInfo($where3);
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 判断是否填写手机号
     * @param string $id [用户ID]
     * @return array
     */
    public function isExistTel($id)
    {
        $infodata = $this->infodata(array('id'=>$id));
        if($infodata){
            if($infodata['tel']){
                return 1;
            }else{
                return 2;
            }
        }else{
            return false;
        }
    }


    /**
     * 验证老手机号发送验证码
     * @param string @uid [用户ID]
     * @param string @tel [手机号]
     * @param string @code [验证码]
     * @return array
     */
    public function checkOldTel($id,$tel,$code)
    {
        $infodata = $this->infodata(array('id'=>$id));
        if($infodata){
            if($infodata['tel'] == $tel){
                $servercode = Session::get('authcode');
                if($code != $servercode['code']){
                    return ['status'=>0,'info'=>lang('code_error')];
                }else{
                    if($tel == session('authcode.phone')){
                        return ['status'=>1,'info'=>lang('success')];
                    }else{
                        return ['status'=>0,'info'=>lang('phone_diffent')];
                    }
                }
            }else{
                return ['status'=>0,'info'=>lang('is_phone')];
            }
        }else{
            return ['status'=>0,'info'=>lang('is_phone')];
        }
    }

    /**
     * 修改新手机号码
     * @param string @uid [用户ID]
     * @param string @tel [手机号]
     * @param string @code [验证码]
     * @return array
     */
    public function saveTel($id,$tel,$code)
    { 
        $infodata = $this->infodata(array('id'=>$id));
        if($infodata['tel'] == $tel){
            return ['status'=>0,'info'=>lang('e')];
        }else{
            $servercode = Session::get('authcode');
            if($code != $servercode['code']){
                return ['status'=>0,'info'=>lang('phone_error')];
            }else{
                if($tel == session('authcode.phone')){
                    $map['id'] = $id;
                    $map['tel'] = $tel;
                    $this->saveInfo($map);
                    return ['status'=>1,'info'=>lang('success')];
                }else{
                    return ['status'=>0,'info'=>lang('phone_diffent')];
                }
            }
        }
    }

    /**
     * 修改用户一级密码
     * @param  string $uid     [用户ID]
     * @param  string $password     [用户原有密码]
     * @param  string $new_password [用户新密码]
     * @param string $type [修改密码类型：1修改一级密码，2修改二级密码]
     * @return mixed         [返回ture/false]
     */
    public function changefirstPwd($uid,$password,$new_password,$type)
    {   
        $encrypt_password = encrypt($password);
        if($type == 1){
            $pwd = 'password';
        }else{
            $pwd = 'payment_password';
            if($password == 'undefined'){
                $encrypt_password = null;
            }
        }
        $where = [
            'id' => $uid,
            $pwd => $encrypt_password,
        ];
        $data = [
            $pwd => encrypt($new_password),
            'update_time' => time(),
        ];
        if(false == $this->where($where)->update($data)){
            return false;
        }
        return true;
    }

    /**
     * 根据账号获取账户信息
     * @param string @account [账号]
     * @return array
     */
    public function checkUser($account)
    {
        return $this->where(array('account' => $account))->find();
    }

    /**
     * 根据查询条件获取账户信息
     * @param string $map [查询条件]
     * @return mixed
     */
    public function infodata($map)
    {
        $list = $this->where($map)->find();
        if (!is_null($list)) {
            return $list->toArray();
        }
        return false;
    }


    /**
     * 添加更新数据
     * @param array $data [数据]
     * @return mixed
     */
    public function saveInfo($data)
    {
        if($data['id']){
            $where = true;
        }else{
            $where = false;
        }
        $result = $this->isUpdate($where)->save($data);
        if ($result) {
            return ['status'=>1,'info'=>lang('success')];
        } else {
            return ['status'=>0,'info'=>lang('error')];
        }      
    }
    /**
     * 根据parent_id获取账户信息
     * @param string $id [账号]
     * @return array
     */
    public function userinfo($id)
    {
        return $this->where(array('parent_id' => $id))->field('id,username,account,parent_id,dfs')->select()->toArray();
    }

    /* 根据键名取键值 用于foreach */
    public function showKey()
    {
        $list = $this->field('id,username')->order('id desc')->select()->toArray();
        foreach ((array)$list as $k => $v) {
            $return[$v['id']] = $v['username'];
        }
        return $return;
    }



	/**
	 *
	 * 我的好友
	 * @param $user 当前用户、 仅查询当前用户的下一层用户信息，和下一层用户产生的业绩
	 * @return false|\PDOStatement|string|\think\Collection
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function my_friends($user)
	{
		$where['parent_id'] = $user['id'];
		$frineds = self::where($where)->field('id,account')->order('create_time desc')->select()->toArray();
		$count = null;
		foreach ($frineds as $s=>$p){
			$Money = new StoData();
			$money = $Money->alias('st')
				->join('user u','u.id = st.uid')
				->where(['uid'=>$p['id']])
				->order('st.id desc')
				->field('u.account,u.create_time,st.total_number,st.status,st.bonus')
				->select();
			foreach ($money as $k=>$v)	$count['total_bonus']+=$v['bonus'];
			$frineds[$s]['money'] = $money;
		}
		$count['friends_number'] = sizeof($frineds);
		return ['friends'=>$frineds,'count'=>$count];
	}

    /**
     * 退出登录、登出、删除token
     * @param $user
     * @return bool
     */
    public function logout($user)
    {
        if ($this->where(['id' => $user['id']])->update(['token' => null])) {
            return true;
        }
        return false;
    }


    /**
     * 获取用户头像昵称信息
     * @param $user
     * @return mixed
     * @throws \think\exception\DbException
     */
    static public function user_info($user)
    {
        $user_info = self::get(['id' => $user['id']]);
        unset($user);
        $user['id'] = $user_info['id'];
        $user['head_icon'] = $user_info['head_icon'];
        $user['nickname'] = $user_info['nickname'];
        return $user;

    }
//
//	/**
//	 * 邀请返佣
//	 * @param $user 用户信息
//	 * @return null
//	 * @throws \think\db\exception\DataNotFoundException
//	 * @throws \think\db\exception\ModelNotFoundException
//	 * @throws \think\exception\DbException
//	 */
//	public function reword($user)
//	{
//		$friend = $this->where(['parent_id'=>$user['id']])->select()->toArray();
//		$r = null;
//		$times = 0;
//		foreach ($friend as $k=>$v){
//			$Sto = new StoData();
//			$sto = $Sto->get_data($v['id']);
//			foreach ($sto as $kk=>$vv){
//				$r[$times] = $vv;
//				$times++;
//			}
//		}
//		return $r;
//	}

    /**
     * 增加用户总投资额
     * @param $id
     * @param $total_invest
     * @return array
     * @throws Exception
     */
    public function record_total_invest($id, $total_invest)
    {
        if (false === $this->where('id', $id)->setInc('total_invest', $total_invest)) {
            return ['code' => 0, 'msg' => '增加用户总投资额失败'];
        }
        return ['code' => 1];
    }


    /**
     * 用户vip等级变动
     * @param $user 用户对象
     * @param $level vip等级
     * @return bool
     */
    static public function user_grade($user, $level)
    {
        if($user['level'] < $level){
            $map['level'] = $level;
            $map['level_time'] = time();
            if($level == 2){
                $map['two_level_time'] = time();
            }
            if($level == 6){
                $map['six_level_time'] = time();
            }
            self::where(['id' => $user['id']])->update($map);
        }

        return true;
    }


}

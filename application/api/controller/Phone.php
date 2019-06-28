<?php

namespace app\api\controller;

use app\api\model\CountryCode;
use app\api\model\Language;
use app\api\model\UserConfig;
use app\common\controller\ApiBase;
use think\Controller;
use think\Session;
use think\Request;
use think\Db;
use think\Validate;
use app\api\model\User as UserModel;
use app\api\model\UserMessage;

/**
 * 首页信息登录注册页面
 * @remark 获取验证码（code）、用户注册（userReg）、用户登录页面信息（LoginPage）、用户登录（userLogin）、首页信息（homePage）
 * Class Phone
 * @package app\api\controller
 */
class Phone extends controller
{
    public function _initialize()
    {
        parent::_initialize();
        if (cookie('think_var') == 'zh-cn') {
            config('THINK_VAR', 'chs_');
        } elseif (cookie('think_var') == 'zh-ct') {
            config('THINK_VAR', 'cht_');
        } else {
            config('THINK_VAR', 'en_');
        }

    }

    /*
    * 切换语言
    * @param  string @lang [语言类型：cn中文，en英文,ct繁体]
    */
    public function lang()
    {
        $token = input('token');
        $uid = db('user')->where('token', $token)->value('id');
        switch (input('lang')) {
            case 'cn':
                cookie('think_var', 'zh-cn');
                db('user_config')->where('uid', $uid)->update(['language' => 1]);
                return rtn(1, '切换成功');
                break;
            case 'en':
                cookie('think_var', 'en-us');
                db('user_config')->where('uid', $uid)->update(['language' => 3]);
                return rtn(1, 'Handover success');
                break;
            case 'ct':
                cookie('think_var', 'zh-ct');
                db('user_config')->where('uid', $uid)->update(['language' => 2]);
                return rtn(1, '切換成功');
                break;
        }
    }

    /**
     * code 获取验证码
     * @param  string @account [账号]
     */
    public function code()
    {
        $account = input('post.account');
        if (!is_mobile($account)) {
            return rtn('-1', lang("is_phone"));
        } else {
            $code = generate_code(6);
            Session::set('authcode', ['code' => $code, 'account' => $account]);
            return rtn(0, lang("success"), session('authcode.code'));
        }
    }


    /**
     * 修改登录密码
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function change_password()
    {
        if (!$_POST['email']) {
            return rtn(0, 'os_error');
        }
        $email = $_POST['email'];
        $user = UserModel::get(['email' => $email]);
        if (!$user) {
            return rtn(0, 'cant found user info');
        }
        if (!($_POST['email_code'])) {
            return rtn(0, Language::lang('请输入邮箱验证码', $user));
        }
        if (!(($_POST['email'] == \session('email_code.email') && $_POST['email_code'] == \session('email_code.code')))) {
            return rtn(0, Language::lang('邮箱验证码不正确', $user));
        }
        if (!$_POST['password']) {
            return rtn(0, Language::lang('请输入密码', $user));
        }
        if (!$_POST['repassword']) {
            return rtn(0, Language::lang('请输入确认密码', $user));
        }
        $password = $_POST['password'];
        $repassword = $_POST['repassword'];
        if ($repassword != $password) {
            return rtn(0, Language::lang('两次密码不一致', $user));
        }
        $change_array = [
            'password' => $password,
        ];
        $UserChange = new User();
        $result = $UserChange->change_user_account($user, $change_array);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['info'], $user));
        }
    }

    /**
     * 用户注册
     * @param  string @email [账号]
     * @param  string @code [验证码]
     * @param  string @invitation_code [邀请码]
     * @param  string @password [密码]
     * @param  string @repassword [重复密码]
     * @param  string @payment_password [支付密码]
     * @param  string @repayment_password [重复支付密码]
     */
    public function userReg()
    {
        $data = input('post.');
        if (!$data['email'] || !$data['code'] || !$data['invitation_code'] || !$data['password'] || !$data['repassword'] || !$data['payment_password'] || !$data['repayment_password']) {
            return rtn(-1, lang("not_null"));
        } else {
            if (preg_match("/^\d*$/", $data['password']) || preg_match("/^[a-z]*$/i", $data['password'])) {
                return rtn(-1, lang('pwd_type'));
            }
            //验证规则
            $validate_rule['password'] = 'length:8,20|alphaNum';
            $validate_rule['payment_password'] = 'length:6|number';

            //验证提示
            $validate_msg['password.length'] = lang('pwd_type');
            $validate_msg['password.alphaNum'] = lang('pwd_type');
            $validate_msg['payment_password.length'] = lang('pay_pwd_type');
            $validate_msg['payment_password.number'] = lang('pay_pwd_type');
            $validate = new Validate($validate_rule, $validate_msg);

            //验证数据
            $validate_data['password'] = $data['password'];
            $validate_data['payment_password'] = $data['payment_password'];
            if (!$validate->check($validate_data)) {
                return rtn(-1, $validate->getError());
            }
            if ($data['code'] == session('email_code.code')) {
                if ($data['email'] == session('email_code.email')) {
                    if ($data['password'] == trim(input('repassword'))) {
                        if ($data['payment_password'] == trim(input('repayment_password'))) {
                            $return = model("User")->userReg($data);
                            if ($return['status'] === 0) {
                                return rtn(0, $return['info']);
                            } else {
                                Session::delete('email_code');
                                $this->user_money($return['id']);
                                $this->user_money_profit($return['id']);

                                return rtn(1, lang('success'));
                            }
                        } else {
                            return rtn(0, lang("pwd_diffent"));
                        }
                    } else {
                        return rtn(0, lang("pwd_diffent"));
                    }
                } else {
                    return rtn(0, lang("phone_diffent"));
                }
            } else {
                return rtn(0, lang("code_error"));
            }
        }
    }


    /**
     * 用户登录
     * @param string @email [账号]
     * @param string @password [密码]
     * @param string @language [账户语言类型]
     */
    public function userLogin()
    {
        $email = trim(input('email'));
        $password = trim(input('password'));
        $language = trim(input('language'));
        $email_code = null;
        /*验证语言*/
        if (!$language || !in_array($_POST['language'], ['1', '2', '3'])) {
            return rtn(0, "language cant be null");
        }
        switch ($language) {
            case '1':
                cookie('think_var', 'zh-cn');
                break;
            case '2':
                cookie('think_var', 'en-us');
                break;
            case '3':
                cookie('think_var', 'zh-ct');
                break;
        }
        $user = UserModel::get(['email' => $_POST['email']]);
        if (!$user) {
            return rtn(0, Language::no_login("用户不存在", $_POST['language']));
        }
        if ($user['pwd_times'] == 5 && time() < $user['time']) {
            //$timess =  $user['time'];
            //$dates  =  date("Y-m-d H:i:s",$timess);
            if ($user['count_times'] == 25) {
                $dates = '24小时';
            } else {
                $dates = '30分钟';
            }
            $return = Language::no_login("密码多次错误,帐号冻结", $_POST['language']);
            return rtn(0, $return . $dates);
        }
        $user_config = UserConfig::config($user);
        if ($user_config['login_email'] == 1) {
            if (!$_POST['email_code']) {
                return rtn(2, Language::lang("请进行邮箱验证", $user));
            }
//            if (isset($_POST['email_code'])) {
//                $email_code = $_POST['email_code'];
//            }
            if (!\session("email_code.code") == $_POST['email_code']) {
                return rtn(0, Language::lang("邮箱验证码不正确", $user));
            }
        }
        if (!$password) {
            return rtn(-1, Language::no_login("请输入登录密码", $_POST['language']));
        } else {
            $User = new UserModel();
            $return = $User->login($email, $password, $language, $email_code = $_POST['email_code'] ? $_POST['email_code'] : null);
            if ($return['status'] === 0) {
                return rtn(0, $return['info']);
            } else if ($return['status'] == 2) {
                return rtn(2, 'email_check');
            } else {
                $edit['pwd_times'] = 0;
                $edit['path_times'] = 0;
                $edit['count_times'] = 0;
                $edit['time'] = 0;
                $User->where('email', $email)->update($edit);
                return rtn(1, lang("success"), $return['info']);
            }
        }
    }

    //拼图
    public function paths()
    {
        $token = trim(input('token'));//token
        $type = trim(input('type'));//状态 成功随便传 失败传2
        $language = trim(input('language'));//language
        $user = UserModel::get(['token' => $token]);
        //次数为3 并且 当前时间小于冻结时间
        if (!$token || !$type) {
            return rtn(0, Language::no_login("不能为空", $language));
        }
        if ($user['path_times'] == 5 && time() < $user['time']) {
            //$timess =  $user['time'];
            //$dates  =  date("Y-m-d H:i:s",$timess);
            if ($user['pic_times'] == 25) {
                $dates = '24小时';
            } else {
                $dates = '30分钟';
            }
            $return = Language::no_login("拼图多次错误,帐号冻结", $_POST['language']);
            return rtn(0, $return . $dates);
        }
        // 验证拼图
        if ($type == 2) {
            UserModel::where('token', $token)->setInc('path_times', 1);//次数自增1
            UserModel::where('token', $token)->setInc('pic_times', 1);//次数自增1
            if ($user['path_times'] == 4 || $user['path_times'] == 9 || $user['path_times'] == 14 || $user['path_times'] == 19 || $user['path_times'] == 24) {//次数为4 9 14 19 24
                $time = time() + 1800;//存时间 当前+半小时(30*60 = 1800)
                UserModel::where('token', $token)->update(['time' => $time]);//修改时间
                $return = Language::no_login("拼图多次错误,帐号冻结", $_POST['language']);
                $dates = '30分钟';
                return rtn(0, $return . $dates);
            }
            if ($user['pic_times'] == 24) {//次数为2
                $time = time() + 86400;//存时间 当前+半小时(24*60*60 = 86400)
                UserModel::where('token', $token)->update(['time' => $time]);//修改时间
                $return = Language::no_login("拼图多次错误,帐号冻结", $_POST['language']);
                $dates = '24小时';
                return rtn(0, $return . $dates);
            }
            return rtn(0, Language::no_login("拼图错误", $language));
        } else {
            $edit['pwd_times'] = 0;
            $edit['path_times'] = 0;
            $edit['pic_times'] = 0;
            $edit['time'] = 0;
            UserModel::where('token', $token)->update($edit);

            // 记录到"通知中心"
            // $UserMessage = new UserMessage();
            // $info['title'] = '用户登陆';
            // $info['first_content'] = '您在';
            // $info['second_content'] = '成功登陆,请注意账户安全。';
            // $data = [
            //     'user_info' => $user,
            //     'data' => $info,
            // ];
            // $message_result = $UserMessage -> create_user_message($data);
            // if($message_result['code'] === 0){
            //     return rtn(0, Language::lang($message_result['msg'], $this->userInfo));
            // }

            return rtn(1, Language::no_login("成功", $_POST['language']));
        }
    }

    /**
     * 忘记密码
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function forgot_password()
    {
        /*验证数据完整性*/
        if (!$_POST['language']) {
            return rtn(0, "language cant be null!");
        }
        $param = [
            'email_code' => "请输入邮箱验证码",
            'email' => "请输入邮箱",
            'password' => "请输入新密码",
            're_password' => "请输入确认密码",
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::no_login($result['param'], $_POST['language']));
        }

        /*验证用户邮箱是否存在*/

        $user = UserModel::get(['email' => $_POST['email']]);
        if (!$user) {
            return rtn(0, Language::no_login("邮箱不存在", $_POST['language']));
        }

        /*验证密码一致*/
        if ($_POST['password'] != $_POST['re_password']) {
            return rtn(0, Language::no_login("两次密码不一致", $_POST['language']));
        }


        /*验证邮箱验证码*/
        if (!\session('email_code')) {
            return rtn(0, Language::no_login("请输入邮箱进行验证", $_POST['language']));
        }
        if (!(\session('email_code.email') == $_POST['email']) && (\session("email_code.code") == $_POST['email_code'])) {
            return rtn(0, Language::no_login("邮箱验证码不匹配", $_POST['language']));
        }

        /*新旧密码*/
        $pwd = encrypt($_POST['password']);
        if ($user->password == $pwd) {
            return rtn(0, Language::no_login("新密码不能与旧密码相同", $_POST['language']));
        }

        /*修改密码*/
        Db::name('user')->where('id', $user['id'])->update(['password' => $pwd]);
        return rtn(1, Language::no_login("请牢记您的新密码", $_POST['language']));
    }

    /**
     * 邮箱发送验证码、未登录
     * @param null $email
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function email_code($email = null)
    {
        if (!($_POST['email'] || $email)) {
            return rtn(0, Language::lang('请输入邮箱', $this->userInfo));
        }
//        验证邮箱格式
        if (is_email($_POST['email']) === false) {
            return rtn(0, Language::lang('邮箱格式不正确', $this->userInfo));
        }
//        生成验证码
        $code = generate_code(6);
        Session::set('email_code', ['email' => $_POST['email'], 'code' => $code]);
        return rtn(1, 'success', $code);
    }

    /**
     * 登录后发送邮箱验证码
     * @param $user
     * @return array|false|string
     * @throws \think\exception\DbException
     */
    public function email_code_login($user)
    {
        if (!$user) {
            return ['status' => 0, 'info' => 'os_error'];
        }
        $email = UserModel::get(['id' => $user['id']]);
        $email = $email['email'];
        if (!$email) {
            return ['status' => 0, 'info' => '用户邮箱不存在！'];
        }
        $code = generate_code(6);
        Session::set('email_code', ['email' => $email, 'code' => $code]);
        return rtn(1, 'os_success');
    }

    /**
     * 加用户钱包数据
     * @param $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_money($id)
    {
        $ins['uid'] = $id;
        $ins['total'] = 0;
        $ins['amount'] = 0;
        $ins['create_time'] = time();
        $ins['update_time'] = time();
        $cur = db('currency')->select();
        foreach ($cur as $k => $v) {
            $ins['cur_id'] = $v['id'];
            $ins['address'] = '0x' . get_hash();
            db('user_money')->insert($ins);
        }
    }

    /**
     * 加用户钱包数据2
     * @param $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_money_profit($id)
    {
        $ins['uid'] = $id;
        $ins['total'] = 0;
        $ins['amount'] = 0;
        $ins['create_time'] = time();
        $ins['update_time'] = time();
        $cur = db('currency')->select();
        foreach ($cur as $k => $v) {
            $ins['cur_id'] = $v['id'];
            $ins['address'] = '0x' . get_hash();
            db('user_money_profit')->insert($ins);
        }
    }


    /**
     * 获取国家电话号码前缀
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function country_pre()
    {
        $param = [
            'language' => "语言选择错误"
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, "please choose your language");
        }

        $country_pre = CountryCode::all();
        $country_pre_add = null;
        foreach ($country_pre as $k => $v) {
            $country_pre_add[$k]['ph_pre_id'] = $v['id'];
            $country_pre_add[$k]['pre_code'] = "+" . $v['pre_code'];
        }
        return rtn(1, Language::no_login("成功", $_POST['language']), $country_pre_add);


    }

    //首页数据（中奖信息、轮播图、公告）
    public function homePage()
    {
        $request = Request::instance();
        $data = [];
        $data['winning'] = '';
        $banner = db('banner')->where('status', 1)->order('sort asc')->field(config('THINK_VAR') . 'url')->select();
        foreach ($banner as $k => $v) {
            $data['banner'][$k]['url'] = $request->domain() . "/btsq/public" . $v[config('THINK_VAR') . 'url'];
        }
        $news = db('news')->order('create_time desc')->field(config('THINK_VAR') . 'title')->limit(3)->select();
        foreach ($news as $k => $v) {
            $data['news'][$k]['title'] = $v[config('THINK_VAR') . 'title'];
        }
        return rtn(1, lang('success'), $data);
    }

    public function bps()
    {
        $request = Request::instance();
        $name = input('type') == 1 ? 'en' : 'zh';
        $url = $request->domain() . "/btsq/public" . '/upload/' . $name . '.png';
        return rtn(1, lang('success'), $url);
    }

    public function img_code()
    {
        $request = Request::instance();
        $id = rand(0, 20);
        echo $request->domain() . "/btsq/public" . '/upload/code/' . $id . '.png';
    }
}
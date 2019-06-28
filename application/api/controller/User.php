<?php

namespace app\api\Controller;

use app\api\model\AutoWithdraw;
use app\api\model\BonusList;
use app\api\model\Config;
use app\api\model\ContributionHis;
use app\api\model\CountryCode;
use app\api\model\Feedback;
use app\api\model\GuessAccount;
use app\api\model\GuessOrder;
use app\api\model\GuessRecode;
use app\api\model\GuessConfig;
use app\api\model\Language;
use app\api\model\Order;
use app\api\model\Trade;
use app\api\model\UserAuth;
use app\api\model\UserBank;
use app\api\model\UserConfig;
use app\api\model\UserContribution;
use app\api\model\UserCur;
use app\api\model\User as UserModel;
use app\api\model\UserCoin;
use app\api\model\UserMessage;

use app\api\controller\Phone;

use app\api\model\UserMining;
use app\api\model\UserMoney;
use app\api\model\UserPay;
use app\common\controller\ApiBase;


use app\api\model\Bank as BankModel;

use think\Validate;
use think\Exception;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;

/**
 * 我的页面
 *
 * @remark
 */
class User extends ApiBase
{
    public function _initialize()
    {
//        listen_sql();
        parent::_initialize();
    }

    /**
     * 我的
     * @param string @uid [用户ID]
     */
    public function userPage()
    {
        $id = trim(input('uid'));
        if (!$id) {
            $r = $this->rtn(-1, lang("parameter"));
        } else {
            $map = ['id'] == $id;
            if (false == ($data = model('User')->userPage($id))) {
                $r = $this->rtn(-1, lang("null"));
            } else {
                $r = $this->rtn(0, lang("success"), $data);
            }
        }
        return json($r);
    }


    /**
     * 获取用户配置信息
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function user_config()
    {
        $user_config = UserConfig::config($this->userInfo);
        if (!$user_config) {
            return rtn(0, Language::lang("用户配置信息不存在", $this->userInfo));
        }
        return rtn(1, "os_success", $user_config);
    }


    /**
     * 修改邮箱验证
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function change_config()
    {
        $param = [
            'type' => '配置选项不正确'
        ];
        $result = ApiBase::check_post($_POST, $param);
        if (($result['status'] != 1) || (!in_array($_POST['type'], ['1', '2', '3']))) {
            return rtn(0, Language::lang("配置选项不正确", $this->userInfo));
        }
        $array = [];
        $user_config = UserConfig::config($this->userInfo);
        if ($_POST['type'] == 1) {
            $array = [
                'login_email' => ($user_config['login_email'] + 1) % 2,
            ];
        } else if ($_POST['type'] == 2) {
            $array = [
                'transfer' => ($user_config['transfer'] + 1) % 2,
            ];
        } else {
            $array = [
                'change_pay_pwd' => ($user_config['change_pay_pwd'] + 1) % 2,
            ];
        }
        $UserConfig = new UserConfig();
        if (!($UserConfig->change($this->userInfo, $array))) {
            return rtn(0, Language::lang("修改用户配置失败", $this->userInfo));
        }
        return rtn(1, Language::lang("修改用户配置成功", $this->userInfo));

    }

    /**
     * 判断是否填写手机号
     * @param string @uid [用户ID]
     */
    public function isExistTel()
    {
        $Phone['phone_pre'] = $this->userInfo['phone_pre'];
        $Phone['phone'] = $this->userInfo['phone'];
        $Phone['is_exist'] = $Phone['phone'] ? 1 : 0;
        return rtn(1, Language::lang("成功", $this->userInfo), $Phone);
    }


    /**
     * 修改支付宝
     * @param string $uid [用户ID]
     * @param string $alipay_accout [支付宝账号]
     */
    public function editAlipay()
    {
        $id = trim(input('uid'));
        $alipay_accout = trim(input('alipay_accout'));

        if (!$id) {
            $r = $this->rtn(-1, lang("parameter"));
        } else if (!$alipay_accout) {
            $r = $this->rtn(-1, lang("cont_empty"));
        } else {
            $map['id'] = $id;
            $map['alipay_accout'] = $alipay_accout;
            $result = model('User')->saveInfo($map);
            if ($result['status'] == 0) {
                $r = $this->rtn(-1, lang("error"));
            } else {
                $r = $this->rtn(0, lang("success"));
            }
        }
        return json($r);
    }

    /**
     * 绑定银行卡
     * @param string $uid [用户ID]
     * @param string $bank_user [开户名]
     * @param string $bank [开户银行]
     * @param string $branch_bank [支行名称]
     * @param string $bank_number [银行卡号]
     */
    public function editBank()
    {
        $id = trim(input('uid'));
        $bank_user = trim(input('bank_user'));
        $bank = trim(input('bank'));
        $branch_bank = trim(input('branch_bank'));
        $bank_number = trim(input('bank_number'));
        if (!$id) {
            $r = $this->rtn(-1, lang("parameter"));
        } else if (!$bank_user) {
            $r = $this->rtn(-1, lang("cont_empty"));
        } else if (!$bank) {
            $r = $this->rtn(-1, lang("cont_empty"));
        } else if (!$branch_bank) {
            $r = $this->rtn(-1, lang("cont_empty"));
        } else if (!$bank_number) {
            $r = $this->rtn(-1, lang("cont_empty"));
        } else {
            $map['id'] = $id;
            $map['bank_user'] = $bank_user;
            $map['bank'] = $bank;
            $map['branch_bank'] = $branch_bank;
            $map['bank_number'] = $bank_number;
            $result = model('User')->saveInfo($map);
            if ($result['status'] == 0) {
                $r = $this->rtn(-1, lang("error"));
            } else {
                $r = $this->rtn(0, lang("success"));
            }
        }
        return json($r);
    }


    /**
     * @param $type 1、问题反馈上传图片  2 上传微信图片   3   上传支付宝图片    4   待加
     * 上传图片 ,将url返回给前台
     * @return \think\response\Json
     */
    public function upload_pic()
    {
        $request = Request::instance();
        $pic_type = null;
        if (!$_POST['type']) {
            $ret = ['code' => 0, 'msg' => Language::lang('未上传图片', $this->userInfo)];
        }
        if ($_POST['type'] == 1) {
            $pic_type = 'feedback/';
        } else if ($_POST['type'] == 2) {
            $pic_type = 'wechat/';
        } else if ($_POST['type'] == 3) {
            $pic_type = 'alipay/';
        } else if ($_POST['type'] = 4) {
            $pic_type = 'head_icon/';
        }
        $file = request()->file('file');
        if ($file) {
//            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload/' . $pic_type, true, true, 2);
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload/' . $pic_type, true, true, 2);
            if ($info) {
                $link = '/upload/' . $pic_type . $info->getSaveName();
                $display_url = $request->domain()."/btsq/public" . $link;
                $ret = ['code' => 1, 'msg' => Language::lang('图片上传成功', $this->userInfo), 'url' => $link, 'display_url' => $display_url];
            } else {
                $ret = ['code' => 0, 'msg' => $file->getError()];
            }
        } else {
            $ret = ['code' => 0, 'msg' => Language::lang('图片未上传!', $this->userInfo)];
        }
        return json($ret);
    }


    /**
     * 我的分享
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function invitation()
    {
        $request = Request::instance();
        $web_url = Config::get(['key' => 'INVITATION_WEB']);
        $user = $this->userInfo;
        $user_minig = UserMining::where(['uid' => $user['id']])->count('id');
        $Invitation['nickname'] = $user['nickname'];
//        判断用户头像是否存在，不存在则使用默认头像。
        $Invitation['head_icon'] = $this->userInfo['head_icon'] ? $request->domain()."/btsq/public" . $this->userInfo['head_icon'] : $request->domain()."/btsq/public" . '/static/ace/images/head.png';
        $Invitation['mining_count'] = $user_minig;
        $Invitation['invitation_web'] = $web_url['value'];
        $Invitation['inv_code'] = $user['invitation_code'];
        return rtn(1, lang('os_success'), $Invitation);
    }

    /**
     * 邀请返佣
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function invitation_reword()
    {
        $BonusList = new BonusList();
        $reword = $BonusList->reword($this->userInfo);
        return rtn(1, lang('os_success'), $reword);
    }


    /**
     * 获取用户交易订单
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_trade_order()
    {
        if (!$_POST['status']) {
            return rtn(0, lang('not_null'));
        }
        $Trade = new Trade();
        $trade = $Trade->get_trade($this->userInfo, $_POST['status']);
        return rtn(1, lang('os_success'), $trade);
    }


    /**
     * 返回“我的”页面 用户信息
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function user_head_info()
    {
        $request = Request::instance();
        $user_info['user_info'] = UserMoney::where(['uid' => $this->userInfo['id'], 'cur_id' => 1])->select();
        $user_money = $user_info['user_info'];
        unset($user_info['user_info']);
        $user_total = null;
        foreach ($user_money as $k => $v) {
            $user_total += $v['total'];
        }
        $user_info['total'] = sprintf('%.2f', $user_total);  // 总账户

        /**
         * 待
         */
        $income = db('user_money_profit')->where('uid', $this->userInfo['id'])->sum('amount');
        $user_info['income'] = $income ? $income : 0;   // 收益账户


        $user_contribution = UserContribution::get(['uid' => $this->userInfo['id']]); // 去掉 toArray()
        $user_contribution = $user_contribution['contribution'];
        $user_info['contribution'] = $user_contribution ? floatval($user_contribution) : 50;
        $user_info['reword'] = $this->userInfo['total_reword'];
        $user_info['invest'] = $this->userInfo['total_invest'];
        $user_info['user_info']['id'] = $this->userInfo['id'];
        $user_info['user_info']['name'] = $this->userInfo['nickname'];
        $user_info['user_info']['level'] = $this->userInfo['level'];
        if ($this->userInfo['head_icon']) {
//            头像存在
            $user_info['user_info']['head_icon'] = $request->domain()."/btsq/public" . $this->userInfo['head_icon'];
        } else {
//            头像不存在使用默认头像
            $user_info['user_info']['head_icon'] = $request->domain()."/btsq/public" . '/static/ace/images/head.png';
        }
        return rtn(1, lang('success'), $user_info);
    }

    /**
     * 打开安全中心
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function come_in_sec()
    {
        $user['phone'] = $this->userInfo['phone'];
        $user['email'] = $this->userInfo['email'];
        $user_config = UserConfig::config($this->userInfo);
        $user['login_email'] = $user_config['login_email'];
        $user['transfer'] = $user_config['transfer'];
        $user['change_pay_pwd'] = $user_config['change_pay_pwd'];
        return rtn(1, $user);
    }


    /**
     * 发送短信验证码
     */
    public function send_phone_code()
    {


        if (!$_POST['phone']) {
            return rtn(0, Language::lang("请输入手机号", $this->userInfo));
        }
//        验证手机号规则
        if (is_mobile($_POST['phone']) === false) {
            return rtn(0, Language::lang("请输入正确的手机号", $this->userInfo));
        }
        $code = generate_code(6);
        Session::set('phone_code', ['code' => $code, 'phone' => $_POST['phone']]);

//       调用发送短信接口
        /**
         * 待
         */


        return rtn(1, Language::lang("短信发送成功", $this->userInfo), $_SESSION);
    }

    /**
     * 绑定用户手机号
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function bind_phone()
    {

        /*验证参数完整性*/
        $param = [
            'phone' => "请输入手机号",
            'code' => "请输入手机验证码",
            'pre_code_id' => "国家前缀不正确",
            'pre_code' => "国家前缀不正确"
        ];
        $check_post = ApiBase::check_post($_POST, $param);
        if ($check_post['status'] != 1) {
            return rtn(0, Language::lang($check_post['param'], $this->userInfo));
        }

        /*验证前缀*/

        $check_post = CountryCode::get(['pre_code' => $_POST['pre_code'], 'id' => $_POST['pre_code_id']]);
        if (!$check_post) {
            return rtn(0, Language::lang("国家前缀不正确", $this->userInfo));
        }
        if ($_POST['code'] != session('phone_code.code')) {
            return rtn(0, lang('phone_error'));
        }
        if ($_POST['phone'] != session('phone_code.phone')) {
            return rtn(0, lang('phone_diffent'));
        }
        $phone_number = db('user')->where('phone', $_POST['phone'])->count();
        if ($phone_number > 4) {
            return rtn(0, Language::lang("改手机号绑定数量已超出", $this->userInfo));
        }
//        修改
        $phone = $_POST['phone'];
        $change = [
            'phone_pre' => $_POST['pre_code'],
            'phone' => $phone,
        ];
        $User = new UserModel();
        $result = $User->change_user_account($this->userInfo, $change);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang("用户信息修改失败", $this->userInfo));
        }
        return rtn(1, Language::lang("用户信息修改成功", $this->userInfo));

    }


    /**
     * 新手机验证码
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function new_phone_code()
    {
        if (!$_POST['new_phone']) {
            return rtn(0, Language::lang("请输入手机号", $this->userInfo));
        }
//        验证手机号规则
        if (is_mobile($_POST['new_phone']) === false) {
            return rtn(0, Language::lang("请输入正确的手机号", $this->userInfo));
        }
        $code = generate_code(6);
        Session::set('new_phone_code', ['code' => $code, 'phone' => $_POST['new_phone']]);

//       调用发送短信接口
        /**
         * 待
         */

        return rtn(1, Language::lang("短信发送成功", $this->userInfo), $_SESSION);
    }

    public function change_phone()
    {
        $param = [
            'old_phone' => "请输入原手机号",
            'old_phone_code' => "请输入原手机号的验证码",
            'new_phone' => "请输入新手机号",
            'new_phone_code' => "请输入新手机号的验证码",
        ];
        if (!input('pre_code_id') || !input('pre_code')) {
            return rtn(0, lang('not_null'));
        }
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }

        /*验证手机号*/
        if (!is_mobile($_POST['new_phone'])) {
            return rtn(0, Language::lang("手机号格式不正确", $this->userInfo));
        }

        /*验证原手机验证码*/
        if ((\session('phone_code.code') != $_POST['old_phone_code']) || (\session('phone_code.phone') != $_POST['old_phone'])) {
            return rtn(0, Language::lang("原手机号与验证码不匹配", $this->userInfo));
        }


        /*验证新验证码*/
        if ((\session('new_phone_code.code') != $_POST['new_phone_code']) || (\session('new_phone_code.phone') !=
                $_POST['new_phone'])) {
            return rtn(0, Language::lang("新手机号与验证码不匹配", $this->userInfo));
        }
        if ($_POST['old_phone'] != $this->userInfo['phone']) {
            return rtn(0, lang('phone_notown'));
        }
        $phone_number = db('user')->where('phone', input('new_phone'))->count();
        if ($phone_number > 4) {
            return rtn(0, Language::lang("改手机号绑定数量已超出", $this->userInfo));
        }
        $update_data['phone'] = input('new_phone');
        $update_data['phone_pre'] = input('pre_code');
        db('user')->where('id', $this->userInfo['id'])->update($update_data);
        Session::delete('phone_code');
        Session::delete('new_phone_code');
        return rtn(1, lang('success'));
    }

    /**
     * 修改手机号--邮箱交易密码验证--提交
     * @param  email
     * @param  payment_password
     * @param  new_phone
     * @param  new_phone_code
     * @return false|string
     */
    public function change_phone_email()
    {
        $data = input('post.');
        if (!$data['email'] || !$data['payment_password'] || !$data['new_phone'] || !$data['new_phone_code'] || !$data['pre_code_id'] || !$data['pre_code']) {
            return rtn(0, lang('not_null'));
        } else {
            if ($data['email'] != $this->userInfo['email'] || encrypt($data['payment_password']) != $this->userInfo['payment_password']) {
                return rtn(0, lang('msg_error'));
            }

            /*验证新验证码*/
            if ((\session('new_phone_code.code') != $_POST['new_phone_code']) || (\session('new_phone_code.phone') !=
                    $_POST['new_phone'])) {
                return rtn(0, Language::lang("新手机号与验证码不匹配", $this->userInfo));
            }
            $update_data['phone'] = input('new_phone');
            $update_data['phone_pre'] = input('pre_code');
            db('user')->where('id', $this->userInfo['id'])->update($update_data);
            Session::delete('new_phone_code');
            return rtn(0, lang('success'));
        }
    }

    /**
     * 已登陆的用户发送邮箱验证码
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function user_send_email()
    {
        $user_email = $this->userInfo['email'];
        $Email = new Phone();
        $Email->email_code_login($this->userInfo);

        /**
         * 待删除
         */

        $data = [
            'email' => \session("email_code.email"),
            'code' => \session("email_code.code"),
        ];


        /**
         * ********************
         */


        return rtn(1, Language::lang("邮箱验证码已发送", $this->userInfo), $data);
    }


    /**
     * 验证邮箱验证码
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function check_email_code()
    {
        $param = [
            'code' => "请输入邮箱验证码",
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
        if ($_POST['code'] != \session('email_code.code')) {
            return rtn(0, Language::lang("邮箱验证码不正确", $this->userInfo));
        }
        return rtn(1, Language::lang("成功", $this->userInfo));
    }

    /**
     * 修改邮箱手机验证码
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function edit_email_code()
    {
        $phone = $this->userInfo['phone'];
        if (!$phone) {
            return rtn(0, lang('nobinding_phone'));
        }
        $code = generate_code(6);
        Session::set('phone_code', ['code' => $code, 'phone' => $phone]);

//       调用发送短信接口
        /**
         * 待
         */

        return rtn(1, Language::lang("短信发送成功", $this->userInfo), $_SESSION);
    }

    /**
     * 修改用户邮箱
     */
    public function change_email()
    {
        if (!$_POST['email']) {
            return rtn(0, Language::lang("请输入邮箱", $this->userInfo));
        }
        if (!$_POST['code']) {
            return rtn(0, Language::lang("请输入手机验证码", $this->userInfo));
        }
        if ($_POST['code'] != \session('phone_code.code')) {
            return rtn(0, Language::lang("短信验证码不正确", $this->userInfo));
        }


//        邮箱规则验证
        if (is_email($_POST['email']) === false) {
            return rtn(0, Language::lang('邮箱格式不正确', $this->userInfo));
        }

//        修改
        $array = [
            'email' => $_POST['email']
        ];
        $User = new UserModel();
        $result = $User->change_user_account($this->userInfo, $array);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang("用户信息修改失败", $this->userInfo));
        }
        Session::delete('phone_code');
        return rtn(1, Language::lang("用户信息修改成功", $this->userInfo));
    }


    /**
     * 修改用户密码
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function change_password()
    {
        if (!$_POST['old_password']) {
            return rtn(0, Language::lang("请输入原密码", $this->userInfo));
        }
        if (!$_POST['new_password']) {
            return rtn(0, Language::lang("请输入新密码", $this->userInfo));
        }
        if (!$_POST['re_new_password']) {
            return rtn(0, Language::lang("请确认新密码", $this->userInfo));
        }
        if (encrypt($_POST['old_password']) != $this->userInfo['password']) {
            return rtn(0, Language::lang("原密码不正确", $this->userInfo));
        }
        if ($_POST['new_password'] != $_POST['re_new_password']) {
            return rtn(0, Language::lang("两次密码不一致", $this->userInfo));
        }
//        验证密码规则
        if (check_pwd($_POST['new_password']) === false) {
            return rtn(0, Language::lang("密码需为8~20位的字母数字", $this->userInfo));
        }

        $array = [
            'password' => $_POST['new_password'],
        ];
        $User = new UserModel();
        $result = $User->change_user_account($this->userInfo, $array);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang("密码修改失败", $this->userInfo));
        }

        // 记录到"通知中心"
        $UserMessage = new UserMessage();
        $info['title'] = '修改登陆密码';
        $info['first_content'] = '您在';
        $info['second_content'] = '修改登陆密码,请注意登陆安全。';
        $data = [
            'user_info' => $this->userInfo,
            'data' => $info,
        ];
        $message_result = $UserMessage->create_user_message($data);
        if ($message_result['code'] === 0) {
            return rtn(0, Language::lang($message_result['msg'], $this->userInfo));
        }

        return rtn(1, Language::lang("密码修改成功", $this->userInfo));
    }


    /**
     * 修改用户支付密码
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function change_pay_password()
    {
//        验证参数完整性
        $param = [
            'old_password' => "请输入原密码",
            'new_password' => "请输入新密码",
            're_new_password' => "请确认新密码",
            'email_code' => "请输入邮箱验证码",
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
//        验证原始密码
        if (encrypt($_POST['old_password']) != $this->userInfo['payment_password']) {
            return rtn(0, Language::lang("原支付密码不正确", $this->userInfo));
        }
//        验证两次输入密码是否相同
        if ($_POST['new_password'] != $_POST['re_new_password']) {
            return rtn(0, Language::lang("两次密码不一致", $this->userInfo));
        }
//        验证密码规则
        if (check_pwd($_POST['new_password']) === false) {
            return rtn(0, Language::lang("密码需为8~20位的字母数字", $this->userInfo));
        }
        if ($_POST['email_code'] != session('email_code.code')) {
            return rtn(0, Language::lang("邮箱验证码不正确", $this->userInfo));
        }
//        修改密码
        $array = [
            'payment_password' => $_POST['new_password'],
        ];
        $User = new UserModel();
        $result = $User->change_user_account($this->userInfo, $array);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang("用户信息修改失败", $this->userInfo));
        }
        Session::delete('email_code');

        // 记录到"通知中心"
        $UserMessage = new UserMessage();
        $info['title'] = '修改支付密码';
        $info['first_content'] = '您在';
        $info['second_content'] = '修改支付密码,请注意资金安全。';
        $data = [
            'user_info' => $this->userInfo,
            'data' => $info,
        ];
        $message_result = $UserMessage->create_user_message($data);
        if ($message_result['code'] === 0) {
            return rtn(0, Language::lang($message_result['msg'], $this->userInfo));
        }

        return rtn(1, Language::lang("用户信息修改成功", $this->userInfo));
    }


    /**
     * 退出登录
     * @return false|string
     */
    public function logout()
    {
        $User = new UserModel();
        if ($User->logout($this->userInfo)) {
            return rtn(1, lang('logout'));
        } else {
            return rtn(-1, lang('os_error'));
        }

    }

    /**
     * 联系客服问题分类
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function feedbackType()
    {
        $feedbackType = db('feedback_type')->select();
        $list = [];
        foreach ($feedbackType as $k => $v) {
            $list[$k]['type'] = $v['feedback_type'];
            $list[$k]['name'] = $v[config('THINK_VAR') . 'name'];
        }
        return rtn(1, lang('success'), $list);
    }

    /**
     * 联系客服
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function feedback()
    {
        $data = input('post.');
        if (!$data['type'] || !$data['content']) {
            return rtn(0, lang('not_null'));
        } else {
            $Feedback = new Feedback();
            if (!$Feedback->save_feedback($_POST, $this->userInfo)) {
                return rtn(1, Language::lang("问题反馈失败", $this->userInfo));
            }
            return rtn(1, Language::lang("已收到问题反馈", $this->userInfo));
        }
    }

    /**
     * 用户反馈--我的消息-- 列表
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function get_feedback()
    {
        $p = input('p') ? input('p') : 1;
        $page_size = input('page_size') ? input('page_size') : 20;
        $list = db('feedback')->where(['uid' => $this->userInfo['id']])->order('create_time desc')->field('id,f_type,create_time,status')->page($p, $page_size)->select();
        $feedbackType = db('feedback_type')->column('feedback_type,' . config('THINK_VAR') . 'name');
        foreach ($list as $k => $v) {
            $list[$k]['status'] = Language::lang($v['status'] == 1 ? "已回复" : "未回复", $this->userInfo);
            $list[$k]['f_type'] = $feedbackType[$v['f_type']];
            $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        return rtn(1, lang('success'), $list);
    }

    /**
     * 获取一条反馈
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function one_feedback()
    {
        if (!$_POST['id']) {
            return rtn(0, Language::lang("暂无用户反馈信息", $this->userInfo));
        }
        $feedback = Feedback::get(['id' => $_POST['id'], 'uid' => $this->userInfo['id']]);
        if (!$feedback) {
            return rtn(1, Language::lang("暂无用户反馈信息", $this->userInfo));
        }
        $return['content'] = $feedback->content;
        $return['reply'] = $feedback->reply;
        $request = Request::instance();
        $return['img'] = $feedback->img ? $request->domain()."/btsq/public" . $feedback->img : null;
        $return['update_time'] = $feedback->update_time;
        return rtn(1, "os_success", $return);
    }


    /**
     * 获取用户银行卡信息
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_bank()
    {
        $user_bank = UserBank::where(['uid' => $this->userInfo['id']])->order('default', ' desc')->select();
        $user_bank = $user_bank->toArray();
        $user_bank_add = $user_bank;
        foreach ($user_bank as $k => $v) {
            $bank_name = BankModel::where(['id' => $v['bid']])->field('bank_name')->find();
            if ($bank_name) {
                $bank_name = $bank_name->toArray();
                $bank_name = Language::lang($bank_name['bank_name'], $this->userInfo);
            } else {
                $bank_name = "null";
            }
            $user_bank_add[$k]['bank_name'] = $bank_name;
        }
        $return['email'] = $this->userInfo['email'];
        $return['list'] = $user_bank_add;
        return rtn(1, Language::lang("成功", $this->userInfo), $return);
    }


    /**
     * 添加银行卡
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_bank()
    {
//        验证参数完整性
        $param = [
            'name' => "请输入开户人姓名",
            'bid' => "请选择银行",
            'bank_card' => "请输入银行卡号",
            'bank_add' => "请输入开户行"
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
//        验证银行卡号规则
        /**
         * 待
         */
        $UserBank = new UserBank();
        $result = $UserBank->add_bank($_POST, $this->userInfo);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['info'], $this->userInfo));
        }
        $userinfo = $this->userinfo;
        return rtn(1, Language::lang("成功", $this->userInfo), $userinfo['email']);
    }


    /**
     * 删除用户银行卡信息
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function del_user_bank()
    {
        $param = [
            'id' => '错误'
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
        $user_bank = UserBank::get(['uid' => $this->userInfo['id'], 'id' => $_POST['id']])->find();
        if ($user_bank) {
            $user_bank = $user_bank->toArray();
            if ($user_bank['default'] == 1) {
                return rtn(0, Language::lang("默认银行卡暂时不能删除", $this->userInfo));
            }
        }
        $del_result = UserBank::destroy(['uid' => $this->userInfo['id'], 'id' => $_POST['id']]);
        if (!$del_result) {
            return rtn(0, Language::lang("失败", $this->userInfo));
        }
        return rtn(1, Language::lang("成功", $this->userInfo));
    }

    //设置默认银行卡
    public function default_bank()
    {
        $id = input('id');
        if (!$id) {
            return rtn(0, lang('parameter'));
        } else {
            $uid = $this->userInfo['id'];
            if (db('user_bank')->where(['uid' => $uid, 'id' => $id])->find()) {
                db('user_bank')->where(['uid' => $uid, 'default' => 1])->update(['default' => 0]);
                db('user_bank')->where('id', $id)->update(['default' => 1]);
                return rtn(1, lang('success'));
            } else {
                return rtn(0, '你想干什么！');
            }
        }
    }


    public function tests()
    {
        return rtn(1, Language::lang("测试111111111111111", $this->userInfo));
        exit;
    }

    /**
     * 获取paypal用户账号
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function get_paypal()
    {
        $user_pay = UserPay::get(['uid' => $this->userInfo['id']]);
        unset($user_pay['create_time']);
        unset($user_pay['uid']);
        unset($user_pay['wechat_status']);
        unset($user_pay['alipay_account']);
        unset($user_pay['alipay_name']);
        unset($user_pay['create_time']);
        unset($user_pay['alipay_status']);
        return rtn(1, Language::lang("成功", $this->userInfo), $user_pay);
    }

    /**
     * 添加paypal
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function add_paypal()
    {
        $param = [
            'paypal' => '请输入paypal账号'
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
        $user_pay = [
            'uid' => $this->userInfo['id'],
            'paypal' => $_POST['paypal'],
        ];
        $UserPay = new UserPay();
        $result = $UserPay->save_pays($this->userInfo, $user_pay);
        if (!$result) {
            return rtn(0, Language::lang('失败', $this->userInfo));
        }
        return rtn(1, Language::lang('成功', $this->userInfo));
    }

    /**
     * 获取用户支付宝信息
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function get_alipay()
    {
        $request = Request::instance();
        $user_pay = UserPay::get(['uid' => $this->userInfo['id']]);
        unset($user_pay['create_time']);
        unset($user_pay['uid']);
        unset($user_pay['wechat_status']);
        $uid = $this->userInfo['id'];
        $bank = db('user_bank')->where('uid', $uid)->find();
        $user_pay['bank'] = $bank ? 1 : 0;
        $user_pay['wechat_img'] = $user_pay['wechat_img']?$request->domain().$user_pay['wechat_img']:null;
//        $user_pay['wechat_img'] = $user_pay['wechat_img'] ? $request->domain()."/btsq/public".$user_pay['wechat_img'] : null;
        return rtn(1, Language::lang("成功", $this->userInfo), $user_pay);
    }


    /**
     * 添加支付宝
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function add_alipay()
    {
        $param = [
            'alipay_account' => "请输入支付宝账号",
            'real_name' => '请输入真实姓名'
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
        $user_pay = [
            'uid' => $this->userInfo['id'],
            'alipay_account' => $_POST['alipay_account'],
            'alipay_name' => $_POST['real_name']
        ];
        $UserPay = new UserPay();
        $result = $UserPay->save_pays($this->userInfo, $user_pay);
        if (!$result) {
            return rtn(0, Language::lang("失败", $this->userInfo));
        }
        return rtn(1, Language::lang("成功", $this->userInfo));
    }

    /**
     * 获取用户的微信
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function get_wechat()
    {
        $user_pay = UserPay::get(['uid' => $this->userInfo['id']]);
        unset($user_pay['alipay_account']);
        unset($user_pay['alipay_name']);
        unset($user_pay['create_time']);
        unset($user_pay['uid']);
        unset($user_pay['alipay_status']);
        return rtn(1, Language::lang("成功", $this->userInfo), $user_pay);
    }


    /**
     * 添加用户微信信息
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function add_wechat()
    {
        $param = [
            'wechat_img' => "微信二维码有误",
            'wechat_nick' => "请输入微信昵称"
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
        $user_pay = [
            'wechat_img' => $_POST['wechat_img'],
            'wechat_nick' => $_POST['wechat_nick'],
            'uid' => $this->userInfo['id'],
        ];
        $UserPay = new UserPay();
        $result = $UserPay->save_pays($this->userInfo, $user_pay);
        if (!$result) {
            return rtn(0, Language::lang("失败", $this->userInfo));
        }
        return rtn(1, Language::lang("成功", $this->userInfo));
    }

    /**
     * 登录后修改系统语言
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function change_user_config()
    {
        $param = [
            'language_type' => "请选择语言",
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
        if (!in_array($_POST['language_type'], ['cht', 'en', 'chs'])) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
        $result = UserConfig::where(['uid' => $this->userInfo['id']])
            ->update(['language' => $_POST['language_type']]);
        if (!$result) {
            return rtn(0, Language::lang("失败", $this->userInfo));
        }
        return rtn(1, Language::lang("成功", $this->userInfo));
    }

    /**
     * 添加矿机
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function add_mining()
    {
        $mining_rang = Config::mining_rang();
        $param = [
            'payment_password' => "请输入支付密码",
            'mining_number' => '请输入投入数量'
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }

        /*验证支付密码*/
        if (encrypt($_POST['payment_password']) != $this->userInfo['payment_password']) {
            return rtn(0, Language::lang("支付密码不正确", $this->userInfo));
        }

        /*获取矿机列表*/
        $UserMiningList = UserMining::mining_list($this->userInfo);
        $mining_number = count($UserMiningList);
        if (($mining_number + 1) > 5) {
            return rtn(0, Language::lang("矿机数量超出", $this->userInfo));
        }
        if ($this->userInfo['level'] == 0) {
            $where['uid'] = $this->userInfo['id'];
            $where['amount'] = ['lt', 100];
            if (!db('user_mining')->where($where)->find()) {
                $mining_rang['min'] = 1;
            }
        }
        /*判断用户矿机数量，若为0，验证新增矿机投入量*/
        if ($_POST['mining_number'] < $mining_rang['min']) {
            return rtn(0, Language::lang("投入量小于最小值", $this->userInfo));
        }
        $user_mining_where['uid'] = $this->userInfo['id'];
        $user_mining_where['mining_status'] = 1;
        $user_amount = db('user_mining')->where($user_mining_where)->sum('amount');
        if ($_POST['mining_number'] > ($mining_rang['max'] - $user_amount)) {
            return rtn(0, Language::lang("单次投入量超出", $this->userInfo) . $mining_rang['max']);
        }

        Db::startTrans();
        try {
            $UserMining = new UserMining();
            $UserMining->add_usermining($_POST, $this->userInfo);

            // 记录到"通知中心"
             $UserMessage = new UserMessage();
             $info['title'] = '新增矿池';
             $info['first_content'] = '您在';
             $info['second_content'] = '新增矿池成功。';
             $data = [
                 'user_info' => $this->userInfo,
                 'data' => $info,
             ];
             $message_result = $UserMessage -> create_user_message($data);
             if($message_result['code'] === 0){
                 throw new Exception($message_result['msg']);
             }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return rtn(0, Language::lang($e->getMessage(), $this->userInfo));
        }
        return rtn(1, Language::lang("添加矿机成功", $this->userInfo));
    }

    /**
     * 手动提取矿机收益
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function withdraw_mining()
    {
        $withdraw_mining_status = db('user_coin_profit')->where(['uid' => $this->userInfo['id'], 'type' => 5])->whereTime('create_time', 'today')->find();
//        if ($withdraw_mining_status) {
//            return rtn(0, lang('ore_mining'));
//        } else {
            /*获取用户矿机信息*/
            $where['uid'] = $this->userInfo['id'];
            $where['reword'] = ['gt', 0];
            $user_mining = db('user_mining')->where($where)->select();
            if ($user_mining) {
                /*提取矿机收益*/
                $UserMinig = new UserMining();
                Db::startTrans();
                try {
                    foreach ($user_mining as $k => $v) {
                        $UserMinig->mining_withdraw($v, 1);
                    }
                    Db::commit();
                    return rtn(1, Language::lang("成功", $this->userInfo));
                } catch (\Exception $e) {
                    Db::rollback();
                    return rtn(1, Language::lang($e->getMessage(), $this->userInfo));
                }
            } else {
                return rtn(0, lang('no_profit'));
            }
//        }
    }


    /**
     * 获取用户开通自动获取收益的配置
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function get_auto_withdraw()
    {
        $auto_withdraw = AutoWithdraw::get_info($this->userInfo);
        if ($auto_withdraw) {
            $auto_withdraw = $auto_withdraw->toArray();
            $auto_withdraw['status'] = $auto_withdraw['status'];
            $auto_withdraw['end_time'] = date("Y-m-d H:i:s", $auto_withdraw['end_time']);
        }
        return rtn(1, Language::lang("成功", $this->userInfo), $auto_withdraw);
    }


    /**
     * 获取用户全部矿机
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_mining()
    {
        $user_mining = UserMining::where(['uid' => $this->userInfo['id']])->order('create_time asc')->field('reword,id,status,amount,net_power,power,total_reword,all_reword,spend')->select();
        foreach ($user_mining as $k => $v) {
            $user_mining[$k]['name'] = '矿池' . ($k + 1);
            $user_mining[$k]['amount'] = number_format($v['amount'], 2);
            $user_mining[$k]['net_power'] = round(rtrim(rtrim($v['net_power'], '0'), '.'), 2);
            $user_mining[$k]['power'] = round(rtrim(rtrim($v['power'], '0'), '.'), 2) * 1000;
            $unit = 'kh/s';
            if ($user_mining[$k]['power'] >= 1024) {
                $user_mining[$k]['power'] = round($r['power'] / 1024, 2);
                $unit = 'mh/s';
            }
            if ($user_mining[$k]['power'] >= 1024) {
                $user_mining[$k]['power'] = round($r['power'] / 1024, 2);
                $unit = 'gh/s';
            }
            if ($user_mining[$k]['power'] >= 1024) {
                $user_mining[$k]['power'] = round($r['power'] / 1024, 2);
                $unit = 'th/s';
            }
            $user_mining[$k]['power'] = $user_mining[$k]['power'] . $unit;
            $user_mining[$k]['surplus'] = number_format($user_mining[$k]['reword'], 2);
        }
        return rtn(1, "os_success", $user_mining);
    }

    /**
     * 矿机首页
     * @return false|string
     * @throws Exception
     * @throws \think\exception\DbException
     */
    public function mining()
    {
        $UserMining = db('user_mining')->where('uid', $this->userInfo['id'])->select();
        $r['B'] = "活跃值";
        $r['pay_B'] = Language::lang("消耗", $this->userInfo) . "活跃值";
        $r['status'] = 0;
        foreach ($UserMining as $k => $v) {
            if ($v['status'] == 1) {
                $r['status'] = 1;
            }
        }
        $r['working'] = UserMining::
        where(['uid' => $this->userInfo['id'], 'status' => 1])->count('id');

        $withdraw_mining_status = db('user_coin')->where(['uid' => $this->userInfo['id'], 'type' => 5])->whereTime('create_time', 'today')->find();
        $r['withdraw_status'] = $withdraw_mining_status ? 0 : 1;
        $r['power'] = round(config('MINING_REWORD') / 100 * db('user_mining')->where('mining_status', 1)->sum('spend'), 2) * 1000;
        $unit = 'kh/s';
        if ($r['power'] >= 1024) {
            $r['power'] = round($r['power'] / 1024, 2);
            $unit = 'mh/s';
        }
        if ($r['power'] >= 1024) {
            $r['power'] = round($r['power'] / 1024, 2);
            $unit = 'gh/s';
        }
        if ($r['power'] >= 1024) {
            $r['power'] = round($r['power'] / 1024, 2);
            $unit = 'th/s';
        }
        $r['power'] = $r['power'] . $unit;
        return rtn(1, Language::lang("成功", $this->userInfo), $r);
    }

    /**
     * 获取用户历史贡献记录
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_contribution_his()
    {
        $request = Request::instance();
        $contribution_his = db('contribution_his')->where(['uid' => $this->userInfo['id']])->select();
        $times = array();
        $values = array();
        foreach ($contribution_his as $k => $v) {
            $times[$k] = date('m.d', $v['update_time']);
            $values[$k] = $v['contribution'];
        }
        unset($contribution_his);
        $contribution_his['head_icon'] = $this->userInfo['head_icon'] ? $request->domain()."/btsq/public" . $this->userInfo['head_icon'] : $request->domain()."/btsq/public" . '/static/ace/images/head.png';

        $contribution_his['name'] = $this->userInfo['nickname'];
        $user_contribution = UserContribution::get(['uid' => $this->userInfo['id']]);
        $contribution_his['today'] = floatval($user_contribution['contribution']);
        $contribution_his['times'] = $times;
        $contribution_his['values'] = $values;
        return rtn(1, Language::lang("成功", $this->userInfo), $contribution_his);
    }


    /**
     * 改变矿机开关
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function change_mining()
    {
        /*验证用户矿机是否存在*/
        if (!$_POST['mining_number']) {
            return rtn(0, Language::lang("矿机不存在", $this->userInfo));
        }
        $user_mining = UserMining::get(['uid' => $this->userInfo['id'], 'id' => $_POST['mining_number']]);
        if (!$user_mining) {
            return rtn(0, Language::lang("矿机不存在", $this->userInfo));
        }

        /*修改*/
        $user_mining->status = ($user_mining->status + 1) % 2;
        $user_mining->save();
        return rtn(1, Language::lang("成功", $this->userInfo));


    }


    /**
     * 获取用户开矿配置
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function mining_config()
    {
        /*可投入范围*/
        $mining_config = null;
        $mining_min = \config("MINING_MIN");
        $mining_max = \config("MINING_MAX");

        $user_mining_where['uid'] = $this->userInfo['id'];
        $user_mining_where['mining_status'] = 1;
        $user_amount = db('user_mining')->where($user_mining_where)->sum('amount');
        $max = $mining_max - $user_amount;
        $mining_config['min'] = $mining_min;
        if ($this->userInfo['level'] == 0) {
            $where['uid'] = $this->userInfo['id'];
            $where['amount'] = ['lt', 100];
            if (!db('user_mining')->where($where)->find()) {
                $mining_config['min'] = 1;
            }
        }
        $mining_config['max'] = $max > 0 ? $max : 0;
        return rtn(1, Language::lang("成功", $this->userInfo), $mining_config);
    }


    /**
     * 查询用户自动提取状态
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function get_authwithdraw()
    {
        $auth = AutoWithdraw::get(['uid' => $this->userInfo['id']]);
        $return = null;
        if ($auth) {
            $return['status'] = $auth['status'];
        }
        return rtn(1, "os_success", $return);
    }

    /**
     * 改变用户自动提取收益的状态
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function change_auto_withdraw()
    {
        /*开关状态判断*/
        $param = [
            'switch' => "开关状态不正确",
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
        if (!in_array($_POST['switch'], ['1', '0'])) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }

        $user_auto_withdraw = AutoWithdraw::get(['uid' => $this->userInfo['id']]);
        if (!$user_auto_withdraw) {
            $user_auto_withdraw = new AutoWithdraw();
            $user_auto_withdraw->status = 0;
        }
        if ($_POST['switch'] == 1) {
            if (2 < $this->userInfo['level']) {
                $time = strtotime(date("Y-m-d", config('TIMING_TASK')));
                if (time() < $time) {
                    $user_auto_withdraw->start_time = time() + 86400;
                } else {
                    $user_auto_withdraw->start_time = time();
                }
            }
        }
        $user_auto_withdraw->status = $_POST['switch'];
        $user_auto_withdraw->save();
        return rtn(1, Language::lang("成功", $this->userInfo));
    }

    /**
     * 修改用户头像昵称信息
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function change_user_info()
    {

        /*验证参数完整性*/
        $param = [
            'type' => '类型不正确',
            'profile_icon' => "头像路径不正确",
            'nickname' => "请输入昵称",
        ];
        $result = ApiBase::check_post($_POST, $param);
        if ($result['status'] != 1) {
            return rtn(0, Language::lang($result['param'], $this->userInfo));
        }
        /*修改用户信息*/
        $user = UserModel::get(['id' => $this->userInfo['id']]);
//        存在原头像，删除。
        if ($user['head_icon']) {
            try {
                unlink(ROOT_PATH . "/public" . $user['head_icon']);
            } catch (\Exception $e) {
                ;
            }
        }
        if ($_POST['type'] == 1) {
            $user->head_icon = $_POST['profile_icon'];
        } else {
            $user->nickname = $_POST['nickname'];
        }
        $user->save();
        return rtn(1, Language::lang("成功", $this->userInfo));
    }

    /**
     * 获取用户昵称信息
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function user_info()
    {
        $user = UserModel::user_info($this->userInfo);
        return rtn(1, Language::lang("成功", $this->userInfo), $user);
    }


    /**
     * 开启关闭全部矿机
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function mining_total_switch()
    {
        $Usermining = new UserMining();
        $Usermining->switch_all($this->userInfo);
        $count = db('user_mining')->where(['uid' => $this->userInfo['id'], 'status' => 1])->count();
        return rtn(1, Language::lang("成功", $this->userInfo), $count);
    }

    /**
     * 钱包总资产页面
     * @param  token  用户token
     * @return string
     */

    // public function walletPage()
    // {
    //     $request = Request::instance();
    //     $user_info = $this->userInfo;
    //     $data['usdt'] = 0;
    //     $user_money = db('user_money')->alias('a')->join('currency b','a.cur_id = b.id')->where('uid',$user_info['id'])->where('cur_id',1)->field('b.id,a.amount,b.name,b.icon')->select();
    //     $data['currency'] = $user_money;
    //     foreach ($user_money as $k => $v) {
    //         $price_new = db('cur_market')->where('cur_id',$v['id'])->value('price_new');
    //         $price_new = $price_new ?$price_new:0;
    //         $data['currency'][$k]['new_price'] = $price_new;
    //         $data['currency'][$k]['usdt'] = $v['amount'] * $price_new;
    //         $data['currency'][$k]['icon'] = $request->domain().$v['icon'];  
    //         $data['usdt'] = $data['usdt']+$v['amount'] * $price_new;
    //     }
    //     $data['cny'] = rtrim(rtrim(sprintf('%.2f',($data['usdt'] * config('USDT_RMB'))), '0'), '.');
    //     return rtn(1,lang('success'),$data);
    // }

    public function walletPage()
    {
        $coin1 = db('user_money')->where('uid', $this->userInfo['id'])->sum('total');
        $coin2 = db('user_money_profit')->where('uid', $this->userInfo['id'])->sum('total');
        $new_price = db('order')->where('order_status', 3)->order('done_time desc')->value('price');
        $data['usdt'] = number_format($coin1 + $coin2, 2);
        $data['cny'] = number_format($data['usdt'] * config('USDT_RMB'), 2);
        return rtn(1, lang('success'), $data);
    }

    /**
     * 分享--我的矿区
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function share_mining()
    {
        $request = Request::instance();
        /*用户信息*/
        $user = $this->userInfo;

        $user_share = UserModel::where(['parent_id' => $user['id']])->select();
        $user_add_mining = UserMining::add_mining_weekily($user)->toArray();


        $r['user_info']['nickname'] = 'ID:' . $user['id'];
        $where['parent_id'] = $user['id'];
        $where['level'] = ['neq', 0];
        $r['user_info']['count_mining_number'] = db('user')->where($where)->count();
        $r['user_info']['head_img'] = $this->userInfo['head_icon'] ? $request->domain()."/btsq/public" . $this->userInfo['head_icon'] : $request->domain()."/btsq/public" . '/static/ace/images/head.png';
        $r['user_info']['mining_amount'] = sprintf('%.2f', db('user_mining')->where('uid', $user['id'])->sum('amount'));
        $r['user_info']['add_mining'] = sprintf('%.2f', $user_add_mining[0]['amount'] ? $user_add_mining[0]['amount'] : 0);
        $r['share'] = null;
        foreach ($user_share as $k => $v) {
            //$share_user = UserModel::get(['id'=>$v['id']]);
            $r['share'][$k]['nickname'] = 'ID:' . $v['id'];
            $r['share'][$k]['head_img'] = $request->domain()."/btsq/public" . '/static/ace/images/head.png';
            $where['parent_id'] = $v['id'];
            $r['share'][$k]['count_mining_number'] = db('user')->where($where)->count();
            $r['share'][$k]['mining_amount'] = number_format(db('user_mining')->where('uid', $v['id'])->sum('amount'), 2);
            $share_user_add_mining = UserMining::add_mining_weekily($v)->toArray();
            $r['share'][$k]['add_mining'] = $share_user_add_mining[0]['amount'] ? number_format($share_user_add_mining[0]['amount'], 2) : '0.00';
        }
        return rtn(1, Language::lang("成功", $user), $r);
    }

    /**
     * 主账户资产列表
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_finance_list()
    {
        if (Request::instance()->isPost()) {
            $UserCoin = new UserCoin();
            $data = [
                'user_info' => $this->userInfo,
                'data' => input('post.'),
            ];
            $coin_result = $UserCoin->user_finance_list($data);
            if ($coin_result['code'] === 0) {
                return rtn(0, $coin_result['msg']);
            }
            return rtn(1, '', $coin_result);
        }
    }

    /**
     * 主账户资产详情
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_finance_detail()
    {
        if (Request::instance()->isPost()) {
            $UserCoin = new UserCoin();
            $data = [
                'user_info' => $this->userInfo,
                'data' => input('post.'),
            ];
            $detail_result = $UserCoin->user_finance_detail($data);
            if ($detail_result['code'] === 0) {
                return rtn(0, $detail_result['msg']);
            }
            return rtn(1, '', $detail_result['data']);
        }
    }

    /**
     * 收益账户资产
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function revenue_finance()
    {
        if (Request::instance()->isPost()) {
            $UserCoin = new UserCoin();
            $data = [
                'user_info' => $this->userInfo,
                'data' => input('post.'),
            ];
            $revenue_result = $UserCoin->revenue_finance($data);
            return rtn(1, '', $revenue_result);
        }
    }

    /**
     * 获取"通知中心"列表
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function message_list()
    {
        if (Request::instance()->isPost()) {
            $UserMessage = new UserMessage();
            $data = [
                'user_info' => $this->userInfo,
                'data' => input('post.'),
            ];
            $message_result = $UserMessage->message_list($data);
            return rtn(1, '', $message_result);
        }
    }

    /**
     * 获取"通知中心"详情
     * @return false|string
     */
    public function message_detail()
    {
        if (Request::instance()->isPost()) {
            $UserMessage = new UserMessage();
            $data = [
                'user_info' => $this->userInfo,
                'data' => input('post.'),
            ];
            $detail_result = $UserMessage->message_detail($data);
            if ($detail_result['code'] === 0) {
                return rtn(0, $detail_result['msg']);
            }
            return rtn(1, '', $detail_result['data']);
        }
    }
}
<?php

namespace app\admin\controller;

use app\api\controller\DailyPlan;
use app\common\controller\AdminBase;
use app\admin\model\Admin;
use think\Request;
use think\Db;
use think\Session;

class Index extends AdminBase
{

    /**
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 管理员每日报表
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function daily_plan(){
        $Daily = new DailyPlan();
        $Daily->run_all();
        return true;
    }

    public function change_theme()
    {
        $r = $_POST['r'] <= 9 ? "0" . $_POST['r'] : $_POST['r'];
        $g = $_POST['g'] <= 9 ? "0" . $_POST['g'] : $_POST['g'];
        $b = $_POST['b'] <= 9 ? "0" . $_POST['b'] : $_POST['b'];
        $color = "#".$r.$g.$b;
        $config = \app\admin\model\Config::get(['key'=>"THEME"]);
        $config->value = $color;
        $config->save();
        return true;
    }

    /**
     *计算贡献度示例
     */
    public function contributions_computer()
    {
        $all = input('number');//总人数
        $each = floor($all / 21);//多少人一份
        $position_101 = $all - $each * 10 + 1;//第一个101分人的位置
        $position_99 = $each * 10;//最后一个99分人的位置
        $position = input('position');//该用户位置
        $score = 100;
        if ($position >= $position_101) {
            $score = 101 + floor(($position - $position_101) / $each);
        }
        if ($position <= $position_99) {
            $score = 99 - floor(($position_99 - $position) / $each);
        }
        pre($score);
        exit;
    }

    public function profile()
    {
        $this->assign('info', model("Admin")->myInfo(AID));
        return $this->fetch();
    }

    /**
     * controller 获取手机验证码
     */
    public function get_verify()
    {
        $Admin = new Admin();
        return json($Admin->getVerify(input('post.')));
    }

    /**
     * controller 修改当前登陆管理员密码
     */
    public function repwd()
    {
        if (Request::instance()->isPost()) {
            if (!input('post.oldpassword')) {
                $this->error('请输入当前密码!');
            }

            // 验证管理员原密码是否正确
            $pwd = Db::name('admin')->where('id =' . AID)->value('password');
            if ($pwd != encrypt(trim(input('post.oldpassword')))) {
                return json(array('code' => 0, 'msg' => '操作失败:原密码不符!'));
            }

            // 判断新密码是否为空
            if (!trim(input('post.password'))) {
                return json(array('code' => 0, 'msg' => '请输入新密码!'));
            }

            // 判断两次输入的密码是否一致
            if (trim(input('post.password')) != trim(input('post.repassword'))) {
                return json(array('code' => 0, 'msg' => '两次输入的新密码不一致!'));
            }

            // 判断手机验证码
            $authcode = session('authcode');
            if (!trim(input('post.code'))) {
                return json(array('code' => 0, 'msg' => '请输入验证码!'));
            }
            if ($authcode['code'] != input('post.code')) {
                return json(array('code' => 0, 'msg' => '验证码错误!'));
            }
            // 清除用户注册时获取的手机验证码
            Session::delete('authcode');

            $data['password'] = encrypt(input('post.password'));
            $id = AID;

            $result = Db::name('admin')->where('id', $id)->update($data);
            if ($result) {
                return json(array('code' => 1, 'msg' => '修改密码成功,请重新登录!', 'url' => url('Publics/logout')));
            } else {
                return json(array('code' => 0, 'msg' => '修改密码失败!'));
            }
        } else {
            $this->assign('info', AID);
            $Admin = new Admin();
            $this->assign('phone', $Admin->get_phone_by_id(AID));
            return $this->fetch();
        }
    }
}
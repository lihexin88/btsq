<?php
/**
 * 全局基类
 */
namespace app\common\controller;
use app\common\controller\Base;
use org\util\Auth;
use think\Request;
class AdminBase extends Base
{

    public function _initialize()
    {
        $request= Request::instance();
        parent::_initialize();
        // 获取当前用户ID
        define('AID', is_login());
        if (!AID) {
            // 还没登录 跳转到登录页面
            $this->redirect('Publics/login');
        }

        /* 是否是超级管理员 & 判断是否为充许访问ID */
        define('IS_ROOT', is_administrator());
//        $ip = $this -> getIP();
//        if (!IS_ROOT && !in_array($ip, explode(',', config('ADMIN_ALLOW_IP')))) {
//            $this->error('403:禁止访问');
//        }

        /* 检测访问权限 */
        $access = $this->accessControl();
        $rule = $request->module() . '/' . $request->controller() . '/' . $request->action();
        if ($access === false) {
            $this->error('403:禁止访问');
        } elseif ($access === null) {
            $dynamic = $this->checkDynamic();//检测分类栏目有关的各项动态权限
            if ($dynamic === null) {//检测非动态权限
                if (!$this->checkRule(strtolower($rule), array('in', '1,2'))) {
                    $this->error('该访问不在授权范围内!');
                }
            } elseif ($dynamic === false) {
                $this->error('未授权访问!');
            }
        }
        if(AID != 1){
            $this->assign('sidebar',model('Common/AuthRule')->ruleMaps());
        }else{
            $this->assign('sidebar',model('Common/AuthRule')->ruleMap());
        }
        $this->assign('pagename',model('Common/AuthRule')->ruleTitle($rule));
        $rule2 = $request->module() . '/' . $request->controller();
        $this->assign('rule2',$rule2);
        $this->assign('rule',$rule);
    }

    // 定义一个函数getIP()
    public function getIP()
    {
        global $ip;
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if(getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else $ip = "Unknow";
        return $ip;
    }

    /**
     * 权限检测
     * @param string $rule 检测的规则
     * @param string $mode check模式
     * @return boolean
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    final protected function checkRule($rule, $type = AuthRuleModel::RULE_URL, $mode = 'url')
    {
        if (IS_ROOT) {
            return true;//管理员允许访问任何页面
        }
        static $Auth = null;
        if (!$Auth) {
            // $Auth = new \Think\Auth();
            //$Auth = new \Org\Util\Auth();
            $Auth = new Auth();
        }
        if (!$Auth->check($rule, AID, $type, $mode)) {
            return false;
        }
        return true;
    }

    /**
     * 检测是否是需要动态判断的权限
     * @return boolean|null
     *      返回true则表示当前访问有权限
     *      返回false则表示当前访问无权限
     *      返回null，则会进入checkRule根据节点授权判断权限
     *
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    protected function checkDynamic()
    {
        if (IS_ROOT) {
            return true;//管理员允许访问任何页面
        }
        return null;//不明,需checkRule
    }


    /**
     * action访问控制,在 **登陆成功** 后执行的第一项权限检测任务
     *
     * @return boolean|null  返回值必须使用 `===` 进行判断
     *
     *   返回 **false**, 不允许任何人访问(超管除外)
     *   返回 **true**, 允许任何管理员访问,无需执行节点权限检测
     *   返回 **null**, 需要继续执行节点权限检测决定是否允许访问
     * @author 朱亚杰  <xcoolcc@gmail.com>
     */
    final protected function accessControl()
    {
        if (IS_ROOT) {
            return true;//管理员允许访问任何页面
        }
        $allow = config('ALLOW_VISIT');
        $deny = config('DENY_VISIT');
        $check = strtolower(CONTROLLER_NAME . '/' . ACTION_NAME);
        if (!empty($deny) && in_array_case($check, $deny)) {
            return false;//非超管禁止访问deny中的方法
        }
        if (!empty($allow) && in_array_case($check, $allow)) {
            return true;
        }
        return null;//需要检测节点权限
    }


}
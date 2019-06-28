<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\admin\model\Admin;
use think\Request;
use think\Db;

class Config extends AdminBase
{
    /**
    * controller 获取手机验证码
    */
    public function get_verify(){
        $Admin = new Admin();
        return json($Admin -> getVerify_config(input('post.')));
    }

    //比例收益设置
    public function index()
    {
        if (Request::instance()->isPost()) {
            $datas = input('post.');
            return json(model('Config')->saveConfig($datas));
        }
        $this->assign("list", model("Config")->configPage('index'));
        return $this->fetch();
    }

    //站点设置
    public function config_file()
    {
        if (Request::instance()->isPost()) {
            $datas = input('post.');
            return json(model('Config')->saveConfig($datas));
        }
        //pre(model("Config")->configPage('file'));
        $this->assign("list", model("Config")->configPage('file'));

        return $this->fetch('index');
    }

    //手续费设置
    public function config_cost()
    {
        if (Request::instance()->isPost()) {
            $datas = input('post.');
            return json(model('Config')->saveConfig($datas));
        }
        
        $this->assign("list", model("Config")->configPage('cost'));

        return $this->fetch('index');
    }

    //挖矿设置
    public function config_mining()
    {
        if (Request::instance()->isPost()) {
            $datas = input('post.');
            return json(model('Config')->saveConfig($datas));
        }
        
        $this->assign("list", model("Config")->configPage('mining'));

        return $this->fetch('index');
    }



    public function info()
    {
        $this->assign("list", model("Config")->configPage('info'));
        return $this->fetch();
    }

    public function data()
    {
        $tabs = db()->query('show table status');
        $total = 0;
        foreach ($tabs as $k => $v) {
            $tabs[$k]['size'] = byteFormat($v['Data_length'] + $v['Index_length']);
            $total += $v['Data_length'] + $v['Index_length'];
        }
        $this->assign("list", $tabs);
        $this->assign("total", byteFormat($total));
        $this->assign("tables", count($tabs));
        return $this->fetch();
    }

    public function setting($p = 1)
    {
        $this->assign("info", model("Config")->infoList(array(), $p));
        return $this->fetch();
    }

    public function add()
    {
        if (Request::instance()->isPost()) {
            return json(model('Config')->saveInfo(input('post.')));
        }
        $this->assign("info", array('id' => null, 'key' => null, 'info' => null, 'url' => 'index', 'type' => '0'));
        $this->assign("url", model("Common/Dict")->showList('config_url'));
        $this->assign("type", model("Common/Dict")->showList('config_type'));
        return $this->fetch();
    }

    public function edit($id)
    {
        $this->assign("info", model("Config")->listInfo($id));
        $this->assign("url", model("Common/Dict")->showList('config_url'));
        $this->assign("type", model("Common/Dict")->showList('config_type'));
        return $this->fetch('add');
    }

}
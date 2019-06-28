<?php
/**
 * 全局基类
 */
namespace app\common\controller;

use think\Controller;

class Base extends Controller
{

    public function _initialize()
    {
        // header("Content-Type:text/html; charset=utf-8");

        /* 读取数据库中的配置 */
        $config = model('Common/Config')->getConfig();
        foreach ($config as $k => $v) {
        	config($k,$v);
        }
    }

}
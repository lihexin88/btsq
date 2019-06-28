<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/11
 * Time: 15:03
 */

namespace app\api\controller;


use think\Controller;
use app\api\model\Bank as BankModel;

class Bank extends Controller
{
    /**
     * 返回银行信息
     * @return false|string
     * @throws \think\exception\DbException
     */
    public function all_bank()
    {
        $banks = BankModel::all_bank();
        return rtn(1,'os_success',$banks);
    }
}
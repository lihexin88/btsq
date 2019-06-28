<?php
/**---------------------------|
 * Created by  '李'
 * User: Administrator
 * Date: 2018/12/1
 * Time: 11:08
 **---------------------------|
 */

namespace app\api\controller;

use app\api\model\Config;

use think\Controller;

class RegisterPro extends Controller
{
	/**
	 * 获取注册协议
	 * @return false|string
	 * @throws \think\exception\DbException
	 */
	public function reg_protocol()
	{
		$pro = Config::reg_protocol();
		return rtn(1,lang('os_success'),$pro);
	}
}
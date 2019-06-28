<?php
/**---------------------------|
 * Created by  '李'
 * User: Administrator
 * Date: 2018/12/1
 * Time: 15:17
 **---------------------------|
 */

namespace app\api\controller;


use think\Controller;

class PlanTaskAccessLimit extends Controller
{
	/**
	 * 计划任务ip限制，只能使用列表内的ip
	 * @return array
	 */
	static public function planTaskAccesslimit()
	{
		$r = [];
		$ip = $_SERVER["REMOTE_ADDR"];
//		允许访问的ip地址列
		$ip_list =[
				'127.0.0.1',
				'localhost',
				'47.75.185.156',
			];
		if(!in_array($ip,$ip_list)){
			$r = ['code'=>-1,'msg'=>'baned access'];
		}else{
			$r = ['code'=>1,'msg'=>'success'];
		}
		return $r;
	}
}
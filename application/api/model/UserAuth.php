<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/24
 * Time: 18:08
 */

namespace app\api\model;


use think\Exception;
use think\Model;

class UserAuth extends Model
{
	public function user_auth($userInfo,$data)
	{
//		存在就更新
//		print_r($userInfo);
		$user = self::get(['uid'=>$userInfo['id']]);
//		echo self::getLastSql();
//		print_r($user);
//		print_r($data);
		if($user){
//			print_r("更新");
			$user = $user->toArray();
			$user['name'] = $data['name'];
			$user['id_number'] = $data['id_number'];
			$user['bank_card'] = $data['bank_card'];
			$user['id_f_url'] = $data['id_f_url'];
			$user['id_b_url'] = $data['id_b_url'];
			$user['take_bank_name'] = $data['take_bank_name'];
			unset($user['create_time']);
			$user['update_time'] = time();
			if(!$this->update($user)){
				throw new Exception('error');
			}else{
				return 'updated';
			}
		}else{
//			print_r("插入");
			$user = new $this;
			$user['name'] = $data['name'];
			$user['uid'] = $userInfo['id'];
			$user['id_number'] = $data['id_number'];
			$user['bank_card'] = $data['bank_card'];
			$user['id_f_url'] = $data['id_f_url'];
			$user['id_b_url'] = $data['id_b_url'];
			$user['take_bank_name'] = $data['take_bank_name'];
			if(!$user->save()){
				throw new Exception('error');
			}else{
				return 'inserted';
			}
//			echo self::getLastSql();
		}
		return true;

	}
}
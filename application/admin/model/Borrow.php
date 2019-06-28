<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;

class Borrow extends Base
{

    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量


    /**
     * model 借币列表
     */
    public function borrowList($p,$user_map,$map){
    	if($user_map){
    		$user_id = Db::name('user') -> where($user_map) -> field('id') -> select();
    		$uid = '';
    		foreach($user_id as $k => $v){
    			$uid .= $v['id'].',';
    		}
    		$map['uid'] = array('in',trim($uid,','));
    	}
    	
    	$list = Db::name('user_borrow') -> where($map) -> page($p,self::PAGE_LIMIT)->order('create_time desc') -> select();
    	$count = Db::name('user_borrow') -> where($map) -> count();
    	$request = Request::instance();
    	$page = boot_page($count,self::PAGE_LIMIT,self::PAGE_SHOW,$p,$request -> action());
    	foreach($list as $k => $v){
    		$list[$k]['user'] = Db::name('user') -> where('id',$v['uid']) -> field('username,tel') -> find();
    		$list[$k]['create_date'] = date('Y-m-d H:i:s',$v['create_time']);
            $time_day = (time()-$v['create_time'])/3600/24;//当前-创建 换算成小数
            $end_time = mktime(0,0,0,date('m',$v['create_time']),date('d',$v['create_time'])+1,date('Y',$v['create_time']))-300;//每天的最后一秒时间戳
            $day = floor((time()-$end_time)/3600/24);//向下取整 当前时间-每天的最后一秒时间戳 转换日期
            $list[$k]['back_fill'] = $day * $v['back_num'];//已回填数量
            $list[$k]['need'] = $v['back_num']*$v['back_day']-$list[$k]['back_fill'];//需回填数量=数量*天数-已回填数量
            if($v['back_num']*$v['back_day'] < $list[$k]['back_fill']){//数量*天数 小于 已回填数量
                $list[$k]['back_fill'] = $v['back_num']*$v['back_day'];//已回填数量 = 数量*天数
                $list[$k]['need'] = 0;//需回填数量 等于 0
            }
    		$dict_where['type'] = 'examine_type';
    		$dict_where['value'] = $v['examine'];
    		switch($v['examine']){
    			case 1:
    				$list[$k]['examine_text'] = Db::name('dict') -> where($dict_where) -> value('key');
    				$list[$k]['examine_btn'] = 'examine_pass';
    				break;
    			case 2:
    				$list[$k]['examine_text'] = Db::name('dict') -> where($dict_where) -> value('key');
    				$list[$k]['examine_btn'] = 'examine_no_pass';
    				break;
    			default:
    				$list[$k]['examine_text'] = Db::name('dict') -> where($dict_where) -> value('key');
    				$list[$k]['examine_btn'] = 'examine_default';
    				break;
    		}
    	}
    	$return['list'] = $list;
    	$return['count'] = $count;
    	$return['page'] = $page;

    	return $return;
    }

    /**
     * model 借币审核
     */
    public function doExamine($data){
    	if(!$data['id']){
    		return ['code' => 0,'msg' => '未获取借币信息'];
    	}
    	if(!$data['examine']){
    		return ['code' => 0,'msg' => '未获取借币状态'];
    	}

    	$result = Db::name('user_borrow') -> where('id',$data['id']) -> update(array('examine' => $data['examine']));
        $info = db('user_borrow')->where('id',$data['id'])->find();
        db('user')->where('id',$info['uid'])->setInc('gcu',$info['number']);
    	if($result){
    		return ['code' => 1,'msg' => '修改成功'];
    	}else{
    		return ['code' => 0,'msg' => '修改失败'];
    	}
    }

}
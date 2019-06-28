<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class Currency extends AdminBase
{
	/**
	 * 币种列表
	 * @param integer $p 页码
	 */
	public function index($p = 1)
	{
		$map = [];
		$keywords = input('get.keywords') ? input('get.keywords') : null;
		if ($keywords) {
			$map['name'] = array('like', '%' .trim($keywords) . '%');
		}
		$this->assign("info",model('Currency')->infoList($map,$p));
		return $this->fetch();
	}

	/**
	 * 新增币种
	 */
	public function add()
	{
		if(Request::instance()->isPost()){
			return json(model('Currency')->saveInfo(input('post.')));
		}
		$info = ['id'=>null,'title'=>null,'content'=>null];
		$this->assign('info',$info);
		$this->assign('pagename','添加币');
		return $this->fetch();
	}
	
	/**
	 * 修改币种
	 */
	public function edit($id)
	{
		
		if(Request::instance()->isPost()){
			return json(model('Currency')->saveInfo(input('post.')));
		}
		$this->assign("info",model('Currency')->currList($id));
		$this->assign('pagename','修改币');
		return $this->fetch('add');
	}

	 //仅供上传申请表
	public function upload(){
        $type = trim(input('type'));
        if(!$type){
            $r = ['status'=>0,'info'=>'参数不正确'];
        }else{
            // 获取表单上传文件 例如上传了001.jpg
            $file = request()->file('file');
            // 移动到框架应用根目录/public/upload/ 目录下
            if($file){
                $info = $file->move(ROOT_PATH . 'public' . DS . 'upload/'.$type);
                if($info){
                    // 成功上传后 获取上传信息
                    $link = '/upload/'.$type.'/'.$info->getSaveName();
                    $r = ['status'=>1,'info'=>'成功','msg'=>$link];
                }else{
                    // 上传失败获取错误信息
                    $r = ['status'=>0,'info'=>$file->getError()];;
                }
            }else{
                $r = ['status'=>0,'info'=>'未上传'];
            }
        }
        return json($r);
    }

}
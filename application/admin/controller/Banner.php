<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class Banner extends Admin
{
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {
        if (Request::instance()->isPost()){
            return json(model('Banner')->changeSort(input('post.')));
        }
        $map = [];
        $keywords = input('get.keywords') ? input('get.keywords') : null;
        if ($keywords) {
            $map['tittle'] = array('like', '%' . trim($keywords) . '%');
        }
        if (is_numeric(input('get.state'))) {
            $map['state'] = input('get.state');
        }
        $this->assign("info", model('Banner')->infoList($map, $p));
        $this->assign("state", model("Common/Dict")->showList('is_show'));//状态
        return $this->fetch();
    }

    /**
     * 新增
     */
    public function add()
    {
        if(Request::instance()->isPost()){
            return json(model('Banner')->saveInfo(input('post.')));
        }
        $this->assign("info", array('id' => null, 'link' => null));
        $this->assign('pagename','添加轮播图');
        return $this->fetch();
    }    /**
     * 修改信息
     * @param  string $id ID
     */
    public function edit($id)
    {
        if (Request::instance()->isPost()) {
            return json(model('Banner')->changeState(input('post.')));
        }
    }

    public function upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');
        
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload/banner');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                $map['carousel_url'] = '/upload/banner/'.$info->getSaveName();
                $result = model('Banner')->saveInfo($map);
                if($result['status'] == 0){
                    $this->error('新增失败');
                }else{
                    $this->success('新增成功', 'Banner/index');
                }
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }else{
            $this->error('未上传');
        }
    }

    /**
     * 删除信息
     * @param  string $id ID
     */
    public function delete()
    {
        if (Request::instance()->isPost()) {
            return json(model('Banner')->deleteInfo(input('post.id')));
        }
    }
}
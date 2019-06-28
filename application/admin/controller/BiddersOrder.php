<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class BiddersOrder  extends Admin
{
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {
        $map = [];
        $keywords = input('get.keywords') ? input('get.keywords') : null;
        if ($keywords) {
            $map['uid'] = array('like', '%' . trim($keywords) . '%');
        }     
        $this->assign("info", model('BiddersOrder')->infoList($map, $p));
        $this->assign("bidding_order_state", model("Common/Dict")->showList('bidding_order_state'));//状态
        $this->assign("userlist", model("User")->showList());
        return $this->fetch();
    }
    public function cancelactive()
    {
        if(Request::instance()->isPost()){
            if(!input('post.canceler_id')){
                return json(array('status' => 0, 'info' => '请选择撤单人'));
            }else{
                $result = model('BiddersOrder')->cancel(input('post.'));
                if($result['status'] == 0){
                    return json(array('status' => 0, 'info' => $result['info']));
                }else{
                    $result = model('BiddersOrder')->saveInfo(input('post.'));
                    $orderinfo = model('BiddersOrder')->infodata(array('id'=>input('post.id')));
                    model('BiddersOrder')->downGrade($orderinfo['buyer_id']);
                    return json($result); 
                }
                
            }
            
        }
    }

    /**
     * 删除信息
     * @param  string $id ID
     */
    public function delete()
    {
        if (Request::instance()->isPost()) {
            return json(model('BiddersOrder')->deleteInfo(input('post.id')));
        }
    }
}
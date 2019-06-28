<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Request;
use think\Db;

class BiddersRecord extends Admin
{
    /**
     * 列表
     * @param  integer $p 页码
     */
    public function index($p = 1)
    {
        $map = [];
        if (is_numeric(input('get.uid'))) {
            $map['uid'] = input('get.uid');
        }
        if (is_numeric(input('get.order_status'))) {
            $map['order_status'] = input('get.order_status');
        }
        if (is_numeric(input('get.buyer_id'))) {
            $map['buyer_id'] = input('get.buyer_id');
        }
        if (is_numeric(input('get.seller_id'))) {
            $map['seller_id'] = input('get.seller_id');
        }
        if (is_numeric(input('get.pricer_type'))) {
            $map['pricer_type'] = input('get.pricer_type');
        }
        // pre($map);exit;
        $this->assign("info", model('BiddersRecord')->infoList($map, $p));
        $this->assign("pricer_type", model("Common/Dict")->showList('pricer_type'));//状态
        $this->assign("userlist", model("User")->showList());
        return $this->fetch();
    }

    public function cancelactive()
    {
        if(Request::instance()->isPost()){
            if(!input('post.canceler_id')){
                return json(array('status' => 0, 'info' => '请选择撤单人'));
            }else{
                $result = model('BiddersRecord ')->cancel(input('post.'));
                if($result['status'] == 0){
                    return json(array('status' => 0, 'info' => $result['info']));
                }else{
                    $result = model('BiddersRecord ')->saveInfo(input('post.'));
                    $orderinfo = model('BiddersRecord ')->infodata(array('id'=>input('post.id')));
                    model('BiddersRecord ')->downGrade($orderinfo['buyer_id']);
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
            return json(model('BiddersRecord ')->deleteInfo(input('post.id')));
        }
    }
}
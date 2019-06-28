<?php
namespace  app\admin\model;
use app\common\model\Base;
use think\Request;
use think\db;
use app\api\model\Trade as Trades;
use app\api\controller\Index as Indexs;

class Order extends Base
{
    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量


    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p  页码
     * @return array      返回列表
     */
    public function infoList($map, $p)
    {

        $request= Request::instance();
        // $list = db('order')->where($map)->order('order_status desc,id desc')->page($p, self::PAGE_LIMIT)->select();
        $list = db('order')->where($map)->order("id desc,field(order_status,1,2,3,4)")->page($p, self::PAGE_LIMIT)->select();
        foreach ($list as $k => $v) {
            $list[$k]['buyer_id'] = db('user')->where('id',$v['buyer_id'])->value('email');
            $list[$k]['seller_id'] = db('user')->where('id',$v['seller_id'])->value('email');
          	$list[$k]['trade_type'] =db('dict')->where('type','trade_type')->where('value',$v['trade_type'])->value('key');
            $list[$k]['create_time'] = date('Y-m-d H:i:s',$v['addtime']);
            if($v['done_time']){
                $list[$k]['done_time'] = date('Y-m-d H:i:s',$v['done_time']);
            }else{
                $list[$k]['done_time'] = '-';
            }
            if($v['payment_method']){
                switch($v['payment_method']){
                    case 1:
                        $list[$k]['payment_method_text'] = '银行卡';
                        break;
                    case 2:
                        $list[$k]['payment_method_text'] = '微信';
                        break;
                    case 3:
                        $list[$k]['payment_method_text'] = '支付宝';
                        break;
                    case 4:
                        $list[$k]['payment_method_text'] = 'PayPal';
                        break;
                }
            }else{
                $list[$k]['payment_method_text'] = '-';
                $list[$k]['voucher'] = '';
            }

            $dict_where['type'] = 'order_status';
            $dict_where['value'] = $v['order_status'];
            $list[$k]['order_status_text'] = db('dict') -> where($dict_where) -> value('key');
        }
        $return['count'] = $this->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p,$request->action());
        return $return;
    }

    public function orderInfo($id)
    {
        $info = db('order')->where('id',$id)->find();
        $buyer_info = db('user')->where('id',$info['buyer_id'])->find();
        $seller_info = db('user')->where('id',$info['seller_id'])->find();
        $info['buyer_name'] = $buyer_info['email'].'--'.$buyer_info['phone'];
        $info['seller_name'] = $seller_info['email'].'--'.$seller_info['phone'];
        return $info;
    }

    public function report_active($data)
    {
        $order_info = db('order')->where('id',$data['id'])->find();
        if($order_info['order_status'] != 5){
            return ['code' => 0,'msg' => '举报已取消或已处理'];
        }
        if($data['report_status'] == 1){
            $trade = new Trades();
            $do_done['data']['order_id'] = $data['id'];
            return $trade -> do_done($do_done);
        }else{
            $Index = new Indexs();
            $order_info = db('order')->where('id',$data['id'])->find();
            $Index -> pass_pay_active($order_info);
            return ['code' => 1,'msg' => '成功'];
        }
    }
}
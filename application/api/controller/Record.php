<?php
namespace app\api\Controller;
use app\common\controller\ApiBase;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;

/**
 * 财务功能
 *
 * @remark 
 */


class Record extends ApiBase
{
    public function _initialize() 
    {
        parent::_initialize();
    }

    /**
     * 财务流水页面信息
     * @param string @uid [用户ID]
     * @param string @p [页数]
     */
    public function recordListPage()
    {
        $id = trim(input('uid'));
        $transaction_type = trim(input('transaction_type'));
        $p = input('p') ? input('p'): 1;
        $start_time = input('start_time') ? input('start_time') : null;
        $end_time = input('end_time') ? input('end_time') : null;
        if($start_time == 'undefined'){
            $start_time = null;
        }
        if($end_time == 'undefined'){
            $end_time = null;
        }
        if(!$id) {
            $r = $this->rtn(-1,lang("parameter"));
        }else{
            $map = ['user_id'] == $id;
            if($transaction_type){
                $map['transaction_type'] = $transaction_type;
            }else{
                $map['transaction_type'] = array('in','3,4');
            }
            if ($start_time && $end_time) {
               $map['create_time'] = array(array('egt', strtotime($start_time)), array('elt', strtotime($end_time))
                );
            } else if ($start_time) {
               $map['create_time'] = array('egt', strtotime($start_time));
            } else if ($end_time) {
               $map['create_time'] = array('elt', strtotime($end_time));
            }
            if(false == ($data = model('Record')->recordListPage($id,$map,$p))){
                $r = $this->rtn(-1,lang("null"));
            }else{
                $r = $this->rtn(0,lang("success"),$data);
            }  
        }
        return json($r);
    }
   
}
<?php
namespace app\api\controller;
use app\common\controller\ApiBase;
use think\Session;
use think\Cookie;
use think\Request;
use think\Captcha;
use think\Db;

/**
 * 公告功能
 *
 * @remark 
 */


class News extends ApiBase
{
    public function _initialize() 
    {
        parent::_initialize();
    }


    /**
     * 公告页面信息
     * @param string @p [页数]
     */
    public function newsListPage()
    {
        $p = input('p') ? input('p'): 1;
        $page_size = $_POST['page_size']?$_POST['page_size']:20;
        if(false == ($data = model('News')->newsListPage($p,$page_size))){
            $result['winning'] = '';
            $result['list'] = '';
            $r = rtn(1,lang("success"),$result);
        }else{
          $result['winning'] = '';
          $result['list'] = $data;
          $r = rtn(1,lang("success"),$result);
        }
        return $r;
    }

    /**
     * 公告详情
     * @param string @id [公告ID]
     */
    public function newsInfo()
    {
        $id = trim(input('id'));
        if(!$id) {
            $r = rtn(0,lang("parameter"));
        }else{
            if(false == ($data = model('News')->newsInfo($id))){
              $result['winning'] = '';
              $result['info'] = '';
              $r = rtn(1,lang("success"),$result);
            }else{
              $result['winning'] = '';
              $result['info'] = $data;
              $r = rtn(1,lang("success"),$result);
            }    
        }
         return $r;
    }
   
}
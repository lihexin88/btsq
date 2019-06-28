<?php

namespace app\api\controller;

use app\api\model\Language;
use app\api\model\UserConfig;
use app\common\controller\ApiBase;
use think\Session;
use think\Request;
use think\Captcha;
use think\Db;


use app\api\model\Notice as NoticeModel;

/**
 * 公告功能
 *
 * @remark
 */
class Notice extends ApiBase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 公告页面信息
     * @param string @p [页数]
     */
    public function noticeListPage()
    {
        $p = input('p') ? input('p') : 1;
        $map['state'] = 1;
        if (false == ($data = model('Notice')->noticeListPage($map, $p))) {
            $r = $this->rtn(-1, lang("null"));
        } else {
            $r = $this->rtn(0, lang("success"), $data);
        }
        return json($r);
    }

    /**
     * 公告详情
     * @param string @id [公告ID]
     */
    public function noticeInfo()
    {
        $id = trim(input('id'));
        if (!$id) {
            $r = $this->rtn(-1, lang("parameter"));
        } else {
            if (false == ($data = model('Notice')->noticeInfo($id))) {
                $r = $this->rtn(-1, lang("null"));
            } else {
                $r = $this->rtn(0, lang("success"), $data);
            }
        }

        return json($r);
    }

    /**
     * 挖矿说明,费用等
     * $param $_POST['type'] 说明类型
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function mining_notice()
    {
        if (!$_POST['type']) {
            return rtn(0, "input type");
        }

        $key = null;
        switch ($_POST['type']) {
            case 1:
            if($this->userInfo['level'] == 0){
                $key = "mining_descript2";
            }else{
                $key = "mining_descript";
            }
                break;
            case 2:
                $key = "mining_auto_fee";
                break;
            case 3:
                $key = "vip_describe";
                break;
                /*........*/
            default:
                $key = "mining_descript";
        }
        if($_POST['type'] == 2){
            switch ($this->userInfo['level']) {
                case 0:
                    $mining_descript = lang('zidongtiqu1');
                    break;
                case 1:
                    $mining_descript = lang('zidongtiqu1');
                    break;
                case 2:
                    $mining_descript = lang('zidongtiqu2');
                    break;
                default:
                    $mining_descript = lang('zidongtiqu3');
            }
        }else{
            $value = str_replace("_","",config('THINK_VAR'));
            $mining_descript = db('notice')->where('key',$key)->value($value);  
        }

        return rtn(1, lang('success'), $mining_descript);
    }
}
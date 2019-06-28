<?php

namespace app\api\model;

use think\Model;
use think\Session;
use think\Db;

class Library extends Model
{
    // 生成二维码
    public function qrcode($id,$invitation_code)
    {
        $savePath = APP_PATH . '/../public/upload/qrcode/';
        $webPath = '/upload/qrcode/';
        $qrData = config('INVITATION_WEB').'/invitation_code/'.$invitation_code;
        $qrLevel = 'H';
        $qrSize = '8';
        $savePrefix = 'NickBai';

        if($filename = createQRcode($savePath, $qrData, $qrLevel, $qrSize, $savePrefix)){
            $pic = $webPath . $filename;
        }
        $map['id'] = $id;
        $map['invitation_img'] = $pic;
        db('user')->update($map);
        return true;
    }

}

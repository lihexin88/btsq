<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/11
 * Time: 16:26
 */

namespace app\api\model;


use think\Model;

class Feedback extends Model
{
    /**
     * @param $post 参数
     * @param $user 用户
     * @return bool
     */
    public function save_feedback($post,$user)
    {
        $feedback = $this;
        $feedback->uid = $user['id'];
        $feedback->f_type = $post['type'];
        $feedback->content = $post['content'];
        $feedback->img = $post['img_url'];
        if(!$feedback->save()){
            return false;
        }
        return true;
    }

}
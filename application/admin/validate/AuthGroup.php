<?php

namespace app\admin\validate;

use think\Validate;

class AuthGroup extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'title' =>  'require',
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'title.require'  =>  '角色组名称必须存在！',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'   =>  ['title'],
        'edit'  =>  [],
    ]; 
    
}

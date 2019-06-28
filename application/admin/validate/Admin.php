<?php

namespace app\admin\validate;

use think\Validate;

class Admin extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'username'  =>  'require|unique:admin',
        'password' =>  'require',
        'group_id'  =>  'require',
        'username'   => 'unique:admin',
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'username.require'  =>  '请输入用户名必须',
        'username.unique'  =>  '用户名已存在',
        'password.require' =>  '请输入密码',
        'group_id.require' =>  '请选择用户组',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'   =>  ['username','password','group_id'],
        'edit'  =>  [],
    ]; 
    
}

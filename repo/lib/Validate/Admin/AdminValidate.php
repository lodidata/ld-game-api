<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class AdminValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'admin_name'           => 'require|alphaNum|length:5,20',
        'role_name'            => 'require|length:2,20',
        'real_name'            => 'require|length:2,20',
        'department'           => 'require|length:2,20', // 部门
        'position'             => 'require|length:2,20', // 职位
        'role_id'              => 'require|egt:0', // 角色id >= 0
        'password'             => 'require|length:6,32',
        'password_confirm'     => 'require|length:6,32',
        'old_password'         => 'length:6,32',
        'new_password'         => 'require|length:6,32',
        'new_password_confirm' => 'require|length:6,32|confirm:new_password',
        'status'               => 'require|in:0,1',
        'type'                 => 'require|in:1,2',
    ];

    protected $field = [
        'admin_name'           => '账户名',
        'role_name'            => '角色名',
        'real_name'            => '真实姓名',
        'department'           => '部门',
        'position'             => '岗位',
        'role_id'              => '角色id',
        'password'             => '账户密码',
        'password_confirm'     => '确认账户密码',
        'old_password'         => '原账户密码',
        'new_password'         => '新账户密码',
        'new_password_confirm' => '新确认账户密码',
        'status'               => '状态',
        'type'                 => '账户类型',
    ];

    protected $message = [
        'admin_name'           => '账户名不少于5(含)个字符,不超过20(含)个字符',
        'role_name'            => '角色名不少于2(含)个字符,不超过20(含)个字符',
        'real_name'            => '真实姓名不少于2(含)个字符,不超过20(含)个字符',
        'department'           => '部门不少于2(含)个字符,不超过20(含)个字符',
        'position'             => '岗位不少于2(含)个字符,不超过20(含)个字符',
        'role_id'              => '账户必须隶属于某个角色即角色id必须大于0',
        'password'             => '账户密码不少于6(含)个字符,不超过32(含)个字符',
        'password_confirm'     => '账户确认密码不少于6(含)个字符,不超过32(含)个字符',
        'old_password'         => '账户原密码不少于6(含)个字符,不超过32(含)个字符',
        'new_password'         => '账户新密码不少于6(含)个字符,不超过32(含)个字符',
        'new_password_confirm' => '账户新确认密码不合法',
        'status'               => '账户状态不合法(0|1)',
        'type'                 => '账户类型不合法(1|2)',
    ];

    protected $scene = [
        'get'   => ['status' => 'in:0,1'],
        'post'  => ['admin_name', 'password', 'password_confirm', 'real_name', 'department', 'position', 'role_id'],
        'put'   => ['real_name', 'position', 'department', 'role_id'],
        'patch' => ['old_password', 'new_password', 'new_password_confirm', 'type'],
    ];
}
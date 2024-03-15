<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class AdminRoleValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'role_name' => 'require|length:2,20',
        'status'    => 'in:0,1',
    ];
    protected $field = [
        'role_name' => '角色名',
        'status'    => '状态',
    ];

    protected $message = [
        'role_name' => '角色名字符不少于2(含)个字符,不超过20(含)个字符',
        'status'    => '状态不合法(0|1)',
    ];

    protected $scene = [
        'get'  => ['status'],
        'post' => ['role_name'],
        'put'  => ['role_name'],
    ];
}
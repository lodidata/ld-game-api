<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class LoginValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        "token"      => "require|length:32",
        "code"       => "require|length:2,4",
        "admin_name" => "require|length:3,15",
        "password"   => "require|length:6,32",
    ];

    protected $field = [
        "token"      => "token不能为空",
        "code"       => "验证码不能为空",
        "admin_name" => "用户名非法",
        "password"   => "密码非法",
    ];

    protected $message = [
        "token"      => "token不能为空，且定长32个字符串",
        "code"       => "验证码不合法，且长度为2-4个数字",
        "admin_name" => "账户名不能为空，且长度为3-15个字符",
        "password"   => "密码非法，且其长度为6-32个字符",

    ];

    protected $scene = [
        'post' => ['token', 'code', 'admin_name', 'password'],
    ];
}
<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class WhiteListValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'agent_code' => 'require|alphaNum|length:4',
        'ip'         => 'require|ip',
    ];

    protected $field = [
        'agent_code' => '代理编号',
        'ip'         => 'ip地址',
    ];

    protected $message = [
        'agent_code' => '代理号不合法，其仅为字母数字且定长4个字符',
        'ip'         => 'ip地址不合法',
    ];

    protected $scene = [
        'get'  => ['agent_code', 'ip' => 'ip'],
        'post' => ['agent_code', 'ip'],
    ];
}
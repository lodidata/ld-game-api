<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class AgentGameValidate extends BaseValidate
{   // 验证规则
    protected $rule = [
        'agent_code'    => 'require|alphaNum|length:4',
        'menu_type'     => 'require|length:1,50',
        'agent_account' => 'require|length:2,100',
        'password'      => 'require|length:0,200',
        'admin_url'     => 'require|length:0,200',
        'api_agent'     => 'require|length:0,150',
        'api_key'       => 'require|length:0,500',
        'status'        => 'require|in:0,1',
    ];

    protected $field = [
        'agent_code'    => '代理编号',
        'menu_type'     => '厂商名称',
        'agent_account' => '后台账户',
        'password'      => '后台密码',
        'admin_url'     => '后台管理地址',
        'api_agent'     => '第三方游戏代理账户',
        'api_key'       => '第三方游戏代理密钥',
        'status'        => '状态',
    ];

    protected $message = [
        'agent_code'    => '代理号不合法，其仅为字母数字且定长4个字符',
        'menu_type'     => '厂商名称不合法，其不少于1(含)个字符,不超过50(含)个字符',
        'agent_account' => '代理账户不合法，其不少于2(含)个字符,不超过100(含)个字符',
        'password'      => '代理账户密码不合法，其不少于0(含)个字符,不超过200(含)个字符',
        'admin_url'     => '代理后台管理地址不合法，其不少于0(含)个字符,不超过200(含)个字符',
        'api_agent'     => '第三方游戏代理账户不合法，其不少于0(含)个字符,不超过150(含)个字符',
        'api_key'       => '第三方游戏代理密钥不合法，其不少于0(含)个字符,不超过500(含)个字符',
        'status'        => '代理货币状态不合法（0|1）',
    ];

    protected $scene = [
        'get'        => ['agent_code'],
        'post'       => ['agent_code', 'menu_type', 'agent_account', 'password', 'admin_url', 'api_agent', 'api_key', 'status'],
        'put'        => ['agent_account', 'password', 'admin_url', 'api_agent', 'api_key', 'status'],
        'select_get' => ['agent_code'],
        'input_get'  => ['agent_code', 'menu_type']
    ];
}
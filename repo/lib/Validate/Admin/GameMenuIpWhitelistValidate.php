<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class GameMenuIpWhitelistValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'menu_type' => 'require',
        'ip'        => 'require|ip',
    ];

    protected $field = [
        'game_menu' => '游戏厂商',
        'ip'        => 'ip地址',
    ];

    protected $message = [
        'menu_type' => '游戏厂商不合法',
        'ip'        => 'ip地址不合法',
    ];

    protected $scene = [
        'get'  => ['menu_type', 'ip' => 'ip'],
        'post' => ['menu_type', 'ip'],
    ];
}
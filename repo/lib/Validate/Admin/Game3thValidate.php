<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class Game3thValidate extends BaseValidate
{   // 验证规则
    protected $rule = [
        'menu_type'        => 'length:0,20',
        'game_type'        => 'length:0,20',
        'game_name'        => 'length:0,20',
        'status'           => 'in:0,1',
        'start_created_at' => 'date',
        'end_created_at'   => 'date',
    ];

    protected $field = [
        "menu_type"        => "厂商类型",
        "game_type"        => "游戏类型",
        "game_name"        => "游戏名称",
        "status"           => "状态",
        "start_created_at" => "上架开始时间",
        "end_created_at"   => "上架结束时间",
    ];

    protected $message = [
        'menu_type'        => '厂商类型不合法,其不少于0(含)个字符,不超过20(含)个字符',
        'game_type'        => '游戏类型不合法,其不少于0(含)个字符,不超过20(含)个字符',
        'game_name'        => '游戏名称不合法,其仅为字母数字且不少于0(含)个字符,不超过20(含)个字符',
        'status'           => '状态不合法(0|1)',
        'start_created_at' => '上架开始时间必须是日期',
        'end_created_at'   => '上架结束时间必须是日期',
    ];

    protected $scene = [
        'get' => ['status', 'start_created_at', 'end_created_at'],
    ];
}
<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class GameMenuValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'menu_type'        => 'alphaNum|length:2,20',
        'menu_name'        => 'length:2,20',
        'status'           => 'in:0,1',
        'start_created_at' => 'date',
        'end_created_at'   => 'date',
        "start_uworked_at" => "date",
        "end_uworked_at"   => "date",
        "work_status"      => "require|in:0,1",
    ];

    protected $field = [
        "menu_type"        => "厂商标识",
        "menu_name"        => "厂商名称",
        "status"           => "状态",
        "start_created_at" => "上架开始时间",
        "end_created_at"   => "上架结束时间",
        "start_uworked_at" => "维护开始时间",
        "end_uworked_at"   => "维护结束时间",
        "work_status"      => "工作状态",
    ];

    protected $message = [
        'menu_type'        => '厂商标识不合法,其不少于2(含)个字符,不超过20(含)个字符',
        'menu_name'        => '厂商名称不合法,其不少于2(含)个字符,不超过20(含)个字符',
        'status'           => '状态不合法(0|1)',
        'start_created_at' => '上架开始时间必须是日期',
        'end_created_at'   => '上架结束时间必须是日期',
        'start_uworked_at' => '维护开始时间必须是日期',
        'end_uworked_at'   => '维护结束时间必须是日期',
        'work_status'      => '工作状态取值0、1',
    ];

    protected $scene = [
        'get' => ['status', 'start_created_at', 'end_created_at'],
        'put' => ['start_uworked_at', 'end_uworked_at', 'work_status']
    ];
}
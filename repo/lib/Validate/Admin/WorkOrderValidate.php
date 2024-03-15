<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class WorkOrderValidate extends BaseValidate
{   // 验证规则
    protected $rule = [
        'order_id'         => 'integer',
        'title'            => 'length:0,50',
        'creator'          => 'length:0,10',
        'status'           => 'in:1,2,3,4,5',
        'created_at_from'  => 'date',
        'created_at_to'    => 'date|egt:created_at_from',
        'finished_at_from' => 'date',
        'finished_at_to'   => 'date|egt:finished_at_from',
    ];

    protected $field = [
        "order_id"         => "工单ID",
        "title"            => "工单标题",
        "creator"          => "创建工单的账户",
        "status"           => "状态",
        "created_at_from"  => "工单创建(起)时间",
        "created_at_to"    => "工单创建(止)时间",
        "finished_at_from" => "工单完成(起)时间",
        "finished_at_to"   => "工单完成(止)时间",
    ];

    protected $message = [
        'order_id'         => '工单ID不合法',
        'title'            => '工单标题不合法,其不少于0(含)个字符,不超过50(含)个字符',
        'creator'          => '创建工单的账户不合法,其仅为字母数字且不少于0(含)个字符,不超过20(含)个字符',
        'status'           => '状态不合法',
        'created_at_from'  => '工单创建(起)时间必须是日期',
        'created_at_to'    => '工单创建(止)时间必须是日期，且必须大于或等于工单创建(起)时间',
        'finished_at_from' => '工单完成(起)时间必须是日期',
        'finished_at_to'   => '工单完成(止)时间必须是日期，且必须大于或等于工单完成(起)时间',
    ];

    protected $scene = [
        'get'   => ['status', 'created_at_from', 'created_at_to', 'finished_at_from', 'finished_at_to'],
        'post'  => ['title' => 'require|length:1,50'],
        'patch' => ['status' => 'require|in:1,2,3,4,5'],
    ];
}
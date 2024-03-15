<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class WorkOrderRecordValidate extends BaseValidate
{   // 验证规则
    protected $rule = [
        'work_order_id' => 'require|integer',
    ];

    protected $field = [
        "work_order_id" => "工单ID",
    ];

    protected $message = [
        'work_order_id' => '工单ID不合法',
    ];

    protected $scene = [
        'post' => ['work_order_id'],
    ];
}
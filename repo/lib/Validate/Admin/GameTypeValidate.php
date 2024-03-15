<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class GameTypeValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'type_code' => 'require|alphaNum|length:1,10',
        'type_name' => 'require|length:2,10',
        'status'    => 'require|in:0,1',
    ];

    protected $field = [
        'type_code' => '类型编号',
        'type_name' => '类型名称',
        'status'    => '状态',
    ];

    protected $message = [
        'type_code' => '类型编号不合法，其不少于1(含)个字符,不超过10(含)个字符',
        'type_name' => '支付货币不合法，其不少于2(含)个字符,不超过10(含)个字符',
        'status'    => '状态不合法(0|1)',
    ];

    protected $scene = [
        'get'  => [
            'status' => 'in:0,1'
        ],
        'post' => ['type_code', 'type_name'],
        'put'  => ['type_code', 'type_name'],
    ];
}
<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class CurrencyValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'currency_type'   => 'require|alphaNum|length:3',
        'currency_name'   => 'require|length:2,20',
        'status'          => 'require|in:0,1',
        'updated_at_from' => 'require|date',
        'updated_at_to'   => 'require|date',
    ];

    protected $field = [
        'currency_type'   => '货币类型',
        'currency_name'   => '货币名称',
        'updated_at_from' => '维护起时间',
        'updated_at_to'   => '维护止时间',
    ];

    protected $message = [
        'currency_type'   => '货币类型不合法，长度为3个字符',
        'currency_name'   => '支付货币不合法，其不少于2(含)个字符,不超过20(含)个字符',
        'updated_at_from' => '维护(起)时间不合法',
        'updated_at_to'   => '维护(止)时间不合法',
    ];

    protected $scene = [
        'get'  => [
            'updated_at_from' => 'date',
            'updated_at_to'   => 'date',
            'status'          => 'in:0,1',
        ],
        'post' => ['currency_type', 'currency_name'],
        'put'  => ['currency_type', 'currency_name'],
    ];
}
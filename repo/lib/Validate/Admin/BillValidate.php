<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class BillValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'agent_code'      => 'require|alphaNum|length:4',
        'brand_name'      => 'require|length:1,20',
        'menu_type'       => 'require|length:1,50',
        'start_bill_date' => 'date',
        'end_bill_date'   => 'date|gt:start_bill_date',
        'currency_id'     => 'require|integer',
        'valid_bet'       => 'require|float|egt:0',
        'win_lose_bet'    => 'require|float|egt:0',
        'rate'            => 'require|float',
        'exchange_rate'   => 'require|float',
        'settlement'      => 'require|float',
        'file'            => 'file',
    ];

    protected $field = [
        'agent_code'      => '代理号',
        'brand_name'      => '品牌名称',
        'menu_type'       => '厂商名称',
        'start_bill_date' => '账单(起)日期',
        'end_bill_date'   => '账单(止)日期',
        'currency_id'     => '货币类型',
        'valid_bet'       => '有效投注',
        'win_lose_bet'    => '输赢',
        'rate'            => '费率',
        'exchange_rate'   => '汇率',
        'settlement'      => '交收金额',
        'file'            => '文件',
    ];

    protected $message = [
        'agent_code'      => '代理号不能为空',
        'brand_name'      => '品牌名称不少于1(含)个字符,不超过20(含)个字符',
        'menu_type'       => '厂商名称不合法，其不少于1(含)个字符,不超过50(含)个字符',
        'start_bill_date' => '账单(起)日期不合法',
        'end_bill_date'   => '账单(止)日期不合法，且必须大于账单(起)日期',
        'currency_id'     => '货币类型不合法',
        'valid_bet'       => '有效投注类型不合法',
        'win_lose_bet'    => '输赢类型不合法',
        'rate'            => '费率类型不合法',
        'exchange_rate'   => '汇率类型不合法',
        'settlement'      => '交收金额类型不合法',
        'file'            => '文件必传',
    ];

    protected $scene = [
        'get'         => [
            'start_bill_date' => 'date',
            'end_bill_date'   => 'date|egt:start_bill_date',
            'currency_id'     => 'integer',
            'rate'            => 'float',
            'exchange_rate'   => 'float'
        ],
        'post'        => ['agent_code', 'brand_name', 'menu_type', 'start_bill_date', 'end_bill_date', 'currency_id', 'valid_bet', 'win_lose_bet', 'rate', 'exchange_rate', 'settlement'],
        'put'         => ['start_bill_date', 'end_bill_date', 'valid_bet', 'win_lose_bet', 'rate', 'exchange_rate', 'settlement'],
        'patch'       => ['win_lose_bet', 'rate', 'exchange_rate'],
        'report_post' => ['file'],
        'report_get'  => [
            'agent_code'    => 'alphaNum|length:4',
            'brand_name'    => 'length:2,20',
            'menu_type'     => 'length:1,50',
            'currency_id'   => 'integer',
            'start_bill_date',
            'end_bill_date',
            'rate'          => 'float',
            'exchange_rate' => 'float'
        ]
    ];
}
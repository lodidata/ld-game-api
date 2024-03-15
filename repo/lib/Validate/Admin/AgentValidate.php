<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class AgentValidate extends BaseValidate
{  // 验证规则
    protected $rule = [
        'brand_name'        => 'require|length:2,20',
        'currency_id'       => 'require|integer',
        'secret_key'        => 'length:64', // 定长密钥
        'site_url'          => 'length:4,100', // 代理平台|网站地址
        'callback_url'      => 'length:4,200', // 回调url
        'is_allow_login'    => 'require|in:0,1',
        'is_allow_transfer' => 'require|in:0,1',
        'is_allow_order'    => 'require|in:0,1',
        'is_limit_recharge' => 'require|in:0,1',
        'status'            => 'require|in:0,1',
        'wallet_type'       => 'require|in:0,1',
        'bill_date'         => 'date',
    ];

    protected $message = [
        'brand_name'        => '品牌名称不能为空且不少于2(含)个字符,不超过20(含)个字符',
        'currency_id'       => '代理货币不合法',
        'secret_key'        => '密钥不能为空，且定长(64个字符)',
        'site_url'          => '官网|平台地址不能为空，且不少于4(含)个字符,不超过100(含)个字符',
        'callback_url'      => '回调API地址不能为空，且不少于4(含)个字符,不超过200(含)个字符',
        'is_allow_login'    => '是否允许注册的开关非法(0|1)',
        'is_allow_transfer' => '是否允许转账的开关非法(0|1)',
        'is_allow_order'    => '是否允许拉单的开关非法(0|1)',
        'is_limit_recharge' => '是否开启限制充值金额的开关非法(0|1)',
        'status'            => '状态开关非法(0|1)',
        'wallet_type'       => '钱包类型非法',
        'bill_date'         => '账单日期非法(Y-m-d)',
    ];

    protected $field = [
        'brand_name'        => '品牌名称',
        'currency_id'       => '货币id',
        'secret_key'        => '密钥',
        'site_url'          => '官网|平台地址',
        'callback_url'      => '回调API地址',
        'is_allow_login'    => '是否允许注册登录',
        'is_allow_transfer' => '是否允许转账',
        'is_allow_order'    => '是否允许拉单',
        'is_limit_recharge' => '是否开启限制充值金额',
        'status'            => '状态',
        'wallet_type'       => '钱包类型',
        'bill_date'         => '账单日期',
    ];

    protected $scene = [
        'get'  => ['opening_date_from', 'opening_date_to'],
        'post' => ['brand_name', 'is_allow_login', 'is_allow_transfer', 'is_allow_order', 'is_limit_recharge', 'status', 'currency_id', 'bill_date', 'secret_key', 'site_url', 'callback_url','wallet_type'],
        'put'  => ['brand_name', 'is_allow_login', 'is_allow_transfer', 'is_allow_order', 'is_limit_recharge', 'status', 'currency_id', 'bill_date', 'secret_key', 'site_url', 'callback_url','wallet_type'],
    ];
}
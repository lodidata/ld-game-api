<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class AdminLogValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'module'      => 'length:3,50', // 模块名
        'method'      => 'integer|in:1,2,3,4,5', //请求|方法 标识 1：GET 2：POST 3：PUT 4：PATCH 5：DELETE
        'operator_id' => 'integer', // 操作人id
        'operator'    => 'length:0,20', // 操作人,模糊搜索字段
        'status'      => 'in:0,1', // 状态
    ];

    protected $field = [
        'module'      => '模块名',
        'method'      => '请求类型名',
        'operator_id' => '操作员ID',
        'operator'    => '操作员名称',
        'status'      => '状态',
    ];

    protected $message = [
        'module'      => '模块名不少于3(含)个字符,不超过50(含)个字符',
        'method'      => '请求类型不合法',
        'operator_id' => '操作员id不合法',
        'operator'    => '操作员名称不少于0(含)个字符,不超过20(含)个字符', // 支持模糊搜索
        'status'      => '账户状态不合法(0|1)',
    ];
    
    protected $scene = [
        'get' => ['module', 'method', 'operator_id', 'operator', 'status']
    ];
}
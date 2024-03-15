<?php

namespace Lib\Validate\Admin;

use Lib\Validate\BaseValidate;

class DownloadValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        'name' => 'require',
    ];

    protected $field = [
        'name' => '名字',
    ];

    protected $message = [
        'name' => '名字不能为空',
    ];

    protected $scene = [
        'get' => ['name']
    ];
}
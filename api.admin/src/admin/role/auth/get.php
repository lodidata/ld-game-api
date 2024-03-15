<?php

use Logic\Admin\BaseController;
use Model\Common\AdminRoleAuthModel;

return new class() extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run(): array
    {
        return AdminRoleAuthModel::query()->where( 'pid', 0 )->where( 'status', AdminRoleAuthModel::STATUS_ON )->get( ['id', 'auth_name',] )->toArray();
    }
};

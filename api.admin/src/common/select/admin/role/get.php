<?php

use Logic\Admin\BaseController;
use Model\Common\AdminRoleModel;
use Lib\Validate\Admin\AdminRoleValidate;

return new class() extends BaseController {
    public function run(): array
    {
        return AdminRoleModel::query()->get(['id','role_name'])->toArray();
    }
};
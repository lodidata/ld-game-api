<?php

use Logic\Admin\BaseController;
use Model\Common\AdminRoleModel;
use Model\Common\AdminRoleAuthModel;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0): array
    {
        // 不能通过$this->checkID($id); 校验id，如编辑、新增角色权限时会需要一颗空'树',所以不能校验id大于 0
        if (!is_numeric( $id ))
            return $this->lang->set( 131 );
        // 用户角色权限表
        $adminRoleAuths = [];
        $adminRoleAuths = AdminRoleAuthModel::query()->where( 'status', 1 )->orderBy( 'sort', 'Asc' )->get( ['id', 'pid', 'auth_name AS title'] )->toArray();
        // 角色表
        $adminRoleAuthIdsStr = '';
        if ($id > 0)
            $adminRoleAuthIdsStr = AdminRoleModel::query()->where( 'id', $id )->value( 'auth' );
        // 格式化角色权限
        $adminRoleAuths = \Utils\PHPTree::makeTree( $adminRoleAuths, [], explode( ',', $adminRoleAuthIdsStr ) );

        return ['tree' => $adminRoleAuths];
    }
};
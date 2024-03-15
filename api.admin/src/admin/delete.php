<?php

use Model\DB;
use Model\Common\AdminModel;
use Model\Common\AdminRoleModel;
use Logic\Admin\AdminToken;
use Logic\Admin\BaseController;
use Model\Common\AdminRoleRelationModel;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 校验id是否合法
        $this->checkID( $id );
        // 获取日志所需的记录表
        $adminTable = (new AdminModel())->getTable();
        $adminRoleRelationTable = (new AdminRoleRelationModel())->getTable();
        // 获取管理员详情
        $adminModel = AdminModel::query()->where( 'id', $id )->first();
        if (!$adminModel)
            return $this->lang->set( 9 );
        // 检查账户角色关系
        $checkAdminRoleRelation = AdminRoleRelationModel::query()->where( 'admin_id', $id )->first(); // 一对一的关系

        DB::pdo()->beginTransaction();
        try {
            // 删除账户
            $delRes = $adminModel->delete();
            if (!$delRes)
                throw new DateException( 141 );
            // 更新角色admin_role.num
            $decrRoleNumRes = AdminRoleModel::query()->where( 'id', $checkAdminRoleRelation->role_id )->decrement( 'num' ); // -= 1
            if (!$decrRoleNumRes)
                throw new DateException( 155 );
            // 写账户删除日志
            $this->writeAdminLog( $adminModel->toArray(), $adminTable, $id, 1 ); 

            // 删除账户角色关系记录
            if ($checkAdminRoleRelation) {
                $delRelationRes = AdminRoleRelationModel::query()->where( 'admin_id', $id )->delete();
                if (!$delRelationRes)
                    throw new DateException( 142 );
                // 写账户角色关系日志
                $this->writeAdminLog( $checkAdminRoleRelation->toArray(), $adminRoleRelationTable, $checkAdminRoleRelation->id, 1 ); 
            }
            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $adminModel->toArray(), $adminTable, $id, 0 );
            $this->writeAdminLog( $checkAdminRoleRelation->toArray(), $adminRoleRelationTable, $checkAdminRoleRelation->id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        // 删除token
        (new AdminToken( $this->ci ))->remove( $id );
        // 响应数据
        return $this->lang->set( 0 );
    }
};
<?php

use Model\DB;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AdminValidate;
use Model\Common\AdminRoleRelationModel;
use Model\Common\AdminModel;
use Model\Common\AdminRoleModel;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 批量验证参数
        $this->checkID( $id );
        // 批量验证请求参数
        (new AdminValidate())->paramsCheck( 'put', $this->request, $this->response );
        // 获取请求参数并格式化
        $adminTable             = (new AdminModel())->getTable();
        $adminRoleRelationTable = (new AdminRoleRelationModel())->getTable();
        $params                 = $this->request->getParams(); // 获取全局请求参数
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }
        // 检查记录是否存在
        $adminModel = AdminModel::query()->where( 'id', $id )->first();
        if (!$adminModel)
            return $this->lang->set( 126 );
        // 检查角色
        $checkAdminRole = AdminRoleModel::query()->where( 'id', (int)$params['role_id'] )->first();
        if (!$checkAdminRole)
            return $this->lang->set( 211 );
        // 检查账户角色关系
        $checkAdminRoleR     = AdminRoleRelationModel::query()->where( 'admin_id', $id )->first(); // 一个账户一个角色
        $adminRoleRelationId = $checkAdminRoleR->id ?? 0; // 账户角色权限关系id
        // 账户角色权限关系数据
        $adminRoleRelation = ['admin_id' => $id, 'role_id' => $params['role_id'] ?? 0];

        DB::pdo()->beginTransaction();
        try {
            /*更新账户*/
            $flag = 0;
            if (isset( $params['real_name'] ) && $params['real_name'] !== $adminModel->real_name) {
                $adminModel->real_name = $params['real_name'];
                // 异动标识
                $flag += 1;
            }
            if (isset( $params['position'] ) && $params['position'] !== $adminModel->position) {
                $adminModel->position = $params['position'];
                // 异动标识
                $flag += 1;
            }
            if (isset( $params['department'] ) && $adminModel->department !== $params['department']) {
                $adminModel->department = $params['department'];
                // 异动标识
                $flag += 1;
            }
            if ($flag > 0) {
                $res = $adminModel->save();
                if (!$res)
                    throw new DateException( 139 );
                // 更新账户写日志
                $this->writeAdminLog( $adminModel->toArray(), $adminTable, $id, 1 );
            }

            /*更新账户角色关系*/
            if (!$checkAdminRoleR) { // 新增
                $insertAdminRoleRelationRes = AdminRoleRelationModel::query()->insertGetId( ['admin_id' => $id, 'role_id' => $params['role_id']] );
                if (!$insertAdminRoleRelationRes)
                    throw new DateException( 140 );
                // 更新角色admin_role.num
                $incrementAdminRoleNumRes = AdminRoleModel::query()->where( 'id', $params['role_id'] )->increment( 'num' ); // += 1
                if (!$incrementAdminRoleNumRes)
                    throw new DateException( 209 );
                $adminRoleRelationId = $insertAdminRoleRelationRes; // insertGetId()接口返回账户角色权限关系id
            } else { // 账户角色关系记录存在则更新
                if ($checkAdminRoleR->role_id != $params['role_id']) {
                    $updateAdminRoleRelationRes = AdminRoleRelationModel::query()->where( 'id', $checkAdminRoleR->id )->update( ['admin_id' => $id, 'role_id' => $params['role_id']] );
                    if (!$updateAdminRoleRelationRes)
                        throw new DateException( 145 );
                    $decrementAdminRoleNumRes = AdminRoleModel::query()->where( 'id', $checkAdminRoleR->role_id )->decrement( 'num' ); // -= 1
                    if (!$decrementAdminRoleNumRes)
                        throw new DateException( 209 );
                    // 更新角色admin_role.num
                    $incrementAdminRoleNumRes = AdminRoleModel::query()->where( 'id', $params['role_id'] )->increment( 'num' ); // += 1
                    if (!$incrementAdminRoleNumRes)
                        throw new DateException( 209 );
                }

                // 若表单中的账户id的角色id与表中的账户id的角色id相同则不用更新
            }
            // 更新账户角色关系写日志
            $this->writeAdminLog( $adminRoleRelation, $adminRoleRelationTable, $adminRoleRelationId, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            // 更新账户时写日志
            $this->writeAdminLog( $adminModel->toArray(), $adminTable, $id, 0 );
            // 更新账户时角色关系写日志
            $this->writeAdminLog( $adminRoleRelation, $adminRoleRelationTable, $adminRoleRelationId, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
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

    public function run()
    {
        // 批量验证参数
        (new AdminValidate())->paramsCheck( 'post', $this->request, $this->response );
        // 获取请求参数并格式化
        $adminTable             = (new AdminModel())->getTable();
        $adminRoleRelationTable = (new AdminRoleRelationModel())->getTable();
        $params                 = $this->request->getParams(); // 获取全局请求参数
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }
        // 校验密码是否一直
        if ($params['password'] !== $params['password_confirm'])
            return $this->lang->set( 124 );
        // 检查管理员账户是否已存在
        $checkAdminName = AdminModel::query()->where( 'admin_name', $params['admin_name'] )->exists();
        if ($checkAdminName)
            return $this->lang->set( 137 );
        // 封装数据
        unset( $params['password_confirm'] );
        $params['password']     = password_hash( $params['password'], PASSWORD_DEFAULT );
        $params['creator_id']   =$this->playLoad['admin_id'];
        $params['creator_name'] = $this->playLoad['admin_name'];
        $adminRoleRelationData  = ['admin_id' => 0, 'role_id' => $params['role_id']];

        DB::pdo()->beginTransaction();
        try {
            // 更新记录
            unset( $params['role_id'] );
            $adminRoleRelationData['admin_id'] = AdminModel::query()->insertGetId( $params );
            if (!$adminRoleRelationData['admin_id'])
                throw new DateException( 132 );
            // 新增账户写日志
            $this->writeAdminLog( $params, $adminTable, $adminRoleRelationData['admin_id'], 1 );

            // 更新角色
            if (!empty( $adminRoleRelationData['role_id'] )) {
                $checkR = AdminRoleRelationModel::query()->where( 'admin_id', $adminRoleRelationData['admin_id'] )->where( 'role_id', $adminRoleRelationData['role_id'] )->exists();
                if (!$checkR) {
                    $adminRoleRelationId = AdminRoleRelationModel::query()->insertGetId( $adminRoleRelationData );
                    if (!$adminRoleRelationId)
                        throw new DateException( 140 );
                    // 更新角色admin_role.num
                    $incrRoleNumRes = AdminRoleModel::query()->where( 'id', $adminRoleRelationData['role_id'] )->increment( 'num' ); // += 1
                    if (!$incrRoleNumRes)
                        throw new DateException( 155 );
                    $this->writeAdminLog( $adminRoleRelationData, $adminRoleRelationTable, $adminRoleRelationId, 1 );
                }
            }

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( [], $adminTable, 0, 0 );
            $this->writeAdminLog( [], $adminRoleRelationTable, 0, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
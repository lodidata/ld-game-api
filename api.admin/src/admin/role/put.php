<?php

use Model\DB;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AdminRoleValidate;
use Model\Common\AdminRoleModel;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID( $id );
        // 验证请求参数
        (new AdminRoleValidate())->paramsCheck( 'put', $this->request, $this->response );
        // 校验id并获取表明
        $table  = (new AdminRoleModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }

        // 检查该记录是否存在
        $adminRoleObj = AdminRoleModel::query()->where( 'id', $id )->first();
        if (!$adminRoleObj)
            return $this->lang->set( 126 );
        // 检查数据是否发生改变
        $checkChange = $this->checkParamsChange( $adminRoleObj, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        // 封装请求参数
        $adminRoleObj->role_name   = $params['role_name'] ?? '';
        $adminRoleObj->auth        = $params['auth'] ?? '';
        $adminRoleObj->operator_id =$this->playLoad['admin_id'];
        $adminRoleObj->operator    = $this->playLoad['admin_name'];

        DB::pdo()->beginTransaction();
        try {
            // 更新记录
            $res = $adminRoleObj->save();
            if (!$res)
                throw new DateException( 139 );
            $this->writeAdminLog( $adminRoleObj->toArray(), $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $adminRoleObj->toArray(), $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        // 响应
        return $this->lang->set( 0 );
    }
};
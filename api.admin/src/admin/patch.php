<?php

use Model\DB;
use Model\Common\AdminModel;
use Logic\Admin\AdminToken;
use Logic\Admin\BaseController;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 校验id是否合法
        $this->checkID( $id );
        // 获取写日志所需的表名
        $table = (new AdminModel())->getTable();
        // 获取或校验管理员
        $adminModel = AdminModel::query()->where( 'id', $id )->first();
        if (!$adminModel)
            return $this->lang->set( 9 );
        // 校验并更新状态
        $params['status'] = $adminModel->status === AdminModel::STATUS_ON ? AdminModel::STATUS_OFF : AdminModel::STATUS_ON;
        $checkChange      = $this->checkParamsChange( $adminModel, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        $adminModel->status = $params['status']; // 状态开关

        DB::pdo()->beginTransaction();
        try {
            // 更新
            $res = $adminModel->save();
            if (!$res)
                throw new DateException( 139 );
            $this->writeAdminLog( $adminModel->toArray(), $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $adminModel->toArray(), $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }
        // 更新状态成功且修改状态为禁用后移除token
        if ($adminModel->status === AdminModel::STATUS_OFF)
            (new AdminToken( $this->ci ))->remove( $id );

        return $this->lang->set( 0 );
    }
};
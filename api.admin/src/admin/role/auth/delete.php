<?php

use Model\DB;
use Model\Common\AdminRoleAuthModel;
use Logic\Admin\BaseController;
use Respect\Validation\Exceptions\DateException;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    /**
     * TODO 弃用额接口
     *
     * @param int $id
     * @return mixed
     */
    public function run(int $id = 0)
    {
        // 检查id是否合法
        $this->checkID( $id );
        // 检查记录是否存在
        $table    = (new AdminRoleAuthModel())->getTable();
        $checkRes = AdminRoleAuthModel::query()->where( 'id', $id )->first();
        if (!$checkRes)
            return $this->lang->set( 126 );

        DB::pdo()->beginTransaction();
        try {
            // 删除
            $delRes = AdminRoleAuthModel::query()->where( 'id', $id )->delete();
            if (!$delRes)
                throw new DateException( 123 );
            $this->writeAdminLog( $checkRes->toArray(), $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $checkRes->toArray(), $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        // 响应数据
        return $this->lang->set( 0 );
    }
};

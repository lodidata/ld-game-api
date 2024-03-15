<?php

use Model\DB;
use Model\Common\AdminRoleAuthModel;
use Logic\Admin\BaseController;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
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
        // 获取请求参数
        $table  = (new AdminRoleAuthModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }
        // 检查该记录是否存在
        $adminRoleAuthObj = AdminRoleAuthModel::query()->where( 'id', $id )->first();
        if (!$adminRoleAuthObj)
            return $this->lang->set( 126 );
        // 检查数据是否发生改变
        $checkChange = $this->checkParamsChange( $adminRoleAuthObj, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );

        DB::pdo()->beginTransaction();
        try {
            // 更新记录
            $res = AdminRoleAuthModel::query()->where( 'id', $id )->update( $params );
            if (!$res)
                throw new DateException( 139 );
            $this->writeAdminLog( $params, $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $params, $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
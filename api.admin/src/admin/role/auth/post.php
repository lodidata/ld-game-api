<?php

use Model\DB;
use Logic\Admin\BaseController;
use Model\Common\AdminRoleAuthModel;
use Respect\Validation\Exceptions\DateException;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    /**
     * TODO 弃用额接口
     *
     * @return mixed
     */
    public function run()
    {
        // 请求参数的集合(array)
        $table  = (new AdminRoleAuthModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'method' && !empty( $params['method'] )) $params['method'] = strtoupper( $params[$key] );
        }

        DB::pdo()->beginTransaction();
        try {
            // 更新记录
            $id = AdminRoleAuthModel::query()->insertGetId( $params );
            if (!$id)
                throw new DateException( 132 );
            $this->writeAdminLog( $params, $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( [], $table, 0, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
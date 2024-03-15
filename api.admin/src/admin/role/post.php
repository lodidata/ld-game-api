<?php

use Model\DB;
use Model\Common\AdminRoleModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AdminRoleValidate;
use Respect\Validation\Exceptions\DateException;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 校验请求参数
        (new AdminRoleValidate())->paramsCheck( 'post', $this->request, $this->response );
        $table  = (new AdminRoleModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }
        // 检查角色名称是否已存在
        $checkRoleRes = AdminRoleModel::query()->where( 'role_name', $params['role_name'] )->exists();
        if ($checkRoleRes)
            return $this->lang->set( 127 );
        // 封装数据
        $data = [
            'auth'        => $params['auth'] ?? '', //权限列表,用逗号(,)隔开
            'role_name'   => $params['role_name'] ?? '', // 菜单名称
            'operator_id' => $this->playLoad['admin_id'],
            'operator'    => $this->playLoad['admin_name'],
        ];

        DB::pdo()->beginTransaction();
        try {
            // 新增货游戏类型
            $id = AdminRoleModel::query()->insertGetId( $data );
            if (!$id)
                throw new DateException( 132 );
            $this->writeAdminLog( $data, $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( [], $table, 0, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
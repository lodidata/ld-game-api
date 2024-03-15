<?php

use Model\Common\AdminModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AdminValidate;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 校验请求参数
        (new AdminValidate())->paramsCheck( 'get', $this->request, $this->response );
        // 批量获取所有的请求参数并对分页信息进行初始化
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'page' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 查询条件封装
        $adminObj = AdminModel::query()
            ->leftJoin( 'admin_role_relation', 'admin.id', 'admin_role_relation.admin_id' )
            ->leftJoin( 'admin_role', 'admin_role.id', 'admin_role_relation.role_id' )
            ->where( 'admin.id', '<>', AdminModel::SUPER_ADMIN_ID );
        !empty( $params['admin_name'] ) && $adminObj->where( 'admin.admin_name', 'like', $params['admin_name'] . '%' ); // 账户名模糊查询
        !empty( $params['role_name'] ) && $adminObj->where( 'admin_role.role_name', 'like', $params['role_name'] . '%' ); // 角色名模糊查询
        isset( $params['status'] ) && is_numeric( $params['status'] ) && in_array( $params['status'], array_keys( AdminModel::STATUS_ARR ) ) && $adminObj->where( 'admin.status', $params['status'] ); // 账户状态查询
        // 统计总的记录数
        $common['total'] = $adminObj->count() ?? 0;
        // 获取分页列表并格式化列表
        $adminList = $adminObj->orderBy( 'created_at', 'desc' )->forpage( $common['page'], $common['page_size'] )->get( ['admin.*', 'admin_role.id AS role_id', 'admin_role.role_name'] )->toArray();
        foreach ($adminList as &$val) {
            $val['status_str'] = AdminModel::STATUS_ARR[$val['status']] ?? '未知';
        }

        return $this->lang->set( 0, [], $adminList, $common );
    }
};
<?php

use Logic\Admin\BaseController;
use Model\Common\AdminRoleModel;
use Lib\Validate\Admin\AdminRoleValidate;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 获取请求参数
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'page' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数封装
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 查询
        $adminRoleObj = AdminRoleModel::query();
        !empty( $params['role_name'] ) && $adminRoleObj->where( 'role_name', trim( $params['role_name'] ) );
        // 总记录数
        $common['total'] = $adminRoleObj->count() ?? 0;
        // 获取排序分页列表
        $adminRoleList = $adminRoleObj->orderBy( 'created_at', 'desc' )->forPage( $common['page'], $common['page_size'] )->get()->toArray();

        return $this->lang->set( 0, [], $adminRoleList, $common );
    }
};
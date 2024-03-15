<?php

use Model\DB;
use Logic\Admin\BaseController;
use Logic\Admin\AdminToken;
use Model\Common\AdminModel;
use Lib\Validate\Admin\AdminValidate;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID( $id );
        // 批量验证请求参数
        (new AdminValidate())->paramsCheck( 'patch', $this->request, $this->response );
        // 批量获取请求参数
        $password = '';
        $table    = (new AdminModel())->getTable();
        $params   = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }
        // 获取管理员的对象
        $adminObj = AdminModel::query()->where( 'id', $id )->first();
        if (!$adminObj)
            return $this->lang->set( 9 );
        // 校验用户原密码

        if (!empty( $params['type'] ) && $params['type'] == 1) {
            if (empty($params['old_password']))
                return $this->lang->set( 160 );
            if (!password_verify( $params['old_password'], $adminObj->password ))
                return $this->lang->set( 154 );
        }
        // 校验账户密码
        if (md5( $params['new_password'] ) != md5( $params['new_password_confirm'] ))
            return $this->lang->set( 124 );

        // 校验输入原密码是否与数据库存的密码是否相等
        if (password_verify( $params['new_password'], $adminObj->password ))
            return $this->lang->set( 125 );

        DB::pdo()->beginTransaction();
        try {
            // 更新账户信息
            $adminObj->password = password_hash( $params['new_password'], PASSWORD_DEFAULT ); // 密码加密
            // 修改密码
            $res = $adminObj->save();
            if (!$res)
                throw new DateException( 130 );
            $this->writeAdminLog( $adminObj->toArray(), $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $adminObj->toArray(), $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }
        // 删除账户的token ,账户需重新登录
        (new AdminToken( $this->ci ))->remove( $id );

        return $this->lang->set( 0 );
    }
};
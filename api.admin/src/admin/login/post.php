<?php

use Model\DB;
use Logic\Admin\BaseController;
use Logic\Admin\AdminToken;
use Model\Common\AdminModel;
use Lib\Validate\Admin\LoginValidate;

return new class extends BaseController {
    public function run()
    {
        // 校验请求参数
        (new LoginValidate())->paramsCheck( 'post', $this->request, $this->response );
        // 格式化请求参数
        $table  = (new AdminModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }
        // 封装请求参数
        $jsonWebToken = $this->ci->get( 'settings' )['jsonwebtoken'];
        $digital      = intval( $jsonWebToken['uid_digital'] );
        $jwt          = new AdminToken( $this->ci );
        DB::pdo()->beginTransaction();
        try {
            // 一路校验并获取账户Map
            $adminLoginMap = $jwt->createToken( $params, $jsonWebToken['public_key'], $jsonWebToken['expire'], $digital );
            // 登录成功日志
            $this->writeAdminLog( $adminLoginMap['info'] ?? [], $table, $adminLoginMap['info']['id'] ?? 0, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $errorMap     = [4 => '账号或密码错误', 9 => '账户不存在', 121 => '图形验证码错误，请重新输入！', 130 => '更新管理员信息失败', 196 => '该用户已禁用!'];
            $params['error'] = $errorMap[$e->getMessage()] ?? '未知'; // 错误信息
            $this->writeAdminLog( $params, $table, 0, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        // 响应返回
        return $this->lang->set( 1, [], $adminLoginMap, [] );
    }
};

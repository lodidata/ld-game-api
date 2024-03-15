<?php

use Model\DB;
use Model\Common\CurrencyModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\CurrencyValidate;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 校验请求参数
        (new CurrencyValidate())->paramsCheck( 'post', $this->request, $this->response );
        // 获取请求参数
        $table  = (new CurrencyModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'currency_type') $params[$key] = strtoupper( trim( $param ) );
        }
        // 检查记录是否存在
        $checkCurrency = CurrencyModel::query()
            ->where( 'currency_type', $params['currency_type'] )
            ->where( 'currency_name', $params['currency_name'] )
            ->first();
        if ($checkCurrency)
            return $this->lang->set( 127 );

        DB::pdo()->beginTransaction();
        try {
            // 新增货币
            $id = CurrencyModel::query()->insertGetId( $params );
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


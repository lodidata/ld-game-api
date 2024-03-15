<?php

use Model\DB;
use Model\Common\CurrencyModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\CurrencyValidate;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID( $id );
        // 验证请求参数
        (new CurrencyValidate())->paramsCheck( 'put', $this->request, $this->response );
        // 获取请求参数
        $table  = (new CurrencyModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'currency_type' && !empty( $param )) $params['currency_type'] = strtoupper( $params[$key] );
        }
        // 检查该记录是否存在
        $currencyObj = CurrencyModel::query()->where( 'id', $id )->first();
        if (!$currencyObj)
            return $this->lang->set( 126 );
        // 检查数据是否发生改变
        $checkChange = $this->checkParamsChange( $currencyObj, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        // 检查记录是否存在
        $checkAgentCurrency = CurrencyModel::query()
            ->where( 'currency_type', $params['currency_type'] )
            ->where( 'currency_name', $params['currency_name'] )
            ->first();
        if ($checkAgentCurrency)
            return $this->lang->set( 127 );

        DB::pdo()->beginTransaction();
        try {
            // 更新记录
            $res = CurrencyModel::query()->where( 'id', $id )->update( $params );
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
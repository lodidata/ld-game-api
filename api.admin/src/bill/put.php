<?php

use Model\DB;
use Lib\Validate\Admin\BillValidate;
use Model\Common\AgentGameModel;
use Model\Common\AgentModel;
use Model\Common\BillModel;
use Logic\Admin\BaseController;
use Model\Common\GameMenuModel;
use Respect\Validation\Exceptions\DateException;

/**
 * 修改账单
 */
return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID( $id );
        // 验证请求参数
        (new BillValidate())->paramsCheck( 'put', $this->request, $this->response );
        // 获取请求参数
        $table  = (new BillModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }
        if(!$params['start_bill_date'] || !$params['end_bill_date']){
            return $this->lang->set( 161 );
        }
        // 检查该记录是否存在
        $billObj = BillModel::query()->where( 'id', $id )->first();
        if (!$billObj)
            return $this->lang->set( 126 );
        // 检查数据是否发生改变
        $checkChange = $this->checkParamsChange( $billObj, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        // 校验费率
        if (bccomp($params['rate'], 99.99, 2) == 1)
            return $this->lang->set( 191 );

        DB::pdo()->beginTransaction();
        try {
            // 更新记录
            $res = BillModel::updateModel( $billObj, $params );
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
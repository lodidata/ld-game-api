<?php

use Model\DB;
use Logic\Define\CacheKey;
use Model\Common\AgentModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AgentValidate;
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
        (new AgentValidate())->paramsCheck( 'put', $this->request, $this->response );
        // 获取请求参数
        $table  = (new AgentModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if (isset( $params['bill_date'] ) && (!$params['bill_date'] || strtotime( $params['bill_date'] ) <= 0)) unset( $params['bill_date'] );
        }
        // 检查品牌名称是否重复
        $checkBrandName = AgentModel::query()->where( 'brand_name', $params['brand_name'] )->where( 'id', '<>', $id )->value( 'id' );
        if ($checkBrandName)
            return $this->lang->set( 133 );
        // 检查该记录是否存在
        $agentObj = AgentModel::query()->where( 'id', $id )->first();
        if (!$agentObj)
            return $this->lang->set( 126 );
        // 检查数据是否发生改变
        $checkChange = $this->checkParamsChange( $agentObj, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );

        DB::pdo()->beginTransaction();
        try {
            // 更新记录
            $res = AgentModel::updateModel( $agentObj, $params );
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
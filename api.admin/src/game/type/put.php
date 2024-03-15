<?php

use Model\DB;
use Model\Common\GameTypeModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\GameTypeValidate;
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
        (new GameTypeValidate())->paramsCheck( 'update', $this->request, $this->response );
        // 获取请求参数
        $table  = (new GameTypeModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'type_code' && !empty( $params['type_code'] )) $params['type_code'] = strtoupper( $params[$key] );
        }
        // 检查该记录是否存在
        $gameTypeObj = GameTypeModel::query()->where( 'id', $id )->first();
        if (!$gameTypeObj)
            return $this->lang->set( 126 );
        // 检查数据是否发生改变
        $checkChange = $this->checkParamsChange( $gameTypeObj, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        // 检查记录是否存在
        $checkGameType = GameTypeModel::query()
            ->where( 'type_code', $params['type_code'] )
            ->where( 'type_name', $params['type_name'] )
            ->first();
        if ($checkGameType) {
            if ($checkGameType->status === GameTypeModel::STATUS_OFF)
                return $this->lang->set( 135 );

            return $this->lang->set( 127 );
        }

        DB::pdo()->beginTransaction();
        try {
            // 更新记录
            $res = GameTypeModel::query()->where( 'id', $id )->update( $params );
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
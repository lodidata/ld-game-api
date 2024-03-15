<?php

use Model\DB;
use Logic\Admin\BaseController;
use Model\Common\GameTypeModel;
use Lib\Validate\Admin\GameTypeValidate;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new GameTypeValidate())->paramsCheck( 'post', $this->request, $this->response );
        // 获取请求参数
        $table  = (new GameTypeModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'type_code' && !empty( $params['type_code'] )) $params['type_code'] = strtoupper( $params[$key] );
        }
        // 检查游戏类型是否已存在
        $checkRes = GameTypeModel::query()->where( 'type_code', $params['type_code'] )->where( 'type_name', $params['type_name'] )->first();
        if ($checkRes) {
            if ($checkRes->status === GameTypeModel::STATUS_OFF)
                return $this->lang->set( 135 );
            return $this->lang->set( 127 );
        }

        DB::pdo()->beginTransaction();
        try {
            // 新增货游戏类型
            $id = GameTypeModel::query()->insertGetId( $params );
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


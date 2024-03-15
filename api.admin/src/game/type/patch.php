<?php

use Model\DB;
use Model\Common\GameTypeModel;
use Logic\Admin\BaseController;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 校验id是否合法
        $this->checkID( $id );
        // 获取写日志所需的表名
        $gameTypeObj = new GameTypeModel();
        $table       = $gameTypeObj->getTable();
        // 获取或校验管理员
        $gameTypeModel = $gameTypeObj::query()->where( 'id', $id )->first();
        if (!$gameTypeModel)
            return $this->lang->set( 146 );
        // 校验并更新状态
        $params['status'] = $gameTypeModel->status === GameTypeModel::STATUS_ON ? GameTypeModel::STATUS_OFF : GameTypeModel::STATUS_ON;
        $checkChange      = $this->checkParamsChange( $gameTypeModel, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        $gameTypeModel->status = $params['status']; // 状态开关

        DB::pdo()->beginTransaction();
        try {
            // 保存
            $res = $gameTypeModel->save();
            if (!$res)
                throw new DateException( 139 );
            $this->writeAdminLog( $gameTypeModel->toArray(), $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $gameTypeModel->toArray(), $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
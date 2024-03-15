<?php

use Model\DB;
use Model\Common\GameMenuModel;
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
        $gameMenuObj = new GameMenuModel();
        $table       = $gameMenuObj->getTable();
        // 获取或校验管理员
        $gameMenuModel = $gameMenuObj::query()->where( 'id', $id )->first();
        if (!$gameMenuModel)
            return $this->lang->set( 146 );

        // 校验并更新状态
        $params['status'] = $gameMenuModel->status === GameMenuModel::STATUS_ON ? GameMenuModel::STATUS_OFF : GameMenuModel::STATUS_ON;
        $checkChange      = $this->checkParamsChange( $gameMenuModel, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        $gameMenuModel->status = $params['status']; // 状态开关

        DB::pdo()->beginTransaction();
        try {
            // 修改状态
            $res = $gameMenuModel->save();
            if (!$res)
                throw new DateException( 139 );
            $this->writeAdminLog( $gameMenuModel->toArray(), $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $gameMenuModel->toArray(), $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
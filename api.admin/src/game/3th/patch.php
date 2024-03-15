<?php

use Model\DB;
use Model\Common\Game3thModel;
use Logic\Admin\BaseController;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 校验id是否合法
        $this->checkID($id);
        // 获取写日志所需的表名
        $game3thObj = new Game3thModel();
        $table      = $game3thObj->getTable();
        // 获取或校验管理员
        $game3thModel = $game3thObj::query()->where('id', $id)->first();
        if (!$game3thModel)
            return $this->lang->set(146);
        // 校验并更新状态
        $params['status'] = $game3thModel->status === Game3thModel::STATUS_ON ? Game3thModel::STATUS_OFF : Game3thModel::STATUS_ON;
        $checkChange = $this->checkParamsChange( $game3thModel, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        $game3thModel->status =$params['status'];

        DB::pdo()->beginTransaction();
        try {
            // 修改状态
            $res = $game3thModel->save();
            if (!$res)
                throw new DateException(139);
            $this->writeAdminLog($game3thModel->toArray(), $table, $id, 1);

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog($game3thModel->toArray(), $table, $id, 0);
            return $this->lang->set($e->getMessage());
        }

        return $this->lang->set(0);
    }
};
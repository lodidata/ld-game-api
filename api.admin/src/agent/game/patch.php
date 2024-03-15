<?php

use Model\DB;
use Model\Common\AgentGameModel;
use Model\Common\GameApiModel;
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
        $agentGameObj = new AgentGameModel();
        $table        = $agentGameObj->getTable();
        $GameApiTable = (new GameApiModel)->getTable();
        // 获取或校验代理游戏
        $agentGameModel = AgentGameModel::query()->where( 'id', $id )->first();
        if (!$agentGameModel)
            return $this->lang->set( 146 );
        //校验game_api
        $gameApiModel = GameApiModel::query()->where( 'agent_game_id', $id )->first();
        if (!$gameApiModel)
            return $this->lang->set( 151 );
        // 校验并更新代理游戏的状态
        $params['status'] = $agentGameModel->status === AgentGameModel::STATUS_ON ? AgentGameModel::STATUS_OFF : AgentGameModel::STATUS_ON; // 修改状态
        $checkChange      = $this->checkParamsChange( $agentGameModel, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        $agentGameModel->status = $params['status'];

        DB::pdo()->beginTransaction();
        try {
            $res = $agentGameModel->save();
            if (!$res)
                throw new DateException( 139 );
            $this->writeAdminLog( $agentGameModel->toArray(), $table, $id, 1 );

            // 更新game_api表status
            $gameApiModel->status = $agentGameModel->status;
            $GameApiRes           = $gameApiModel->save();
            if (!$GameApiRes)
                throw new DateException( 139 );
            $this->writeAdminLog( $gameApiModel->toArray(), $GameApiTable, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $agentGameModel->toArray(), $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
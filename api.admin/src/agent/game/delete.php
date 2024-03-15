<?php

use Model\Common\GameApiModel;
use Model\DB;
use Logic\Admin\BaseController;
use Model\Common\AgentGameModel;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID($id);
        // 获取表名
        $agentGameModel = new AgentGameModel();
        $gameApiModel   = new GameApiModel();
        $agentGameTable = $agentGameModel->getTable();
        $gameApiTable   = $gameApiModel->getTable();

        // 校验id是否存在于对应表中
        $agentGameObj = AgentGameModel::query()->where('id', $id)->first();
        if (!$agentGameObj)
            return $this->lang->set(126);

        $agentApiObj = GameApiModel::query()->where('agent_game_id', $id)->first();
        if (!$agentApiObj)
            return $this->lang->set(126);
        $agentApiId = $agentApiObj->id;

        DB::pdo()->beginTransaction();
        try {
            // 删除
            $delRes = $agentGameObj->delete();
            if (!$delRes)
                throw new DateException(123);
            $this->writeAdminLog($agentGameObj->toArray(), $agentGameTable, $id, 1);

            //删除game_api表记录
            $delRes = $agentApiObj->delete();
            if (!$delRes)
                throw new DateException(123);
            $this->writeAdminLog($agentApiObj->toArray(), $gameApiTable, $agentApiId, 1);

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog([], $agentGameTable, 0, 0); // 写代理游戏错误日志
            $this->writeAdminLog([], $gameApiTable, 0, 0); // 写代理api错误日志
            return $this->lang->set($e->getMessage());
        }

        return $this->lang->set(0);
    }
};


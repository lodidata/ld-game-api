<?php

use Model\DB;
use Model\Common\AgentGameModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AgentGameValidate;
use Model\Common\GameApiModel;
use Respect\Validation\Exceptions\DateException;
use Utils\Utils;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID( $id );
        // 验证请求参数
        (new AgentGameValidate())->paramsCheck( 'put', $this->request, $this->response );
        // 获取请求参数
        $agentGameTable = (new AgentGameModel())->getTable();
        $gameApiTable   = (new GameApiModel())->getTable();
        $params         = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'password' && !empty( $params['password'] )) $params['password'] = Utils::RSAEncrypt( $params[$key] );
            if ($key === 'api_key' && !empty( $params['api_key'] )) $params['api_key'] = Utils::RSAEncrypt( $params[$key] );
        }
        // 检查该记录是否存在
        $agentGameObj = AgentGameModel::query()->where( 'id', $id )->first();
        if (!$agentGameObj)
            return $this->lang->set( 126 );
        // 检查数据是否发生改变
        $checkChangeAgentGameModel = $this->checkParamsChange( $agentGameObj, $params );
        //查找game_api
        $gameApiObj = GameApiModel::query()
            ->where( 'agent_code', $agentGameObj->agent_code )
            ->where( 'menu_type', $agentGameObj->menu_type )
            ->first();
        if (!$gameApiObj)
            return $this->lang->set( 151 );
        if ($gameApiObj->agent_game_id != $id)
            return $this->lang->set( 156 );
        // 检查数据是否发生改变
        $checkChangeGameApiModel = $this->checkParamsChange( $gameApiObj, $params );
        if ($checkChangeAgentGameModel == 0 && $checkChangeGameApiModel == 0) {
            return $this->lang->set( 122 );
        }

        // 校验游戏Api是否重复添加
        $gameApiObjAgentCode = GameApiModel::where('agent_code', $agentGameObj->agent_code)
            ->where('menu_type', $agentGameObj->menu_type)
            ->where('agent_game_id', '<>', $id)
            ->first();
        if ($gameApiObjAgentCode)
            return $this->lang->set(152);
        $gameApiObjApiAgent = GameApiModel::where('menu_type', $agentGameObj->menu_type)
            ->where('api_agent', $params['api_agent'])
            ->where('agent_game_id', '<>', $id)
            ->first();
        if ($gameApiObjApiAgent)
            return $this->lang->set(147);

        DB::pdo()->beginTransaction();
        try {
            // 更新记录
            if ($checkChangeAgentGameModel == 1) {
                $AgentGameRes = AgentGameModel::updateModel( $agentGameObj, $params );
                if (!$AgentGameRes)
                    throw new DateException( 139 );
                $this->writeAdminLog( $agentGameObj->toArray(), $agentGameTable, $id, 1 );
            }
            // 更改game_api表
            if ($checkChangeGameApiModel == 1) {
                $gameApiRes = GameApiModel::updateModel( $gameApiObj, $params );
                if (!$gameApiRes)
                    throw new DateException( 139 );
                $this->writeAdminLog( $gameApiObj->toArray(), $gameApiTable, $gameApiObj->id, 1 );
            }

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $agentGameObj->toArray(), $agentGameTable, $id, 0 );
            $this->writeAdminLog( $gameApiObj->toArray(), $gameApiTable, $gameApiObj->id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
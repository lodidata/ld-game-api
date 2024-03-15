<?php

use Model\Common\AgentGameModel;
use Logic\Admin\BaseController;

return new class() extends BaseController {

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID( $id );
        // 检查该记录是否存在
        $agentGameObj = AgentGameModel::query()
            ->leftJoin('game_api','agent_game.id','game_api.agent_game_id')
            ->where( 'agent_game.id', $id )
            ->first(['agent_game.*','game_api.api_agent','game_api.api_key','game_api.lobby']);
        if (!$agentGameObj)
            return $this->lang->set( 126 );

        // 响应数据
        return $this->lang->set( 0, [], $agentGameObj->toArray(), [] );
    }
};

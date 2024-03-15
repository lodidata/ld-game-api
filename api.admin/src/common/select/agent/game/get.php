<?php

use Lib\Validate\Admin\AgentGameValidate;
use Logic\Admin\BaseController;
use Model\Common\AgentModel;
use Model\Common\AgentGameModel;

/**
 * 代理游戏厂商选择
 */
return new class() extends BaseController {
    public function run(): array
    {
        (new AgentGameValidate())->paramsCheck( 'select_get', $this->request, $this->response );
        $params = $this->request->getParams();

        return AgentGameModel::query()->where( 'status', AgentModel::STATUS_ON )->where( 'agent_code', $params['agent_code'] )->get( ['menu_type'] )->toArray();
    }
};

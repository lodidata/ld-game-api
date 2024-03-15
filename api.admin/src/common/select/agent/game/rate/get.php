<?php

use Lib\Validate\Admin\AgentGameValidate;
use Logic\Admin\BaseController;
use Model\Common\AgentModel;
use Model\Common\AgentGameModel;

/**
 * 游戏厂商费率选择
 */
return new class() extends BaseController {
    public function run()
    {
        (new AgentGameValidate())->paramsCheck('input_get', $this->request, $this->response);
        $params = $this->request->getParams();
        $rateList   = AgentGameModel::query()
            ->where('status', AgentModel::STATUS_ON)
            ->where('agent_code', $params['agent_code'])
            ->where('menu_type', $params['menu_type'])
            ->pluck('rate')
            ->toArray();

        return $this->lang->set(0, [], $rateList, []);
    }
};

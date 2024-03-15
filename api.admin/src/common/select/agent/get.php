<?php

use Logic\Admin\BaseController;
use Model\Common\AgentModel;

/**
 * 代理品牌货币选择
 */
return new class() extends BaseController {
    public function run(): array
    {
        return AgentModel::query()
            ->leftJoin('currency','agent.currency_id','currency.id')
            ->where('agent.status', AgentModel::STATUS_ON)
            ->get(['agent.agent_code', 'agent.brand_name', 'agent.currency_id', 'currency.currency_type AS currency'])->toArray();
    }
};

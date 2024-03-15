<?php

use Model\Common\AgentModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AgentValidate;
use Utils\Utils;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        // 验证请求参数
        (new AgentValidate())->paramsCheck('get', $this->request, $this->response);
        // 获取所有的请求参数
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty($param)) $params[$key] = trim($param);
            if ($key === 'agent_code' && !empty($params['agent_code'])) $params['agent_code'] = strtolower($params[$key]);
            if ($key === 'page' && (!is_numeric($param) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric($param) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 查询条件封装
        $agentObj = AgentModel::query()->leftJoin('currency', 'agent.currency_id', 'currency.id');
        !empty($params['agent_code']) && $agentObj->where('agent.agent_code', 'like', $params['agent_code'] . '%'); // 代理号模糊搜索
        !empty($params['brand_name']) && $agentObj->where('agent.brand_name', 'like', $params['brand_name'] . '%'); // 品牌名称模糊搜索
        !empty($params['currency_id']) && $agentObj->where('agent.currency_id', $params['currency_id']); // 货币查询
        isset($params['status']) && is_numeric($params['status']) && in_array($params['status'], array_keys(AgentModel::STATUS_ARR)) && $agentObj->where('agent.status', $params['status']); // 状态搜索
        isset($params['wallet_type']) && is_numeric($params['wallet_type']) && in_array($params['wallet_type'], array_keys(AgentModel::WALLET_TYPE_ARR)) && $agentObj->where('agent.wallet_type', $params['wallet_type']); // 状态搜索
        !empty($params['opening_date_from']) && $agentObj->where('agent.created_at', '>=', $params['opening_date_from'] . ' 00:00:00'); // 创建时间搜索
        !empty($params['opening_date_to']) && $agentObj->where('agent.created_at', '<=', $params['opening_date_to'] . ' 23:59:59');
        // 统计总的记录数
        $common['total'] = $agentObj->count() ?? 0;
        // 获取并格式化代理列表
        $agentList = $agentObj->forPage($common['page'], $common['page_size'])->orderBy('agent.created_at', 'desc')->get(['agent.*', 'currency.currency_type', 'currency.currency_name'])->toArray();
        // 格式化列表
        foreach ($agentList as &$item) {
            $item['status_str']            = AgentModel::STATUS_ARR[$item['status']] ?? '未知'; // 代理开启状态
            $item['wallet_type_str']       = AgentModel::WALLET_TYPE_ARR[$item['wallet_type']] ?? '未知'; // 钱包类型
            $item['secret_key']            = Utils::RSADecrypt($item['secret_key']);
            $item['is_allow_login_str']    = AgentModel::IS_ALLOW_LOGIN_ARR[$item['is_allow_login']] ?? '未知'; // 是否允许注册登录
            $item['is_allow_transfer_str'] = AgentModel::IS_ALLOW_TRANSFER_ARR[$item['is_allow_transfer']] ?? '未知'; // 是否允许转账
            $item['is_allow_order_str']    = AgentModel::IS_ALLOW_ORDER_ARR[$item['is_allow_order']] ?? '未知'; // 是否允许拉单
            $item['is_limit_recharge_str'] = AgentModel::IS_ALLOW_RECHARGE_ARR[$item['is_limit_recharge']] ?? '未知'; // 是否开启限制充值金额
            if ($item['is_limit_recharge'] == AgentModel::IS_LIMIT_RECHARGE_OFF) $item['limit_recharge_money'] = '无上限'; // 不限制
            if (strtotime($item['bill_date']) <= 0) $item['bill_date'] = '';
        }

        return $this->lang->set(0, [], $agentList, $common);
    }
};

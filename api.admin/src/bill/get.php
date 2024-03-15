<?php

use Lib\Validate\Admin\BillValidate;
use Model\Common\AgentModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AgentValidate;
use Utils\Utils;
use Model\Common\BillModel;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        // 验证请求参数
        (new BillValidate())->paramsCheck('get', $this->request, $this->response);
        // 获取所有的请求参数
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty($param)) $params[$key] = trim($param);
            if ($key === 'page' && (!is_numeric($param) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric($param) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 查询条件封装
        $obj    = BillModel::query()->leftJoin('currency', 'bill.currency_id', 'currency.id');
        !empty($params['agent_code']) && $obj->where('agent_code', 'like', $params['agent_code'] . '%');
        !empty($params['brand_name']) && $obj->where('brand_name', 'like', $params['brand_name'] . '%');
        !empty($params['menu_type']) && $obj->where('menu_type', 'like', $params['menu_type'] . '%');
        !empty($params['currency_id']) && $obj->where('currency_id', $params['currency_id']);
        !empty($params['start_bill_date']) && $obj->where('start_bill_date', '>=', $params['start_bill_date']);
        !empty($params['end_bill_date']) && $obj->where('end_bill_date', '<=', $params['end_bill_date']);
        !empty($params['rate']) && $obj->where('rate', $params['rate']);
        !empty($params['exchange_rate']) && $obj->where('exchange_rate', $params['exchange_rate']);
        // 统计总的记录数
        $common['total'] = $obj->count() ?? 0;
        $list            = $obj->forPage($common['page'], $common['page_size'])
            ->orderBy('id', 'desc')
            ->get(['bill.*', 'currency.currency_type'])
            ->toArray();

        return $this->lang->set(0, [], $list, $common);
    }
};

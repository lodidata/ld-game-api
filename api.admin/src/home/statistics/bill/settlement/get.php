<?php

use Model\Common\BillModel;
use Logic\Admin\BaseController;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken'
    ];

    public function run()
    {
        // 格式化并封装数据
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'page' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 获取分页列表
        $billObj = BillModel::query()->join( 'currency', 'bill.currency_id', 'currency.id' );
        $billObj->groupBy( ['currency.currency_type'] );
        $billGroupListObj = $billObj->get();
        $billObj->selectRaw( 'currency.id,currency.currency_type,COUNT(bill.id) AS bill_num,SUM(bill.settlement) AS total_settlement' );
        $billObj->orderBy( 'total_settlement', 'desc' );
        $billObj->orderBy( 'bill_num', 'desc' );
        $groupBillList = $billObj->forPage( $common['page'], $common['page_size'] )->get()->toArray();
        // 获取分组组数
        $common['total'] = $billGroupListObj->count() ?? 0;

        // 响应
        return $this->lang->set( 0, [], $groupBillList, $common );
    }
};

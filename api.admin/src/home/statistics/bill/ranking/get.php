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
        $params = $this->calculateLastDayByMonth( $params ); // 计算统计开始时间和结束时间
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 获取分页列表
        $billObj = BillModel::query()->join( 'currency', 'bill.currency_id', 'currency.id' );
        $billObj->where( 'bill.created_at', '>=', $params['statistics_at_from'] ); // 起统计时间
        $billObj->where( 'bill.created_at', '<=', $params['statistics_at_to'] ); // 止统计时间
        $billObj->groupBy( ['currency.currency_type'] );
        $billGroupListObj = $billObj->get( ['bill.*', 'currency.currency_type'] );
        $billObj->selectRaw( 'currency.id,currency.currency_type,COUNT(bill.id) AS bill_num,SUM(settlement) AS total_settlement' );
        $billObj->orderBy( 'total_settlement', 'desc' );
        $groupBillList = $billObj->forPage( $common['page'], $common['page_size'] )->get()->toArray();
        // 获取分组组数
        $common['total'] = $billGroupListObj->count() ?? 0;

        // 响应
        return $this->lang->set( 0, [], $groupBillList, array_merge( $params, $common ) );
    }

    /**
     * 获取时间
     *
     * @param array $params
     * @return array
     */
    protected function calculateLastDayByMonth(array $params = []): array
    {
        // 格式化默认时间字符出并返回：[年]、[月]
        $now          = date( 'Y-m', time() );
        $statisticsAt = $params['statistics_at'] ?? $now; // 校验不存在该key时初始化默认值
        if (!$statisticsAt || strtotime( $statisticsAt ) <= 0) $statisticsAt = $now; // 校验空值时初始化默认值
        $statisticsAtArr = explode( '-', $statisticsAt );
        $year            = $statisticsAtArr[0];
        $month           = (int)$statisticsAtArr[1];
        // 校验[年]、[月]
        if ($year <= 0 || $month <= 0 || $month > 12)
            return $this->lang->set( 203 );
        // 格式化每月最后一天的[月-日]
        $monthEndDays = [1 => '1-31', 2 => '2-28', 3 => '3-31', 4 => '4-30', 5 => '5-31', 6 => '6-30', 7 => '7-31', 8 => '8-31', 9 => '9-30', 10 => '10-31', 11 => '11-30', 12 => '12-31']; // 每月最后一天
        if ($year % 4 === 0) $monthEndDays[2] = '2-29'; // 润年
        // 获取账单列表
        $zero = '';
        if ($month < 10) $zero = 0;
        $params['statistics_at']      = $statisticsAt;
        $params['statistics_at_from'] = $year . '-' . $zero . $month . '-01 00:00:00';
        $params['statistics_at_to']   = $year . '-' . $zero . $monthEndDays[$month] . ' 23:59:59';

        return $params;
    }
};

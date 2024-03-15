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
        $params  = $this->request->getParams();
        $params  = $this->calculateDefaultDate( $params ); // 计算默认查询时间,精度：Y-m
        $params  = $this->calculateLastDayByMonth( $params ); // 计算默认查询时间,精度 Y-m-d H:i:s
        $billObj = BillModel::query()->join( 'currency', 'bill.currency_id', 'currency.id' );
        $billObj->where( 'bill.created_at', '>=', $params['start_bill_date'] ); // 起时间
        $billObj->where( 'bill.created_at', '<=', $params['end_bill_date'] ); // 止时间
        $billList = $billObj->get( ['bill.*', 'currency.currency_type'] )->toArray(); // 未分页
        // 按currency_type分组
        $groupedList = $settlementList = [];
        foreach ($billList as $item) {
            $currencyType = $item['currency_type'] ?? '';
            if ($currencyType) {
                if (!isset( $groupedList[$currencyType] )) {
                    $groupedList[$currencyType] = [];
                }
                $groupedList[$currencyType][] = $item;
            }
        }
        // 处理每个currency_type
        foreach ($groupedList as $currencyType => $data) {
            // 初始化时间段数据
            $startBillDateTime = strtotime( $params['start_bill_date'] );
            $monthlyData       = [];
            $absMonths         = getMonthNum( $params['start_bill_date'], $params['end_bill_date'] );
            for ($i = 0; $i <= $absMonths; $i++) {
                $yearMonth  = date( 'Y-m', strtotime( "+$i months", $startBillDateTime ) );
                $yearMonths = explode( '-', $yearMonth );
                $zero       = '';
                if ((int)$yearMonths[1] < 10) $zero = 0;
                // $monthlyData[$yearMonth] = ['end_month_day' => $zero . $params['last_every_month_day'][(int)$yearMonths[1]], 'settlement' => 0.00]; // 每月最后一天(月日形式)
                $monthlyData[$yearMonth] = ['end_month_day' => $yearMonths[0] . '-' . $zero . $params['last_every_month_day'][(int)$yearMonths[1]], 'settlement' => 0.00]; // 每月最后一天(年月日形式)
            }
            // 按时间分组，求和
            foreach ($data as $item) {
                if (!empty( $item['created_at'] )) {
                    $billYearMonth = date( 'Y-m', strtotime( $item['created_at'] ) );
                    if (isset( $monthlyData[$billYearMonth] )) {
                        $monthlyData[$billYearMonth]['settlement'] = bcadd( $monthlyData[$billYearMonth]['settlement'], $item['settlement'], 2 ); // 累加加收金额
                    }
                }
            }
            // 存储到$settlementList中
            $settlementList[$currencyType] = array_values( $monthlyData );
        }
        // 默认检索仅半-的统计数据
        return $this->lang->set( 0, [], $settlementList, $params );
    }

    /**
     * 计算默认统计年月时间
     *
     * @param array $params
     * @return array
     */
    protected function calculateDefaultDate(array $params = []): array
    {
        // 获取默认时间
        $nowYearMonth    = date( 'Y-m', time() );
        $nowYearMonthArr = explode( '-', $nowYearMonth );
        // 统计开始时间默认值计算
        if (empty( $params['statistics_at_from'] )) {
            $params['statistics_at_from'] = $nowYearMonthArr[0] . '-';
            if ($nowYearMonthArr[1] <= 6)
                $month = 1;
            else
                $month = 6;
            $params['statistics_at_from'] = $params['statistics_at_from'] . $month; // 开始：年-月
        }
        // 统计结束时间默认值计算
        if (empty( $params['statistics_at_to'] )) {
            $params['statistics_at_to'] = $nowYearMonthArr[0] . '-';
            if ($nowYearMonthArr[1] <= 6)
                $month = 6;
            else
                $month = 12;
            $params['statistics_at_to'] = $params['statistics_at_to'] . $month; // 结束：年-月
        }
        unset( $params['page'], $params['page_size'] ); // 干掉此统计无效的分页

        return $params;
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
        $startBillTimes = explode( '-', $params['statistics_at_from'] ?? [] );
        $endBillTimes   = explode( '-', $params['statistics_at_to'] ?? [] );
        $startBillYear  = (int)$startBillTimes[0] ?? 0; // 开始年份
        $startBillMonth = (int)$startBillTimes[1] ?? 0; // 开始月份
        $endBillYear    = (int)$endBillTimes[0] ?? 0; // 结束年份
        $endBillMonth   = (int)$endBillTimes[1] ?? 0; // 结束月份
        // 校验[年]、[月]
        if ($startBillYear <= 0 || $startBillMonth <= 0 || $startBillMonth > 12 || $endBillYear <= 0 || $endBillMonth <= 0 || $endBillMonth > 12)
            return $this->lang->set( 203 );
        // 格式化每月最后一天的[月-日]
        $monthEndDays = [1 => '1-31', 2 => '2-28', 3 => '3-31', 4 => '4-30', 5 => '5-31', 6 => '6-30', 7 => '7-31', 8 => '8-31', 9 => '9-30', 10 => '10-31', 11 => '11-30', 12 => '12-31']; // 每月最后一天
        if ($startBillYear % 4 === 0) $monthEndDays[2] = '2-29'; // 润年
        // 获取账单列表
        $zero = '';
        if ($endBillMonth < 10) $zero = 0;
        $params['start_bill_date']      = $startBillYear . '-' . $zero . $startBillMonth . '-01 00:00:00';
        $params['end_bill_date']        = $endBillYear . '-' . $zero . $monthEndDays[$endBillMonth] . ' 23:59:59';
        $params['start_bill_year']      = $startBillYear;
        $params['start_bill_month']     = $startBillMonth;
        $params['end_bill_year']        = $endBillYear;
        $params['end_bill_month']       = $endBillMonth;
        $params['last_every_month_day'] = $monthEndDays;

        return $params;
    }
};

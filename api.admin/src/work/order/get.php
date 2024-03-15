<?php

use Model\Common\WorkOrderModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\WorkOrderValidate;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new WorkOrderValidate())->paramsCheck( 'get', $this->request, $this->response );
        // 格式化并封装数据
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'page' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 查询条件封装
        $workOrderObj = WorkOrderModel::query();
        !empty( $params['title'] ) && $workOrderObj->where( 'title', 'like', $params['title'] . '%' ); // 工单标题
        isset( $params['status'] ) && is_numeric( $params['status'] ) && in_array( $params['status'], array_keys( WorkOrderModel::WORK_ORDER_STATUS_LIST ) ) && $workOrderObj->where( 'status', $params['status'] ); // 工单状态
        !empty( $params['creator'] ) && $workOrderObj->where( 'created_admin', 'like', $params['creator'] . '%' ); // 创建工单的账户名
        !empty( $params['created_at_from'] ) && $workOrderObj->where( 'created_at', '>=', $params['created_at_from'] . ' 00:00:00' ); // 工单创建起时间
        !empty( $params['created_at_to'] ) && $workOrderObj->where( 'created_at', '<=', $params['created_at_to'] . ' 23:59:59' ); // 工单创建止时间
        !empty( $params['finished_at_from'] ) && $workOrderObj->where( 'finished_at', '>=', $params['finished_at_from'] . ' 00:00:00' ); // 工单解决止时间
        !empty( $params['finished_at_to'] ) && $workOrderObj->where( 'finished_at', '<=', $params['finished_at_to'] . ' 23:59:59' ); // 工单解决止时间

        // 统计总的记录数
        $common['total'] = $workOrderObj->count() ?? 0;
        // 获取分页列表
        $workOrderList = $workOrderObj->forPage( $common['page'], $common['page_size'] )->orderBy( 'created_at', 'desc' )->get()->toArray();
        // 格式化列表
        foreach ($workOrderList as $k => $workOrder) {
            $workOrderList[$k]['status_str']             = WorkOrderModel::WORK_ORDER_STATUS_LIST[$workOrder['status']] ?? '未知'; // 工单状态类型(int)转成字符串
            $workOrderList[$k]['button_permission_list'] = WorkOrderModel::checkWorkOrderStatus( $workOrder['status'] ); // 不同工单状态不同权限按钮
            $workOrderList[$k]['appendix']               = !empty( $workOrder['appendix'] ) || is_json( $workOrder['appendix'] ) ? json_decode( $workOrder['appendix'] ) : [];
            if (empty( $workOrder['finished_at'] ) || strtotime( $workOrder['finished_at'] ) <= 0) $workOrderList[$k]['finished_at'] = ''; // 格式化工单完成时间
        }

        return $this->lang->set( 0, [], $workOrderList, $common );
    }
};

<?php

use Model\Common\WorkOrderModel;
use Model\Common\WorkOrderRecordModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\WorkOrderValidate;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run(int $id = 0)
    {
        // 验证id
        $this->checkID( $id );
        // 检查记录是否存在
        $checkWorkOrder = WorkOrderModel::query()->where( 'id', $id )->first();
        if (!$checkWorkOrder)
            return $this->lang->set( 184 );
        $workOrder = $checkWorkOrder->toArray();
        // 工单格式化
        $workOrder['status_str'] = WorkOrderModel::WORK_ORDER_STATUS_LIST[$workOrder['status']] ?? '未知'; // 工单状态转成字符串
        (empty( $workOrder['finished_at'] ) || strtotime( $workOrder['finished_at'] ) <= 0) && $workOrder['finished_at'] = ''; // 格式化工单完成时间
        $workOrder['button_permission_list'] = WorkOrderModel::checkWorkOrderStatus( $workOrder['status'] ); // 不同权限按钮初始化
        $workOrder['appendix']               = !empty( $workOrder['appendix'] ) || is_json( $workOrder['appendix'] ) ? json_decode( $workOrder['appendix'] ) : []; // 工单附件格式化
        // 获取历史记录
        $workOrderRecordList = WorkOrderRecordModel::query()->where( 'work_order_id', $id )->orderBy( 'id', 'asc' )->get()->toArray();
        foreach ($workOrderRecordList as $key => $record) {
            $workOrderRecordList[$key]['type_str'] = WorkOrderRecordModel::RECORD_TYPE_LIST[$record['type']] ?? '未知';
            $workOrderRecordList[$key]['appendix'] = !empty( $record['appendix'] ) || is_json( $record['appendix'] ) ? json_decode( $record['appendix'] ) : []; // 工单附件格式化
        }
        $workOrder['history_record'] = $workOrderRecordList;

        return $workOrder;
    }
};
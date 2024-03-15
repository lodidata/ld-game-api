<?php

use Model\DB;
use Logic\Admin\BaseController;
use Model\Common\AdminModel;
use Model\Common\WorkOrderModel;
use Model\Common\WorkOrderRecordModel;
use Lib\Validate\Admin\WorkOrderValidate;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 校验id
        $this->checkID( $id );
        // 校验请求参数
        (new WorkOrderValidate())->paramsCheck( 'patch', $this->request, $this->response );
        // 获取请求参数
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'status' && in_array( (int)$param, array_keys( WorkOrderModel::WORK_ORDER_STATUS_LIST ) )) $params['status'] = (int)$param;
        }
        // 检查记录是否存在
        $checkWorkOrder = WorkOrderModel::query()->where( 'id', $id )->first();
        if (!$checkWorkOrder)
            return $this->lang->set( 184 );
        $params['original_status'] = $checkWorkOrder->status ?? WorkOrderModel::STATUS_UNCONFIRMED; // 防止覆盖原始状态值
        // 检查数据是否发生改变
        $checkChange = $this->checkParamsChange( $checkWorkOrder, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        // 检查更新状态是否发生改变改变
        if ($params['original_status'] == $params['status'])
            return $this->lang->set( 202 );
        // 检查权限
        $flag = WorkOrderModel::checkWorkOrderPermission( $params['original_status'], $params['status'] );
        if ($flag === false)
            return $this->lang->set( 186 );
        // 检查工单备注信息的附件
        $this->checkWorkOrderAppendix( $params );

        DB::pdo()->beginTransaction();
        try {
            // 修改工单的状态
            if ($params['status'] == WorkOrderModel::STATUS_FINISHED) {
                // 完成时的数据
                $params['finished_admin_id'] = $this->playLoad['admin_id'];
                $params['finished_admin']    = $this->playLoad['admin_name'];
                $params['finished_at']       = date( 'Y-m-d H:i:s', time() );
            }
            $res = WorkOrderModel::patchModel( $checkWorkOrder, $params );
            if (!$res)
                throw new DateException( 139 );
            // 新增工单操作记录
            $record = [
                'work_order_id'    => $id,
                'remark'           => $params['remark'] ?? '', // 备注信息
                'appendix'         => $params['appendix'] ?? '', // 备注附件
                'type'             => WorkOrderRecordModel::getRecordType( $params['original_status'], $params['status'] ), // 基于工单新旧状态获取工单记录类型
                'reply_admin_id'   => $this->playLoad['admin_id'],
                'reply_admin_name' => $this->playLoad['admin_name'],
            ];
            // 写记录
            $result = WorkOrderRecordModel::addModel( new WorkOrderRecordModel(), $record );
            if (!$result)
                throw new DateException( 185 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};


<?php

use Model\DB;
use Model\Common\AdminModel;
use Model\Common\WorkOrderModel;
use Model\Common\WorkOrderRecordModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\WorkOrderValidate;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 校验订单
        $this->checkID( $id );
        // 获取请求参数
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'appendix' && !empty( $params['appendix'] )) $params['appendix'] = trim( $params['appendix'], ',' ); // 格式化代码
        }
        // 检查记录是否存在
        $checkWorkOrder = WorkOrderModel::query()->where( 'id', $id )->first();
        if (!$checkWorkOrder)
            return $this->lang->set( 184 );
        // 校验状态
        if (!in_array( $checkWorkOrder->status, array_keys( WorkOrderModel::WORK_ORDER_STATUS_LIST ) ))
            throw new DateException( 201 );
        // 检查数据是否发生改变
        $checkChange = $this->checkParamsChange( $checkWorkOrder, $params );
        if ($checkChange === 0)
            return $this->lang->set( 122 );
        //附件工单的校验
        $this->checkWorkOrderAppendix($params);

        DB::pdo()->beginTransaction();
        try {
            // 更新工单
            $res = WorkOrderModel::updateModel( $checkWorkOrder, $params );
            if (!$res)
                throw new DateException( 132 );
            // 新增工单操作记录
            $record = [
                'work_order_id'    => $id,
                'type'             => WorkOrderRecordModel::TYPE_EDITED, // 编辑
                'reply_admin_id'   => $this->playLoad['admin_id'], // 新建工单账户id
                'reply_admin_name' => $this->playLoad['admin_name'] // 新建工单账户名
            ];
            $result = WorkOrderRecordModel::addModel( new WorkOrderRecordModel(), $record ); // 仅新增默认备注
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


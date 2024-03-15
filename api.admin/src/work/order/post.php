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

    public function run()
    {
        // 校验请求参数
        (new WorkOrderValidate())->paramsCheck( 'post', $this->request, $this->response );
        $workOrderModel       = new WorkOrderModel();
        $workOrderRecordModel = new WorkOrderRecordModel();
        // 获取请求参数
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'appendix' && !empty( $params['appendix'] )) $params['appendix'] = trim( $params['appendix'], ',' ); // 格式化代码
        }
        $params['created_admin_id'] = $this->playLoad['admin_id']; // 新建工单账户id
        $params['created_admin']    = $this->playLoad['admin_name']; // 新建工单账户名
        //附件工单的校验
        $this->checkWorkOrderAppendix($params);

        DB::pdo()->beginTransaction();
        try {
            // 新增工单
            $res = WorkOrderModel::addModel( $workOrderModel, $params );
            if (!$res)
                throw new DateException( 132 );
            // 新增工单操作记录
            $record = [
                'work_order_id'    => $workOrderModel->id,
                'type'             => WorkOrderRecordModel::TYPE_CREATED, // 新增类型(默认值)
                'reply_admin_id'   => $this->playLoad['admin_id'],
                'reply_admin_name' => $this->playLoad['admin_name'],
            ];
            $result = WorkOrderRecordModel::addModel( $workOrderRecordModel, $record );
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


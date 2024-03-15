<?php

use Model\DB;
use Logic\Admin\BaseController;
use Model\Common\WorkOrderRecordModel;
use Lib\Validate\Admin\WorkOrderRecordValidate;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 校验请求参数
        (new WorkOrderRecordValidate())->paramsCheck( 'post', $this->request, $this->response );
        $workOrderRecordModel = new WorkOrderRecordModel();
        // 获取请求参数
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }
        // 校验备注信息
        if (empty( $params['remark'] ))
            throw new DateException( 187 );
        // 封装参数
        $params['type']             = WorkOrderRecordModel::TYPE_REMARK; // 添加备注类型
        $params['reply_admin_id']   = $this->playLoad['admin_id']; // 新建工单账户id
        $params['reply_admin_name'] = $this->playLoad['admin_name']; // 新建工单账户名

        DB::pdo()->beginTransaction();
        try {
            $result = WorkOrderRecordModel::addModel( $workOrderRecordModel, $params );
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


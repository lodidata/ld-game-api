<?php

use Model\DB;
use Model\Common\AgentModel;
use Logic\Admin\BaseController;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 校验id是否合法
        $this->checkID( $id );
        // 获取写日志所需的表名
        $agentModelObj = new AgentModel();
        $table         = $agentModelObj->getTable();
        // 获取所有的请求参数
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param ); // 校验type
        }
        $type = $params['type'] ?? 0; // 开关类型
        // 获取或校验代理
        $agentModel = $agentModelObj::query()->where( 'id', $id )->first();
        if (!$agentModel || !in_array( $type, [1, 2, 3, 4, 5] ))
            return $this->lang->set( 146 );

        // 重新实现是为了能账户日志，更新成功了，数据没变的情况
        $isExchange = 0; // 是否改变
        switch ($type) {
            case 1: // 修改状态 0：禁用 1：启用
                $status = $agentModel->status === AgentModel::STATUS_ON ? AgentModel::STATUS_OFF : AgentModel::STATUS_ON;
                $agentModel->status !== $status && $isExchange = 1;
                $agentModel->status = $status;
                break;
            case 2: // 是否允许注册登录 0：禁用 1：开启(默认)
                $isAllowLogin = $agentModel->is_allow_login === AgentModel::IS_ALLOW_LOGIN_ON ? AgentModel::IS_ALLOW_LOGIN_OFF : AgentModel::IS_ALLOW_LOGIN_ON;
                $agentModel->is_allow_login !== $isAllowLogin && $isExchange = 1;
                $agentModel->is_allow_login = $isAllowLogin;
                break;
            case 3: // 是否允许转账 0：禁用 1：开启(默认)
                $isAllowTransfer = $agentModel->is_allow_transfer === AgentModel::IS_ALLOW_TRANSFER_ON ? AgentModel::IS_ALLOW_TRANSFER_OFF : AgentModel::IS_ALLOW_TRANSFER_ON;
                $agentModel->is_allow_transfer !== $isAllowTransfer && $isExchange = 1;
                $agentModel->is_allow_transfer = $isAllowTransfer;
                break;
            case 4: // 是否允许拉单 0：禁用 1：开启(默认)
                $isAllowOrder = $agentModel->is_allow_order === AgentModel::IS_ALLOW_ORDER_ON ? AgentModel::IS_ALLOW_ORDER_OFF : AgentModel::IS_ALLOW_ORDER_ON;
                $agentModel->is_allow_order !== $isAllowOrder && $isExchange = 1;
                $agentModel->is_allow_order = $isAllowOrder;
                break;
            case 5: // 是否开启限制充值金额  0：禁用(默认) 1：开启
                $isLimitRecharge = $agentModel->is_limit_recharge === AgentModel::IS_LIMIT_RECHARGE_ON ? AgentModel::IS_LIMIT_RECHARGE_OFF : AgentModel::IS_LIMIT_RECHARGE_ON;
                $agentModel->is_limit_recharge !== $isLimitRecharge && $isExchange = 1;
                $agentModel->is_limit_recharge = $isLimitRecharge;
                break;
            default:
                return $this->lang->set( 146 );
        }

        // 检查数据是否发生改变
        if ($isExchange === 0)
            return $this->lang->set( 122 );

        DB::pdo()->beginTransaction();
        try {
            $res = $agentModel->save();
            if (!$res)
                throw new DateException( 139 );
            $this->writeAdminLog( $agentModel->toArray(), $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $agentModel->toArray(), $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
<?php

use Model\Common\AgentModel;
use Model\DB;
use Model\Common\WhiteListModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\WhiteListValidate;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new WhiteListValidate())->paramsCheck( 'post', $this->request, $this->response );
        // 格式化获取请求参数并封装特定参数
        $WhiteListModel = new WhiteListModel();
        $table          = $WhiteListModel->getTable();
        $params         = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param ) && is_string( $param )) $params[$key] = trim( $param );
            if (!empty( $params['agent_code'] )) $params['agent_code'] = strtolower( $params['agent_code'] );
            // 校验ip
            if (!empty( $params['ip'] ) && filter_var( $params['ip'], FILTER_VALIDATE_IP ))
                $params['ip'] = ip2long( $params['ip'] );
            else if (!empty( $params['ip'] ) && !filter_var( $params['ip'], FILTER_VALIDATE_IP ))
                return $this->lang->set( 144 );
            else
                return $this->lang->set( 131 );
        }
        $params['admin_id']   =$this->playLoad['admin_id'];
        $params['admin_name'] = $this->playLoad['admin_name'];

        //判断代理商是否存在
        $agent = AgentModel::getOne($params['agent_code']);
        if (is_null($agent))
            return $this->lang->set(134);
        if ($agent['status'] === AgentModel::STATUS_OFF)
            return $this->lang->set(150);

        // 检查是否已提交(幂等性)
        $checkRes = WhiteListModel::query()->where( 'agent_code', $params['agent_code'] )->where( 'ip', $params['ip'] )->first();
        if ($checkRes)
            return $this->lang->set( 127 );

        DB::pdo()->beginTransaction();
        try {
            // 写入白名单
            $res = WhiteListModel::addModel( $WhiteListModel, $params );
            if (!$res) {
                throw new DateException( 132 );
            }
            $this->writeAdminLog( $params, $table, $WhiteListModel->id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( [], $table, 0, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};


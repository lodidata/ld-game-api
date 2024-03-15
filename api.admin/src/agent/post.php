<?php

use Model\DB;
use Logic\Admin\BaseController;
use Model\Common\AgentModel;
use Lib\Validate\Admin\AgentValidate;
use Respect\Validation\Exceptions\DateException;
use Utils\Utils;

return new class extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new AgentValidate())->paramsCheck( 'post', $this->request, $this->response );
        // 获取请求参数
        $AgentModel = new AgentModel();
        $table      = $AgentModel->getTable();
        $params     = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }
        // 检查品牌名称是否重复
        $checkBrandName = AgentModel::query()->where( 'brand_name', $params['brand_name'] )->value( 'id' );
        if ($checkBrandName)
            return $this->lang->set( 133 );
        // 唯一性校验并赋值：agent_code、secret_key
        $agentCode    = strtolower( getRandStr( 4 ) ); // 定长
        $checkCodeRes = AgentModel::query()->where( 'agent_code', $agentCode )->where( 'status', AgentModel::STATUS_ON )->value( 'id' ) ?? 0;
        while ($checkCodeRes > 0) {
            $agentCode    = strtolower( getRandStr( 4 ) ); // 定长
            $checkCodeRes = AgentModel::query()->where( 'agent_code', $agentCode )->value( 'id' );
        }
        $params['agent_code'] = $agentCode; // 全表唯一
        $params['secret_key'] = Utils::RSAEncrypt( getRandStr( 32 ) ); // 全表唯一

        DB::pdo()->beginTransaction();
        try {
            // 新增代理
            $res = AgentModel::addModel( $AgentModel, $params );
            if (!$res)
                throw new DateException( 132 );
            $this->writeAdminLog( $params, $table, $AgentModel->id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( [], $table, 0, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};


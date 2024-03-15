<?php

use Model\DB;
use Logic\Admin\BaseController;
use Model\Common\AgentModel;
use Respect\Validation\Exceptions\DateException;
use Lib\Validate\Admin\BillValidate;
use Model\Common\BillModel;
use Model\Common\GameMenuModel;
use Model\Common\AgentGameModel;

/**
 * 新增账单
 */
return new class extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new BillValidate())->paramsCheck('post', $this->request, $this->response);
        // 获取请求参数
        $model  = new BillModel();
        $table  = $model->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty($param)) $params[$key] = trim($param);
        }
        if (!$params['start_bill_date'] || !$params['end_bill_date']) {
            return $this->lang->set(161);
        }
        //检测代理账号
        $agent = AgentModel::getOne($params['agent_code']);
        if (is_null($agent)) {
            return $this->lang->set(134);
        }
        //检测品牌名
        if (isset($agent['brand_name']) && $agent['brand_name'] != $params['brand_name']) {
            return $this->lang->set(163);
        }
        //检测游戏厂商
        if (AgentGameModel::isExist($params['agent_code'], $params['menu_type']) === false) {
            return $this->lang->set(164);
        }
        //检测币种
        if ($params['currency_id'] != $agent['currency_id']) {
            return $this->lang->set(165);
        }
        // 校验费率
        if (bccomp($params['rate'], 99.99, 2) == 1)
            return $this->lang->set(191);

        DB::pdo()->beginTransaction();
        try {
            // 新增账单
            $res = BillModel::addModel($model, $params);
            if (!$res)
                throw new DateException(132);
            $this->writeAdminLog($params, $table, $model->id, 1);

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog([], $table, 0, 0);
            return $this->lang->set($e->getMessage());
        }

        return $this->lang->set(0);
    }
};


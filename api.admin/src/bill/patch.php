<?php

use Lib\Validate\Admin\BillValidate;
use Logic\Admin\BaseController;

/**
 * 交收金额计算
 */
return new class extends BaseController {
    public function run()
    {
        // 验证请求参数
        (new BillValidate())->paramsCheck('patch', $this->request, $this->response);
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty($param)) $params[$key] = trim($param);
        }
        //交收金额 = 输赢 * 费率 * 汇率
        $settlement = bcmul($params['win_lose_bet'] * $params['rate'] / 100, $params['exchange_rate'], 2);
        return $this->lang->set(0, [], ['settlement' => $settlement]);
    }
};
<?php

use Logic\Admin\BaseController;
use Lib\Validate\Admin\BillValidate;
use Model\Common\BillModel;

/**
 * 表-导出
 */
return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        // 'verifyToken', 'authorize',
    ];

    public function run()
    {
        // 验证请求参数
        (new BillValidate())->paramsCheck('report_get', $this->request, $this->response);
        // 获取所有的请求参数
        $params = $this->request->getParams();

        //入参校验
        $obj    = BillModel::query()->leftJoin('currency', 'bill.currency_id', 'currency.id');
        $column = ['bill.id', 'bill.agent_code', 'bill.brand_name', 'bill.menu_type', 'bill.start_bill_date', 'bill.end_bill_date', 'currency.currency_type', 'bill.valid_bet', 'bill.win_lose_bet', 'bill.rate', 'bill.exchange_rate', 'bill.settlement', 'bill.created_at', 'bill.updated_at'];
        if (!empty($params['ids'])) {
            //判断格式
            if (!is_string($params['ids'])) {
                return $this->lang->set(131);
            }
            $list = $obj->whereIn('bill.id', explode(',', $params['ids']))->get($column);
        } else {
            // 查询条件封装
            !empty($params['agent_code']) && $obj->where('agent_code', 'like', $params['agent_code'] . '%');
            !empty($params['brand_name']) && $obj->where('brand_name', 'like', $params['brand_name'] . '%');
            !empty($params['menu_type']) && $obj->where('menu_type', 'like', $params['menu_type'] . '%');
            !empty($params['currency_id']) && $obj->where('bill.currency_id', $params['currency_id']);
            !empty($params['start_bill_date']) && $obj->where('start_bill_date', '>=', $params['start_bill_date']);
            !empty($params['end_bill_date']) && $obj->where('end_bill_date', '>=', $params['end_bill_date']);
            !empty($params['rate']) && $obj->where('rate', $params['rate']);
            !empty($params['exchange_rate']) && $obj->where('exchange_rate', $params['exchange_rate']);
            $list = $obj->orderBy('bill.id', 'desc')->get($column);
        }
        if ($list->isEmpty()) {
            $data = [];
        } else {
            $data = $list->toArray();
            foreach ($data as $k => $v) {
                $data[$k]['rate'] = $v['rate'] . '%';
            }
        }
        $field      = ['id', '代理账号', '品牌名称', '游戏厂商', '账单(起)日期', '账单(止)日期', '货币类型', '有效投注', '输赢', '费率', '汇率', '交收金额', '创建时间', '更新时间'];
        $config     = [
            'path' => getTmpDir() . '/',
        ];
        $fileName   = 'bill' . date('YmdHis') . '.xlsx';
        $xlsxObject = new \Vtiful\Kernel\Excel($config);
        // Init File
        $fileObject = $xlsxObject->fileName($fileName);
        // AutPut
        $filePath = $fileObject
            ->header($field)
            ->data($data)
            ->output();
        // Set Header
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        ob_clean();
        flush();
        if (copy($filePath, 'php://output') === false) {
            // Throw exception
            return $this->lang->set(147);
        }
        // Delete temporary file
        @unlink($filePath);
        die;
    }
};

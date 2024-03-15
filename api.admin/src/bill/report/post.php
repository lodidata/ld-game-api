<?php

use Logic\Admin\BaseController;
use Lib\Validate\Admin\BillValidate;
use Model\Common\BillModel;

/**
 * 表-解析
 */
return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        // 验证请求参数
        (new BillValidate())->paramsCheck('report_post', $this->request, $this->response);
        if (!$_FILES) {
            return $this->lang->set(177);
        }
        $adminId = $this->playLoad['admin_id'] ?? 0;
        $res     = BillModel::listBill($adminId, $_FILES['file']);
        if ($res['code'] == 0) {
            if (!empty($res['message'])) {
                return $this->lang->set($res['state'], $res['message']);
            }
            return $this->lang->set($res['state']);
        }
        $common          = [];
        $common['total'] = count($res['data']);
        return $this->lang->set(0, [], $res['data'], $common);
    }
};

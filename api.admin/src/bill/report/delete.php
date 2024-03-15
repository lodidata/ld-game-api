<?php

use Logic\Admin\BaseController;
use Model\Common\BillModel;

/**
 * 表-删除
 */
return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        if ($id < 0) {
            return $this->lang->set( 3 );
        }
        $adminId = $this->playLoad['admin_id'];
        $res     = BillModel::delCacheBill( $adminId, $id );
        if ($res == 0) {
            return $this->lang->set( 123 );
        }
        return $this->lang->set( 0 );
    }
};
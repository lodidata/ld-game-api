<?php

use Model\Common\BillModel;
use Logic\Admin\BaseController;

/**
 * 详情
 */
return new class() extends BaseController {

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID( $id );
        // 检查该记录是否存在
        $obj = BillModel::query()
            ->select( ['bill.*', 'currency.currency_type'] )
            ->leftJoin( 'currency', 'bill.currency_id', 'currency.id' )
            ->where( 'bill.id', $id )
            ->first();
        if (!$obj)
            return $this->lang->set( 126 );

        // 响应数据
        return $this->lang->set( 0, [], $obj->toArray(), [] );
    }
};

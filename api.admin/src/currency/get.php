<?php

use Model\Common\CurrencyModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\CurrencyValidate;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new CurrencyValidate())->paramsCheck( 'get', $this->request, $this->response );
        // 请求参数的集合(array)
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'page' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->pageSize;
        }

        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 查询条件封装
        $currencyObj = CurrencyModel::query();
        !empty( $params['currency_id'] ) && $currencyObj->where( 'id', $params['currency_id'] ); // 货币id
        !empty( $params['currency_type'] ) && $currencyObj->where( 'currency_type', $params['currency_type'] ); // 货币类型
        !empty( $params['currency_name'] ) && $currencyObj->where( 'currency_name', 'like', $params['currency_name'] . '%' ); // 货币名称模糊检索
        !empty( $params['updated_at_from'] ) && strtotime( $params['updated_at_from'] ) > 0 && $currencyObj->where( 'updated_at', '>=', $params['updated_at_from'] . ' 00:00:00' ); // 维护起时间
        !empty( $params['updated_at_to'] ) && strtotime( $params['updated_at_to'] ) > 0 && $currencyObj->where( 'updated_at', '<=', $params['updated_at_to'] . ' 23:59:59' ); // 维护止时间
        // 统计总的记录数
        $common['total'] = $currencyObj->count() ?? 0;
        // 获取搜索分页列表
        $currencyList = $currencyObj->forPage( $params['page'], $params['page_size'] )->orderBy( 'created_at', 'desc' )->get()->toArray();

        return $this->lang->set( 0, [], $currencyList, $common );
    }
};

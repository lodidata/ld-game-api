<?php

use Model\Common\GameTypeModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\GameTypeValidate;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new GameTypeValidate())->paramsCheck( 'get', $this->request, $this->response );
        // 请求参数的集合(array)
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'type_code' && !empty( $params['type_code'] )) $params['type_code'] = strtoupper( $params[$key] );
            if ($key === 'page' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 封装查询(部分字段支持范围检索)
        $gameTypeObj = GameTypeModel::query();
        !empty( $params['type_code'] ) && $gameTypeObj->where( 'type_code', $params['type_code'] ); // 类型编号支持模糊查询
        !empty( $params['type_name'] ) && $gameTypeObj->where( 'type_name', 'like', $params['type_name'] . '%' ); // 类型名称支持模糊查询
        isset( $params['status'] ) && is_numeric( $params['status'] ) && in_array( 'status', array_keys( GameTypeModel::STATUS_ARR ) ) && $gameTypeObj->where( 'status', $params['status'] ); // 状态查询
        // 统计总记录数
        $common['total'] = $gameTypeObj->count() ?? 0;
        // 分页查询
        $gameTypeList = $gameTypeObj->forPage( $common['page'], $common['page_size'] )->orderBy( 'created_at', 'desc' )->get()->toArray();
        // 格式化列表
        foreach ($gameTypeList as $key => $gameType) {
            $gameTypeList[$key]['status_str'] = GameTypeModel::STATUS_ARR[$gameType['status']] ?? '未知';
        }

        return $this->lang->set( 0, [], $gameTypeList, $common );
    }
};

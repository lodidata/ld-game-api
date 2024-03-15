<?php

use Model\Common\Game3thModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\Game3thValidate;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new Game3thValidate())->paramsCheck( 'get', $this->request, $this->response );
        // 格式化并封装数据
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'menu_type' && !empty( $params['menu_type'] )) $params['menu_type'] = strtoupper( $params[$key] );
            if ($key === 'page' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 查询条件封装
        $game3thObj = Game3thModel::query();
        !empty( $params['game_name'] ) && $game3thObj->where( 'kind_name', 'like', $params['game_name'] . '%' ); // 游戏名称模糊搜索
        !empty( $params['menu_type'] ) && $game3thObj->where( 'menu_type', 'like', $params['menu_type'] . '%' ); // 支持模糊查询
        !empty( $params['game_type'] ) && $game3thObj->where( 'game_type', 'like', $params['game_type'] ); // 游戏类型模糊搜索
        isset( $params['status'] ) && is_numeric( $params['status'] ) && in_array( $params['status'], array_keys( Game3thModel::STATUS_ARR ) ) && $game3thObj->where( 'status', $params['status'] ); // 状态搜索
        !empty( $params['start_created_at'] ) && $game3thObj->where( 'created_at', '>=', $params['start_created_at'] . ' 00:00:00' ); // 起时间搜索
        !empty( $params['end_created_at'] ) && $game3thObj->where( 'created_at', '<=', $params['end_created_at'] . ' 23:59:59' ); // 止时间搜索
        // 统计总的记录数
        $common['total'] = $game3thObj->count() ?? 0;
        // 获取分页列表
        $game3thList = $game3thObj->forPage( $common['page'], $common['page_size'] )->orderBy( 'created_at', 'desc' )->get()->toArray();
        // 格式化列表
        foreach ($game3thList as $k => $game) {
            $game3thList[$k]['status_str']  = Game3thModel::STATUS_ARR[$game['status']] ?? '未知';
            $game3thList[$k]['is_demo_str'] = Game3thModel::IS_DEMO_ARR[$game['is_demo']] ?? '未知';
            $game3thList[$k]['game_name']   = $game['kind_name'];
        }

        return $this->lang->set( 0, [], $game3thList, $common );
    }
};

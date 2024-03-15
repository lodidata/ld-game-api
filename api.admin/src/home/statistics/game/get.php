<?php

use Model\Common\GameTypeModel;
use Model\Common\Game3thModel;
use Model\Common\GameMenuModel;
use Logic\Admin\BaseController;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken'
    ];

    public function run()
    {
        // 格式化并封装数据
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'page' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 获取分页列表
        $game3thObj       = Game3thModel::query()->where( 'status', Game3thModel::STATUS_ON )->groupBy( ['game_type'] );
        $gameGroupListObj = $game3thObj->get(); // 分组列表
        $game3thObj->selectRaw( 'game_type,COUNT(distinct menu_type) total_game_menu_num ,COUNT(id) AS total_game_num' );
        $game3thList = $game3thObj->orderBy( 'total_game_num', 'desc' )->forPage( $common['page'], $common['page_size'] )->get()->toArray();
        // 获取分组组总数
        $common['total'] = $gameGroupListObj->count() ?? 0;

        // 响应
        return $this->lang->set( 0, [], $game3thList, $common );
    }
};

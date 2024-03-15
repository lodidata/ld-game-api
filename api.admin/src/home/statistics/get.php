<?php

use Model\Common\AgentModel;
use Model\Common\BillModel;
use Model\Common\GameMenuModel;
use Model\Common\Game3thModel;
use Logic\Admin\BaseController;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        // 'verifyToken', 'authorize'
    ];

    public function run(): array
    {
        $statistics    = [];
        $startTime = date( 'Y-m-d 00:00:00', strtotime( '-1 month' ) ); // 一个月前的时间
        $endTime   = date( 'Y-m-d 23:59:59', time() ); // 今天时间
        /*代理统计*/
        // 代理对象
        $agentObj = AgentModel::query();
        // 代理统计
        $statistics['agent']['total'] = $agentObj->count( 'id' ) ?? 0;
        if ($statistics['agent']['total'] > 0)
            $statistics['agent']['nearly_one_month'] = $agentObj->where( 'created_at', '>=', $startTime )->where( 'created_at', '<=', $endTime )->count( 'id' ) ?? 0;
        else
            $statistics['agent']['nearly_one_month'] = 0;

        /*月结账单*/
        // 账单对象
        $billObj = BillModel::query();
        // 月账单统计
        $statistics['bill']['total'] = $billObj->count( 'id' ) ?? 0;
        if ($statistics['bill']['total'] > 0)
            $statistics['bill']['nearly_one_month'] = $billObj->where( 'created_at', '>=', $startTime )->where( 'created_at', '<=', $endTime )->count( 'id' ) ?? 0;
        else
            $statistics['bill']['nearly_one_month'] = 0;

        /*厂商统计*/
        // 游戏厂商对象
        $gameMenuObj = GameMenuModel::query();
        // 游戏厂商统计
        $statistics['game_menu']['total'] = $gameMenuObj->count( 'id' ) ?? 0;
        if ($statistics['game_menu']['total'] > 0)
            $statistics['game_menu']['nearly_one_month'] = $gameMenuObj->where( 'created_at', '>=', $startTime )->where( 'created_at', '<=', $endTime )->count( 'id' ) ?? 0;
        else
            $statistics['game_menu']['nearly_one_month'] = 0;

        /*游戏统计*/
        // 第三方游戏对象
        $game3thObj = Game3thModel::query();
        // 第三方游戏统计
        $statistics['game_3th']['total'] = $game3thObj->count( 'id' ) ?? 0;
        if ($statistics['game_3th']['total'] > 0)
            $statistics['game_3th']['nearly_one_month'] = $game3thObj->where( 'created_at', '>=', $startTime )->where( 'created_at', '<=', $endTime )->count( 'id' ) ?? 0;
        else
            $statistics['game_3th']['nearly_one_month'] = 0;

        return $statistics;
    }
};

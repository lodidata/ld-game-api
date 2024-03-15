<?php

use Model\Common\GameMenuModel;
use Model\Common\GameMenuCurrencyModel;
use Model\Common\CurrencyModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\GameMenuValidate;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new GameMenuValidate())->paramsCheck( 'get', $this->request, $this->response );
        // 请求参数的集合(array)
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
        $gameMenuObj = GameMenuModel::query();
        !empty( $params['menu_type'] ) && $gameMenuObj->where( 'menu_type', $params['menu_type'] ); // 厂商标识模糊查询
        !empty( $params['menu_name'] ) && $gameMenuObj->where( 'menu_name', 'like', $params['menu_name'] . '%' ); // 厂商名称糊查询
        isset( $params['status'] ) && is_numeric( $params['status'] ) && in_array( $params['status'], array_keys( GameMenuModel::STATUS_ARR ) ) && $gameMenuObj->where( 'status', $params['status'] ); // 状态查询
        isset( $params['work_status'] ) && is_numeric( $params['work_status'] ) && in_array( $params['work_status'], array_keys( GameMenuModel::WORK_STATUS_ARR ) ) && $gameMenuObj->where( 'work_status', $params['work_status'] ); // 工作状态查询
        !empty( $params['start_created_at'] ) && $gameMenuObj->where( 'created_at', '>=', $params['start_created_at'] . ' 00:00:00' ); // 创建起时间
        !empty( $params['end_created_at'] ) && $gameMenuObj->where( 'created_at', '<=', $params['end_created_at'] . ' 23:59:59' ); // 创建止时间
        // 统计总的记录数
        $common['total'] = $gameMenuObj->count() ?? 0;
        // 分页列表
        $gameMenuList = $gameMenuObj->forPage( $common['page'], $common['page_size'] )->orderBy( 'created_at', 'desc' )->get()->toArray();
        // 格式化列表
        $gameMenuIds = $gameMenuCurrencyList = [];
        foreach ($gameMenuList as $key => $gameMenu) {
            $gameMenuList[$key]['status_str']      = GameMenuModel::STATUS_ARR[$gameMenu['status']] ?? '未知';
            $gameMenuList[$key]['work_status_str'] = GameMenuModel::WORK_STATUS_ARR[$gameMenu['work_status']] ?? '未知';
            if (empty( $gameMenu['start_uworked_at'] ) || strtotime( $gameMenu['start_uworked_at'] ) <= 0) $gameMenuList[$key]['start_uworked_at'] = '';
            if (empty( $gameMenu['end_uworked_at'] ) || strtotime( $gameMenu['end_uworked_at'] ) <= 0) $gameMenuList[$key]['end_uworked_at'] = '';
            $gameMenuIds[] = $gameMenu['id']; // 厂商ids
        }
        // 获取厂商货币列表
        if ($gameMenuIds) {
            $gameMenuCurrencyObj = CurrencyModel::query()->join( 'game_menu_currency AS gmc', 'gmc.currency_id', 'currency.id' );
            if (!empty( $params['currency_id'] )) $gameMenuCurrencyObj->where( 'currency.id', $params['currency_id'] ); // 货币id检索
            $gameMenuCurrencyList = $gameMenuCurrencyObj->whereIn( 'gmc.game_menu_id', $gameMenuIds )->get( ['gmc.*', 'currency.currency_type AS currency'] )->toArray();
        }
        // 格式化
        $newGameMenuList = [];
        foreach ($gameMenuList as $key => $gameMenu) {
            $currencyStr = '';
            $currencyIds = [];
            foreach ($gameMenuCurrencyList as $k => $currency) {
                if ($gameMenu['id'] === $currency['game_menu_id']) {
                    $currencyStr   .= $currency['currency'] . ',';
                    $currencyIds[] = $currency['currency_id'];
                }
            }
            if (!empty( $params['currency_id'] ) && !empty( $currencyIds )) {
                $item                 = $gameMenu;
                $item['currency']     = trim( $currencyStr, ',' );
                $item['currency_ids'] = $currencyIds;
                $newGameMenuList[]    = $item;
            } else if (empty( $params['currency_id'] )) {
                $item                 = $gameMenu;
                $item['currency']     = trim( $currencyStr, ',' );
                $item['currency_ids'] = $currencyIds;
                $newGameMenuList[]    = $item;
            }
        }

        return $this->lang->set( 0, [], $newGameMenuList, $common );
    }
};

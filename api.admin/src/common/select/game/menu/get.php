<?php

use Model\Common\GameMenuModel;
use Model\Common\GameMenuCurrencyModel;
use Logic\Admin\BaseController;

return new class() extends BaseController {
    public function run(): array
    {
        $gameMenuObj  = GameMenuModel::query()->where( 'status', GameMenuModel::STATUS_ON );
        $gameMenuIds  = $gameMenuObj->pluck( 'id' )->toArray();
        $gameMenuList = $gameMenuObj->get( ['id', 'menu_type', 'api_config'] )->toArray();
        $currencyList = GameMenuCurrencyModel::query()
            ->leftJoin( 'currency', 'game_menu_currency.currency_id', 'currency.id' )
            ->whereIn( 'game_menu_currency.game_menu_id', $gameMenuIds )
            ->get( ['game_menu_currency.game_menu_id', 'currency.id', 'currency.currency_type AS currency'] )
            ->toArray();
        foreach ($gameMenuList as $key => $gameMenu) {
            $currencyTypeStr = '';
            foreach ($currencyList as $currency) {
                if ($gameMenu['id'] === $currency['game_menu_id']) {
                    $currencyTypeStr .= $currency['currency'] . ',';
                }
            }
            if ($currencyTypeStr) $currencyTypeStr = rtrim( $currencyTypeStr, ',' );
            $gameMenuList[$key]['currency'] = $currencyTypeStr;
        }
        
        return $gameMenuList;
    }
};

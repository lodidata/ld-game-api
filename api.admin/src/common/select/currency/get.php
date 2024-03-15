<?php

use Model\Common\CurrencyModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\CurrencyValidate;

return new class() extends BaseController {
    public function run(): array
    {
        $currencyList    = CurrencyModel::query()->orderBy('created_at','desc')->get( ['id', 'currency_type'] )->toArray();
        $newCurrencyList = [];
        foreach ($currencyList as $currency) {
            $newCurrencyList[$currency['id']] = $currency['currency_type'];
        }

        return $newCurrencyList;
    }
};

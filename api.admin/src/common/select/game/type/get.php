<?php

use Model\Common\GameTypeModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\GameTypeValidate;

return new class() extends BaseController {
    public function run(): array
    {
        $gameTypeList = GameTypeModel::query()->groupBy( ['type_code'] )->pluck( 'type_code' )->toArray();
        foreach ($gameTypeList as $key => $item) {
            if (!$item)
                unset( $gameTypeList[$key] ); // 格式化，存在空值的情况
        }

        return $gameTypeList;
    }
};

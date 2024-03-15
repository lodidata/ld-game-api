<?php

use Model\DB;
use Logic\Define\CacheKey;
use Model\Common\GameMenuModel;
use Model\Common\CurrencyModel;
use Model\Common\GameMenuCurrencyModel;
use Logic\Admin\BaseController;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID( $id );
        // 获取请求参数
        $table  = (new GameMenuCurrencyModel())->getTable();
        $params = $this->request->getParams();
        if (empty( $params['currency_id_str'] ))
            return $this->lang->set( 208 );
        // 基于货币类型获取货币id
        $currencyIds = explode( ',', rtrim(trim($params['currency_id_str']),','));
        // 校验游戏厂商
        $checkGameMenu = GameMenuModel::query()->where( 'id', $id )->first();
        if (!$checkGameMenu)
            return $this->lang->set( 198 );
        $oldCurrencyIds = GameMenuCurrencyModel::query()->where( 'game_menu_id', $id )->pluck( 'currency_id' )->toArray();
        // 如果2个数组王泉相同则不允许后续逻辑
        if (!array_diff( $currencyIds, $oldCurrencyIds ) && !array_diff( $oldCurrencyIds, $currencyIds ))
            return $this->lang->set( 122 );
        // 新的货币列表不能为空

        DB::pdo()->beginTransaction();
        try {
            // 批量删除非交集的数据
            if ($oldCurrencyIds) {
                $batchDelRes = GameMenuCurrencyModel::query()->where( 'game_menu_id', $id )->whereIn( 'currency_id', $oldCurrencyIds )->forceDelete();
                if (!$batchDelRes)
                    throw new DateException ( 123 );
            }
            // 新增
            foreach ($currencyIds as $currencyId) {
                $insertRes = GameMenuCurrencyModel::query()->insertGetId( ['game_menu_id' => $id, 'currency_id' => $currencyId] );
                if (!$insertRes)
                    throw new DateException ( 132 );
            }
            // 写日志
            $this->writeAdminLog( $params, $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $params, $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
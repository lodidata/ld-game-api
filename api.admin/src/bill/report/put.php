<?php

use Model\DB;
use Logic\Admin\BaseController;
use Logic\Define\CacheKey;
use Model\Common\BillModel;

/**
 * 表-导入
 */
return new class extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        $adminId = $this->playLoad['admin_id'] ?? 0;
        $res     = BillModel::listCacheBill( $adminId );
        if ($res['code'] == 0) {
            if (!empty( $res['message'] )) {
                return $this->lang->set( $res['state'], $res['message'] );
            }
            return $this->lang->set( $res['state'] );
        }
        $billArr = $res['data'];
        $size    = count( $billArr );

        //插入bill表
        DB::beginTransaction();
        try {
            $sql       = "INSERT INTO bill(agent_code, brand_name, menu_type, start_bill_date, end_bill_date, currency_id, valid_bet, win_lose_bet, rate, exchange_rate, settlement) VALUES ";
            $batchSize = 3000;
            for ($i = 0; $i < $size; $i += $batchSize) {
                $values = [];
                for ($j = $i; $j < $i + $batchSize && $j < $size; $j++) {
                    $values[] = <<<BILL
                        (
                            "{$billArr[$j]['agent_code']}",
                            "{$billArr[$j]['brand_name']}",
                            "{$billArr[$j]['menu_type']}",
                            "{$billArr[$j]['start_bill_date']}",
                            "{$billArr[$j]['end_bill_date']}",
                            "{$billArr[$j]['currency_id']}",
                            {$billArr[$j]['valid_bet']},
                            {$billArr[$j]['win_lose_bet']},
                            {$billArr[$j]['rate']},
                            {$billArr[$j]['exchange_rate']},
                            {$billArr[$j]['settlement']}
                        )
                        BILL;
                }
                $sqlBatch = $sql . implode( ", ", $values );
                $res      = DB::insert( $sqlBatch );
                if (!$res) {
                    throw new Exception( '插入失败' );
                }
            }
            DB::commit();
            //删除billHash缓存
            $cacheKey     = CacheKey::$prefix['billHash'] . $adminId;
            $redisHandler = app()->redis;
            $redisHandler->del( $cacheKey );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->lang->set( $e->getMessage() );
        }
        return $this->lang->set( 0 );
    }
};


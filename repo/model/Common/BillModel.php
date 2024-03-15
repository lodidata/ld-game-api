<?php

namespace Model\Common;

use Illuminate\Database\Eloquent\Model;
use Logic\Define\CacheKey;
use Respect\Validation\Exceptions\DateException;
use Utils\CacheDb;
use Vtiful\Kernel\Excel;

class BillModel extends Model
{
    protected $table      = 'bill';
    protected $primaryKey = 'id';

    const CELLS_NUM = 11; // 列长度

    /**
     * 增加model
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function addModel(object $model, array $params): bool
    {
        $model->agent_code  = $params['agent_code'] ?? '';
        $model->brand_name  = $params['brand_name'] ?? '';
        $model->menu_type   = $params['menu_type'] ?? '';
        $model->currency_id = $params['currency_id'] ?? 0;
        return self::operatingElements($model, $params);
    }

    /**
     * 更新model
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function updateModel(object $model, array $params): bool
    {
        return self::operatingElements($model, $params);
    }

    /**
     * 操作元素
     * @param object $model
     * @param array $params
     * @return bool
     */
    public static function operatingElements(object $model, array $params): bool
    {
        $model->start_bill_date = $params['start_bill_date'] ?? '';
        $model->end_bill_date   = $params['end_bill_date'] ?? '';
        $model->valid_bet       = $params['valid_bet'] ?? 0.00;
        $model->win_lose_bet    = $params['win_lose_bet'] ?? 0.00;
        $model->rate            = $params['rate'] ?? 0.00;
        $model->exchange_rate   = $params['exchange_rate'] ?? 0.00;
        $model->settlement      = $params['settlement'] ?? 0.00;
        return $model->save();
    }

    /**
     * 获取账单列表
     * @param int $adminId 管理员id
     * @param array $file 文件路径
     * @return array
     */
    public static function listBill(int $adminId, array $file): array
    {
        //判断文件类型
        if (pathinfo($file['name'], PATHINFO_EXTENSION) != 'xlsx') {
            return ['code' => 0, 'state' => 170];
        }
        $fileError = $file['error'];
        if ($fileError != 0) {
            return ['code' => 0, 'state' => 30];
        }

        $path   = pathinfo($file['tmp_name']);
        $config = ['path' => $path['dirname']];
        $excel  = new Excel($config);
        // 读取账单文件
        $data = $excel->openFile($path['basename'])
            ->openSheet()
            ->setType([
                Excel::TYPE_STRING,
                Excel::TYPE_STRING,
                Excel::TYPE_STRING,
                Excel::TYPE_TIMESTAMP,
                Excel::TYPE_TIMESTAMP,
                Excel::TYPE_STRING,
                Excel::TYPE_DOUBLE,
                Excel::TYPE_DOUBLE,
                Excel::TYPE_DOUBLE,
                Excel::TYPE_DOUBLE,
                Excel::TYPE_DOUBLE
            ])
            ->setSkipRows(1)
            ->getSheetData();
        if (!$data) {
            return ['code' => 0, 'state' => 171];
        }
        //删除旧账单缓存
        self::existCacheBill($adminId);
        $field    = ['agent_code', 'brand_name', 'menu_type', 'start_bill_date', 'end_bill_date', 'currency_id', 'valid_bet', 'win_lose_bet', 'rate', 'exchange_rate', 'settlement'];
        $billList = [];
        foreach ($data as $k => $v) {
            //判断列大小
            if (count($v) != self::CELLS_NUM) {
                return ['code' => 0, 'state' => 172, 'message' => [($k + 2)]];
            }
            //判断空
            $res = self::checkEmptyCell($v[0], $v[1], $v[2], $v[3], $v[4], $v[5], $v[6], $v[7], $v[8], $v[9], $v[10]);
            if ($res['code'] == 0) {
                return ['code' => 0, 'state' => 162, 'message' => [($k + 2), $res['data'] + 1]];
            }
            //判断数字列
            $res = self::checkNumericCell($v[6], $v[7], $v[8], $v[9], $v[10]);
            if ($res['code'] == 0) {
                return ['code' => 0, 'state' => 162, 'message' => [($k + 2), $res['data'] + 1]];
            }
            //判断列数据类型
            if ((int)$v[3] <= 0) {
                return ['code' => 0, 'state' => 173, 'message' => [($k + 2), 4]];
            }
            //判断列数据类型
            if ((int)$v[4] <= 0) {
                return ['code' => 0, 'state' => 173, 'message' => [($k + 2), 5]];
            }
            //格式化日期
            if ((int)$v[3] >= (int)$v[4]) {
                return ['code' => 0, 'state' => 206, 'message' => [($k + 2), 4]];
            }
            $v[3] = date('Y-m-d', (int)$v[3]);
            $v[4] = date('Y-m-d', (int)$v[4]);
            //添加指定key
            $billList[] = array_combine($field, $v);
        }
        $agentList = AgentModel::select(['agent.agent_code', 'agent.brand_name', 'agent_game.menu_type', 'agent_game.rate', 'currency.id as currency_id', 'currency.currency_type'])
            ->leftJoin('agent_game', 'agent.agent_code', 'agent_game.agent_code')
            ->leftJoin('currency', 'agent.currency_id', 'currency.id')
            ->where('agent.status', AgentModel::STATUS_ON)
            ->where('agent_game.status', AgentGameModel::STATUS_ON)
            ->get()
            ->toArray();
        $agent     = [];
        foreach ($agentList as $v) {
            if (!isset($agent[$v['agent_code']])) {
                $agent[$v['agent_code']] = [
                    'brand_name'    => $v['brand_name'],
                    'currency_id'   => $v['currency_id'],
                    'currency_type' => $v['currency_type'],
                    'menu_type'     => [],
                    'rate'          => []
                ];
            }
            $agent[$v['agent_code']]['menu_type'][]    = $v['menu_type'];
            $rateKey                                   = $v['agent_code'] . '_' . $v['menu_type'];
            $agent[$v['agent_code']]['rate'][$rateKey] = $v['rate'];
        }
        foreach ($billList as $k => $v) {
            // 判断代理账号
            if (empty($agent[$v['agent_code']])) {
                return ['code' => 0, 'state' => 174, 'message' => [$k + 2, $v['agent_code']]];
            }
            //判断代理品牌
            if ($agent[$v['agent_code']]['brand_name'] != $v['brand_name']) {
                return ['code' => 0, 'state' => 197, 'message' => [$k + 2, $v['brand_name']]];
            }
            //判断代理币种
            if ($agent[$v['agent_code']]['currency_type'] != $v['currency_id']) {
                return ['code' => 0, 'state' => 199, 'message' => [$k + 2, $v['currency_id']]];
            }
            //判断代理厂商
            if (!in_array($v['menu_type'], $agent[$v['agent_code']]['menu_type'])) {
                return ['code' => 0, 'state' => 198, 'message' => [$k + 2, $v['menu_type']]];
            }
            //判断费率
            $rateKey = $v['agent_code'] . '_' . $v['menu_type'];
            if ($agent[$v['agent_code']]['rate'][$rateKey] != $v['rate']) {
                return ['code' => 0, 'state' => 205, 'message' => [$k + 2, $v['rate']]];
            }
            // 添加账单缓存
            $v['currency_id'] = $agent[$v['agent_code']]['currency_id'];
            BillModel::addCacheBill($adminId, $k, $v, $field);
            $billList[$k]['currency_id']   = $agent[$v['agent_code']]['currency_id'];
            $billList[$k]['currency_type'] = $agent[$v['agent_code']]['currency_type'];
        }
        return ['code' => 1, 'data' => $billList, 'message' => '成功'];
    }

    /**
     * 缓存账单列表
     * @param $adminId
     * @return array
     */
    public static function listCacheBill($adminId): array
    {
        $cacheKey     = CacheKey::$prefix['billHash'] . $adminId;
        $redisHandler = app()->redis;
        $bill         = $redisHandler->hGetall($cacheKey);
        if (!$bill) {
            return ['code' => 0, 'state' => 171];
        }
        $billJson = array_values($bill);
        $billArr  = [];
        foreach ($billJson as $k => $v) {
            //判断数据格式
            $v = isJson($v, true);
            if ($v === false) {
                return ['code' => 0, 'state' => 175];
            }
            //判断列大小
            if (count($v) != self::CELLS_NUM) {
                return ['code' => 0, 'state' => 172, 'message' => [($k + 2)]];
            }
            //判断空
            $res = self::checkEmptyCell($v['agent_code'], $v['brand_name'], $v['menu_type'], $v['start_bill_date'], $v['end_bill_date'], $v['currency_id'], $v['valid_bet'], $v['win_lose_bet'], $v['rate'], $v['exchange_rate'], $v['settlement']);
            if ($res['code'] == 0) {
                return ['code' => 0, 'state' => 162, 'message' => [($k + 2), $res['data'] + 1]];
            }
            //判断列数据类型
            if (isDate($v['start_bill_date']) === false) {
                return ['code' => 0, 'state' => 173, 'message' => [($k + 2), 4]];
            }
            //判断列数据类型
            if (isDate($v['end_bill_date']) === false) {
                return ['code' => 0, 'state' => 173, 'message' => [($k + 2), 5]];
            }
            $billArr[] = $v;
        }
        return ['code' => 1, 'data' => $billArr, 'message' => '成功'];
    }

    /**
     * 判断缓存账单是否存在
     * @param $adminId
     */
    public static function existCacheBill($adminId)
    {
        $cacheKey     = CacheKey::$prefix['billHash'] . $adminId;
        $redisHandler = app()->redis;
        if ($redisHandler->exists($cacheKey)) {
            $redisHandler->del($cacheKey);
        }
    }

    /**
     * 添加缓存账单
     * @param $adminId
     * @param $k
     * @param $bill
     * @param $field
     */
    public static function addCacheBill($adminId, $k, $bill, $field)
    {
        $cacheKey = CacheKey::$prefix['billHash'] . $adminId;
        CacheDb::make($cacheKey, function () use ($k, $bill, $field) {
            return [$k => json_encode(array_combine($field, $bill))];
        })->hSet();
    }

    /**
     * 删除缓存账单
     * @param int $adminId
     * @param int $key
     * @return int
     */
    public static function delCacheBill(int $adminId, int $key): int
    {
        $cacheKey     = CacheKey::$prefix['billHash'] . $adminId;
        $redisHandler = app()->redis;
        return $redisHandler->hDel($cacheKey, $key);
    }


    /**
     * 判断空列
     * @param $cell0
     * @param $cell1
     * @param $cell2
     * @param $cell3
     * @param $cell4
     * @param $cell5
     * @param $cell6
     * @param $cell7
     * @param $cell8
     * @param $cell9
     * @param $cell10
     * @return array
     */
    public static function checkEmptyCell($cell0, $cell1, $cell2, $cell3, $cell4, $cell5, $cell6, $cell7, $cell8, $cell9, $cell10): array
    {
        if (empty($cell0)) {
            return ['code' => 0, 'data' => 0];
        }
        if (empty($cell1)) {
            return ['code' => 0, 'data' => 1];
        }
        if (empty($cell2)) {
            return ['code' => 0, 'data' => 2];
        }
        if (empty($cell3)) {
            return ['code' => 0, 'data' => 3];
        }
        if (empty($cell4)) {
            return ['code' => 0, 'data' => 4];
        }
        if (empty($cell5)) {
            return ['code' => 0, 'data' => 5];
        }
        if (empty($cell6)) {
            return ['code' => 0, 'data' => 6];
        }
        if (empty($cell7)) {
            return ['code' => 0, 'data' => 7];
        }
        if (empty($cell8)) {
            return ['code' => 0, 'data' => 8];
        }
        if (empty($cell9)) {
            return ['code' => 0, 'data' => 9];
        }
        if (empty($cell10)) {
            return ['code' => 0, 'data' => 10];
        }
        return ['code' => 1];
    }

    /**
     * 判断数字列
     * @param $cell6
     * @param $cell7
     * @param $cell8
     * @param $cell9
     * @param $cell10
     * @return array
     */
    public static function checkNumericCell($cell6, $cell7, $cell8, $cell9, $cell10): array
    {
        if (!is_numeric($cell6) || $cell6 < 0) {
            return ['code' => 0, 'data' => 6];
        }
        if (!is_numeric($cell7) || $cell7 < 0) {
            return ['code' => 0, 'data' => 7];
        }
        if (!is_numeric($cell8) || $cell8 < 0) {
            return ['code' => 0, 'data' => 8];
        }
        if (!is_numeric($cell9) || $cell9 < 0) {
            return ['code' => 0, 'data' => 9];
        }
        if (!is_numeric($cell10) || $cell10 < 0) {
            return ['code' => 0, 'data' => 10];
        }
        return ['code' => 1];
    }

}
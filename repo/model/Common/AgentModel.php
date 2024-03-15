<?php

namespace Model\Common;

use \Illuminate\Database\Eloquent\Model;
use Logic\Define\CacheKey;
use Model\Event\AgentObserver;
use Respect\Validation\Exceptions\DateException;
use Utils\CacheDb;

class AgentModel extends Model
{
    protected $table = 'agent';
    protected $primaryKey = 'id';

    /**
     * 状态
     */
    const STATUS_OFF = 0; // 停用
    const STATUS_ON  = 1; // 已开通
    const STATUS_ARR = [
        self::STATUS_OFF => '停用',
        self::STATUS_ON  => '已开通',
    ];

    /*
     * 是否允许登录
     */
    const IS_ALLOW_LOGIN_OFF = 0; // 不允许
    const IS_ALLOW_LOGIN_ON  = 1; // 允许
    const IS_ALLOW_LOGIN_ARR = [
        self::IS_ALLOW_LOGIN_OFF => '不允许',
        self::IS_ALLOW_LOGIN_ON  => '允许',
    ];

    /**
     * 是否允许转账
     */
    const IS_ALLOW_TRANSFER_OFF = 0; // 不允许
    const IS_ALLOW_TRANSFER_ON  = 1; // 允许
    const IS_ALLOW_TRANSFER_ARR = [
        self::IS_ALLOW_TRANSFER_OFF => '不允许',
        self::IS_ALLOW_TRANSFER_ON  => '允许',
    ];

    /**
     * 是否允许转账
     */
    const IS_ALLOW_ORDER_OFF = 0; // 不允许
    const IS_ALLOW_ORDER_ON  = 1; // 允许
    const IS_ALLOW_ORDER_ARR = [
        self::IS_ALLOW_ORDER_OFF => '不允许',
        self::IS_ALLOW_ORDER_ON  => '允许',
    ];

    /**
     * 是否开启限制充值金额
     */
    const IS_LIMIT_RECHARGE_OFF = 0; // 不允许
    const IS_LIMIT_RECHARGE_ON  = 1; // 允许
    const IS_ALLOW_RECHARGE_ARR = [
        self::IS_LIMIT_RECHARGE_OFF => '不允许',
        self::IS_LIMIT_RECHARGE_ON  => '允许',
    ];
    /*
     * 钱包类型
     */
    const TRANSFER_WALLET_TYPE = 0; // 转账钱包
    const SINGLE_WALLET_TYPE   = 1; // 单一钱包
    const WALLET_TYPE_ARR      = [
        self::TRANSFER_WALLET_TYPE => '转账钱包',
        self::SINGLE_WALLET_TYPE   => '单一钱包',
    ];

    public static function boot()
    {
        parent::boot();
        self::observe(AgentObserver::class);
    }

    /**
     * 增加model
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function addModel(object $model, array $params): bool
    {
        $model->agent_code = $params['agent_code'] ?? '';
        $model->secret_key = $params['secret_key'] ?? '';
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
        $model->brand_name        = $params['brand_name'] ?? '';
        $model->currency_id       = $params['currency_id'] ?? '';
        $model->site_url          = $params['site_url'] ?? '';
        $model->callback_url      = $params['callback_url'] ?? '';
        $model->is_allow_login    = $params['is_allow_login'] ?? self::IS_ALLOW_LOGIN_ON;
        $model->is_allow_transfer = $params['is_allow_transfer'] ?? self::IS_ALLOW_TRANSFER_ON;
        $model->is_allow_order    = $params['is_allow_order'] ?? self::IS_ALLOW_ORDER_ON;
        $model->is_limit_recharge = $params['is_limit_recharge'] ?? self::IS_LIMIT_RECHARGE_OFF;
        // 限制充值金额 校验
        if ($params['is_limit_recharge'] == self::IS_LIMIT_RECHARGE_ON) {
            if (!empty($params['limit_recharge_money']) && (!is_numeric($params['limit_recharge_money']) || bccomp($params['limit_recharge_money'], 0.00, 2) === -1))
                throw new DateException(157);
            else
                $model->limit_recharge_money = !empty($params['limit_recharge_money']) ? $params['limit_recharge_money'] : 0.00;
        } else {
            $model->limit_recharge_money = 0.00;
        }
        !empty($params['bill_date']) && $params['bill_date'] = date('Y-m-d', strtotime($params['bill_date']) + 3600 * 8); // 修正因时区影响服务器时间
        $model->bill_date   = $params['bill_date'] ?? '';
        $model->status      = $params['status'] ?? self::STATUS_ON;
        $model->wallet_type = $params['wallet_type'] ?? self::TRANSFER_WALLET_TYPE; // 钱包类型

        return $model->save();
    }

    /**
     * 获取代理详情
     *
     * @param string $agentCode
     * @return array|null
     * @throws \Exception
     */
    public static function getOne(string $agentCode): ?array
    {
        $cacheKey = CacheKey::$prefix['agent'] . $agentCode;
        //展示字段
        $fields = ['agent.id', 'agent.agent_code', 'agent.brand_name', 'agent.currency_id', 'currency.currency_type', 'agent.secret_key', 'agent.site_url', 'agent.callback_url', 'agent.is_allow_login', 'agent.is_allow_transfer', 'agent.is_allow_order', 'agent.is_limit_recharge', 'agent.limit_recharge_money', 'agent.bill_date', 'agent.status', 'agent.wallet_type'];
        return CacheDb::make($cacheKey, function () use ($fields, $agentCode) {
            return self::query()->leftJoin('currency', 'currency.id', 'agent.currency_id')
                ->select($fields)
                ->where('agent.agent_code', $agentCode)
                ->first();
        })->hGet();
    }
}
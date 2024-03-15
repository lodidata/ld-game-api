<?php

namespace Model\Common;

use \Illuminate\Database\Eloquent\Model;
use Logic\Define\CacheKey;
use Model\Event\WhiteListObserver;
use Utils\CacheDb;

/**
 * @method static where(string $string, string $agentCode)
 */
class WhiteListModel extends Model
{
    protected $table = 'agent_ip_whitelist';
    protected $primaryKey = 'id';

    public static function boot()
    {
        parent::boot();
        self::observe( WhiteListObserver::class );
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
        $model->ip         = $params['ip'] ?? '';
        $model->admin_id   = $params['admin_id'] ?? 0;
        $model->admin_name = $params['admin_name'] ?? '';
        return $model->save();
    }

    /**
     * 是否存在
     *
     * @param string $agentCode :代理编码
     * @param string $customerIP :客户ip
     * @return bool
     * @throws \Exception
     */
    public static function isExist(string $agentCode, string $customerIP): bool
    {
        $cacheKey = CacheKey::$prefix['agentIp'] . $agentCode;

        return CacheDb::make( $cacheKey, function () use ($agentCode, $customerIP) {
            return self::where( 'agent_code', $agentCode )->where( 'ip', $customerIP )->first();
        } )->isMember( $customerIP );
    }

}
<?php

namespace Model\Common;

use Illuminate\Database\Eloquent\Model;
use Model\Event\GameApiObserver;
use Utils\CacheDb;
use Logic\Define\CacheKey;

/**
 * @method static select(string[] $fields)
 * @method static leftJoin(string $string, string $string1, string $string2, string $string3)
 * @method static where(string $string, string $agentCode)
 */
class GameApiModel extends Model
{
    protected $table = 'game_api';
    protected $primaryKey = 'id';

    const STATUS_OFF = 0; // 已下架
    const STATUS_ON  = 1; // 已上架
    const STATUS_ARR = [
        self::STATUS_OFF => '已下架',
        self::STATUS_ON  => '已上架',
    ];

    /**
     * 获取当前时间
     *
     * @return int
     */
    public function freshTimestamp(): int
    {
        return time();
    }

    public static function boot()
    {
        parent::boot();
        self::observe(GameApiObserver::class);
    }

    /**
     * 新增model
     *
     * @param object|null $model
     * @param array $params
     * @return bool
     * */
    public static function addModel(?object $model, array $params): bool
    {
        $model->agent_game_id = $params['agent_game_id'] ?? 0;
        $model->agent_code    = $params['agent_code'] ?? '';
        $model->menu_type     = $params['menu_type'] ?? '';
        return self::operatingElements($model, $params);
    }

    /**
     * 更新model
     *
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
     *
     * @param object $model
     * @param array $params
     * @return bool
     */
    public static function operatingElements(object $model, array $params): bool
    {
        $model->api_agent = $params['api_agent'] ?? '';
        $model->api_key   = $params['api_key'] ?? '';
        $model->lobby     = !empty($params['lobby']) ? $params['lobby'] : '{}';
        $model->status    = array_keys(self::STATUS_ARR)[$params['status']] ?? ($params['status'] ?? self::STATUS_ON);

        return $model->save();
    }

    /**
     * 获取详情
     *
     * @param string $apiAgent :代理编号
     * @return array|mixed|null
     * @throws \Exception
     */
    public static function getOne(string $apiAgent)
    {
        $cacheKey = CacheKey::$prefix['gameApiAgent'] . $apiAgent;
        $fields   = ['id', 'agent_code', 'api_agent', 'api_key'];

        return CacheDb::make($cacheKey, function () use ($fields, $apiAgent) {
            return self::select($fields)->where('api_agent', $apiAgent)->first();
        })->hGet();
    }

    /**
     * 获取游戏代理
     *
     * @param string $agentCode
     * @param string $gamePlatform
     * @return array|mixed|null
     * @throws \Exception
     */
    public static function getGameAgent(string $agentCode, string $gamePlatform)
    {
        $cacheKey = CacheKey::$prefix['gameApi'] . $gamePlatform . ':' . $agentCode;
        $fields   = ['game_api.id', 'agent_code', 'api_agent', 'api_key', 'api_api_url', 'api_login_url', 'api_order_url', 'game_api.lobby'];

        return CacheDb::make($cacheKey, function () use ($fields, $agentCode, $gamePlatform) {
            return self::leftJoin('game_menu', 'game_menu.menu_type', '=', 'game_api.menu_type')
                ->where('agent_code', $agentCode)
                ->where('game_api.menu_type', $gamePlatform)
                ->select($fields)
                ->first();
        })->hGet();
    }

    /**
     * 获取状态
     *
     * @param string $agentCode
     * @param string $gamePlatform
     * @return int|null
     * @throws \Exception
     */
    public static function getStatus(string $agentCode, string $gamePlatform): ?int
    {
        $cacheKey = CacheKey::$prefix['gameApiStatus'] . $agentCode . ':' . $gamePlatform;

        return CacheDb::make($cacheKey, function () use ($agentCode, $gamePlatform) {
            return self::where('agent_code', $agentCode)->where('menu_type', $gamePlatform)->value('status');
        })->get();
    }

}
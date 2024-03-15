<?php

namespace Model\Event;

use Logic\Define\CacheKey;
use Model\Common\GameApiModel;

/**
 * GameApiModel观察类
 */
class GameApiObserver
{
    /**
     * 更新事件
     * @param GameApiModel $model
     */
    public function updated(GameApiModel $model)
    {
        //删除game_api缓存
        $redisHandler = app()->redis;
        $cacheKey     = CacheKey::$prefix['gameApi'] . "{$model->menu_type}:{$model->agent_code}";
        $redisHandler->del($cacheKey);

        //删除game_api状态缓存
        $cacheKey = CacheKey::$prefix['gameApiStatus'] . $model->agent_code . ':' . $model->menu_type;
        $redisHandler->del($cacheKey);
    }
}
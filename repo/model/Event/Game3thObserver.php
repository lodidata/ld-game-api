<?php

namespace Model\Event;

use Logic\Define\CacheKey;
use Model\Common\Game3thModel;

/**
 * Game3thModel观察类
 */
class Game3thObserver
{
    /**
     * 更新事件
     * @param Game3thModel $model
     */
    public function updated(Game3thModel $model)
    {
        //删除缓存
        $redisHandler = app()->redis;
        $cacheKey     = CacheKey::$prefix['game3th'] . $model->menu_type . ':' . $model->kind_id;
        $redisHandler->del($cacheKey);
    }
}
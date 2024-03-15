<?php

namespace Model\Event;

use Logic\Define\CacheKey;
use Model\Common\GameApiModel;
use Model\Common\GameMenuModel;

/**
 * GameMenuModel观察类
 */
class GameMenuObserver
{
    public function updated(GameMenuModel $model)
    {
        //删除game_menu缓存
        $cacheKeyStatus = CacheKey::$prefix['gameMenu'] . $model->menu_type;
        $redisHandler   = app()->redis;
        $redisHandler->del($cacheKeyStatus);
    }
}
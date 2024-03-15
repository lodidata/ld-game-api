<?php

namespace Model\Event;

use Logic\Define\CacheKey;
use Model\Common\WhiteListModel;

/**
 * WhiteListModel观察类
 */
class WhiteListObserver
{
    /**
     * 删除事件
     * */
    public function deleted(WhiteListModel $model)
    {
        // 删除集合元素
        $cacheKey = CacheKey::$prefix['agentIp'] . $model->agent_code;
        $redisHandler = app()->redis;
        $redisHandler->srem( $cacheKey, $model->ip );
    }
}
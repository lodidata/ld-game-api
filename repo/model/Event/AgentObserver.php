<?php

namespace Model\Event;

use Logic\Define\CacheKey;
use Model\Common\AgentModel;

/**
 * AgentModel观察类
 */
class AgentObserver
{
    /**
     * 更新事件
     * */
    public function updated(AgentModel $model)
    {
        $redisHandler = app()->redis;
        //删除agent缓存
        $cacheKey = CacheKey::$prefix['agent'] . $model->agent_code;
        $redisHandler->del($cacheKey);
    }
}
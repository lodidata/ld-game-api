<?php

namespace Logic\Define;

/**
 * Class CacheKey
 * 缓存key定义 前缀定义类
 */
class CacheKey
{
    /**
     * key
     * @var array
     */
    public static $prefix = [
        //管理员token
        'adminCacheToken'   => 'admin:cache:token:',
        //验证码
        'authVCode'         => 'image:code:',
        // 短信通知码
        'captchaRefresh'    => 'cache_refresh_',
        // 短信通知码
        'captchaText'       => 'cache_text_',
        //代理
        'agent'             => 'agent:',
        //代理ip
        'agentIp'           => 'agent:ip:',
        //游戏API
        'gameApi'           => 'game_api:',
        //游戏API代理
        'gameApiAgent'      => 'game_api:agent:',
        //代理游戏厂商状态
        'gameApiStatus'     => 'game_api:status:',
        //厂商维护状态
        'gameMenu'          => 'game_menu:',
        //厂商消息id
        'gameMenuMessageId' => 'game_menu:message:id',
        //三方游戏
        'game3th'           => 'game_3th:',
        //三方游戏类型
        'game3thType'       => 'game_3th:type:',
        //账单hash
        'billHash'          => 'bill:hash:',
        //游戏类型
        'gameType'          => 'game:type',
    ];
}
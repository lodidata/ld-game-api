<?php

namespace Utils;

use Logic\Define\CacheKey;

class CacheDb
{
    /**
     * 缓存 key
     * @var
     */
    protected $cacheKey;

    /**
     * 缓存 value
     * @var
     */
    protected $value;

    protected $redisHandler;

    public function __construct(string $cacheKey, $value)
    {
        $this->cacheKey     = $cacheKey;
        $this->value        = $value;
        $this->redisHandler = app()->redis;
    }

    public static function make(string $cacheKey, $value): CacheDb
    {
        return new static( $cacheKey, $value );
    }

    /**
     * 缓存 key 前缀
     * @return string
     */
    public function getCacheKeyPrefix(): string
    {
        return CacheKey::$prefix[$this->cacheKey] ?? $this->cacheKey;
    }

    /**
     * 缓存值
     */
    public function getData()
    {
        $data = $this->value;
        if ($this->value instanceof \Closure) {
            $data = ($this->value)();
            if ($data instanceof \Illuminate\Database\Eloquent\Model) {
                $data = $data->toArray();
            }
        }
        // if (is_null($data)) {
        //     throw new \InvalidArgumentException('the data to cached is empty');
        // }
        return $data;
    }

    /**
     * 设置string类型
     *
     * @return array|false|mixed|string
     * @throws \Exception
     */
    public function set()
    {
        $cacheKeyPrefix = $this->getCacheKeyPrefix();
        $redisHandler   = app()->redis;
        $val            = $this->getData();
        if (is_array( $val )) {
            $val = json_encode( $val );
        }
        $redisHandler->setex( $cacheKeyPrefix, app()->get( 'settings' )['app']['general_cache_timeout'] ?? 86400, $val );
        return $val;
    }

    /**
     * 获取string类型
     *
     * @return array|false|mixed|string|null
     * @throws \Exception
     */
    public function get()
    {
        $cacheKeyPrefix = $this->getCacheKeyPrefix();
        $redisHandler   = app()->redis;
        $data           = $redisHandler->get( $cacheKeyPrefix );
        if ($data) {
            return $data;
        }
        try {
            $val = $this->getData();
        } catch (\InvalidArgumentException $e) {
            return null;
        }
        if (is_array( $val )) {
            $val = json_encode( $val );
        }
        $redisHandler->setex( $cacheKeyPrefix, app()->get( 'settings' )['app']['general_cache_timeout'] ?? 86400, $val );

        return $val;
    }

    /**
     * 设置hash类型
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function hSet()
    {
        $cacheKeyPrefix = $this->getCacheKeyPrefix();
        $rows           = $this->getData();
        $this->redisHandler->hmset( $cacheKeyPrefix, $rows );
        $this->redisHandler->expire( $cacheKeyPrefix, app()->get( 'settings' )['app']['general_cache_timeout'] ?? 86400 );

        return $rows;
    }

    /**
     * 获取hash类型
     *
     * @return array|mixed|null
     * @throws \Exception
     */
    public function hGet()
    {
        $cacheKeyPrefix = $this->getCacheKeyPrefix();
        $redisHandler   = app()->redis;
        $data           = $redisHandler->hGet( $cacheKeyPrefix, 'id' );
        if ($data === '0') {
            return null;
        }
        // has Cache
        if ($data) {
            return $redisHandler->hgetAll( $cacheKeyPrefix );
        }
        // no Cache
        $rows = $this->getData();
        if (is_null( $rows )) {
            //缓存默认值，防止缓存穿透
            $redisHandler->hSet( $cacheKeyPrefix, 'id', 0 );
            $redisHandler->expire( $cacheKeyPrefix, app()->get( 'settings' )['app']['temporary_cache_timeout'] ?? 3600 );
            return null;
        }

        $redisHandler->hMset( $cacheKeyPrefix, $rows );
        $redisHandler->expire( $cacheKeyPrefix, app()->get( 'settings' )['app']['general_cache_timeout'] ?? 86400 );

        return $rows;
    }

    /**
     * 查找Set元素
     *
     * @param string $member
     * @return bool
     * @throws \Exception
     */
    public function isMember(string $member): bool
    {
        $cacheKeyPrefix = $this->getCacheKeyPrefix();
        $redisHandler   = app()->redis;
        $data           = $redisHandler->sismember( $cacheKeyPrefix, $member );
        // has Cache
        if ($data === 1) {
            return true;
        }
        try {
            $rows = $this->getData();
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        // no Cache
        if (is_null( $rows )) {
            return false;
        }
        if (!$redisHandler->exists( $cacheKeyPrefix )) {
            $redisHandler->sadd( $cacheKeyPrefix, $member );
            $redisHandler->expire( $cacheKeyPrefix, app()->get( 'settings' )['app']['general_cache_timeout'] ?? 86400 );
        } else {
            $redisHandler->sadd( $cacheKeyPrefix, $member );
        }

        return true;
    }

    /**
     * string列表
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function list()
    {
        $cacheKeyPrefix = $this->getCacheKeyPrefix();
        $redisHandler   = app()->redis;
        $data           = $redisHandler->get( $cacheKeyPrefix );
        if ($data) {
            return json_decode( $data, true );
        }
        try {
            $val = $this->getData();
        } catch (\InvalidArgumentException $e) {
            return null;
        }
        $val = $val->toArray();
        if (is_array( $val )) {
            $val = json_encode( $val );
        }
        $redisHandler->setex( $cacheKeyPrefix, app()->get( 'settings' )['app']['general_cache_timeout'] ?? 86400, $val );

        return json_decode( $val, true );
    }

}
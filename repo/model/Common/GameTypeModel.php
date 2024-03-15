<?php

namespace Model\Common;

use \Illuminate\Database\Eloquent\Model;
use Logic\Define\CacheKey;
use Utils\CacheDb;

class GameTypeModel extends Model
{
    protected $table = 'game_type';
    protected $primaryKey = 'id';

    const STATUS_OFF = 0; // 已下架
    const STATUS_ON  = 1; // 已上架

    const STATUS_ARR = [
        self::STATUS_OFF => '已下架',
        self::STATUS_ON  => '已上架',
    ];

    /**
     * 游戏类型列表
     */
    public static function getList()
    {
        $cacheKey = CacheKey::$prefix['gameType'];
        $data     = CacheDb::make($cacheKey, function () {
            return self::select(['id', 'type_code', 'type_name', 'status'])->where('status', self::STATUS_ON)->get();
        })->list();
        return $data;
    }

}


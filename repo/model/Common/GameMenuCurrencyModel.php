<?php

namespace Model\Common;

use Illuminate\Database\Eloquent\Model;
use Logic\Define\CacheKey;
use Model\Event\GameMenuObserver;
use Respect\Validation\Exceptions\DateException;
use Utils\CacheDb;
use Utils\Telegram;

class GameMenuCurrencyModel extends Model
{
    protected $table = 'game_menu_currency';
    protected $primaryKey = 'id';
    public $timestamps = false;

    const STATUS_OFF = 0; // 下架
    const STATUS_ON  = 1; // 上架
    const STATUS_ARR = [
        self::STATUS_OFF => '禁用',
        self::STATUS_ON  => '启用',
    ];
}
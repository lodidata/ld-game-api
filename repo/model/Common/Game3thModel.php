<?php

namespace Model\Common;

use Illuminate\Database\Eloquent\Model;
use Logic\Define\CacheKey;
use Model\DB;
use Model\Event\Game3thObserver;
use Utils\CacheDb;

class Game3thModel extends Model
{
    protected $table = 'game_3th';
    protected $primaryKey = 'id';

    const STATUS_OFF = 0; // 已下架
    const STATUS_ON  = 1; // 已上架
    const STATUS_ARR = [
        self::STATUS_OFF => '已下架',
        self::STATUS_ON  => '已上架',
    ];

    //是否试玩
    const IS_DEMO_OFF = 0;
    const IS_DEMO_ON  = 1;
    const IS_DEMO_ARR = [
        self::IS_DEMO_OFF => '否',
        self::IS_DEMO_ON  => '是',
    ];

    public static function boot()
    {
        parent::boot();
        self::observe( Game3thObserver::class );
    }

    /**
     * 更新model
     */
    public static function updateModel(object $model, int $status = 1)
    {
        $model->status = $status;
        return $model->save();
    }

    /**
     * 获取数据
     *
     * @param string $menuType
     * @param string $kindId
     * @return array|null
     */
    public static function getOne(string $menuType, string $kindId): ?array
    {
        $cacheKey = CacheKey::$prefix['game3th'] . $menuType . ':' . $kindId;
        $fields   = ['id', 'kind_id', 'kind_type', 'kind_name', 'menu_type', 'game_type', 'status', 'is_demo'];
        return CacheDb::make( $cacheKey, function () use ($fields, $menuType, $kindId) {
            return self::where( 'menu_type', $menuType )
                ->where( 'kind_id', $kindId )
                ->select( $fields )
                ->first();
        } )->hGet();
    }

    /**
     * 游戏列表
     *
     * @param $gameType
     * @return array
     */
    public static function getGame3thList($gameType): array
    {
        $cacheKey = CacheKey::$prefix['game3thType'] . $gameType;
        $data     = CacheDb::make($cacheKey, function () use ($gameType) {
            return self::select( ['menu_type'] )->where( 'game_type', $gameType )->where('status', self::STATUS_ON)->groupBy( 'menu_type' )->get();
        })->list();
        return $data;
    }

    /**
     * 游戏厂商列表
     *
     * @param $menuTypes
     * @return array
     */
    public static function getMenuTypeList($menuTypes): array
    {
        return self::select( ['menu_type', 'game_type', 'kind_id', 'kind_name'] )
            ->whereIn( 'menu_type', $menuTypes )
            ->where( 'status', self::STATUS_ON )
            ->get()
            ->toArray();
    }

}
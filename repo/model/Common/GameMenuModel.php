<?php

namespace Model\Common;

use Illuminate\Database\Eloquent\Model;
use Logic\Define\CacheKey;
use Model\Event\GameMenuObserver;
use Utils\CacheDb;
use Utils\Telegram;

class GameMenuModel extends Model
{
    protected $table      = 'game_menu';
    protected $primaryKey = 'id';
    protected $fillable   = [
        'id',
        'status',
        'menu_type',
        'menu_name',
        'start_uworked_at',
        'end_uworked_at',
        'api_login_url',
        'api_api_url',
        'api_order_url',
        'api_config'
    ];

    const STATUS_OFF = 0; // 下架
    const STATUS_ON  = 1; // 上架
    const STATUS_ARR = [
        self::STATUS_OFF => '下架',
        self::STATUS_ON  => '上架',
    ];

    const WORK_STATUS_OFF = 0; // 维护状态
    const WORK_STATUS_ON  = 1; // 工作状态
    const WORK_STATUS_ARR = [
        self::WORK_STATUS_OFF => '维护',
        self::WORK_STATUS_ON  => '工作',
    ];

    public static function boot()
    {
        parent::boot();
        self::observe(GameMenuObserver::class);
    }

    /**
     * 更新model
     * @param object $model
     * @param array $params
     * @return mixed
     */
    public static function updateModel(object $model, array $params)
    {
        $model->start_uworked_at = !empty($params['start_uworked_at']) ? $params['start_uworked_at'] : null;
        $model->end_uworked_at   = !empty($params['end_uworked_at']) ? $params['end_uworked_at'] : null;
        $model->work_status      = $params['work_status'] ?? 0;
        return $model->save();
    }

    /**
     * 获取数据
     * @param string $gamePlatform
     * @return array|null
     */
    public static function getOne(string $gamePlatform): ?array
    {
        $cacheKey = CacheKey::$prefix['gameMenu'] . $gamePlatform;
        //展示字段
        $fields = ['id', 'menu_type', 'menu_name', 'status', 'work_status', 'start_uworked_at', 'end_uworked_at', 'api_login_url', 'api_api_url', 'api_order_url'];
        return CacheDb::make($cacheKey, function () use ($fields, $gamePlatform) {
            return self::where('menu_type', $gamePlatform)
                ->select($fields)
                ->first();
        })->hGet();
    }

    /**
     * 获取列表
     * @param string $agentCode
     * @return array
     */
    public static function getList(string $agentCode): array
    {
        $list = AgentGameModel::select(['agent_game.menu_type', 'gm.id', 'rate'])
            ->leftJoin('game_menu as gm', 'agent_game.menu_type', 'gm.menu_type')
            ->where('agent_code', $agentCode)
            ->where('agent_game.status', AgentGameModel::STATUS_ON)
            ->where('gm.status', self::STATUS_ON)
            ->get()
            ->toArray();
        // 获取游戏厂商货币列表
        $gameMenuCurrencyMap = [];
        $gameMenuIds         = array_column($list, 'id');//游戏厂商ids
        if ($gameMenuIds) {
            $gameMenuCurrencyList = CurrencyModel::query()
                ->join('game_menu_currency AS gmc', 'gmc.currency_id', 'currency.id')
                ->whereIn('gmc.game_menu_id', $gameMenuIds)
                ->get(['gmc.*', 'currency.currency_type'])
                ->toArray();
            foreach ($gameMenuCurrencyList as $currency) {
                $gameMenuCurrencyMap[$currency['game_menu_id']][] = $currency['currency_type'];
            }
        }
        //合并货币
        foreach ($list as $k => $v) {
            if (!empty($gameMenuCurrencyMap[$v['id']])) {
                // 封装货币列表的字符串
                $list[$k]['currency'] = implode(',', $gameMenuCurrencyMap[$v['id']]); //拼接游戏厂商得货币类型的字符串
            }
        }
        $arr = [];
        foreach ($list as $v) {
            $arr[$v['menu_type']] = $v;
        }

        //取menu_type
        $menuTypes   = array_column($list, 'menu_type');
        $game3thList = Game3thModel::getMenuTypeList($menuTypes);
        foreach ($game3thList as $k => $v) {
            $game3thList[$k]['currency'] = $arr[$v['menu_type']]['currency'];
            $game3thList[$k]['rate']     = $arr[$v['menu_type']]['rate'];
        }
        return $game3thList;
    }

    /**
     * 设置维护状态
     * @param $id
     * @param $workStatus
     */
    public static function setWorkStatus($id, $workStatus)
    {
        $GameMenu = self::where('id', $id)->first();
        if ($GameMenu) {
            $GameMenu->work_status = $workStatus;
            $GameMenu->save();
            self::sendTelegram($GameMenu->menu_type, true, "{$GameMenu->menu_type} 维护完成/{$GameMenu->menu_type}  Maintenance complete");
        }
    }

    /**
     * 发送Telegram维护消息
     * @param string $menuType 游戏厂商
     * @param bool $isReplyMessage 是否回复消息
     * @param string $content 消息体
     */
    public static function sendTelegram(string $menuType, bool $isReplyMessage, string $content)
    {
        $cacheKey       = CacheKey::$prefix['gameMenuMessageId'];
        $redisHandler   = app()->redis;
        $field          = 'message_' . $menuType;
        $replyMessageId = $redisHandler->hGet($cacheKey, $field);
        if (!$isReplyMessage) {
            $message_id = Telegram::sendMaintainMsg($content);
            // 加入缓存
            if ($message_id) {
                CacheDb::make($cacheKey, function () use ($field, $message_id) {
                    return [$field => $message_id];
                })->hSet();
            }
        } else {
            if (is_null($replyMessageId)) {
                Telegram::sendMaintainMsg($content);
            } else {
                Telegram::sendMaintainMsg($content, $replyMessageId);
            }
        }
    }

}
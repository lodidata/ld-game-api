<?php

namespace Model\Common;

use \Illuminate\Database\Eloquent\Model;
use Respect\Validation\Exceptions\DateException;

class AgentGameModel extends Model
{
    protected $table = 'agent_game';
    protected $primaryKey = 'id';

    const STATUS_OFF = 0; // 停用
    const STATUS_ON  = 1; // 已开通
    const STATUS_ARR = [
        self::STATUS_OFF => '停用',
        self::STATUS_ON  => '已开通',
    ];

    /**
     * 增加model
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function addModel(object $model, array $params): bool
    {
        $model->agent_code = $params['agent_code'] ?? '';
        $model->menu_type  = $params['menu_type'] ?? '';
        return self::operatingElements($model, $params);
    }

    /**
     * 更新model
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function updateModel(object $model, array $params): bool
    {
        return self::operatingElements($model, $params);
    }

    /**
     * 操作元素
     * @param object $model
     * @param array $params
     * @return bool
     */
    public static function operatingElements(object $model, array $params): bool
    {
        $model->agent_account = $params['agent_account'] ?? '';
        $model->password      = $params['password'] ?? '';
        $model->admin_url     = $params['admin_url'] ?? '';
        if (!empty($params['rate']) && bccomp($params['rate'], 99.99, 2) == 1)
            throw new DateException(191); // 费率不合法
        $model->rate   = !empty($params['rate']) ? $params['rate'] : 1.00;
        $model->status = array_keys(self::STATUS_ARR)[$params['status']] ?? ($params['status'] ?? self::STATUS_ON);

        // 操作记录
        return $model->save();
    }

    /**
     * 是否存在
     * @param $agentCode
     * @param $menuType
     * @return bool
     */
    public static function isExist($agentCode, $menuType): bool
    {
        return self::where('agent_code', $agentCode)->where('menu_type', $menuType)->exists();
    }

}
<?php

namespace Model\Common;

use \Illuminate\Database\Eloquent\Model;

class GameMenuIpWhitelistModel extends Model
{
    protected $table = 'game_menu_ip_whitelist';
    protected $primaryKey = 'id';

    /**
     * 增加model
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function addModel(object $model, array $params): bool
    {
        $model->menu_type  = $params['menu_type'] ?? '';
        $model->ip         = $params['ip'] ?? '';
        $model->admin_id   = $params['admin_id'] ?? 0;
        $model->admin_name = $params['admin_name'] ?? '';
        return $model->save();
    }
}
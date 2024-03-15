<?php

namespace Model\Common;

use Illuminate\Database\Eloquent\Model;

class AdminRoleModel extends Model
{
    protected $table = 'admin_role';
    protected $primaryKey = 'id';

    const STATUS_OFF = 0; // 失败
    const STATUS_ON = 1; // 成功
    const STATUS_ARR = [
        self::STATUS_OFF => '失败',
        self::STATUS_ON => '成功',
    ];
}
<?php

namespace Model\Common;

use \Illuminate\Database\Eloquent\Model;

class AdminModel extends Model
{
    protected $table = 'admin';
    protected $primaryKey = 'id';

    const SUPER_ADMIN_ID = 1;

    const STATUS_OFF = 0; // 禁用
    const STATUS_ON  = 1; // 启用
    const STATUS_ARR = [
        self::STATUS_OFF => '停用',
        self::STATUS_ON  => '启用',
    ];
}
<?php

namespace Model\Common;

use \Illuminate\Database\Eloquent\Model;

class AdminRoleAuthModel extends Model
{
    protected $table      = 'admin_role_auth';
    protected $primaryKey = 'id';

    const STATUS_OFF = 0;
    const STATUS_ON  = 1;
    const STATUS_ARR = [
        self::STATUS_OFF => '禁用',
        self::STATUS_ON  => '启用',
    ];
}
<?php

namespace Logic\Admin;

use Logic\Logic;
use Utils\Client;
use Model\Common\AdminLogModel;

class Log extends Logic
{
    /**
     * @param array $data
     * @return int
     */
    public function writeAdminLog(array $data): int
    {
        $data['method'] = AdminLogModel::METHOD_V_K[$data['method']] ?? 0;
        return AdminLogModel::query()->insertGetId( $data );
    }
}
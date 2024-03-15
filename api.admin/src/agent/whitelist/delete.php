<?php

use Model\DB;
use Logic\Define\CacheKey;
use Model\Common\WhiteListModel;
use Logic\Admin\BaseController;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID( $id );
        // 获取日志所需的表明
        $table = (new WhiteListModel())->getTable();
        // 检查记录是否存在
        $whitListObj = WhiteListModel::query()->where( 'id', $id )->first();
        if (!$whitListObj)
            return $this->lang->set( 126 );

        DB::pdo()->beginTransaction();
        try {
            $res = $whitListObj->delete();
            if (!$res)
                return $this->lang->set( 123 );
            $this->writeAdminLog( $whitListObj->toArray(), $table, $id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( $whitListObj->toArray(), $table, $id, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set( 0 );
    }
};
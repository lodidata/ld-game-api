<?php

use Model\Common\GameMenuModel;
use Model\DB;
use Logic\Admin\BaseController;
use Respect\Validation\Exceptions\DateException;
use Model\Common\GameMenuIpWhitelistModel;
use Lib\Validate\Admin\GameMenuIpWhitelistValidate;

return new class extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new GameMenuIpWhitelistValidate())->paramsCheck('post', $this->request, $this->response);
        // 格式化获取请求参数并封装特定参数
        $WhiteListModel = new GameMenuIpWhitelistModel();
        $table          = $WhiteListModel->getTable();
        $params         = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty($param) && is_string($param)) $params[$key] = trim($param);
            $params['ip'] = ip2long($params['ip']);
        }
        $params['admin_id']   = $this->playLoad['admin_id'];
        $params['admin_name'] = $this->playLoad['admin_name'];

        //判断游戏厂商是否存在
        $gameMenu = GameMenuModel::getOne($params['menu_type']);
        if (is_null( $gameMenu ))
            return $this->lang->set( 212 );
        if ($gameMenu['status'] === GameMenuModel::STATUS_OFF)
            return $this->lang->set( 213 );

        // 检查是否已提交(幂等性)
        $checkRes = GameMenuIpWhitelistModel::query()->where('menu_type', $params['menu_type'])->where('ip', $params['ip'])->first();
        if ($checkRes)
            return $this->lang->set(127);

        DB::pdo()->beginTransaction();
        try {
            // 写入白名单
            $res = GameMenuIpWhitelistModel::addModel( $WhiteListModel, $params );
            if (!$res) {
                throw new DateException( 132 );
            }
            $this->writeAdminLog( $params, $table, $WhiteListModel->id, 1 );

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog( [], $table, 0, 0 );
            return $this->lang->set( $e->getMessage() );
        }

        return $this->lang->set(0);
    }
};


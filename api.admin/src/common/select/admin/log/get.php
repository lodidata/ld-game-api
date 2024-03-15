<?php

use Model\Common\AdminLogModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AdminLogValidate;

return new class extends BaseController {
    public function run()
    {
        // 批量获取所有的请求参数并对分页信息进行初始化
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
        }
        if (empty( $params['type'] ) || !is_numeric( $params['type'] ) || $params['type'] < 0)
            $params['type'] = 0; // 默认值
        $typeList = [];
        switch ($params['type']) {
            case 0: // 模块名与方法名的集合
                $typeList[] = AdminLogModel::MODULE_LIST;
                $typeList[] = AdminLogModel::METHOD_K_V_STR;
                $typeList[] = AdminLogModel::STATUS_ARR;
                break;
            case 1: // 模块名
                $typeList[] = AdminLogModel::MODULE_LIST;
                break;
            case 2: // 方法名
                $typeList[] = AdminLogModel::METHOD_K_V_STR;
                break;
            case 3: // 状态
                $typeList[] = AdminLogModel::STATUS_ARR;
                break;
            default:
                return $this->lang->set( 159 );
        }

        return $typeList;
    }
};
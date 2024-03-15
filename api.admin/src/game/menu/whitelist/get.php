<?php

use Logic\Admin\BaseController;
use Model\Common\GameMenuIpWhitelistModel;
use Lib\Validate\Admin\GameMenuIpWhitelistValidate;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new GameMenuIpWhitelistValidate())->paramsCheck('get', $this->request, $this->response);
        // 获取并格式化请求参数
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'menu_type' && !empty( $params['menu_type'] )) $params['menu_type'] = $params[$key];
            if ($key === 'page' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 搜索
        $whiteListObj = GameMenuIpWhitelistModel::query()->where( 'menu_type', $params['menu_type'] );
        !empty( $params['ip'] ) && $whiteListObj->where( 'ip', ip2long( $params['ip'] ) ); // ip查询
        // 计算总记录数
        $common['total'] = $whiteListObj->count() ?? 0;
        // 分页列表
        $whiteList = $whiteListObj->orderBy( 'created_at', 'desc' )->forpage( $common['page'], $common['page_size'] )->get()->toArray();
        // 格式化列表
        foreach ($whiteList as $key => $item) {
            $whiteList[$key]['ip'] = long2ip( $item['ip'] );
        }

        return $this->lang->set( 0, [], $whiteList, $common );
    }
};

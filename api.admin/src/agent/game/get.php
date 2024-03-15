<?php

use Model\Common\AgentGameModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AgentGameValidate;
use Utils\Utils;

return new class() extends BaseController {

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new AgentGameValidate())->paramsCheck( 'get', $this->request, $this->response );
        // 获取所有的请求参数
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty( $param )) $params[$key] = trim( $param );
            if ($key === 'agent_code' && !empty( $params['agent_code'] )) $params['agent_code'] = strtolower( $params[$key] );
            if ($key === 'page' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric( $param ) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 校验代理号
        if (empty( $params['agent_code'] )) return $this->lang->set( 138 );
        // 获取代理号对象
        $agentGameObj = AgentGameModel::query()
            ->leftJoin( 'game_api', 'agent_game.id', 'game_api.agent_game_id' )
            ->where( 'agent_game.agent_code', $params['agent_code'] );
        // 代理账户模糊搜索
        if (!empty( $params['agent_account'] )) $agentGameObj->where( 'agent_game.agent_account', 'like', $params['agent_account'] . '%' );
        // 总记录数
        $common['total'] = $agentGameObj->count() ?? 0;
        // 获取分页列表
        $agentGameList = $agentGameObj->forPage( $common['page'], $common['page_size'] )->orderBy( 'agent_game.created_at', 'desc' )->get( ['agent_game.*', 'game_api.api_agent', 'game_api.api_key', 'game_api.lobby'] )->toArray();
        // 格式化列表
        foreach ($agentGameList as $key => $agentGame) {
            $agentGameList[$key]['password'] = Utils::RSADecrypt( $agentGame['password'] );
            !empty( $agentGameList[$key]['api_key'] ) && $agentGameList[$key]['api_key'] = Utils::RSADecrypt( $agentGame['api_key'] );
            $agentGameList[$key]['status_str'] = AgentGameModel::STATUS_ARR[$agentGame['status']] ?? '未知';
        }

        // 响应数据
        return $this->lang->set( 0, [], $agentGameList, $common );
    }
};

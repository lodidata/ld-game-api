<?php

use Model\DB;
use Model\Common\AgentGameModel;
use Model\Common\GameApiModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AgentGameValidate;
use Respect\Validation\Exceptions\DateException;
use Utils\Utils;
use Model\Common\AgentModel;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 验证请求参数
        (new AgentGameValidate())->paramsCheck('post', $this->request, $this->response);
        // 获取请求参数
        $agentGameModel = new AgentGameModel();
        $gameApiModel   = new GameApiModel();
        $agentGameTable = $agentGameModel->getTable();
        $gameApiTable   = $gameApiModel->getTable();

        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty($param)) $params[$key] = trim($param);
            if ($key === 'agent_code' && !empty($params['agent_code'])) $params['agent_code'] = strtolower($params[$key]);
            if ($key === 'password' && !empty($params['password'])) $params['password'] = Utils::RSAEncrypt($params[$key]);
            if ($key === 'api_key' && !empty($params['api_key'])) $params['api_key'] = Utils::RSAEncrypt($params[$key]);
        }
        //判断数据格式
        if (!empty($params['lobby'])) {
            if (is_json($params['lobby']) === false) {
                return $this->lang->set(31);
            }
        }

        //判断代理商是否存在
        $agent = AgentModel::getOne($params['agent_code']);
        if (is_null($agent))
            return $this->lang->set(134);
        if ($agent['status'] === AgentModel::STATUS_OFF)
            return $this->lang->set(150);

        // 校验代理游戏是否重复添加
        $agentGameObj = $agentGameModel->where('agent_code', $params['agent_code'])->where('menu_type', $params['menu_type'])->first();
        if ($agentGameObj)
            return $this->lang->set(136);
        // 校验游戏Api是否重复添加
        $gameApiObj = $gameApiModel->where('agent_code', $params['agent_code'])->where('menu_type', $params['menu_type'])->first();
        if ($gameApiObj)
            return $this->lang->set(152);
        $gameApiObjApiAgent = $gameApiModel->where('menu_type', $params['menu_type'])->where('api_agent', $params['api_agent'])->first();
        if ($gameApiObjApiAgent)
            return $this->lang->set(147);

        DB::pdo()->beginTransaction();
        try {
            // 新增代理
            $agentGameRes = $agentGameModel::addModel($agentGameModel, $params);
            if (!$agentGameRes)
                throw new DateException(132);
            // 代理游戏写日志
            $this->writeAdminLog($agentGameModel->toArray(), $agentGameTable, $agentGameModel->id, 1);

            // 新增game_api表记录
            $params['agent_game_id'] = $agentGameModel->id;
            $gameApiRes              = $gameApiModel::addModel($gameApiModel, $params);
            if (!$gameApiRes)
                throw new DateException(132);
            // 游戏Api表写日志
            $this->writeAdminLog($gameApiModel->toArray(), $gameApiTable, $gameApiModel->id, 1);

            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog([], $agentGameTable, 0, 0); // 写代理游戏错误日志
            $this->writeAdminLog([], $gameApiTable, 0, 0); // 写代理api错误日志

            return $this->lang->set($e->getMessage());
        }

        return $this->lang->set(0);
    }
};


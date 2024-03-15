<?php

use Model\DB;
use Model\Common\GameMenuModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\GameMenuValidate;
use Respect\Validation\Exceptions\DateException;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id = 0)
    {
        // 检查id是否合法
        $this->checkID($id);
        // 验证请求参数
        (new GameMenuValidate())->paramsCheck('put', $this->request, $this->response);
        // 获取请求参数
        $table  = (new GameMenuModel())->getTable();
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty($param)) {
                $params[$key] = trim($param);
            }
        }
        // 检查该记录是否存在
        $gameMenuObj = GameMenuModel::query()->where('id', $id)->first();
        if (!$gameMenuObj) {
            return $this->lang->set(126);
        }
        // 检查数据是否发生改变
        $checkChange = $this->checkParamsChange($gameMenuObj, $params);
        if ($checkChange === 0)
            return $this->lang->set(122);
        //开始时间不能小于当前时间
        if (!empty($params['start_uworked_at']) && strtotime($params['start_uworked_at']) < time()) {
            return $this->lang->set(190);
        }
        //延期必须有结束时间
        if (empty($params['end_uworked_at']) && $params['work_status'] == GameMenuModel::WORK_STATUS_OFF && $gameMenuObj->work_status == GameMenuModel::WORK_STATUS_OFF) {
            return $this->lang->set(158);
        }
        //处理消息体
        $isReplyMessage = false; // 是否回复消息
        $content        = ''; //消息体
        //开始、结束时间为空，维护状态为维护，原维护状态为工作
        if (empty($params['start_uworked_at']) && empty($params['end_uworked_at']) && $params['work_status'] == GameMenuModel::WORK_STATUS_OFF && $gameMenuObj->work_status == GameMenuModel::WORK_STATUS_ON) {
            $content = "维护产品/Maintain product：【{$gameMenuObj->menu_type}】" . PHP_EOL . "维护时间/Maintenance time：现在Now—另行通知until further notice（GMT+8）";
        } else if (empty($params['start_uworked_at']) && $params['work_status'] == GameMenuModel::WORK_STATUS_OFF && $gameMenuObj->work_status == GameMenuModel::WORK_STATUS_ON) {
            //开始时间为空，维护状态为维护，原维护状态为工作
            $content = "维护产品/Maintain product：【{$gameMenuObj->menu_type}】" . PHP_EOL . "维护时间/Maintenance time：现在Now—" . date('Y/m/d H:i', strtotime($params['end_uworked_at'])) . "（GMT+8）";
        } else if (empty($params['end_uworked_at']) && $params['work_status'] == GameMenuModel::WORK_STATUS_OFF && $gameMenuObj->work_status == GameMenuModel::WORK_STATUS_ON) {
            //无结束时间，维护状态为维护，原维护状态为工作
            $content = "维护产品/Maintain product：【{$gameMenuObj->menu_type}】" . PHP_EOL . "维护时间/Maintenance time：" . date('Y/m/d H:i', strtotime($params['start_uworked_at'])) . "—另行通知until further notice（GMT+8）";
        } else if (!empty($params['start_uworked_at']) && !empty($params['end_uworked_at']) && $params['work_status'] == GameMenuModel::WORK_STATUS_OFF && $gameMenuObj->work_status == GameMenuModel::WORK_STATUS_ON) {
            //开始、结束时间有值，维护状态为维护，原维护状态为工作
            $content = "维护产品/Maintain product：【{$gameMenuObj->menu_type}】" . PHP_EOL . "维护时间/Maintenance time：" . date('Y/m/d H:i', strtotime($params['start_uworked_at'])) . "—" . date('Y/m/d H:i', strtotime($params['end_uworked_at'])) . "（GMT+8）";
        } else if (!empty($params['end_uworked_at']) && time() < strtotime($params['end_uworked_at']) && $params['work_status'] == GameMenuModel::WORK_STATUS_ON && $gameMenuObj->work_status == GameMenuModel::WORK_STATUS_OFF) {
            $isReplyMessage = true;
            //提前完成，当前时间小于维护结束时间，维护状态为工作，原维护状态为维护
            $content = "{$gameMenuObj->menu_type} 维护提前完成/{$gameMenuObj->menu_type} Maintenance completed ahead of schedule";
        } else if ($params['work_status'] == GameMenuModel::WORK_STATUS_OFF && $gameMenuObj->work_status == GameMenuModel::WORK_STATUS_OFF) {
            $isReplyMessage = true;
            //延期，维护状态为维护，原维护状态为维护
            $content = "{$gameMenuObj->menu_type} 维护延迟至 " . date('Y/m/d H:i', strtotime($params['end_uworked_at'])) . " /{$gameMenuObj->menu_type} Maintenance delayed until  " . date('Y/m/d H:i', strtotime($params['end_uworked_at']));
        }

        DB::pdo()->beginTransaction();
        try {
            // 封装更新条件
            $res = GameMenuModel::updateModel($gameMenuObj, $params);
            if (!$res)
                throw new DateException(139);
            // 写日志
            $this->writeAdminLog($gameMenuObj->toArray(), $table, $id, 1);
            DB::pdo()->commit();
        } catch (Exception $e) {
            DB::pdo()->rollBack();
            $this->writeAdminLog($gameMenuObj->toArray(), $table, $id, 0);
            return $this->lang->set($e->getMessage());
        }

        //发送Telegram维护消息
        if ($content) {
            GameMenuModel::sendTelegram($gameMenuObj->menu_type, $isReplyMessage, $content);
        }

        return $this->lang->set(0);
    }
};
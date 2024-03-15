<?php

namespace Model\Common;

use ClickHouseDB\Exception\DatabaseException;
use Illuminate\Database\Eloquent\Model;
use Respect\Validation\Exceptions\DateException;

class WorkOrderModel extends Model
{
    protected $table = 'work_order';
    protected $primaryKey = 'id';

    const STATUS_UNCONFIRMED = 1; // 待确认状态
    const STATUS_DOING       = 2; // 处理中状态
    const STATUS_FINISHED    = 3; // 已解决状态
    const STATUS_CLOSED      = 4; // 已关闭
    const STATUS_ACTIVATED   = 5; // 已激活状态

    const WORK_ORDER_STATUS_LIST = [
        self::STATUS_UNCONFIRMED => '待确认',
        self::STATUS_DOING       => '处理中',
        self::STATUS_FINISHED    => '解决',
        self::STATUS_CLOSED      => '关闭',
        self::STATUS_ACTIVATED   => '激活',
    ];

    /**
     * 增加model
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function addModel(object $model, array $params): bool
    {
        $model->created_admin_id = $params['created_admin_id'] ?? 0;
        $model->created_admin    = $params['created_admin'] ?? '';
        $model->status           = self::STATUS_UNCONFIRMED; // 默认待确认

        return self::operatingElements( $model, $params );
    }

    /**
     * 更新model
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function updateModel(object $model, array $params): bool
    {
        return self::operatingElements( $model, $params );
    }

    /**
     * 更新model
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function patchModel(object $model, array $params): bool
    {
        // 校验并复制工单状态
        if (!in_array( $params['status'], array_keys( self::WORK_ORDER_STATUS_LIST ) ))
            throw new DateException( 201 );
        else
            $model->status = $params['status'];
        // 修改工单的状态
        if ($params['status'] == WorkOrderModel::STATUS_FINISHED) {
            $model->finished_admin_id = $params['finished_admin_id'] ?? '';
            $model->finished_admin    = $params['finished_admin'] ?? '';
            $model->finished_at       = $params['finished_at'] ?? date( 'Y-m-d H:i:s', time() );
        }

        return $model->save();
    }

    /**
     * 操作元素
     * @param object $model
     * @param array $params
     * @return bool
     */
    public static function operatingElements(object $model, array $params): bool
    {
        $model->title       = $params['title'] ?? ''; // 工单标题
        $model->appendix    = !empty( $params['appendix'] ) ? $params['appendix'] : null; // 工单附件
        $model->description = $params['description'] ?? ''; // 工单描述

        return $model->save();
    }

    /**
     * 检查工单类型
     *
     * @param int $status
     * @return array
     */
    public static function checkWorkOrderStatus(int $status = 0): array
    {
        $buttons = ['activated' => false, 'confirm' => false, 'solve' => false, 'close' => false, 'edit' => true, 'copy' => true];
        switch ($status) {
            case WorkOrderModel::STATUS_UNCONFIRMED: // 工单待确认状态 => 入口：确认、解决、编辑、复制
                $buttons['confirm'] = true;
                $buttons['solve']   = true;
                break;
            case WorkOrderModel::STATUS_DOING: // 工单处理中状态 => 入口：解决、编辑、复制
                $buttons['solve'] = true;
                break;
            case WorkOrderModel::STATUS_FINISHED: // 工单已解决状态 => 入口：关闭、编辑、复制
                $buttons['close'] = true;
                break;
            case WorkOrderModel::STATUS_CLOSED: // 工单已关闭状态 => 入口：激活、编辑、复制
                $buttons['activated'] = true;
                break;
            case WorkOrderModel::STATUS_ACTIVATED: // 工单激活状态 => 入口：确认、解决、编辑、复制
                $buttons['confirm'] = true;
                $buttons['solve']   = true;
                break;
        }

        return $buttons;
    }

    /**
     * 检查角色工单的权限
     *
     * @param int $originalStatus
     * @param int $newStatus
     * @return bool
     */
    public static function checkWorkOrderPermission(int $originalStatus = 0, int $newStatus = 0): bool
    {
        $flag = false;
        switch ($originalStatus) {
            case WorkOrderModel::STATUS_UNCONFIRMED: // 待确认
                if ($newStatus === WorkOrderModel::STATUS_DOING) $flag = true; // 待确认 -> 确认(进行中);
                if ($newStatus === WorkOrderModel::STATUS_FINISHED) $flag = true; // 待确认 -> 解决;
                break;
            case WorkOrderModel::STATUS_DOING: // 处理中
                if ($newStatus === WorkOrderModel::STATUS_FINISHED) $flag = true; // 处理中 -> 解决
                break;
            case WorkOrderModel::STATUS_FINISHED: // 完成
                if ($newStatus === WorkOrderModel::STATUS_CLOSED) $flag = true; // 已解决 -> 关闭
                break;
            case WorkOrderModel::STATUS_CLOSED: // 已关闭
                if ($newStatus === WorkOrderModel::STATUS_ACTIVATED) $flag = true; // 已关闭 -> 激活
                break;
            case WorkOrderModel::STATUS_ACTIVATED: // 已激活
                if ($newStatus === WorkOrderModel::STATUS_DOING) $flag = true; // 激活 -> 确认
                if ($newStatus === WorkOrderModel::STATUS_FINISHED) $flag = true; // 激活 -> 解决
                break;
        }

        return $flag;
    }
}
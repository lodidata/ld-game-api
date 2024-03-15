<?php

namespace Model\Common;

use Illuminate\Database\Eloquent\Model;
use Respect\Validation\Exceptions\DateException;

class WorkOrderRecordModel extends Model
{
    protected $table = 'work_order_record';
    protected $primaryKey = 'id';
    /* 类型 */
    const TYPE_CONFIRMED   = 1; // 确认
    const TYPE_FINISHED    = 2; // 解决
    const TYPE_CLOSED      = 3; // 关闭
    const TYPE_EDITED      = 4; // 编辑
    const TYPE_CREATED     = 5; // 创建
    const TYPE_ACTIVATED   = 6; // 激活
    const TYPE_REMARK      = 7; // 添加备注
    const RECORD_TYPE_LIST = [
        self::TYPE_CONFIRMED => '确认',
        self::TYPE_FINISHED  => '解决',
        self::TYPE_CLOSED    => '关闭',
        self::TYPE_EDITED    => '编辑',
        self::TYPE_CREATED   => '创建',
        self::TYPE_ACTIVATED => '激活',
        self::TYPE_REMARK    => '添加备注',
    ];

    /**
     * 增加model
     *
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function addModel(object $model, array $params): bool
    {
        return self::operatingElements( $model, $params );
    }

    /**
     * 编辑model
     *
     * @param object $model
     * @param array $params
     * @return bool
     * */
    public static function updateModel(object $model, array $params): bool
    {
        return self::operatingElements( $model, $params );
    }

    /**
     * 操作元素
     * @param object $model
     * @param array $params
     * @return bool
     */
    public static function operatingElements(object $model, array $params): bool
    {
        $model->work_order_id    = $params['work_order_id'] ?? 0;
        $model->type             = $params['type'] ?? self::TYPE_CONFIRMED; // 类型，默认类型为：确认类型(1)
        $model->reply_admin_id   = $params['reply_admin_id'] ?? 0;
        $model->reply_admin_name = $params['reply_admin_name'] ?? '';
        if (!empty( $params['appendix'] )) $model->appendix = $params['appendix']; // 附件信息
        if (!empty( $params['remark'] )) $model->remark = $params['remark']; // 备注信息

        return $model->save();
    }

    /**
     * 计算工单记录类型
     *
     * @param int $oldStatus
     * @param int $newStatus
     * @return int
     */
    public static function getRecordType(int $oldStatus, int $newStatus): int
    {
        // 初始化类型
        $type = 0;
        // 校验新旧状态
        if (($oldStatus <= 0 || $newStatus <= 0) || $oldStatus === $newStatus)
            throw new DateException( 201 );
        /*计算记录的类型*/
        switch ($oldStatus) {
            case WorkOrderModel::STATUS_UNCONFIRMED: // 原状态为：待确认
                if ($newStatus === WorkOrderModel::STATUS_DOING) $type = WorkOrderRecordModel::TYPE_CONFIRMED; //【确认】
                if ($newStatus === WorkOrderModel::STATUS_FINISHED) $type = WorkOrderRecordModel::TYPE_FINISHED; //【解决】
                break;
            case WorkOrderModel::STATUS_DOING: // 原状态为：进行中...
                if ($newStatus === WorkOrderModel::STATUS_FINISHED) $type = WorkOrderRecordModel::TYPE_FINISHED; //【解决】
                break;
            case WorkOrderModel::STATUS_FINISHED: // 原状态：已完成
                if ($newStatus === WorkOrderModel::STATUS_CLOSED) $type = WorkOrderRecordModel::TYPE_CLOSED; //【关闭】
                break;
            case WorkOrderModel::STATUS_CLOSED: // 原状态：已关闭
                if ($newStatus === WorkOrderModel::STATUS_ACTIVATED) $type = WorkOrderRecordModel::TYPE_ACTIVATED; //【激活】
                break;
            case WorkOrderModel::STATUS_ACTIVATED: // 原状态：已激活
                if ($newStatus === WorkOrderModel::STATUS_DOING) $type = WorkOrderRecordModel::TYPE_CONFIRMED; //【确认】
                if ($newStatus === WorkOrderModel::STATUS_FINISHED) $type = WorkOrderRecordModel::TYPE_FINISHED; //【解决】
                break;
        }

        // 校验工单类型
        if ($type === 0)
            throw new DateException( 207 );

        return $type;
    }

}
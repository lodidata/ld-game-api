<?php

namespace Model\Event;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * 日志模块
 */
class LogsObserver
{
    /**
     * 老数据
     * @var
     */
    public $origin;

    /**
     * 更新数据
     * @var
     */
    public $attribute;

    /**
     * 表名
     * @var
     */
    public $table;

    /**
     * 表的主键值
     * @var
     */
    public $primaryKeyValue;

    public function __construct(Model $model)
    {
        $this->table           = $model->getTable();
        $this->attribute       = $model->getAttributes();
        $this->origin          = $model->getOriginal();
        $this->primaryKeyValue = $model->getKey();
    }

    /**
     * 模型创建数据 log
     * @param Model $model
     * @return void
     */
    public function created(Model $model)
    {

    }

    /**
     * 模型修改数据 log
     * @param Model $model
     * @return void
     */
    public function updated(Model $model)
    {

    }

    /**
     * 模型删除数据 log
     * @param Model $model
     * @return void
     */
    public function deleted(Model $model)
    {

    }
}
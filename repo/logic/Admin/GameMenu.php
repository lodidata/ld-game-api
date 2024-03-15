<?php

namespace Logic\Admin;

use Logic\Logic;
use Model\Common\GameMenuModel;

class GameMenu extends Logic
{
    /**
     * 设置维护状态
     * @param $id
     */
    public function setWorkStatus($id)
    {
        GameMenuModel::setWorkStatus($id, GameMenuModel::STATUS_ON);
    }

}
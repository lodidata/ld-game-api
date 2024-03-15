<?php

namespace Utils\Admin;

use ClickHouseDB\Exception\DatabaseException;
use Model\Common\AgentModel;
use Model\Common\GameMenuModel;

/**
 * @property $request
 * @property $response
 */
class Action
{
    protected $ci;

    /**
     * 前置操作方法列表
     * @var array $beforeActionList
     * @access protected
     */
    protected $beforeActionList = [];

    public function init($ci)
    {
        $this->ci = $ci;
        if (strtolower( $this->request->getMethod() ) == 'get') {
            $data = $this->request->getQueryParams();
            if (!empty( $data )) {
                $data['page'] = $data['page'] ?? 1;
            }
            $data['page_size'] = $data['page_size'] ?? 10;
            $this->ci->request = $this->ci->request->withQueryParams( $data );
        }
    }

    public function before()
    {
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method) {
                call_user_func( [$this, $method] );
            }
        }
    }

    public function __get($field)
    {
        if (!isset( $this->$field )) {
            return $this->ci->$field;
        } else {
            return $this->$field;
        }
    }

}

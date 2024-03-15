<?php

namespace Logic\Admin;

use ClickHouseDB\Exception\DatabaseException;
use Model\DB;
use Respect\Validation\Exceptions\DateException;
use Utils\Admin\Action;
use Utils\Encrypt;
use Logic\Admin\AdminToken;
use Logic\Admin\AdminAuth;
use Lib\Exception\BaseException;
use Logic\Admin\Log;
use Model\Common\WorkOrderModel;
use Model\Common\AdminModel;
use Model\Common\AdminLogModel;
use Utils\Client;

/**
 * @property $lang
 * @property $redis
 */
class BaseController extends Action
{
    protected $playLoad = [
        'admin_id'   => 0,
        'admin_name' => '',
        'role_id'    => 0,
        'type'       => 1, // 1 普通用户; 2 平台用户
        'ip'         => '',
        'client_id'  => '',
    ];
    protected $adminToken;
    // 分页默认参数
    protected $page = 1; // 默认页码
    protected $pageSize = 10; // 每页默认显示记录数

    public function init($ci)
    {
        parent::init( $ci );
        $this->adminToken = new AdminToken( $this->ci );
        $this->before();
    }

    /**
     * 检查id
     *
     * @throws \Lib\Exception\BaseException
     */
    public function checkID($id): bool
    {
        if (empty( $id )) {
            $newResponse = $this->response->withStatus( 200 );
            $newResponse = $newResponse->withJson( [
                'state'   => -1,
                'message' => 'id不能为空',
                'ts'      => time(),
            ] );
            throw new BaseException( $this->request, $newResponse );
        }

        if (is_numeric( $id ) && is_int( $id + 0 ) && ($id + 0) > 0) {
            return true;
        }

        $newResponse = $this->response->withStatus( 200 );
        $newResponse = $newResponse->withJson( [
            'state'   => -1,
            'message' => 'id必须为正整数',
            'ts'      => time(),
        ] );
        throw new BaseException( $this->request, $newResponse );
    }

    /**
     * 校验token
     *
     * @throws BaseException
     */
    public function verifyToken()
    {
        $this->playLoad = $this->adminToken->verifyToken();
    }

    /**
     * 校验权限
     *
     * @throws \Lib\Exception\BaseException
     */
    public function authorize(): bool
    {
        $roleId = $this->playLoad['role_id'];
        if ($roleId == 0 || $roleId == 1) {
            return true;
        }
        $dir   = $this->getRequestDir(); // 获取请求地址
        $allow = DB::table( 'admin_role_auth' )->where( 'method', $this->request->getMethod() )->where( 'path', $dir )->value( 'id' );
        $auth  = DB::table( 'admin_role' )->where( 'id', $roleId )->value( 'auth' );
        if (!$allow || !$auth || !in_array( $allow, explode( ',', $auth ) )) {
            $newResponse = $this->response->withStatus( 401 );
            $newResponse = $newResponse->withJson( [
                'state'   => -1,
                'message' => '您无权限操作，请联系管理员添加',
                'ts'      => time(),
            ] );
            throw new BaseException( $this->request, $newResponse );
        }

        return true;
    }

    public function getRequestDir(): string
    {
        $ver = ['v1', 'v2', 'v3', 'v4'];
        $dir = explode( '/', $this->request->getUri()->getPath() );
        $res = [];
        foreach ($dir as $v) {
            if ($v == $ver) continue;
            if (!is_numeric( $v )) {//排除id值的put方法和patch方法
                $res[] = $v;
            }
        }
        return implode( '/', $res );
    }

    /**
     * 更新操作校验数据是否有变更
     *
     * @param object|array $model
     * @param array $params
     * @return int
     */
    protected function checkParamsChange($model, array $params): int
    {
        $flag = 0;
        // 校验$model参数
        if (is_object( $model ))
            $original = $model->toArray();
        else if (is_array( $model ))
            $original = $model;
        else
            return $this->lang->set( 131 );
        // 校验请求参数
        if (!$original || !$params)
            return $this->lang->set( 131 );
        // 检查请求参数
        foreach ($params as $key => $value) {
            foreach ($original as $k => $v) {
                if ($key == $k && $value != $v) {
                    $flag = 1;
                    break;
                }
            }
        }

        return $flag;
    }

    /**
     * 贴合公司业务需求，修正了写日志的逻辑
     *
     * @param array $record
     * @param string $table
     * @param int $rowId
     * @param int $status
     * @return void
     */
    protected function writeAdminLog(array $record, string $table = '', int $rowId = 0, int $status = 0): void
    {
        // 校验表名
        if (!$table)
            throw new DateException( 193 ); // 模块名不能为空
        // 校验方法名
        $method = strtoupper( trim( $_SERVER['REQUEST_METHOD'] ) );
        if (!$method)
            throw new DateException( 194 ); // 方法名不能为空
        // 封装数据 && 时间 && 去掉日志记录中的[重复]数据
        $record['uri']      = $_SERVER['REQUEST_URI'] . '/' . strtolower( $method ) . '.php';
        $record['login_ip'] = Client::getIp(); // 获取用户登录IP
        $now                = date( 'Y-m-d H:i:s', time() );
        if (!isset( $record['created_at'] ) || !$record['created_at'])
            $record['created_at'] = $now; // 创建时间
        if ((!empty( $record ) && $rowId > 0) && (!isset( $record['updated_at'] ) || !$record['updated_at']))
            $record['updated_at'] = $now; // 封装时间
        // 封装原始数据
        $logData = [
            'table'      => $table,
            'row_id'     => $rowId,
            'admin_id'   => $this->playLoad['admin_id'] ?? 0, // 试错的账户id不一定是系统账户
            'admin_name' => $this->playLoad['admin_name'] ?? '', // 试错的账户名不一定是系统账户
            'method'     => $record['uri'] == '/admin/login/post.php' ? 'GET' : $method,
            'status'     => $status,
        ];
        // 用户登录时：获取用户信息
        if (empty( $logData['admin_id'] ) && empty( $logData['admin_name'] )) {
            $admin                 = AdminModel::query()->where( 'admin_name', trim( $record['admin_name'] ) )->first( ['id', 'admin_name'] );
            $logData['admin_id']   = $admin->id ?? 0;
            $logData['admin_name'] = $admin->admin_name ?? '';
        }
        // 格式化记录
        unset( $record['creator_id'], $record['creator_name'], $record['admin_id'], $record['admin_name'] );
        if (!empty( $record ))
            $logData['record'] = json_encode( $record, JSON_UNESCAPED_UNICODE );
        // 入库并结果判断
        $res = (new Log( $this->ci ))->writeAdminLog( $logData );
        if (!$res)
            throw new DateException( 195 ); // 写日志失败
    }

    /**
     * 检查工单类型
     *
     * @param int $status
     * @return array
     */
    protected function checkWorkOrderStatus(int $status = 0): array
    {
        $buttons = ['activated' => false, 'confirm' => false, 'solve' => false, 'close' => false, 'edit' => true, 'copy' => true];
        switch ($status) {
            case WorkOrderModel::STATUS_UNCONFIRMED: // 待确认状态 => 确认、解决、编辑、复制
                $buttons['confirm'] = true;
                $buttons['solve']   = true;
                break;
            case WorkOrderModel::STATUS_DOING: // 处理中状态 => 解决、编辑、复制
                $buttons['solve'] = true;
                break;
            case WorkOrderModel::STATUS_FINISHED: // 已解决状态 => 关闭、编辑、复制
                $buttons['close'] = true;
                break;
            case WorkOrderModel::STATUS_CLOSED: // 已关闭状态 => 激活、编辑、复制
                $buttons['activated'] = true;
                break;
            case WorkOrderModel::STATUS_ACTIVATED: // 激活状态 => 确认、解决、编辑、复制
                $buttons['confirm'] = true;
                $buttons['solve']   = true;
                break;
        }

        return $buttons;
    }

    /**
     * 检查上传附件
     *
     * @param array $params
     * @return void
     */
    protected function checkWorkOrderAppendix(array $params)
    {
        //附件校验
        if (!empty( $params['appendix'] )) {
            //判断数据格式
            $appendix = isJson( $params['appendix'], true );
            if ($appendix === false) {
                return $this->lang->set( 200 );
            }
            //判断数量
            if (count( $appendix ) > 5) {
                return $this->lang->set( 204 );
            }
            //判断工单附件
            foreach ($appendix as $v) {
                //判断数量
                if (count( $v ) != 2) {
                    return $this->lang->set( 200 );
                }
                //判断字段
                if (empty( $v['appendix_name'] ) || empty( $v['appendix_url'] )) {
                    return $this->lang->set( 200 );
                }
            }
        }
    }
}

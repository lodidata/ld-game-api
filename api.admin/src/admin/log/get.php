<?php

use Model\DB;
use Model\Common\CurrencyModel;
use Model\Common\AdminLogModel;
use Logic\Admin\BaseController;
use Lib\Validate\Admin\AdminLogValidate;
use Utils\Client;

return new class extends BaseController {
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        // 校验请求参数
        (new AdminLogValidate())->paramsCheck('get', $this->request, $this->response);
        // 批量获取所有的请求参数并对分页信息进行初始化
        $params = $this->request->getParams();
        foreach ($params as $key => $param) {
            if (!empty($param)) $params[$key] = trim($param);
            if ($key === 'page' && (!is_numeric($param) || $param <= 0)) $params[$key] = $this->page;
            if ($key === 'page_size' && (!is_numeric($param) || $param <= 0)) $params[$key] = $this->pageSize;
        }
        // 分页参数
        $common = ['page' => $params['page'] ?? $this->page, 'page_size' => $params['page_size'] ?? $this->pageSize];
        // 查询条件封装
        $adminLogObj = AdminLogModel::query();
        isset($params['id']) && is_numeric($params['id']) && $params['id'] > 0 && $adminLogObj->where('id', $params['id']); // 基于id检索，可以进行排错
        !empty($params['module']) && $adminLogObj->where('table', $params['module']); // 模块
        !empty($params['method']) && in_array($params['method'], array_keys(AdminLogModel::METHOD_K_V)) && $adminLogObj->where('method', (int)$params['method']); // 获取方法类型
        !empty($params['operator_id']) && $adminLogObj->where('admin_id', $params['operator_id']); // 操作人账户id
        !empty($params['operator']) && $adminLogObj->where('admin_name', 'like', $params['operator'] . '%'); // 操作人账户
        isset($params['status']) && is_numeric($params['status']) && in_array($params['status'], array_keys(AdminLogModel::STATUS_ARR)) && $adminLogObj->where('status', (int)$params['status']); // 状态
        // 统计总的记录数
        $common['total'] = $adminLogObj->count() ?? 0;
        // 获取分页列表并格式化列表
        $adminLogList = $adminLogObj->orderBy('id', 'desc')->forpage($common['page'], $common['page_size'])->get()->toArray();
        // 格式化列表
        foreach ($adminLogList as $k => $adminLog) {
            $adminLogList[$k]['remark'] = '';
            if (in_array($adminLog['method'], [AdminLogModel::METHOD_PUT, AdminLogModel::METHOD_PATCH]) && $adminLog['status'] == AdminLogModel::STATUS_ON) $adminLogList[$k]['remark'] = $this->getPreviousRecord($adminLogList, $adminLog); // 剔除查询、新增、删除类型,并格式化发生修改的数据
            if ($adminLog['method'] == AdminLogModel::METHOD_GET) $adminLogList[$k]['remark'] = $this->getLoginDetail($adminLog, $adminLog['status']); // 登录
            $adminLogList[$k]['status'] = AdminLogModel::STATUS_ARR[$adminLog['status']] ?? '未知';
            $adminLogList[$k]['module'] = AdminLogModel::MODULE_LIST[$adminLog['table']] ?? '未知';
            $adminLogList[$k]['method'] = AdminLogModel::METHOD_K_V_STR[$adminLog['method']] ?? '未知';
            // 格式化苏阿努按信息
            if (!empty($adminLog['table']) && !empty($adminLog['row_id']))
                $adminLogList[$k]['table_id'] = $adminLog['table'] . '->' . $adminLog['row_id'];
            else
                $adminLogList[$k]['table_id'] = '未知';
            empty($adminLog['admin_id']) && $adminLogList[$k]['admin_id'] = '未知'; // 初始化账户id
            empty($adminLog['admin_name']) && $adminLogList[$k]['admin_name'] = '未知'; // 初始化账户名
            unset($adminLogList[$k]['record'], $adminLogList[$k]['table'], $adminLogList[$k]['row_id']);
        }
        return $this->lang->set(0, [], $adminLogList, $common);
    }

    /**
     * 获取当前记录的上一条记录
     *
     * @param array $adminLogList
     * @param array $adminLog
     * @return string
     */
    protected function getPreviousRecord(array $adminLogList, array $adminLog): string
    {
        // 查询、新增、删除不用查询数据表
        if (in_array($adminLog['method'], [AdminLogModel::METHOD_GET, AdminLogModel::METHOD_POST, AdminLogModel::METHOD_DELETE])) return '';
        // 1th.在原列表中检索数据
        $record = ''; // 初始化
        foreach ($adminLogList as $log) {
            if (!empty($log['table']) && !empty($adminLog['table']) && $log['table'] == $adminLog['table'] &&
                !empty($log['row_id']) && !empty($adminLog['row_id']) && $log['row_id'] == $adminLog['row_id'] &&
                !empty($log['method']) && in_array($log['method'], [AdminLogModel::METHOD_POST, AdminLogModel::METHOD_PUT]) &&
                isset($log['status']) && $log['status'] == AdminLogModel::STATUS_ON &&
                $log['id'] != $adminLog['id'] // 过滤掉非本条记录的场景
            ) {
                $record = $log['record'] ?? ''; // 在列表中的record记录
                break; // 取最新的一条就返回
            }
        }
        // 2th.获取当前记录的上一次操作类型
        if (!$record) {
            $limitAdminLogList = AdminLogModel::query()
                ->where('table', $adminLog['table'])
                ->where('row_id', $adminLog['row_id'])
                ->where('status', AdminLogModel::STATUS_ON) // 成功的
                ->orderBy('id', 'desc')
                ->limit(2)
                ->get()
                ->toArray();
            foreach ($limitAdminLogList as $log) {
                if (!empty($log['id']) && !empty($adminLog['id']) && $log['id'] != $adminLog['id'] && !empty($log['record'])) {
                    $record = $log['record']; // 取账户日志表的record记录
                    break;
                }
            }
        }
        // 3th.取原数据
        if (!$record) {
            $tableData = (array)DB::table($adminLog['table'])->where('id', $adminLog['row_id'])->first();
            if ($tableData) {
                $record = json_encode($tableData, true);
            }
        }
        // 校验
        if (!$record || !is_json($record)) return $record ?: '{}';
        // record的值 => json2arr
        $oldRecord = json_decode($record, true);
        $this->formatJsonToArray($oldRecord); // 个别特殊字段需要将：json 转 数组参与比较
        // 获取差异数据
        $newRecord = is_json($adminLog['record'] ?? '') ? json_decode($adminLog['record'], true) : []; // record => json2arr
        $this->formatJsonToArray($newRecord); // 个别特殊字段需要将：json 转 数组参与比较
        // 获取差异数据
        return $this->getDifferentData($adminLog, $oldRecord, $newRecord);
    }

    /**
     * 个别特殊字段：json 转 数组
     *
     * @param array $array
     * @return void
     */
    protected function formatJsonToArray(array &$array)
    {
        // 去掉无需比对的字段
        unset($array['method'], $array['created_at'], $array['updated_at'], $array['password'], $array['uri']);
    }

    /**
     * 更新操作校验数据是否有变更
     *
     * @param array $adminLog
     * @param array $newRecord
     * @param array $oldRecord
     * @return string
     */
    protected function getDifferentData(array $adminLog = [], array $oldRecord = [], array $newRecord = []): string
    {
        // 初始化
        $str = '';
        // 请求参数非空校验
        if (!$adminLog || !$newRecord || !$oldRecord) return $str;
        // 带索引检查计算数组的差集
        $diffArr = array_diff_assoc($oldRecord, $newRecord);
        foreach ($diffArr as $key => $value) {
            if (!empty($key) && is_string($key)) {
                $str .= $this->getDifferentToString($adminLog, $key, $value);
            }
        }
        return $str;
    }

    /**
     * 不相同的2个数组获取差异数据转成字符串
     *
     * @param array $adminLog
     * @param string $key
     * @param string|array $value
     * @return string
     */
    protected function getDifferentToString(array $adminLog, string $key, $value): string
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (is_string($v)) return $this->getDifferentToString($value, $k, $v); // 递归
            }
        }
        $str = AdminLogModel::MODULE_MAP[$adminLog['table']][$key] ?? '';
        if ($str) {
            // 特殊字段格式化
            $str .= ':';
            if (in_array($key, AdminLogModel::SPECIAL_FIELD)) {
                $tmp = AdminLogModel::MODULE_MAP[$adminLog['table']][$key . '_str'][$value] ?? '未知';
                $str .= '被修改为[' . $tmp . '];';
            } else { // 常规字段格式化
                if (in_array($key, ['password', 'ip', 'secret_key'])) {
                    if ($key === 'ip') {
                        if (strpos('.', $value) === false || is_numeric($value)) $value = long2ip($value); // 格式化IP
                    } else {
                        $value = '******;'; // 加密
                    }
                }
                $str .= '被修改为[' . $value . '];';
            }
        }

        return $str;
    }

    /**
     * 登录日志封装备注信息
     *
     * @param array $adminLog
     * @param int $status
     * @return string
     */
    protected function getLoginDetail(array $adminLog, int $status = 1): string
    {
        $str    = '';
        $record = json_decode($adminLog['record'] ?? '', true);
        if ($status === 1) {
            $str .= '账户ID：' . $adminLog['admin_id'] . '；';
            $str .= '账户名：' . $adminLog['admin_name'] . '；';
            $str .= '角色ID：' . $record['role_id'] . '；';
            $str .= '部门：' . $record['department'] . '；';
            $str .= '职位：' . $record['position'] . '；';
            $str .= '最近登录IP：' . $record['last_login_ip'] . '；';
            $str .= '最近登录时间：' . $record['last_login_time'] . '；';
        } else {
            $str .= '账户名：' . $adminLog['admin_name'] . '；';
            if (!empty($record['error'])) $str .= '备注：' . $record['error'] . '；';
        }
        // 公共参数
        if (!empty($record['login_ip'])) $str .= '登录IP:' . $record['login_ip'] . '；';
        if (!empty($record['created_at'])) $str .= '登录时间：' . $record['created_at'] . '；';

        return $str;
    }

    /**
     * 格式化特殊字符
     *
     * @param array $record
     * @param string $table
     * @return void
     */
    private function formatRecordSpecialField(array &$record, string $table)
    {
        $specialFields = AdminLogModel::SPECIAL_FIELD;
        foreach ($specialFields as $specialField) {
            if (isset($record[$specialField])) {
                $record[$specialField . '_str'] = AdminLogModel::MODULE_MAP[$table][$specialField . '_str'][$record[$specialField]] ?? '未知';
            }
        }
    }
};
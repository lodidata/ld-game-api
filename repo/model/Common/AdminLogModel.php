<?php

namespace Model\Common;

use Illuminate\Database\Eloquent\Model;

class AdminLogModel extends Model
{
    protected $table = 'admin_log';
    protected $primaryKey = 'id';
    const UPDATED_AT = null;
    // 集合
    const MODULE_LIST = [
        // 账户对象
        'admin'                  => '账户管理',
        'admin_role'             => '账户角色管理',
        'admin_role_auth'        => '账户角色权限管理',
        'admin_role_relation'    => '账户角色关系管理',
        // 代理对象
        'agent'                  => '代理管理',
        'agent_game'             => '代理游戏管理',
        'agent_ip_whitelist'     => '代理白名单管理',
        // 游戏对象
        'game_3th'               => '游戏管理',
        'game_api'               => '游戏API管理',
        'game_menu'              => '游戏厂商管理',
        'game_menu_currency'     => '游戏厂商货币管理',
        'game_menu_ip_whitelist' => '游戏厂商IP白名单',
        'game_type'              => '游戏类型管理',
        // 货币对象
        'currency'               => '货币管理',
        // 账单对象
        'bill'                   => '账单管理',
    ];
    const MODULE_MAP  = [
        'admin'                  => self::ADMIN_MODULE, // 账户管理模块
        'admin_role'             => self::ADMIN_ROLE_MODULE, // 账户角色管理模块
        'admin_role_auth'        => self::ADMIN_ROLE_AUTH_MODULE, // 账户角色权限管理模块
        'admin_role_relation'    => self::ADMIN_ROLE_RELATION_MODULE, // 账户角色关系管理模块
        'agent'                  => self::AGENT_MODULE, // 代理管理模块
        'agent_game'             => self::AGENT_GAME_MODULE, // 代理游戏管理模块
        'agent_ip_whitelist'     => self::AGENT_IP_WHITELIST_MODULE, // 代理白名单管理模块
        'game_3th'               => self::GAME_3TH_MODULE, // 游戏管理模块
        'game_api'               => self::GAME_API_MODULE, // 游戏API管理模块
        'game_menu'              => self::GAME_MENU_MODULE, // 游戏厂商管理模块
        'game_menu_currency'     => self::GAME_MENU_CURRENCY_MODULE, // 游戏厂商货币关系表
        'game_menu_ip_whitelist' => self::GAME_MENU_IP_WHITELIST_MODULE, //游戏厂商IP白名单模块
        'game_type'              => self::GAME_TYPE_MODULE, // 游戏类型管理模块
        'currency'               => self::CURRENCY_MODULE, // 货币管理模块
        'bill'                   => self::BILL_MODULE, // 货币管理模块
    ];

    // 账户模块
    const ADMIN_MODULE               = [
        'admin_name'      => '账户',
        'password'        => '密码',
        'real_name'       => '姓名',
        'nick_name'       => '昵称',
        'position'        => '职位',
        'department'      => '部门',
        'status'          => '状态',
        'status_str'      => ['禁用', '启用'],
        'last_login_ip'   => '最近登录IP',
        'last_login_time' => '最近登录时间',
    ];
    const ADMIN_ROLE_MODULE          = [
        'role_name'   => '角色名',
        'auth'        => '权限列表',
        'num'         => '角色权限数量',
        'operator_id' => '操作人ID',
        'operator'    => '操作人',
    ];
    const ADMIN_ROLE_AUTH_MODULE     = [
        'pid'        => '父级别ID',
        'auth_name'  => '权限名',
        'method'     => '请求类型',
        'path'       => '请求地址',
        'status'     => '状态',
        'status_str' => ['禁用', '启用'],
        'sort'       => '序号'
    ];
    const ADMIN_ROLE_RELATION_MODULE = [
        'admin_id' => '账户ID',
        'role_id'  => '角色ID',
    ];
    const AGENT_GAME_MODULE          = [
        'agent_code'    => '代理号',
        'menu_type'     => '厂商名称',
        'agent_account' => '后台账户',
        'password'      => '代理游戏密码',
        'admin_url'     => '代理游戏管理地址',
        'rate'          => '费率',
        'status'        => '状态',
        'status_str'    => ['禁用', '启用']
    ];
    const AGENT_MODULE               = [
        'agent_code'            => '代理号',
        'brand_name'            => '品牌名称',
        'currency'              => '货币',
        'secret_key'            => '密钥',
        'site_url'              => '官网地址',
        'callback_url'          => '回调API地址',
        'is_allow_login'        => '是否允许注册登录',
        'is_allow_login_str'    => ['禁用', '开启'],
        'is_allow_transfer'     => '是否允许转账',
        'is_allow_transfer_str' => ['禁用', '开启'],
        'is_allow_order'        => '是否允许拉单',
        'is_allow_order_str'    => ['禁用', '开启'],
        'is_limit_recharge'     => '是否开启限制充值金额',
        'is_limit_recharge_str' => ['禁用', '开启'],
        'limit_recharge_money'  => '限制充值金额',
        'bill_date'             => '账单日期',
        'status'                => '状态',
        'status_str'            => ['禁用', '启用'],
        'wallet_type'           => '钱包类型',
        'wallet_type_str'       => ['转账钱包', '单一钱包'],
    ];
    const AGENT_IP_WHITELIST_MODULE  = [
        'agent_code' => '代理号',
        'ip'         => 'IP白名单',
    ];
    const CURRENCY_MODULE            = [
        'currency_type' => '货币类型',
        'currency_name' => '货币名称',
        'status'        => '状态',
        'status_str'    => ['下架', '上架']
    ];
    const GAME_3TH_MODULE            = [
        'kind_id'     => '第三方游戏id',
        'kind_type'   => '第三方游戏类型',
        'kind_name'   => '第三方游戏名称',
        'menu_type'   => '厂商类型',
        'game_type'   => '游戏类型',
        'status'      => '状态',
        'status_str'  => ['下架', '上架'],
        'is_demo'     => '试玩',
        'is_demo_str' => ['否', '是']
    ];
    const GAME_API_MODULE            = [
        'agent_code' => '代理号',
        'menu_type'  => '厂商名称',
        'api_agent'  => '代理编码',
        'api_key'    => '密钥',
        'lobby'      => '特殊参数',
        'status'     => '状态',
        'status_str' => ['禁用', '开启']
    ];
    const GAME_MENU_MODULE           = [
        'menu_type'        => '厂商类型',
        'menu_name'        => '厂商名称',
        'currency'         => '支持货币',
        'status'           => '显示状态',
        'status_str'       => ['下架', '上架'],
        'start_uworked_at' => '维护开始时间',
        'end_uworked_at'   => '维护结束时间',
        'work_status'      => '工作状态',
        'work_status_str'  => ['维护状态', '工作状态'],
        'api_login_url'    => '游戏登录API接口',
        'api_api_url'      => '游戏通用API接口',
        'api_order_url'    => '游戏拉单API接口',
        'api_config'       => 'game_api表配置项',
    ];
    const GAME_TYPE_MODULE           = [
        'type_code'  => '类型编号',
        'type_name'  => '类型名称',
        'status'     => '状态',
        'status_str' => ['禁用', '开启']
    ];
    const BILL_MODULE                = [
        'agent_code'      => '代理号',
        'brand_name'      => '品牌名称',
        'menu_type'       => '厂商名称',
        'start_bill_date' => '账单(起)日期',
        'end_bill_date'   => '账单(止)日期',
        'currency_type'   => '货币类型',
        'valid_bet'       => '有效投注',
        'win_lose_bet'    => '输赢',
        'rate'            => '费率',
        'exchange_rate'   => '汇率',
        'settlement'      => '交收金额',
    ];
    const GAME_MENU_CURRENCY_MODULE  = [
        'game_menu_id'      => '游戏厂商id',
        'game_menu_type'    => '游戏厂商类型',
        'currency_id'       => '货币id',
        'currency_type'     => '(单)货币类型',
        'currency_type_str' => '(多)货币类型',
    ];

    const GAME_MENU_IP_WHITELIST_MODULE = [
        'menu_type' => '游戏厂商',
        'ip'        => 'IP白名单'
    ];

    // 特殊字段
    const SPECIAL_FIELD = ['status', 'work_status', 'is_allow_login', 'is_allow_transfer', 'is_allow_order', 'is_limit_recharge', 'is_demo', 'wallet_type'];

    const TIME_TO_STRING = ['start_uworked_at', 'end_uworked_at'];

    const METHOD_GET    = 1; // 查询
    const METHOD_POST   = 2; // 新增
    const METHOD_PUT    = 3; // 更新
    const METHOD_PATCH  = 4; // 状态
    const METHOD_DELETE = 5; // 删除
    // KV方法列表
    const METHOD_K_V = [
        self::METHOD_GET    => 'GET',
        self::METHOD_POST   => 'POST',
        self::METHOD_PUT    => 'PUT',
        self::METHOD_PATCH  => 'PATCH',
        self::METHOD_DELETE => 'DELETE',
    ];
    // VK方法列表
    const METHOD_V_K     = [
        'GET'    => self::METHOD_GET,
        'POST'   => self::METHOD_POST,
        'PUT'    => self::METHOD_PUT,
        'PATCH'  => self::METHOD_PATCH,
        'DELETE' => self::METHOD_DELETE,
    ];
    const METHOD_K_V_STR = [
        self::METHOD_GET    => '登录', // 全站仅账户登录的时候method:获取类型
        self::METHOD_POST   => '创建',
        self::METHOD_PUT    => '修改',
        self::METHOD_PATCH  => '状态',
        self::METHOD_DELETE => '删除',
    ];
    // 状态
    const STATUS_OFF = 0; // 失败
    const STATUS_ON  = 1; // 成功
    const STATUS_ARR = [
        self::STATUS_OFF => '失败',
        self::STATUS_ON  => '成功',
    ];
}
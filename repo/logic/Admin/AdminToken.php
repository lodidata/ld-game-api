<?php

namespace Logic\Admin;

use Model\DB;
use Utils\Client;
use \Logic\Captcha\Captcha;
use Model\Common\AdminModel;
use Lib\Exception\BaseException;
use Illuminate\Database\Capsule\Manager as Capsule;
use ClickHouseDB\Exception\DatabaseException;

/**
 * json web token
 * 保证web api验证信息不被篡改
 *
 * @property mixed $lang
 * @property mixed $request
 * @property mixed $response
 * @property mixed $redis
 */
class AdminToken extends \Logic\Logic
{
    const KEY    = 'this is secret use for jwt';
    const EXPIRE = 3600 * 24;
    protected $Db;
    protected $adminAuth;
    protected $playLoad = [
        'admin_id'   => 0, // 0 匿名用户
        'role_id'    => 0, // 0 默认权限
        'type'       => 1, // 1 普通用户; 2 平台用户
        'admin_name' => '',
        'ip'         => '',
        'client_id'  => '',
    ];

    public function __construct($ci)
    {
        parent::__construct( $ci );
        $this->Db = new Capsule();
        $this->Db->setFetchMode( \PDO::FETCH_ASSOC );
        $this->adminAuth = new AdminAuth( $ci );
    }

    /**
     * 创建token
     *
     * @param array $data
     * @param string $publicKey
     * @param int $ext
     * @param string $digital
     * @return array
     */
    public function createToken(array $data = [], string $publicKey = self::KEY, int $ext = self::EXPIRE, string $digital = ''): array
    {
        // 校验基于用户名获取用户信息
        $userObj = AdminModel::query()->where( 'admin_name', $data['admin_name'] )->first();
        if (empty( $userObj ))
            throw new DatabaseException( 9 );
        else
            $user = $userObj->toArray();
        // 校验用户状态
        if ($user['status'] != 1)
            throw new DatabaseException( 196 );
        // 校验用户密码
        if (!password_verify( $data['password'], $user['password'] ))
            throw new DatabaseException( 4 );
        else
            unset( $user['password'] );
        // 校验验证码
        $checkRes = (new Captcha( $this->ci ))->validateImageCode( $data['token'], $data['code'] );
        if (!$checkRes)
            throw new DatabaseException( 121 );
        // 校验用户角色权限 并封装数据
        $roleId          = DB::table( 'admin_role_relation' )->where( 'admin_id', $user['id'] )->value( 'role_id' );
        $user['role_id'] = $roleId ?? 0; // 如果缺少role_id，则为：0, 后期可以基于role_id为0标识为超管等等
        $userData        = [
            'admin_id'   => self::fakeId( $user['id'], $digital ),
            'role_id'    => self::fakeId( intval( $user['role_id'] ), $digital ),
            'admin_name' => $user['admin_name'] ?? '',
            'ip'         => Client::getIp(),
            'mac'        => Client::ClientId(),
        ];
        // 生成header
        $header = ['alg' => "HS256", 'typ' => "JWT"];
        $header = base64_encode( json_encode( $header ) );
        // 生成payload
        $payload = base64_encode( json_encode( array_merge( ["iss" => "lxz", "exp" => time() + $ext], $userData ) ) );
        // 生成Signature
        $signature = hash_hmac( 'sha256', $header . '.' . $payload, $publicKey, false );
        // 封装并设置token
        $token = $header . '.' . $payload . '.' . $signature;
        $this->adminAuth->saveAdminWithToken( $user['id'], $token, $ext );
        // 更新登录信息
        $res = DB::table( 'admin' )->where( 'id', $user['id'] )->update( ['last_login_ip' => $userData['ip'], 'last_login_time' => date( 'Y-m-d H:i:s', time() )] );
        if (!$res)
            throw new DatabaseException( 130 );
        // 获取账户的权限列表
        $routes = $this->adminAuth->getAuths( intval( $user['role_id'] ) );

        // 返回结果
        return ['token' => $token, 'info' => $user, 'route' => $routes];
    }

    /**
     * @throws \Lib\Exception\BaseException
     */
    public function verifyToken()
    {
        if ($this->playLoad['role_id'] === 0 || $this->playLoad['admin_id'] === 0 || empty( $this->playLoad['admin_name'] )) $this->getToken();

        return $this->playLoad;
    }

    public function remove($adminId)
    {
        $this->adminAuth->removeToken( $adminId );
    }

    /**
     * @throws \Lib\Exception\BaseException
     * @throws \Exception
     */
    protected function getToken()
    {
        $header = trim( $this->request->getHeaderLine( 'Authorization' ) );
        // 判断header是否携带token信息
        if (!$header) {
            $newResponse = createResponse( $this->response, 401, 10041, '缺少验证信息！' );
            throw new BaseException( $this->request, $newResponse );
        }

        $config = $this->ci->get( 'settings' )['jsonwebtoken'];
        $token  = substr( $header, 7 );
        if ($token && $data = $this->decode( $token, $config['public_key'] ?? self::KEY )) {
            $adminId     = $this->originId( $data['admin_id'], $config['uid_digital'] ?? '' ) ?? 0;
            $key         = \Logic\Set\SetConfig::SET_GLOBAL;
            $cache       = json_decode( $this->redis->get( $key ), true );
            $login_check = $cache['base']['Duplicate_LoginCheck'];
            if ($login_check)
                $this->adminAuth->checkAdminWithToken( $adminId, $token );
            $roleId = $this->originId( $data['role_id'] ?? 0, $config['uid_digital'] ?? '' ) ?? 0;
            // 封装全局用户信息
            $this->playLoad      = array_merge(
                $this->playLoad,
                ['role_id' => $roleId, 'admin_id' => $adminId, 'admin_name' => $data['admin_name'] ?? '', 'ip' => Client::getIp()],
            );
            $GLOBALS['playLoad'] = $this->playLoad;
        } else {
            $newResponse = createResponse( $this->response, 401, 10041, '验证信息不合法！' );
            throw new BaseException( $this->request, $newResponse );
        }
    }

    /**
     * @param $token
     * @param string $publicKey
     *
     * @return mixed|null
     * @throws BaseException
     * @throws \Exception
     */
    protected function decode($token, string $publicKey = self::KEY)
    {
        if (substr_count( $token, '.' ) != 2) {
            return null;
        }
        [$header, $payload, $signature] = explode( '.', $token, 3 );
        // 校验签名是否合法
        if (hash_hmac( 'sha256', $header . '.' . $payload, $publicKey ) != $signature) {
            $newResponse = createResponse( $this->response, 401, 10041, '验证不通过！' );
            throw new BaseException( $this->request, $newResponse );
        }
        // 是否过期
        $_payload = json_decode( base64_decode( $payload, true ), true );
        if ($_payload['exp'] <= time()) {
            $newResponse = createResponse( $this->response, 401, 10041, '登录超时！' );
            throw new BaseException( $this->request, $newResponse );
        }

        return $_payload;
    }

    /**
     * 伪uid
     *
     * @param int $adminId
     * @param int $digital
     *
     * @return int
     */
    public static function fakeId(int $adminId, int $digital): int
    {
        return ~$digital - $adminId;
    }

    /**
     * 原uid
     *
     * @param int $fakeId
     * @param int $digital
     *
     * @return int
     */
    public function originId(int $fakeId, int $digital): int
    {
        return ~($fakeId + $digital);
    }
}

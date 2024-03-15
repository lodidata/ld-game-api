<?php

use Utils\Encrypt;

function createResponse($response, $status = 200, $state = 0, $message = 'ok', $data = null, $attributes = null)
{
    return $response
        ->withStatus( $status )
        ->withJson( [
            'data'       => $data,
            'attributes' => $attributes,
            'state'      => $state,
            'message'    => $message,
            'ts'         => time(),
        ] );
}

/**
 * 判断值是否是大于0的正整数
 *
 * @param $value
 * @return bool
 */
function isPositiveInteger($value): bool
{
    if (is_numeric( $value ) && is_int( $value + 0 ) && ($value + 0) > 0) {
        return true;
    } else {
        return false;
    }
}


/**
 * 使用正则验证数据
 *
 * @access public
 * @param string $value :要验证的数据
 * @param string $rule :验证规则
 * @return boolean
 */
function regex(string $value, string $rule): bool
{
    $validate = [
        'require'      => '/\S+/',
        'email'        => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
        'mobile'       => '/^(((13[0-9]{1})|(14[5,7]{1})|(15[0-35-9]{1})|(17[0678]{1})|(18[0-9]{1}))+\d{8})$/',
        'phone'        => '/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/',
        'url'          => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
        'currency'     => '/^\d+(\.\d+)?$/',
        'number'       => '/^\d+$/',
        'zip'          => '/^\d{6}$/',
        'integer'      => '/^[-\+]?\d+$/',
        'double'       => '/^[-\+]?\d+(\.\d+)?$/',
        'english'      => '/^[A-Za-z]+$/',
        'bankcard'     => '/^\d{14,19}$/',
        'safepassword' => '/^(?=.*\\d)(?=.*[a-z])(?=.*[A-Z]).{8,20}$/',
        'chinese'      => '/^[\x{4e00}-\x{9fa5}]+$/u',
        'oddsid'       => '/^([+]?\d+)|\*$/',//验证赔率设置id
        'qq'           => '/^[1-9]\\d{4,14}/',//验证qq格式
    ];
    // 检查是否有内置的正则表达式
    if (isset ( $validate [strtolower( $rule )] ))
        $rule = $validate [strtolower( $rule )];
    return 1 === preg_match( $rule, $value );
}

function is_json($string): bool
{
    json_decode( $string );
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * 判断字符串是否为 Json 格式
 *
 * @param string $data Json 字符串
 * @param bool $assoc 是否返回关联数组。默认返回对象
 * @return array|bool|object 成功返回转换后的对象或数组，失败返回 false
 */
function isJson(string $data = '', bool $assoc = false)
{
    $data = json_decode( $data, $assoc );
    if (($data && is_object( $data )) || (is_array( $data ) && !empty( $data ))) {
        return $data;
    }
    return false;
}

/**
 * 随机长度的字符串
 *
 * @param $length
 * @return string
 */
function getRandStr($length): string
{
    //字符组合
    $str     = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $len     = strlen( $str ) - 1;
    $randStr = '';
    for ($i = 0; $i < $length; $i++) {
        $num     = mt_rand( 0, $len );
        $randStr .= $str[$num];
    }

    return $randStr;
}

/**
 * @throws \Exception
 */
function app()
{
    return Utils\App::getContainer();
}

if (!function_exists( 'merge_request' )) {
    /**
     * @throws \ReflectionException
     */
    function merge_request($request, array $data)
    {
        $body  = $request->getParsedBody();
        $input = array_merge( $body, $data );

        $rec = new \ReflectionProperty( $request, 'bodyParsed' );
        $rec->setAccessible( true );
        $rec->setValue( $request, $input );
    }
}

if (!function_exists( 'getTmpDir' )) {
    /**
     * 获取临时目录
     * @return string
     */
    function getTmpDir(): string
    {
        $tmp = ini_get( 'upload_tmp_dir' );
        if ($tmp !== False && file_exists( $tmp )) {
            return realpath( $tmp );
        }
        return realpath( sys_get_temp_dir() );
    }
}

/**
 * 判断是否是日期
 * @param $dateString
 * @return bool
 */
function isDate($dateString): bool
{
    return strtotime( date( 'Y-m-d', strtotime( $dateString ) ) ) === strtotime( $dateString );
}

/**
 * 获取m、n之间的随机数，保留2位小数
 *
 * @param int $m
 * @param int $n
 * @param bool $flag
 * @return float
 */
function generateRand(int $m, int $n, $flag = 0): float
{
    if ($m > $n) {
        $numMax = $m;
        $numMin = $n;
    } else {
        $numMax = $n;
        $numMin = $m;
    }
    /**
     * 生成$numMin和$numMax之间的随机浮点数，保留2位小数
     */
    $rand = $numMin + mt_rand() / mt_getrandmax() * ($numMax - $numMin);
    if ($flag) {
        return floatval( number_format( $rand, 2 ) ); // 除以了1000
    }
    return bcadd( floatval( $rand ), 0.00, 2 );
}


/**
 * php生成某个范围内的随机时间
 *
 * @param string $beginTime :起始时间 格式为 Y-m-d H:i:s
 * @param string $endTime :结束时间 格式为 Y-m-d H:i:s
 * @param bool $is :是否是时间戳 格式为 Boolean
 * @return false|int|string
 */
function randomDate(string $beginTime = '', string $endTime = '', bool $is = true)
{
    $begin     = strtotime( $beginTime );
    $end       = $endTime == "" ? mktime() : strtotime( $endTime );
    $timestamp = rand( $begin, $end );

    return $is ? date( "Y-m-d H:i:s", $timestamp ) : $timestamp;
}

/**
 * 计算给定的2个日期计算相差多少个月
 *
 * @param string $date1
 * @param string $date2
 * @param string $tags
 * @return int
 */

function getMonthNum(string $date1, string $date2, string $tags = '-'): int
{
    $date1 = explode( $tags, $date1 );
    $date2 = explode( $tags, $date2 );

    return abs( $date1[0] - $date2[0] ) * 12 + abs( $date1[1] - $date2[1] );
}

/**
 * 对象转换成数组
 *
 * @param $obj
 * @return array
 */
function objToArray($obj): array
{
    return json_decode( json_encode( $obj ), true );
}

function makePassword($password): array
{
    $salt = Encrypt::salt();

    return [md5( md5( $password ) . $salt ), $salt];
}
<?php

namespace Utils;

/**
 * 工具类
 * Class Utils
 * @package Utils
 */
class Utils
{
    public static function getKey($key)
    {
        $k   = '';
        $s   = ['*', '$', '\\', '/', "'", '"', "?", "#", "]", ",", ";", "["];
        $r   = ['A', 'E', 'S', '2', "5", '6', "C", "B", "C", "a", "e", "s"];
        $key = str_replace( $s, $r, strtoupper( $key ) ) . 'plat';
        for ($i = 0; $i < strlen( $key ); $i++) {
            $k .= chr( ord( $key[$i] ) + 2 );
        }
        return substr( $k, strlen( str_replace( $s, $r, $k ) ) - 16 );
    }

    /**
     * openssl 双向加密
     *
     * @param string $string
     * @param bool $encrypt
     * @return object|string
     */
    public static function xmCryptString(string $string, bool $encrypt = true)
    {
        $mts = null;
        if (trim( $string )) {
            $key = self::getKey( 'kQYOdspla9I5elv2wdaaaDcg==' );
            $mts = new Encrypt( $key );
            $e   = $encrypt ? $mts->encrypt( $string ) : $mts->decrypt( $string );
        }
        return isset( $e ) && $e ? $e : $string;
    }

    //返回当前的毫秒时间戳
    public static function msecTime(): float
    {
        [$msec, $sec] = explode( ' ', microtime() );
        return (float)sprintf( '%.0f', (floatval( $msec ) + floatval( $sec )) * 1000 );
    }

    /**
     * openssl 双向加密
     *
     * @return Encrypt|null
     */
    public static function xmCrypt(): ?Encrypt
    {
        global $app;
        static $mt = null;
        if (is_null( $mt )) {
            $key = $app->getContainer()->get( 'settings' )['app']['app_key'];
            $mt  = new Encrypt( $key );
        }

        return $mt;
    }

    /**
     * openssl 双向加密
     *
     * @return Encrypt
     */
    public static function xmCryptSetting(): Encrypt
    {
        static $mts = null;
        if (is_null( $mts )) {
            $key = 'kQYOdswcm9I5elv2wdJucg==';
            $mts = new Encrypt( $key );
        }
        return $mts;
    }

    /**
     * 加密
     *
     * @param string|array $data
     * @param bool $encrypt
     * @return array|string
     */
    public static function settleCrypt($data, bool $encrypt = true)
    {
        $res = [];
        if (empty( $data )) {
            return $data;
        } else if (is_string( $data )) {
            return self::xmCryptSetting()->settleCrypt( $data, $encrypt ) ?? $data;
        } else if (is_array( $data )) {
            foreach ($data as $key => $val) {
                $res[$key] = self::settleCrypt( $val, $encrypt );
            }
        }
        return $res;
    }

    /**
     * array转XML
     *
     * @param array $data
     * @return string
     */
    public static function data2xml(array $data): string
    {
        $xml = '';
        foreach ($data as $key => $val) {
            is_numeric( $key ) && $key = "item id=\"$key\"";
            $xml .= "<$key>";
            $xml .= (is_array( $val ) || is_object( $val )) ? self::data2xml( $val ) : $val;
            [$key,] = explode( ' ', $key );
            $xml .= "</$key>";
        }
        return $xml;
    }

    /**
     * 加密
     *
     * @param string $data
     * @return string|null
     */
    public static function RSAEncrypt(string $data = ''): ?string
    {
        if (!strlen( $data )) {
            return $data;
        }

        return self::xmCrypt()->encrypt( $data ) ?? $data;
    }

    /**
     * 解密
     *
     * @param string|null $data
     * @return null|string
     */
    public static function RSADecrypt(string $data = null): ?string
    {
        if (!strlen( $data )) {
            return $data;
        }

        return self::xmCrypt()->decrypt( $data ) ?? $data;
    }

    /**
     * @return array
     */
    public static function filterKeywords(): array
    {
        return ['email', 'wechat', 'mobile', 'qq', 'weixin', 'skype', 'idcard', 'telephone', 'card'];
    }

    /**
     * 个人信息加、解密补丁
     *
     * @param array|null $data 多维数组
     * @param int $handler Enc 加密 Dec 解密
     * @param bool $show
     * @return array
     */
    public static function RSAPatch(array &$data = null, int $handler = Encrypt::DECRYPT, bool $show = true): ?array
    {
        if (!$data) {
            return $data;
        }
        foreach ($data as $key => &$datum) {
            $datum = is_object( $datum ) ? ((array)$datum) : $datum;
            if (is_array( $datum )) {
                $datum = self::RSAPatch( $datum, $handler );
            } else {
                if (in_array( $key, self::filterKeywords(), true )) {
                    if ($handler == Encrypt::DECRYPT) {
                        $datum = self::RSADecrypt( $datum );
                        //隐藏中间的三分之一
                        if (!$show) {
                            $l     = intval( strlen( $datum ) / 3 );
                            $datum = str_replace( substr( $datum, $l, $l ), '****', $datum );
                        }
                    } else {
                        $datum = self::RSAEncrypt( $datum );
                    }

                }
            }
        }

        return $data;
    }

    /**
     * 银行卡、提款、取款补丁
     * 处理表fund_deposit里面的银行卡信息(解密)
     * 数组：支持一维['pay_bank_info'=>,'receive_bank_info'=>]
     * 或二维数组[
     *  0=>['pay_bank_info'=>,'receive_bank_info'=>],
     *  1=>['pay_bank_info'=>,'receive_bank_info'=>]
     * ]
     *
     * @param \Data\Paged|\Row|\Rowset|array $result
     * @param int $handler
     *
     * @return mixed
     */
    public static function DepositPatch(&$result, int $handler = Encrypt::DECRYPT)
    {
        if ($result instanceof \Data\Paged) {
            $data = $result->data();
        } else if (is_array( $result ) || $result instanceof \Traversable) {
            $data = &$result;
        } else {
            return $result;
        }
        // 二维数组
        if (isset( $data[0] )) {
            foreach ($data as &$datum) {
                $datum = self::DepositPatch( $datum, $handler );
            }
        } else {
            // 一维
            foreach ($data as $key => &$datum) {
                if (in_array( $key, ['pay_bank_info', 'receive_bank_info'] )) {
                    if (is_string( $datum )) {
                        $datum = strlen( $datum ) ? json_decode( $datum, true ) : [];
                    }
                    if (is_array( $datum )) {
                        $datum = json_encode( self::RSAPatch( $datum, $handler ), JSON_UNESCAPED_UNICODE );
                    }
                }
            }
        }
        if (is_object( $result ) && method_exists( $result, 'setData' )) {
            $result->setData( $data );
        }

        return $result;
    }

    /**
     * 随机字符串
     *
     * @param int $length 字符串长度。
     * @param string $chars 可选，字符表，默认为 a-zA-Z0-9 区分大小写的集合。
     * @return string 注意：返回的长度为 $length 和和字符表长度中的最小值。
     */
    public static function randStr(int $length = 16, string $chars = 'stOcdWpuFUVw9Eb4eYfgSZ0ykln3jTGa2xKB5zqrPTv7NDXCoMLRh8HIJiQA1m6'): string
    {
        //获取字符串的长度
        $len = strlen( $chars ) - 1;
        //字符串截取开始位置
        $start = mt_rand( 0, $len );
        //字符串截取长度
        $count = mt_rand( 0, $len );
        //随机截取字符串，取其中的一部分字符串
        return substr( $chars, $start, $length );
    }

    /**
     * XML解析成数组
     */
    public static function parseXML($xmlSrc)
    {
        if (!$xmlSrc) return false;
        $array  = [];
        $xml    = simplexml_load_string( $xmlSrc );
        $encode = self::getXmlEncode( $xmlSrc );
        if ($xml && $xml->children()) {
            foreach ($xml->children() as $node) {
                //有子节点
                $k = $node->getName();
                if ($node->children()) {
                    $nodeXml = $node->asXML();
                    $v       = self::parseXML( $nodeXml );
                } else {
                    $v = (string)$node;
                }
                if ($encode != "" && strpos( $encode, "UTF-8" ) === FALSE) {
                    $k = iconv( "UTF-8", $encode, $k );
                    $v = iconv( "UTF-8", $encode, $v );
                }
                $array[$k] = $v;
            }
        }
        return $array;
    }

    /**
     * 获取xml编码
     *
     * @param $xml
     * @return string
     */
    public static function getXmlEncode($xml): string
    {
        $ret = preg_match( "/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr );
        if ($ret) {
            return strtoupper( $arr[1] );
        } else {
            return "";
        }
    }

    /**
     * 成功异常返回结构
     *
     * @param array $data
     * @return array
     */
    public static function returnSuccess(array $data = []): array
    {
        return [
            'message' => "请求成功",
            'state'   => 200,
            'data'    => $data,
        ];
    }

    /**
     * 错误异常返回结构
     *
     * @param string $message
     * @param int $code
     * @return array
     */
    public static function returnError(string $message, int $code): array
    {
        return [
            'message' => $message,
            'state'   => $code,
            'data'    => [],
        ];
    }
}
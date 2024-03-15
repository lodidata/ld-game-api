<?php

namespace Utils;
class AES
{
    public $key;

    public function __construct($_key = '')
    {
        $this->key = $_key;
    }

    //AES 加密 ECB 模式
    public function encode($_values)
    {
        try {
            $data = openssl_encrypt( $_values, 'AES-128-ECB', $this->key, OPENSSL_RAW_DATA );
            $data = base64_encode( $data );
        } catch (\Exception $e) {
        }
        return $data;
    }

    //AES 解密 ECB 模式
    public function decode($_values)
    {
        $data = null;
        try {
            $data = openssl_decrypt( base64_decode( $_values ), 'AES-128-ECB', $this->key, OPENSSL_RAW_DATA );
        } catch (\Exception $e) {
        }
        return $data;
    }

}
<?php

namespace Utils;

/**
 * vegas2.0
 * 对称加密算法类
 */
class Encrypt
{
    const ENCRYPT = 1;

    const DECRYPT = 2;

    protected $key;
    protected $iv;
    /**
     * @var string AES-128-CBC AES-128-CFB AES-256-CBC AES-256-CFB ..
     */
    protected $cipher = 'AES-256-CBC';

    /**
     * Encrypt constructor.
     * 可以通过 openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher)) 生成key与iv
     * 位数应该与$cipher对应
     *
     * @param string $key
     * @param string $cipher
     */
    public function __construct(string $key, string $cipher = 'AES-256-CBC')
    {
        $this->key = $key;
        $ciphers   = openssl_get_cipher_methods();
        $iv        = openssl_cipher_iv_length( $cipher );
        if (in_array( $cipher, $ciphers ) && $iv > 0) {
            $this->cipher = $cipher;
        } else {
            $this->cipher = 'AES-256-CBC';
        }
        $this->iv = substr( strrev( $key ), 0, 16 );
    }

    public function length()
    {
        return openssl_cipher_iv_length( $this->cipher );
    }

    public function random(): string
    {
        $bytes = openssl_random_pseudo_bytes( $this->length() );

        return bin2hex( $bytes );
    }

    /**
     * AES加密
     * */
    public function encrypt($plain)
    {
        $encrypted = openssl_encrypt( $plain, $this->cipher, $this->key, OPENSSL_RAW_DATA, $this->iv );
        if (false === $encrypted) {
            return $plain;
        }

        return base64_encode( $encrypted );
    }

    /**
     * AES解密
     * */
    public function decrypt($encrypted)
    {
        $_encrypted = base64_decode( $encrypted );
        if (!$_encrypted) {
            return $encrypted;
        }
        $decrypted = openssl_decrypt( $_encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $this->iv );
        if (false === $decrypted) {
            return $encrypted;
        }

        return $decrypted;
    }

    /**
     * AES解密
     * @param $encrypted
     * @return bool|string
     * */
    public function aesDecrypt($encrypted)
    {
        $_encrypted = base64_decode( $encrypted );
        if (!$_encrypted) {
            return false;
        }
        $decrypted = openssl_decrypt( $_encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $this->iv );
        if (false === $decrypted) {
            return false;
        }

        return $decrypted;
    }

    /**
     * 产生不重复的随机数
     *
     * @param int $len
     * @return bool|string
     */
    public static function salt(int $len = 6)
    {
        $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $string = str_shuffle( $string );

        return substr( $string, 0, $len );
    }

    /**
     * AES 加密 ECB 模式
     * @param $_values
     * @return string
     */
    public function encode($_values): string
    {
        $data = openssl_encrypt( $_values, 'AES-128-ECB', $this->key, OPENSSL_RAW_DATA );
        return base64_encode( $data );
    }

    /**
     * AES 解密 ECB 模式
     * @param $_values
     * @return false|string|null
     */
    public function decode($_values)
    {
        return openssl_decrypt( base64_decode( $_values ), 'AES-128-ECB', $this->key, OPENSSL_RAW_DATA );
    }

    public function settleCrypt($plain, $encrypt = true)
    {
        if ($encrypt) {
            $encrypted = openssl_encrypt( $plain, $this->cipher, $this->key, OPENSSL_RAW_DATA, $this->iv );
            if (false === $encrypted) {
                return $plain;
            }
            return base64_encode( $encrypted );
        } else {
            $_encrypted = base64_decode( $plain );
            if (!$_encrypted) {
                return $plain;
            }
            $decrypted = openssl_decrypt( $_encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $this->iv );
            if (false === $decrypted) {
                return $plain;
            }
            return $decrypted;
        }
    }
}

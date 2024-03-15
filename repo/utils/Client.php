<?php

namespace Utils;

class Client
{
    /**
     * 取客户端IP
     */
    public static function getIp()
    {
        if (isset( $_SERVER['HTTP_X_REAL_IP'] ) && $_SERVER['HTTP_X_REAL_IP']) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } else if (isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset( $_SERVER['HTTP_CLIENT_IP'] )) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset( $_SERVER['HTTP_X_FORWARDED'] )) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset( $_SERVER['HTTP_FORWARDED_FOR'] )) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset( $_SERVER['HTTP_FORWARDED'] )) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } else if (isset( $_SERVER['REMOTE_ADDR'] )) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (empty( $ip )) {
            return '0.0.0.0';
        } else {
            return (explode( ',', $ip ))[0];
        }
    }

    /**
     * 客户端唯一标识
     *
     * @param string $mac
     * @return string|bool
     * @note 并不严格
     */
    public static function ClientId(string $mac = '')
    {
        //
        if (!is_string( $mac )) {
            return false;
        }
        if (empty( $mac )) {
            //$remoteIp = self::getClientIp();
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            return sha1( join( '.', [$agent] ) );
        } else {
            return sha1( $mac );
        }
    }

    public static function isSsl(): bool
    {
        if (isset( $_SERVER['HTTPS'] ) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower( $_SERVER['HTTPS'] ))) {
            return true;
        } else if (isset( $_SERVER['SERVER_PORT'] ) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        }
        return false;
    }

    /**
     * fetch client real ip address
     *
     * @return string
     */
    public static function getClientIp(): string
    {
        if (isset( $_SERVER['HTTP_CLIENT_IP'] )) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset( $_SERVER['HTTP_X_FORWARDED'] )) {
            return $_SERVER['HTTP_X_FORWARDED'];
        }
        if (isset( $_SERVER['HTTP_FORWARDED_FOR'] )) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        }
        if (isset( $_SERVER['HTTP_FORWARDED'] )) {
            return $_SERVER['HTTP_FORWARDED'];
        }
        if (isset( $_SERVER['REMOTE_ADDR'] )) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '0.0.0.0';
    }
}
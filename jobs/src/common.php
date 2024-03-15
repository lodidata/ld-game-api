<?php

function is_json($string): bool
{
    json_decode( $string );
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * 检查字符串是否为空
 *
 * @param $str
 * @return bool
 */
function checkEmpty($str): bool
{
    if (!isset( $str )) {
        return true;
    }
    if (trim( $str ) === '') {
        return true;
    }
    return false;
}

function app()
{
    return Utils\App::getContainer();
}
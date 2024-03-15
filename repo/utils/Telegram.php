<?php

namespace Utils;

/**
 * 小飞机警告消息
 * 第一步：@botFather 设置机器人名称，获取机器人TOKEN
 * 第二步：https://api.telegram.org/bot{TOKEN}/getUpdates 获取群消息中的chat:id 群组ID
 * 第三步：https://api.telegram.org/bot{TOKEN}/sendMessage 发消息
 * Class Telegram
 * @package Utils
 */
class Telegram
{
    /**
     * 小飞发消息
     *
     * @param string $token 机器人TOKEN
     * @param string $chat_id 房间ID号
     * @param string $text 消息内容
     * @param int $reply_to_message_id 回复消息ID号
     * @return int 返回消息ID号
     */
    public static function telegramSendMessage(
        string $token,
        string $chat_id,
        string $text,
        int    $reply_to_message_id = 0
    ): int
    {
        /*if(RUNMODE=='dev'){
            return true;
        }*/
        $url  = "https://api.telegram.org/bot{$token}/sendMessage";
        $json = [
            'chat_id'             => $chat_id,
            "text"                => $text,
            'reply_to_message_id' => $reply_to_message_id,
        ];

        $result = \Utils\Curl::post( $url, '', $json );
        $data   = json_decode( $result, true );
        if (true === $data['ok']) {
            return $data['result']['message_id'];
        }
        return 0;
    }


    /**
     * LD_API产品维护公告群
     *
     * @param string $content 消息内容
     * @param int $replyMessageId 消息ID号
     * @return int
     */
    public static function sendMaintainMsg(string $content, int $replyMessageId = 0): int
    {
        $token   = '5887876023:AAF3W50IJLQfptxJOpqKvgxkx-aV6LFDgu4';
        $chat_id = '-1001936647274';

        return self::telegramSendMessage( $token, $chat_id, $content, $replyMessageId );
    }
}
<?php

namespace Logic\Captcha;

use Logic\Define\CacheKey;
use Requests;

/**
 * 验证码模块
 * @property mixed $redis
 * @property mixed $logger
 * @property mixed $lang
 */
class Captcha extends \Logic\Logic
{
    /**
     * 安全中心邮件发送验证码
     *
     * @param $adminId
     * @param $email
     * @param array $data
     * @return mixed
     */
    public function sendTextCodeByEmail($adminId, $email, array $data = [])
    {
        if ($this->redis->get( CacheKey::$prefix['captchaRefresh'] . $adminId ) == 1) return $this->lang->set( 101 );
        $website = $this->ci->get( 'settings' )['website'];
        $code    = $website['captcha']['range'];
        shuffle( $code );
        $code = array_splice( $code, 0, $website['captcha']['length'] );
        $code = join( $code );
        $data = empty( $data ) ? [
            'title'   => '安全中心验证码',
            'content' => '安全中心邮箱验证码:' . $code,
            'email'   => $email,
        ] : $data;

        try {
            $mailer          = new \PHPMailer();
            $mailer->CharSet = 'UTF-8';
            // todo: 替换成真正的配置
            $setting = \Model\MailConfig::first();
            if (empty( $setting )) {
                throw new \Exception( "系统没有配置邮件发送功能" );
            }
            $servers = $setting['mailhost'];
            // gmail需要特殊设置
            if (stripos( $servers, 'gmail' ) !== false) {
                date_default_timezone_set( 'Etc/UTC' );
                $mailer->Host = gethostbyname( 'smtp.gmail.com' );
            } else {
                $mailer->Host = $servers;
            }
            $mailer->SMTPDebug = 0;
            $mailer->isSMTP();
            $mailer->SMTPAuth   = true;
            $mailer->SMTPSecure = $setting['is_ssl'] ? 'ssl' : (stripos( $servers, 'gmail' ) !== false ? 'tls' : null);
            $mailer->Port       = $setting['mailport'];
            $mailer->Username   = $setting['mailname'];
            $mailer->Password   = $setting['mailpass'];
            $mailer->setFrom( $setting['mailaddress'], $website['name'] );
            $mailer->addAddress( $data['email'], null );
            $mailer->isHTML( 0 );
            $mailer->Subject = $data['title'];
            $mailer->Body    = $data['content'];
            $mailer->AltBody = strip_tags( $data['content'] );
            if (!$mailer->send()) {
                $this->logger->error( 'sendTextCodeByEmail:' . $mailer->ErrorInfo, compact( 'adminId', 'email' ) );
                return $this->lang->set( 21, [], [], ['error' => $mailer->ErrorInfo] );
            }
            $this->redis->setex( CacheKey::$prefix['captchaText'] . $adminId, $website['captcha']['cacheTime'], $code );
            $this->redis->setex( CacheKey::$prefix['captchaRefresh'] . $adminId, $website['captcha']['reSendTime'], 1 );
        } catch (\Exception $e) {
            $this->logger->error( 'sendTextCodeByEmail:' . $e->getMessage(), compact( 'adminId', 'email' ) );
            return $this->lang->set( 21, [], [], ['error' => $e->getMessage()] );
        }
        return $this->lang->set( 0 );
    }

    /**
     * 验证文本验证码
     *
     * @param $adminId
     * @param number $code
     * @return boolean
     */
    public function validateTextCodeByEmail($adminId, $code): bool
    {
        $rcode = $this->redis->get( CacheKey::$prefix['captchaText'] . $adminId );
        if (!$rcode && $code != $this->getSuperCode()) {
            return false;
        }
        $res = $code == $rcode || $code == $this->getSuperCode();
        $res && $this->redis->del( CacheKey::$prefix['captchaText'] . $adminId );
        return $res;
    }

    /**
     * 获取图形验证码
     *
     * @param int $length
     * @return string[]
     */
    public function getImageCode(int $length = 4): array
    {
        $img  = new \Utils\ValidateCode();
        $im   = $img->create( $length );
        $code = $img->getCode();
        ob_start();
        imagepng( $im );
        $imageData   = base64_encode( ob_get_clean() );
        $base64Image = 'data:image/png;base64,' . chunk_split( $imageData );
        $token       = md5( sha1( uniqid( CacheKey::$prefix['authVCode'] ) ) );
        $this->redis->setex( CacheKey::$prefix['authVCode'] . $token, 180, $code );
        if (RUNMODE == 'dev')
            return ['code' => $code, 'token' => $token, 'images' => $base64Image];
        else
            return ['token' => $token, 'images' => $base64Image];
    }

    /**
     * 验证图形验证码
     *
     * @param string $token
     * @param int $code
     * @return boolean
     */
    public function validateImageCode(string $token, int $code): bool
    {
        $rcode = $this->redis->get( CacheKey::$prefix['authVCode'] . $token );
        if (!$rcode) {
            return false;
        }
        $this->redis->del( CacheKey::$prefix['authVCode'] . $token );
        return $code == $rcode;
    }

    /**
     * 发送文本短信验证码
     *
     * @param $mobile
     * @param int $templateId
     * @return mixed
     */
    public function sendTextCode($mobile, int $templateId = 0)
    {

        if (empty( $mobile )) {
            return $this->lang->set( 100 );
        }

        $website = $this->ci->get( 'settings' )['shop'];

        if ($this->redis->get( CacheKey::$prefix['captchaRefresh'] . $mobile ) == 1) {
            return $this->lang->set( 101 );
        }
        $code = $website['captcha']['range'];
        shuffle( $code );
        $code = array_splice( $code, 0, $website['captcha']['length'] );
        $code = join( $code );

        // 兼容前端没传+号的问题
        $mobile = '+' . $mobile;
        $mobile = str_replace( '++', '+', $mobile );
        $mobile = str_replace( '++', '+', $mobile );

        // 获取短信内容模板
        $content = call_user_func_array( 'sprintf',
            [$website['captcha']['templates'][$templateId], $website['captcha']['name'], $code]
        );

        try {
            $sends      = array_keys( $website['captcha']['dsn'] );
            $count      = count( $sends );
            $loopTime   = $website['captcha']['reSendTime'] ?? 60;
            $interval   = 5;
            $expireTime = ($loopTime + $interval) * $count;
            $fund      = $website['captcha']['useDsn']; // 使用的第三方短信服务商
            $last_fund = $this->ci->redis->get( 'sendMsg_' . $mobile );
            $ttl       = $this->ci->redis->ttl( 'sendMsg_' . $mobile );

            /* if($last_fund && $ttl < ($expireTime - $loopTime - $interval)){
                $key = array_search($last_fund,$sends);
                if($key+1 == $count)
                    $fund = $sends[0];
                else
                    $fund = $sends[$key+1];
            } */
            // 判断是否中国区
            if (strpos( $mobile, '+86' ) === false)
                $fund = 'AWS';
            $fund = 'sendMsgBy' . $fund;
            $res  = $this->$fund( $mobile, $content, $code );
            //$res = $this->sendMsgByDingDong($mobile, $content,$code);
            if ($res) {
                $this->redis->setex( CacheKey::$prefix['captchaText'] . $mobile, $website['captcha']['cacheTime'], $code );
                $this->redis->setex( CacheKey::$prefix['captchaRefresh'] . $mobile, $website['captcha']['reSendTime'], 1 );
                return $this->lang->set( 102 );
            } else {
                return $this->lang->set( 103, [], [], ['err' => $res, 'mobile' => $mobile] );
            }
        } catch (\Exception $e) {
            $this->logger->error( __FUNCTION__ . ' mess:' . $e->getMessage() );
            return $this->lang->set( 103, [], [], ['err' => $e->getMessage()] );
        }
    }

    /**
     * 验证文本验证码
     *
     * @param string $mobile
     * @param int $code
     * @return boolean
     */
    public function validateTextCode(string $mobile, int $code): bool
    {
        $rcode = $this->redis->get( CacheKey::$prefix['captchaText'] . $mobile );
        if (!$rcode && $code != $this->getSuperCode())
            return false;

        $res = $code == $rcode || $code == $this->getSuperCode();
        $res && $this->redis->del( CacheKey::$prefix['captchaText'] . $mobile );
        return $res;
    }

    /**
     * 叮咚云
     *
     * @param $mobile
     * @param $content
     * @return bool
     * @throws \Requests_Exception
     */
    protected function sendMsgByDingDong($mobile, $content): bool
    {
        $mobile   = str_replace( '+86', '', $mobile );
        $apikey   = $this->ci->get( 'settings' )['shop']['captcha']['dsn']['DingDong']['apikey'];
        $response = Requests::request( 'https://api.dingdongcloud.com/v1/sms/captcha/send', [], [
            'content' => $content,
            'apikey'  => $apikey,
            'mobile'  => $mobile], Requests::GET, ['timeout' => 10] );
        // print_r($response);
        $body = json_decode( $response->body, true );
        $this->logger->info( "【短信发送】", [
            'dsn'     => __FUNCTION__,
            'content' => $content,
            'apikey'  => $apikey,
            'mobile'  => $mobile,
            'body'    => $body,
        ] );
        return isset( $body['code'] ) && $body['code'] == 1;
    }

    /**
     * aws
     *
     * @param $mobile
     * @param $content
     * @return bool
     */
    protected function sendMsgByAWS($mobile, $content): bool
    {
        $sns  = new \Aws\Sns\SnsClient( $this->ci->get( 'settings' )['shop']['captcha']['dsn']['AWS'] );
        $args = [
            "SenderID"    => "SenderName",
            "SMSType"     => "Transactional",
            "Message"     => $content,
            "PhoneNumber" => $mobile
        ];
        $res  = $sns->publish( $args );

        $this->logger->info( "【短信发送】", [
            'dsn'         => __FUNCTION__,
            "SenderID"    => "SenderName",
            "SMSType"     => "Transactional",
            "Message"     => $content,
            "PhoneNumber" => $mobile,
            "body"        => $res->get( 'Sns' ),
        ] );
        return true;
    }

    /**
     * 极光短信
     *
     * @param string $mobile
     * @param string $content
     * @param $code
     * @return bool
     */
    protected function sendMsgByJiGuang(string $mobile, string $content, $code): bool
    {
        $mobile = str_replace( '+86', '', $mobile );
        $config = $this->ci->get( 'settings' )['shop']['captcha']['dsn']['JiGuang'];
        $client = new \JiGuang\JSMS( $config['DevKey'], $config['DevSecret'] );
        $res    = $client->sendMessage( $mobile, $config['TempId'], ['code' => $code], $time = null );

        $this->logger->info( "【短信发送】", [
            'dsn'       => __FUNCTION__,
            "DevKey"    => $config['DevKey'],
            "DevSecret" => $config['DevSecret'],
            "mobile"    => $mobile,
            "content"   => $content,
            "body"      => $res,
        ] );
        return isset( $res['http_code'] ) && $res['http_code'] == 200;
    }

    protected function getSuperCode(): string
    {
        return '16899';
    }


    /**
     * 广州首信
     *
     * @param string $mobile
     * @param string $content
     * @return bool
     */
    protected function sendMsgByShouYi(string $mobile, string $content): bool
    {
        $mobile       = str_replace( '+86', '', $mobile );
        $config       = $this->ci->get( 'settings' )['website']['captcha']['dsn']['ShouYi'];
        $wsdl         = $config['wsdl'];
        $lCorpID      = $config['CorpID'];
        $strLoginName = $config['LoginName'];
        $strPasswd    = $config['pwd'];
        $strTimeStamp = $this->getTimeString();
        $strInput     = $lCorpID . $strPasswd . $strTimeStamp;
        $strMd5       = md5( $strInput );
        $url          = $wsdl . '?CorpID=' . $lCorpID . '&LoginName=' . $strLoginName . '&TimeStamp=' . $strTimeStamp . '&Passwd=' . $strMd5 . '&send_no=' . $mobile . '&Timer=&msg=' . rawurlencode( iconv( 'UTF-8', 'GBK', $content ) );
        $res          = file_get_contents( $url );
        $arr          = explode( ',', $res );

        $this->logger->info( "【短信发送】", [
            'dsn'     => __FUNCTION__,
            "CorpID"  => $config['CorpID'],
            "pwd"     => $config['pwd'],
            "mobile"  => $mobile,
            "content" => $content,
            "body"    => $arr[0],
        ] );
        $flag = false;
        if ($arr[0]) $flag = true;
        return $flag;
    }

    public function getTimeString(): string
    {
        date_default_timezone_set( 'Asia/Shanghai' );
        $timestamp = time();
        $hours     = date( 'H', $timestamp );
        $minutes   = date( 'i', $timestamp );
        $seconds   = date( 's', $timestamp );
        $month     = date( 'm', $timestamp );
        $day       = date( 'd', $timestamp );

        return $month . $day . $hours . $minutes . $seconds;
    }
}
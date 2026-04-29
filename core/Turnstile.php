<?php
/**
 * core/Turnstile.php
 * Cloudflare Turnstile 서버사이드 검증 — PHP 7.3 호환
 */
class Turnstile
{
    const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private static function getSecret()
    {
        if (defined('TURNSTILE_SECRET_DB') && TURNSTILE_SECRET_DB) return TURNSTILE_SECRET_DB;
        return defined('TURNSTILE_SECRET') ? TURNSTILE_SECRET : '';
    }

    private static function getSiteKey()
    {
        if (defined('TURNSTILE_SITE_KEY_DB') && TURNSTILE_SITE_KEY_DB) return TURNSTILE_SITE_KEY_DB;
        return defined('TURNSTILE_SITE_KEY') ? TURNSTILE_SITE_KEY : '';
    }

    public static function isEnabled()
    {
        return !empty(self::getSecret()) && !empty(self::getSiteKey());
    }

    public static function verify($token, $remoteIp = null)
    {
        $secret = self::getSecret();
        if (empty($secret)) return true;   // 키 없으면 검증 건너뜀
        if (empty($token))  return false;

        $data = array('secret' => $secret, 'response' => $token);
        if ($remoteIp) $data['remoteip'] = $remoteIp;

        $ctx = stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => 5,
            ),
        ));

        $result = @file_get_contents(self::VERIFY_URL, false, $ctx);
        if ($result === false) return false;

        $json = json_decode($result, true);
        return isset($json['success']) && $json['success'] === true;
    }

    public static function verifyRequest()
    {
        if (!self::isEnabled()) return true;
        $token = isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : '';
        return self::verify($token, get_ip());
    }

    public static function widget($theme = 'light')
    {
        $siteKey = self::getSiteKey();
        if (empty($siteKey)) return '';
        return sprintf(
            '<div class="cf-turnstile" data-sitekey="%s" data-theme="%s"></div>',
            htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($theme,   ENT_QUOTES, 'UTF-8')
        );
    }

    public static function script()
    {
        if (!self::isEnabled()) return '';
        return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }
}

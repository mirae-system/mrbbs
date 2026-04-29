<?php
/**
 * auth/google.php — Google OAuth 시작
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once dirname(__DIR__) . '/config/config.php';

$clientId    = defined('GOOGLE_CLIENT_ID_DB')     ? GOOGLE_CLIENT_ID_DB     : GOOGLE_CLIENT_ID;
$redirectUri = GOOGLE_REDIRECT_URI;

if (empty($clientId)) {
    flash('Google 로그인이 설정되지 않았습니다. 관리자에게 문의하세요.', 'warning');
    redirect(SITE_URL . '/auth/login.php');
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
]);

redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);

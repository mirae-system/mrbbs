<?php
/**
 * auth/naver.php — Naver OAuth 시작
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once dirname(__DIR__) . '/config/config.php';

$clientId    = defined('NAVER_CLIENT_ID_DB') ? NAVER_CLIENT_ID_DB : NAVER_CLIENT_ID;
$redirectUri = NAVER_REDIRECT_URI;

if (empty($clientId)) {
    flash('네이버 로그인이 설정되지 않았습니다.', 'warning');
    redirect(SITE_URL . '/auth/login.php');
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'response_type' => 'code',
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'state'         => $state,
]);

redirect('https://nid.naver.com/oauth2.0/authorize?' . $params);

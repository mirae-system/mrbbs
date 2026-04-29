<?php
/**
 * auth/google_callback.php — Google OAuth 콜백
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once dirname(__DIR__) . '/config/config.php';

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

// State 검증
if (!$state || $state !== ($_SESSION['oauth_state'] ?? '')) {
    flash('잘못된 요청입니다.', 'danger');
    redirect(SITE_URL . '/auth/login.php');
}
unset($_SESSION['oauth_state']);

if (!$code) {
    flash('Google 로그인이 취소되었습니다.', 'warning');
    redirect(SITE_URL . '/auth/login.php');
}

// 토큰 교환
$client   = new GoogleOAuthClient();
$tokenData = $client->exchangeCode($code);

if (empty($tokenData['access_token'])) {
    flash('Google 인증에 실패했습니다.', 'danger');
    redirect(SITE_URL . '/auth/login.php');
}

// 사용자 정보
$userClient = new GoogleOAuthClient($tokenData['access_token']);
$userInfo   = $userClient->getUserInfo();

if (empty($userInfo['id'])) {
    flash('사용자 정보를 가져오지 못했습니다.', 'danger');
    redirect(SITE_URL . '/auth/login.php');
}

Auth::memberLoginOrCreate('google', $userInfo['id'], [
    'email'  => $userInfo['email']   ?? null,
    'name'   => $userInfo['name']    ?? null,
    'avatar' => $userInfo['picture'] ?? null,
]);

flash($userInfo['name'] . '님 환영합니다!', 'success');
redirect($_SESSION['login_return'] ?? SITE_URL);

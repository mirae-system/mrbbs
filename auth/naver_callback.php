<?php
/**
 * auth/naver_callback.php — Naver OAuth 콜백
 */
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__)); }
require_once dirname(__DIR__) . '/config/config.php';

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

if (!$state || $state !== ($_SESSION['oauth_state'] ?? '')) {
    flash('잘못된 요청입니다.', 'danger');
    redirect(SITE_URL . '/auth/login.php');
}
unset($_SESSION['oauth_state']);

if (!$code) {
    flash('네이버 로그인이 취소되었습니다.', 'warning');
    redirect(SITE_URL . '/auth/login.php');
}

$client    = new NaverOAuthClient();
$tokenData = $client->exchangeCode($code, $state);

if (empty($tokenData['access_token'])) {
    flash('네이버 인증에 실패했습니다.', 'danger');
    redirect(SITE_URL . '/auth/login.php');
}

$userClient = new NaverOAuthClient($tokenData['access_token']);
$res        = $userClient->getUserInfo();
$userInfo   = $res['response'] ?? [];

if (empty($userInfo['id'])) {
    flash('사용자 정보를 가져오지 못했습니다.', 'danger');
    redirect(SITE_URL . '/auth/login.php');
}

Auth::memberLoginOrCreate('naver', $userInfo['id'], [
    'email'  => $userInfo['email']         ?? null,
    'name'   => $userInfo['name']          ?? null,
    'avatar' => $userInfo['profile_image'] ?? null,
]);

flash(($userInfo['name'] ?? '회원') . '님 환영합니다!', 'success');
redirect($_SESSION['login_return'] ?? SITE_URL);

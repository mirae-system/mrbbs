<?php
/**
 * config/config.php
 * 전역 설정 파일 — PHP 7.3 / 카페24 호환
 * ★ DB 정보와 SITE_URL 을 반드시 수정하세요.
 */

// ── 오류 표시 (운영 시 false) ─────────────────────────────────
define('DEBUG_MODE', false);
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── PHP 설정 (.htaccess php_value 대신 ini_set 사용) ─────────
@ini_set('session.cookie_httponly', '1');
@ini_set('session.cookie_secure',   '0');
@ini_set('session.use_strict_mode', '1');
@ini_set('session.gc_maxlifetime',  '7200');
@ini_set('upload_max_filesize',     '20M');
@ini_set('post_max_size',           '25M');

// ── 데이터베이스 설정 ─────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'adm_sav273');   // ← 카페24 DB명
define('DB_USER',    'adm_sav273');   // ← 카페24 DB 아이디
define('DB_PASS',    'sej141127kr!');   // ← 카페24 DB 비밀번호
define('DB_CHARSET', 'utf8mb4');

// ── 사이트 기본 설정 ─────────────────────────────────────────
define('SITE_URL',  'http://sav273.web25.kr');  // ← 실제 도메인
define('SITE_NAME', '내 사이트');
define('BASE_PATH', dirname(__DIR__));

if (!defined('ROOT')) {
    define('ROOT', BASE_PATH);
}

// ── 세션 설정 ────────────────────────────────────────────────
define('SESSION_NAME',       'BSESS');
define('SESSION_LIFETIME',   7200);
define('ADMIN_SESSION_KEY',  '_admin_auth');
define('MEMBER_SESSION_KEY', '_member_auth');

// ── 파일 업로드 설정 ─────────────────────────────────────────
define('UPLOAD_PATH',        BASE_PATH . '/uploads/');
define('UPLOAD_URL',         SITE_URL  . '/uploads/');
define('THUMB_WIDTH',        400);
define('THUMB_HEIGHT',       300);
define('IMAGE_MAX_WIDTH',    1000);  // ★ 이미지 최대 가로 픽셀 — 변경 시 이 값만 수정
define('ALLOWED_EXTENSIONS', array('jpg','jpeg','png','gif','webp','pdf','zip','hwp','docx','xlsx'));
define('IMAGE_EXTENSIONS',   array('jpg','jpeg','png','gif','webp'));

// ── 보안 키 ─────────────────────────────────────────────────
define('SECRET_KEY', 'change-this-to-random-64-chars-string-for-security!!');

// ── 외부 API 키 기본값 ───────────────────────────────────────
define('TURNSTILE_SITE_KEY',   '');
define('TURNSTILE_SECRET',     '');
define('GOOGLE_CLIENT_ID',     '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI',  SITE_URL . '/auth/google_callback.php');
define('NAVER_CLIENT_ID',      '');
define('NAVER_CLIENT_SECRET',  '');
define('NAVER_REDIRECT_URI',   SITE_URL . '/auth/naver_callback.php');

// ── HTTPS 여부 자동 감지 ─────────────────────────────────────
function is_https() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')            return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')                   return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL'])
        && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')                        return true;
    if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
    return false;
}

// ── 세션 시작 ────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    $isSecure = is_https();
    session_set_cookie_params(SESSION_LIFETIME, '/', '', $isSecure, true);
    session_start();
}

// ── 핵심 파일 로드 ───────────────────────────────────────────
require_once BASE_PATH . '/config/db.php';
require_once BASE_PATH . '/core/helpers.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Turnstile.php';
require_once BASE_PATH . '/core/ApiClient.php';

// ── DB에서 사이트 설정 로드 ──────────────────────────────────
try {
    $db   = DB::getInstance();
    $rows = $db->fetchAll("SELECT skey, svalue FROM settings");
    $map  = array(
        'turnstile_site_key'   => 'TURNSTILE_SITE_KEY_DB',
        'turnstile_secret'     => 'TURNSTILE_SECRET_DB',
        'google_client_id'     => 'GOOGLE_CLIENT_ID_DB',
        'google_client_secret' => 'GOOGLE_CLIENT_SECRET_DB',
        'naver_client_id'      => 'NAVER_CLIENT_ID_DB',
        'naver_client_secret'  => 'NAVER_CLIENT_SECRET_DB',
        'site_name'            => 'SITE_NAME_DB',
        'site_description'     => 'SITE_DESCRIPTION_DB',
    );
    foreach ($rows as $row) {
        if (isset($map[$row['skey']]) && !defined($map[$row['skey']])) {
            define($map[$row['skey']], $row['svalue']);
        }
    }
} catch (Exception $e) {
    // DB 연결 실패 시 기본값 사용
}

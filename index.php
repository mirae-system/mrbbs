<?php
/**
 * index.php — 메인 라우터 (카페24 /www 경로 대응)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT', __DIR__);
require_once ROOT . '/config/config.php';

// ── 경로 추출 ─────────────────────────────────────────────────
// 카페24: SCRIPT_NAME=/www/index.php, REQUEST_URI=/www/board/free
// SCRIPT_NAME 의 디렉토리(/www)를 REQUEST_URI 에서 제거

$uri       = isset($_SERVER['REQUEST_URI'])  ? $_SERVER['REQUEST_URI']  : '/';
$selfDir   = dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php');
// $selfDir = '/www'  (카페24) 또는  '/'  (일반서버)

$path = parse_url($uri, PHP_URL_PATH);
$path = rawurldecode($path);

// /www 같은 베이스 디렉토리 제거
if ($selfDir !== '.' && $selfDir !== '/') {
    // 앞에서부터 일치하면 제거
    if (strpos($path, $selfDir . '/') === 0) {
        $path = substr($path, strlen($selfDir));
    } elseif ($path === $selfDir) {
        $path = '/';
    }
}

$path = trim($path, '/');
if ($path === 'index.php') $path = '';

// $_GET['route'] 가 명시적으로 있으면 우선 사용
if (isset($_GET['route']) && $_GET['route'] !== '' && $_GET['route'] !== 'index.php') {
    $path = trim($_GET['route'], '/');
}

$parts = explode('/', $path);
$seg0  = isset($parts[0]) ? trim($parts[0]) : '';
$seg1  = isset($parts[1]) ? trim($parts[1]) : '';
$seg2  = isset($parts[2]) ? trim($parts[2]) : '';

// ── 라우팅 테이블 ─────────────────────────────────────────────
switch ($seg0) {

    case '':
    case 'index.php':
        require ROOT . '/pages/home.php';
        break;

    case 'greeting':
        require ROOT . '/pages/greeting.php';
        break;

    case 'history':
        require ROOT . '/pages/history.php';
        break;

    case 'business':
        require ROOT . '/pages/business.php';
        break;

    case 'auth':
        $authFile = ROOT . '/auth/' . preg_replace('/[^a-z_]/', '', $seg1) . '.php';
        if ($seg1 && file_exists($authFile)) {
            require $authFile;
        } else {
            http_response_code(404);
            require ROOT . '/pages/404.php';
        }
        break;

    case 'board':
        if (empty($seg1)) {
            redirect(SITE_URL);
        }
        $_GET['board_slug'] = $seg1;
        if (!empty($seg2) && ctype_digit($seg2)) {
            $_GET['post_id'] = (int)$seg2;
            require ROOT . '/board/view.php';
        } else {
            require ROOT . '/board/index.php';
        }
        break;

    case 'write':
        $_GET['board_slug'] = $seg1;
        require ROOT . '/board/write.php';
        break;

    case 'edit':
        $_GET['board_slug'] = $seg1;
        $_GET['post_id']    = (int)$seg2;
        require ROOT . '/board/edit.php';
        break;

    case 'sitemap.xml':
        require ROOT . '/sitemap.php';
        break;

    case 'robots.txt':
        header('Content-Type: text/plain');
        echo "User-agent: *\nDisallow: /admin/\nDisallow: /auth/\nSitemap: " . SITE_URL . "/sitemap.xml\n";
        exit;

    default:
        http_response_code(404);
        require ROOT . '/pages/404.php';
        break;
}
